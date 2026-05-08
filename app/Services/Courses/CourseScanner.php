<?php

namespace App\Services\Courses;

use App\Models\Course;
use App\Models\CourseResource;
use App\Models\CourseSection;
use App\Models\Lesson;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Throwable;

class CourseScanner
{
    public function __construct(private readonly MediaProbeService $mediaProbeService)
    {
    }

    public function scan(string $scanRoot, bool $dryRun = false, ?bool $generateThumbnails = null): ScanSummary
    {
        $resolvedRoot = $this->resolveDirectoryPath($scanRoot);
        $summary = new ScanSummary();

        $runner = function () use ($resolvedRoot, $summary, $generateThumbnails): void {
            $this->performScan($resolvedRoot, $summary, $generateThumbnails);
        };

        if (! $dryRun) {
            DB::transaction($runner);

            return $summary;
        }

        DB::beginTransaction();
        try {
            $runner();
        } finally {
            DB::rollBack();
        }

        return $summary;
    }

    private function performScan(string $scanRoot, ScanSummary $summary, ?bool $generateThumbnails): void
    {
        $scanAt = CarbonImmutable::now();
        $courseDirectories = $this->topLevelDirectories($scanRoot, $this->excludedDirectories());

        foreach ($courseDirectories as $sortIndex => $courseDirectory) {
            try {
                $this->scanSingleCourse(
                    scanRoot: $scanRoot,
                    courseDirectory: $courseDirectory,
                    sortIndex: $sortIndex + 1,
                    scanAt: $scanAt,
                    summary: $summary,
                    generateThumbnails: $generateThumbnails
                );
            } catch (Throwable $exception) {
                $summary->addError(sprintf(
                    'Failed to scan course "%s": %s',
                    basename($courseDirectory),
                    $exception->getMessage()
                ));
            }
        }

        $this->markMissingRecords($scanRoot, $scanAt, $summary);
    }

    private function scanSingleCourse(
        string $scanRoot,
        string $courseDirectory,
        int $sortIndex,
        CarbonImmutable $scanAt,
        ScanSummary $summary,
        ?bool $generateThumbnails
    ): void {
        $courseRelativePath = $this->relativePath($scanRoot, $courseDirectory);
        $courseTitle = basename($courseDirectory);

        $course = $this->upsertCourse(
            scanRoot: $scanRoot,
            courseRelativePath: $courseRelativePath,
            sourcePath: $courseDirectory,
            sourceTitle: $courseTitle,
            sortIndex: $sortIndex,
            scanAt: $scanAt,
            summary: $summary
        );

        $sections = $this->upsertCourseSections(
            course: $course,
            courseDirectory: $courseDirectory,
            scanAt: $scanAt,
            summary: $summary
        );

        $files = $this->gatherCourseFiles($courseDirectory, array_keys($sections));
        $this->syncLessons(
            course: $course,
            sectionsByRelativePath: $sections,
            videoFiles: $files['videos'],
            scanAt: $scanAt,
            summary: $summary,
            generateThumbnails: $generateThumbnails
        );
        $this->syncResources(
            course: $course,
            sectionsByRelativePath: $sections,
            resourceFiles: $files['resources'],
            scanAt: $scanAt,
            summary: $summary
        );
    }

    private function upsertCourse(
        string $scanRoot,
        string $courseRelativePath,
        string $sourcePath,
        string $sourceTitle,
        int $sortIndex,
        CarbonImmutable $scanAt,
        ScanSummary $summary
    ): Course {
        $scanRootHash = hash('sha256', $scanRoot);
        $relativeHash = hash('sha256', $courseRelativePath);

        /** @var Course|null $course */
        $course = Course::query()
            ->where('scan_root_hash', $scanRootHash)
            ->where('relative_path_hash', $relativeHash)
            ->first();

        if (! $course) {
            $course = new Course();
            $course->scan_root = $scanRoot;
            $course->scan_root_hash = $scanRootHash;
            $course->relative_path = $courseRelativePath;
            $course->relative_path_hash = $relativeHash;
            $course->source_path = $sourcePath;
            $course->source_title = $sourceTitle;
            $course->display_title = $sourceTitle;
            $course->sort_index = $sortIndex;
            $course->is_missing = false;
            $course->last_seen_at = $scanAt;
            $course->save();

            $summary->increment('courses_created');

            return $course;
        }

        $this->syncDisplayTitle($course, $sourceTitle, 'source_title', 'display_title');

        $course->scan_root = $scanRoot;
        $course->scan_root_hash = $scanRootHash;
        $course->relative_path = $courseRelativePath;
        $course->relative_path_hash = $relativeHash;
        $course->source_path = $sourcePath;
        $course->sort_index = $sortIndex;
        $course->is_missing = false;
        $course->last_seen_at = $scanAt;

        if ($course->isDirty()) {
            $course->save();
            $summary->increment('courses_updated');
        } else {
            $summary->increment('courses_unchanged');
        }

        return $course;
    }

