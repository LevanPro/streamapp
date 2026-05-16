<?php

namespace App\Services\Courses;

use App\Models\Lesson;

class LessonPreviewGenerator
{
    public function __construct(private readonly MediaProbeService $mediaProbeService)
    {
    }

    public function generate(Lesson $lesson): bool
    {
        $lesson->loadMissing('course');
        if ($lesson->is_missing || $lesson->course === null || $lesson->course->is_missing) {
            return false;
        }

        if (! (bool) config('courses.preview_enabled', true)) {
            return false;
        }

        $previewDirectory = trim((string) config('courses.preview_directory', 'course-previews'), '/');
        if ($previewDirectory === '') {
            return false;
        }

        $videoAbsolutePath = $lesson->source_path;
        if (! is_string($videoAbsolutePath) || ! is_file($videoAbsolutePath)) {
            return false;
        }

        $basePath = sprintf('%s/%d/%s', $previewDirectory, $lesson->course_id, sha1($lesson->relative_path));
        $relativeThumbnailPath = $basePath.'.jpg';
        $absoluteThumbnailPath = storage_path('app/private/'.$relativeThumbnailPath);

        if (! is_file($absoluteThumbnailPath)) {
            $thumbnailCreated = $this->mediaProbeService->createThumbnail($videoAbsolutePath, $absoluteThumbnailPath);
            if (! $thumbnailCreated) {
                return false;
            }
        }

        $manifestPath = $this->generateTimelineManifest($lesson, $videoAbsolutePath, $basePath);

        $changed = false;
        if ($lesson->thumbnail_path !== $relativeThumbnailPath) {
            $lesson->thumbnail_path = $relativeThumbnailPath;
            $changed = true;
        }
        if ($manifestPath !== null && $lesson->preview_manifest_path !== $manifestPath) {
            $lesson->preview_manifest_path = $manifestPath;
            $changed = true;
        }

        if ($changed) {
            $lesson->save();
        }

        return true;
    }

    private function generateTimelineManifest(Lesson $lesson, string $videoAbsolutePath, string $basePath): ?string
    {
        $duration = $lesson->duration_seconds;
        if (! is_numeric($duration) || (float) $duration <= 0) {
            $duration = $this->mediaProbeService->probeDuration($videoAbsolutePath);
        }
        if (! is_numeric($duration) || (float) $duration <= 0) {
            return null;
        }
        $duration = (float) $duration;

        $frameWidth = max(48, (int) config('courses.preview_width', 160));
        $frameHeight = max(27, (int) config('courses.preview_height', 90));
        $columns = max(1, (int) config('courses.preview_columns', 10));
        $maxFrames = max(8, (int) config('courses.preview_max_frames', 120));
        $baseInterval = max(1.0, (float) config('courses.preview_interval_seconds', 10));

        $targetFrameCount = (int) ceil($duration / $baseInterval);
        $frameCount = max(1, min($maxFrames, $targetFrameCount));
        $intervalSeconds = max($duration / $frameCount, 0.001);
        $rows = (int) ceil($frameCount / $columns);
        $sampleFps = 1 / $intervalSeconds;

        $relativeSpritePath = $basePath.'_sprite.jpg';
        $absoluteSpritePath = storage_path('app/private/'.$relativeSpritePath);

        if (! is_file($absoluteSpritePath)) {
            $spriteCreated = $this->mediaProbeService->createPreviewSprite(
                videoPath: $videoAbsolutePath,
                spriteAbsolutePath: $absoluteSpritePath,
                frameWidth: $frameWidth,
                frameHeight: $frameHeight,
                columns: $columns,
                rows: $rows,
                frameCount: $frameCount,
                sampleFps: $sampleFps
            );
            if (! $spriteCreated) {
                return null;
            }
        }

        $relativeManifestPath = $basePath.'_manifest.json';
        $absoluteManifestPath = storage_path('app/private/'.$relativeManifestPath);

        $manifestDirectory = dirname($absoluteManifestPath);
        if (! is_dir($manifestDirectory)) {
            @mkdir($manifestDirectory, 0775, true);
        }

        $manifest = [
            'version' => 1,
            'duration_seconds' => round($duration, 3),
            'interval_seconds' => round($intervalSeconds, 3),
            'frame_count' => $frameCount,
            'columns' => $columns,
            'rows' => $rows,
            'frame_width' => $frameWidth,
            'frame_height' => $frameHeight,
            'sprite_path' => $relativeSpritePath,
        ];

        $encodedManifest = json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        file_put_contents($absoluteManifestPath, $encodedManifest);

        return $relativeManifestPath;
    }
}
