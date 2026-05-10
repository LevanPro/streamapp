<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseResource;
use App\Models\Lesson;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class StreamController extends Controller
{
    public function lesson(Lesson $lesson): Response
    {
        $lesson->load('course');
        abort_if($lesson->is_missing || $lesson->course->is_missing, 404);

        $relativePath = $this->courseRelativeMediaPath($lesson->course, $lesson->relative_path);
        $absolutePath = $this->resolveSafeAbsolutePath($lesson->course->scan_root, $relativePath);

        if (! $this->shouldUseAccel()) {
            return response()->file($absolutePath, [
                'Content-Type' => $lesson->mime_type ?: 'video/mp4',
                'Cache-Control' => 'private, max-age=86400',
                'Accept-Ranges' => 'bytes',
            ]);
        }

        return $this->accelResponse(
            relativePath: $relativePath,
            mimeType: $lesson->mime_type ?: 'video/mp4',
            disposition: 'inline',
            filename: $lesson->filename
        );
    }

    public function resource(CourseResource $resource): Response
    {
        $resource->load('course');
        abort_if($resource->is_missing || $resource->course->is_missing, 404);

        $relativePath = $this->courseRelativeMediaPath($resource->course, $resource->relative_path);
        $absolutePath = $this->resolveSafeAbsolutePath($resource->course->scan_root, $relativePath);
        $extension = $this->resourceExtension($resource);
        $isInlinePdf = $extension === 'pdf';
        $disposition = $isInlinePdf ? 'inline' : 'attachment';

        if (! $this->shouldUseAccel()) {
            if ($isInlinePdf) {
                return response()->file($absolutePath, [
                    'Content-Type' => $resource->mime_type ?: 'application/pdf',
                    'Cache-Control' => 'private, max-age=86400',
                    'Content-Disposition' => 'inline',
                ]);
            }

            return response()->download($absolutePath, $resource->filename, [
                'Content-Type' => $resource->mime_type ?: 'application/octet-stream',
                'Cache-Control' => 'private, max-age=86400',
            ]);
        }

        return $this->accelResponse(
            relativePath: $relativePath,
            mimeType: $resource->mime_type ?: 'application/octet-stream',
            disposition: $disposition,
            filename: $resource->filename
        );
    }

    public function previewTextResource(CourseResource $resource): JsonResponse
    {
        $resource->load('course');
        abort_if($resource->is_missing || $resource->course->is_missing, 404);

        $extension = $this->resourceExtension($resource);
        abort_if(! in_array($extension, ['txt', 'go'], true), 404);

        $relativePath = $this->courseRelativeMediaPath($resource->course, $resource->relative_path);
        $absolutePath = $this->resolveSafeAbsolutePath($resource->course->scan_root, $relativePath);

        $maxBytes = 1024 * 512;
        $sizeBytes = filesize($absolutePath);
        if ($sizeBytes === false) {
            abort(404);
        }

        $length = min($sizeBytes, $maxBytes);
        $content = file_get_contents($absolutePath, false, null, 0, $length);
        if ($content === false) {
            abort(500);
        }

        if (str_contains($content, "\0")) {
            return response()->json([
                'filename' => $resource->filename,
                'extension' => $extension,
                'content' => '',
                'truncated' => false,
                'error' => 'This file looks binary and cannot be previewed as text.',
            ], 422);
        }

        if (function_exists('mb_check_encoding') && ! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8, Windows-1251, Windows-1252, ISO-8859-1');
        }

        return response()->json([
            'filename' => $resource->filename,
            'extension' => $extension,
            'content' => $content,
            'truncated' => $sizeBytes > $maxBytes,
        ]);
    }

    public function lessonThumbnail(Lesson $lesson): Response
    {
        $lesson->load('course');
        abort_if($lesson->is_missing || $lesson->course->is_missing || ! $lesson->thumbnail_path, 404);

        $absolutePath = storage_path('app/private/'.$lesson->thumbnail_path);
        if (! is_file($absolutePath)) {
            abort(404);
        }

        return response()->file($absolutePath, [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    public function lessonPreviewManifest(Lesson $lesson): JsonResponse
    {
        $manifest = $this->loadLessonPreviewManifest($lesson);
        unset($manifest['sprite_path']);
        $manifest['sprite_url'] = route('lessons.preview.sprite', $lesson);

        return response()->json($manifest, 200, [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    public function lessonPreviewSprite(Lesson $lesson): Response
    {
        $manifest = $this->loadLessonPreviewManifest($lesson);
        $spriteRelativePath = trim((string) ($manifest['sprite_path'] ?? ''), '/');
        if ($spriteRelativePath === '') {
            abort(404);
        }

        $absoluteSpritePath = storage_path('app/private/'.$spriteRelativePath);
        if (! is_file($absoluteSpritePath)) {
            abort(404);
        }

        return response()->file($absoluteSpritePath, [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    private function accelResponse(string $relativePath, string $mimeType, string $disposition, string $filename): Response
    {
        $encodedPath = $this->encodeForInternalUri($relativePath);
        $internalPrefix = '/'.trim((string) config('courses.internal_media_prefix', '/_protected_media'), '/');
        $internalUri = $internalPrefix.'/'.$encodedPath;

        $asciiFilename = Str::ascii($filename);
        $safeFilename = str_replace('"', '', $asciiFilename !== '' ? $asciiFilename : 'file');
        $dispositionHeader = sprintf(
            "%s; filename=\"%s\"; filename*=UTF-8''%s",
            $disposition,
            $safeFilename,
            rawurlencode($filename)
        );

        return response('', 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => $dispositionHeader,
            'X-Accel-Redirect' => $internalUri,
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    private function encodeForInternalUri(string $relativePath): string
    {
        $segments = array_filter(explode('/', trim($relativePath, '/')), static fn (string $segment): bool => $segment !== '');

        return implode('/', array_map(static fn (string $segment): string => rawurlencode($segment), $segments));
    }

    private function courseRelativeMediaPath(Course $course, string $courseRelativePath): string
    {
        return trim(trim($course->relative_path, '/').'/'.trim($courseRelativePath, '/'), '/');
    }

    private function resolveSafeAbsolutePath(string $scanRoot, string $relativePath): string
    {
        $normalizedRelativePath = str_replace('\\', '/', trim($relativePath, '/'));
        if (
            $normalizedRelativePath === ''
            || str_contains($normalizedRelativePath, '/../')
            || str_starts_with($normalizedRelativePath, '../')
            || str_contains($normalizedRelativePath, '/..')
        ) {
            abort(404);
        }

        $scanRootRealPath = realpath($scanRoot);
        if ($scanRootRealPath === false) {
            abort(404);
        }

        $candidatePath = $scanRootRealPath.DIRECTORY_SEPARATOR.str_replace('/', DIRECTORY_SEPARATOR, $normalizedRelativePath);
        $resolvedPath = realpath($candidatePath);
        if ($resolvedPath === false || ! is_file($resolvedPath)) {
            abort(404);
        }

        $normalizedRoot = rtrim(str_replace('\\', '/', $scanRootRealPath), '/');
        $normalizedFile = str_replace('\\', '/', $resolvedPath);

        if (! Str::startsWith($normalizedFile, $normalizedRoot.'/')) {
            abort(404);
        }

        return $resolvedPath;
    }

    private function shouldUseAccel(): bool
    {
        $driver = strtolower((string) config('courses.stream_driver', 'auto'));

        return match ($driver) {
            'accel' => true,
            'laravel' => false,
            default => ! app()->environment('local'),
        };
    }

    private function resourceExtension(CourseResource $resource): string
    {
        $extension = $resource->extension ?? pathinfo($resource->filename, PATHINFO_EXTENSION);

        return strtolower((string) $extension);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadLessonPreviewManifest(Lesson $lesson): array
    {
        $lesson->load('course');
        abort_if($lesson->is_missing || $lesson->course->is_missing || ! $lesson->preview_manifest_path, 404);

        $absoluteManifestPath = storage_path('app/private/'.$lesson->preview_manifest_path);
        if (! is_file($absoluteManifestPath)) {
            abort(404);
        }

        $manifestContent = file_get_contents($absoluteManifestPath);
        if ($manifestContent === false) {
            abort(404);
        }

        try {
            $manifest = json_decode($manifestContent, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            abort(404);
        }

        if (! is_array($manifest)) {
            abort(404);
        }

        return $manifest;
    }
}