    /**
     * @return array<string, CourseSection>
     */
    private function upsertCourseSections(
        Course $course,
        string $courseDirectory,
        CarbonImmutable $scanAt,
        ScanSummary $summary
    ): array {
        $sections = [];

        $sections[CourseSection::ROOT_RELATIVE_PATH] = $this->upsertSection(
            course: $course,
            relativePath: CourseSection::ROOT_RELATIVE_PATH,
            sourcePath: $courseDirectory,
            sourceTitle: 'General',
            sortIndex: 0,
            scanAt: $scanAt,
            summary: $summary
        );

        $topLevelDirectories = $this->topLevelDirectories($courseDirectory, $this->excludedDirectories());
        foreach ($topLevelDirectories as $index => $directoryPath) {
            $directoryName = basename($directoryPath);
            $relativePath = $this->normalizeRelativePath($directoryName);

            $sections[$relativePath] = $this->upsertSection(
                course: $course,
                relativePath: $relativePath,
                sourcePath: $directoryPath,
                sourceTitle: $directoryName,
                sortIndex: $index + 1,
                scanAt: $scanAt,
                summary: $summary
            );
        }

        return $sections;
    }

    private function upsertSection(
        Course $course,
        string $relativePath,
        string $sourcePath,
        string $sourceTitle,
        int $sortIndex,
        CarbonImmutable $scanAt,
        ScanSummary $summary
    ): CourseSection {
        $relativeHash = hash('sha256', $relativePath);

        /** @var CourseSection|null $section */
        $section = CourseSection::query()
            ->where('course_id', $course->id)
            ->where('relative_path_hash', $relativeHash)
            ->first();

        if (! $section) {
            $section = new CourseSection();
            $section->course_id = $course->id;
            $section->relative_path = $relativePath;
            $section->relative_path_hash = $relativeHash;
            $section->source_path = $sourcePath;
            $section->source_title = $sourceTitle;
            $section->display_title = $sourceTitle;
            $section->sort_index = $sortIndex;
            $section->is_missing = false;
            $section->last_seen_at = $scanAt;
            $section->save();

            $summary->increment('sections_created');

            return $section;
        }

        $this->syncDisplayTitle($section, $sourceTitle, 'source_title', 'display_title');
        $section->source_path = $sourcePath;
        $section->sort_index = $sortIndex;
        $section->is_missing = false;
        $section->last_seen_at = $scanAt;

        if ($section->isDirty()) {
            $section->save();
            $summary->increment('sections_updated');
        } else {
            $summary->increment('sections_unchanged');
        }

        return $section;
    }

    /**
     * @param  array<string, CourseSection>  $sectionsByRelativePath
     * @param  list<array<string, mixed>>  $videoFiles
     */
    private function syncLessons(
        Course $course,
        array $sectionsByRelativePath,
        array $videoFiles,
        CarbonImmutable $scanAt,
        ScanSummary $summary,
        ?bool $generateThumbnails
    ): void {
        usort($videoFiles, static fn (array $a, array $b): int => strnatcasecmp($a['relative_path'], $b['relative_path']));

        $sectionSortIndexes = [];

        foreach ($videoFiles as $videoFile) {
            $sectionRelativePath = $videoFile['section_relative_path'];
            $section = $sectionsByRelativePath[$sectionRelativePath] ?? $sectionsByRelativePath[CourseSection::ROOT_RELATIVE_PATH];

            $sectionSortIndexes[$section->id] = ($sectionSortIndexes[$section->id] ?? 0) + 1;

            $relativeHash = hash('sha256', $videoFile['relative_path']);
            /** @var Lesson|null $lesson */
            $lesson = Lesson::query()
                ->where('course_id', $course->id)
                ->where('relative_path_hash', $relativeHash)
                ->first();

            $sourceTitle = $this->lessonTitleFromFilename($videoFile['filename']);
            $duration = $this->mediaProbeService->probeDuration($videoFile['absolute_path']);

            if (! $lesson) {
                $lesson = new Lesson();
                $lesson->course_id = $course->id;
                $lesson->course_section_id = $section->id;
                $lesson->relative_path = $videoFile['relative_path'];
                $lesson->relative_path_hash = $relativeHash;
                $lesson->source_path = $videoFile['absolute_path'];
                $lesson->filename = $videoFile['filename'];
                $lesson->source_title = $sourceTitle;
                $lesson->display_title = $sourceTitle;
                $lesson->sort_index = $sectionSortIndexes[$section->id];
                $lesson->duration_seconds = $duration;
                $lesson->file_size_bytes = $videoFile['file_size_bytes'];
                $lesson->mime_type = $videoFile['mime_type'];
                $lesson->extension = $videoFile['extension'];
                $lesson->is_missing = false;
                $lesson->last_seen_at = $scanAt;
                $lesson->save();

                $summary->increment('lessons_created');
            } else {
                $this->syncDisplayTitle($lesson, $sourceTitle, 'source_title', 'display_title');

                $lesson->course_section_id = $section->id;
                $lesson->relative_path = $videoFile['relative_path'];
                $lesson->relative_path_hash = $relativeHash;
                $lesson->source_path = $videoFile['absolute_path'];
                $lesson->filename = $videoFile['filename'];
                $lesson->sort_index = $sectionSortIndexes[$section->id];
                $lesson->duration_seconds = $duration ?? $lesson->duration_seconds;
                $lesson->file_size_bytes = $videoFile['file_size_bytes'];
                $lesson->mime_type = $videoFile['mime_type'];
                $lesson->extension = $videoFile['extension'];
                $lesson->is_missing = false;
                $lesson->last_seen_at = $scanAt;

                if ($lesson->isDirty()) {
                    $lesson->save();
                    $summary->increment('lessons_updated');
                } else {
                    $summary->increment('lessons_unchanged');
                }
            }

            $this->maybeGenerateThumbnail($course, $lesson, $videoFile['absolute_path'], $generateThumbnails);
        }
    }

    /**
     * @param  array<string, CourseSection>  $sectionsByRelativePath
     * @param  list<array<string, mixed>>  $resourceFiles
     */
    private function syncResources(
        Course $course,
        array $sectionsByRelativePath,
        array $resourceFiles,
        CarbonImmutable $scanAt,
        ScanSummary $summary
    ): void {
        usort($resourceFiles, static fn (array $a, array $b): int => strnatcasecmp($a['relative_path'], $b['relative_path']));

        $lessonByDirectory = [];
        /** @var \Illuminate\Support\Collection<int, Lesson> $courseLessons */
        $courseLessons = Lesson::query()
            ->where('course_id', $course->id)
            ->where('is_missing', false)
            ->get();

        foreach ($courseLessons as $lesson) {
            $directory = dirname($lesson->relative_path);
            $normalizedDirectory = $directory === '.' ? '' : $this->normalizeRelativePath($directory);
            $lessonByDirectory[$normalizedDirectory][] = $lesson;
        }

        foreach ($resourceFiles as $index => $resourceFile) {
            $sectionRelativePath = $resourceFile['section_relative_path'];
            $section = $sectionsByRelativePath[$sectionRelativePath] ?? $sectionsByRelativePath[CourseSection::ROOT_RELATIVE_PATH];

            $directoryLessons = $lessonByDirectory[$resourceFile['directory_relative_path']] ?? [];
            $attachedLesson = count($directoryLessons) === 1 ? $directoryLessons[0] : null;

            $relativeHash = hash('sha256', $resourceFile['relative_path']);
            /** @var CourseResource|null $resource */
            $resource = CourseResource::query()
                ->where('course_id', $course->id)
                ->where('relative_path_hash', $relativeHash)
                ->first();

            if (! $resource) {
                $resource = new CourseResource();
                $resource->course_id = $course->id;
                $resource->course_section_id = $section?->id;
                $resource->lesson_id = $attachedLesson?->id;
                $resource->relative_path = $resourceFile['relative_path'];
                $resource->relative_path_hash = $relativeHash;
                $resource->source_path = $resourceFile['absolute_path'];
                $resource->filename = $resourceFile['filename'];
                $resource->display_title = $resourceFile['filename'];
                $resource->sort_index = $index + 1;
                $resource->file_size_bytes = $resourceFile['file_size_bytes'];
                $resource->mime_type = $resourceFile['mime_type'];
                $resource->extension = $resourceFile['extension'];
                $resource->is_missing = false;
                $resource->last_seen_at = $scanAt;
                $resource->save();

                $summary->increment('resources_created');
                continue;
            }

            $this->syncDisplayTitle($resource, $resourceFile['filename'], 'filename', 'display_title');

            $resource->course_section_id = $section?->id;
            $resource->lesson_id = $attachedLesson?->id;
            $resource->relative_path = $resourceFile['relative_path'];
            $resource->relative_path_hash = $relativeHash;
            $resource->source_path = $resourceFile['absolute_path'];
            $resource->filename = $resourceFile['filename'];
            $resource->sort_index = $index + 1;
            $resource->file_size_bytes = $resourceFile['file_size_bytes'];
            $resource->mime_type = $resourceFile['mime_type'];
            $resource->extension = $resourceFile['extension'];
            $resource->is_missing = false;
            $resource->last_seen_at = $scanAt;

            if ($resource->isDirty()) {
                $resource->save();
                $summary->increment('resources_updated');
            } else {
                $summary->increment('resources_unchanged');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $resourceFile
     */
    private function syncDisplayTitle(object $model, string $newSourceTitle, string $sourceField, string $displayField): void
    {
        $oldSource = (string) ($model->{$sourceField} ?? '');
        $oldDisplay = (string) ($model->{$displayField} ?? '');

        $model->{$sourceField} = $newSourceTitle;
        if ($oldDisplay === '' || $oldDisplay === $oldSource) {
            $model->{$displayField} = $newSourceTitle;
        }
    }

    /**
     * @param  array<string, CourseSection>  $sectionsByRelativePath
     * @return array{videos: list<array<string, mixed>>, resources: list<array<string, mixed>>}
     */
    private function gatherCourseFiles(string $courseDirectory, array $sectionsByRelativePath): array
    {
        $videos = [];
        $resources = [];

        $videoExtensions = $this->videoExtensions();
        $resourceExtensions = $this->resourceExtensions();
        $excludedDirectories = $this->excludedDirectories();
        $knownSectionKeys = array_fill_keys($sectionsByRelativePath, true);

        $directoryIterator = new RecursiveDirectoryIterator($courseDirectory, RecursiveDirectoryIterator::SKIP_DOTS);
        $filteredIterator = new RecursiveCallbackFilterIterator(
            $directoryIterator,
            static function (SplFileInfo $current) use ($excludedDirectories): bool {
                if (! $current->isDir()) {
                    return true;
                }

                return ! in_array(strtolower($current->getFilename()), $excludedDirectories, true);
            }
        );
        $iterator = new RecursiveIteratorIterator($filteredIterator);

        foreach ($iterator as $fileInfo) {
            if (! $fileInfo instanceof SplFileInfo || ! $fileInfo->isFile()) {
                continue;
            }

            $absolutePath = $fileInfo->getRealPath();
            if ($absolutePath === false) {
                continue;
            }

            $relativePath = $this->relativePath($courseDirectory, $absolutePath);
            $directoryRelativePath = dirname($relativePath);
            $directoryRelativePath = $directoryRelativePath === '.' ? '' : $this->normalizeRelativePath($directoryRelativePath);
            $extension = strtolower((string) pathinfo($absolutePath, PATHINFO_EXTENSION));

            $sectionRelativePath = $this->detectSectionRelativePath($relativePath, $knownSectionKeys);

            $payload = [
                'absolute_path' => $absolutePath,
                'relative_path' => $relativePath,
                'directory_relative_path' => $directoryRelativePath,
                'filename' => $fileInfo->getFilename(),
                'file_size_bytes' => $fileInfo->getSize(),
                'extension' => $extension,
                'mime_type' => $this->detectMimeType($absolutePath),
                'section_relative_path' => $sectionRelativePath,
            ];

            if (in_array($extension, $videoExtensions, true)) {
                $videos[] = $payload;

                continue;
            }

            if (in_array($extension, $resourceExtensions, true)) {
                $resources[] = $payload;
            }
        }

        return [
            'videos' => $videos,
            'resources' => $resources,
        ];
    }

    /**
     * @param  array<string, bool>  $knownSectionKeys
     */
    private function detectSectionRelativePath(string $fileRelativePath, array $knownSectionKeys): string
    {
        $parts = explode('/', $this->normalizeRelativePath($fileRelativePath));
        $first = $parts[0] ?? '';

        if ($first !== '' && isset($knownSectionKeys[$first])) {
            return $first;
        }

        return CourseSection::ROOT_RELATIVE_PATH;
    }

    private function maybeGenerateThumbnail(
        Course $course,
        Lesson $lesson,
        string $videoAbsolutePath,
        ?bool $generateThumbnails
    ): void {
        $previewEnabled = (bool) config('courses.preview_enabled', true);
        if ($generateThumbnails !== null) {
            $previewEnabled = $generateThumbnails;
        }

        if (! $previewEnabled) {
            return;
        }

        $relativeThumbnailPath = sprintf(
            '%s/%d/%s.jpg',
            trim((string) config('courses.preview_directory', 'course-previews'), '/'),
            $course->id,
            sha1($lesson->relative_path)
        );
        $absoluteThumbnailPath = storage_path('app/private/'.$relativeThumbnailPath);

        if (! is_file($absoluteThumbnailPath)) {
            $thumbnailCreated = $this->mediaProbeService->createThumbnail($videoAbsolutePath, $absoluteThumbnailPath);
            if (! $thumbnailCreated) {
                return;
            }
        }

        if ($lesson->thumbnail_path !== $relativeThumbnailPath) {
            $lesson->thumbnail_path = $relativeThumbnailPath;
            $lesson->save();
        }
    }

    private function markMissingRecords(string $scanRoot, CarbonImmutable $scanAt, ScanSummary $summary): void
    {
        $scanRootHash = hash('sha256', $scanRoot);
        $isStale = static fn ($query) => $query
            ->whereNull('last_seen_at')
            ->orWhere('last_seen_at', '<', $scanAt);

        $coursesMissing = Course::query()
            ->where('scan_root_hash', $scanRootHash)
            ->where($isStale)
            ->update(['is_missing' => true]);
        $summary->increment('courses_missing', $coursesMissing);

        $sectionsMissing = CourseSection::query()
            ->whereHas('course', static fn ($query) => $query->where('scan_root_hash', $scanRootHash))
            ->where($isStale)
            ->update(['is_missing' => true]);
        $summary->increment('sections_missing', $sectionsMissing);

        $lessonsMissing = Lesson::query()
            ->whereHas('course', static fn ($query) => $query->where('scan_root_hash', $scanRootHash))
            ->where($isStale)
            ->update(['is_missing' => true]);
        $summary->increment('lessons_missing', $lessonsMissing);

        $resourcesMissing = CourseResource::query()
            ->whereHas('course', static fn ($query) => $query->where('scan_root_hash', $scanRootHash))
            ->where($isStale)
            ->update(['is_missing' => true]);
        $summary->increment('resources_missing', $resourcesMissing);
    }

    /**
     * @return list<string>
     */
    private function topLevelDirectories(string $directory, array $excludedDirectories): array
    {
        $paths = [];
        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if (in_array(strtolower($entry), $excludedDirectories, true)) {
                continue;
            }

            $absolute = $directory.DIRECTORY_SEPARATOR.$entry;
            if (is_dir($absolute)) {
                $paths[] = $this->resolveDirectoryPath($absolute);
            }
        }

        usort($paths, static fn (string $a, string $b): int => strnatcasecmp(basename($a), basename($b)));

        return $paths;
    }

    private function relativePath(string $basePath, string $targetPath): string
    {
        $base = $this->normalizeAbsolutePath($basePath);
        $target = $this->normalizeAbsolutePath($targetPath);

        $baseWithSlash = rtrim($base, '/').'/';
        if (! str_starts_with($target, $baseWithSlash)) {
            throw new \RuntimeException(sprintf('Path "%s" is outside base "%s".', $targetPath, $basePath));
        }

        return substr($target, strlen($baseWithSlash));
    }

    private function resolveDirectoryPath(string $directory): string
    {
        $resolved = realpath($directory);
        if ($resolved === false || ! is_dir($resolved)) {
            throw new \InvalidArgumentException(sprintf('Directory does not exist: %s', $directory));
        }

        return $this->normalizeAbsolutePath($resolved);
    }

    private function normalizeAbsolutePath(string $path): string
    {
        $normalized = str_replace('\\', '/', $path);

        return rtrim($normalized, '/');
    }

    private function normalizeRelativePath(string $path): string
    {
        return trim(str_replace('\\', '/', $path), '/');
    }

    private function lessonTitleFromFilename(string $filename): string
    {
        $title = pathinfo($filename, PATHINFO_FILENAME);

        return Str::of($title)->replace(['_', '-'], ' ')->squish()->toString();
    }

    private function detectMimeType(string $path): string
    {
        $mime = @mime_content_type($path);
        if (is_string($mime) && $mime !== '') {
            return $mime;
        }

        return 'application/octet-stream';
    }

    /**
     * @return list<string>
     */
    private function videoExtensions(): array
    {
        return (array) config('courses.video_extensions', []);
    }

    /**
     * @return list<string>
     */
    private function resourceExtensions(): array
    {
        return (array) config('courses.resource_extensions', []);
    }

    /**
     * @return list<string>
     */
    private function excludedDirectories(): array
    {
        return (array) config('courses.exclude_directories', []);
    }
}
