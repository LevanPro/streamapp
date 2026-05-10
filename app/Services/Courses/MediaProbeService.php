<?php

namespace App\Services\Courses;

use Symfony\Component\Process\Process;

class MediaProbeService
{
    private ?bool $hasFfprobe = null;

    private ?bool $hasFfmpeg = null;

    public function hasFfprobe(): bool
    {
        if ($this->hasFfprobe !== null) {
            return $this->hasFfprobe;
        }

        $binary = (string) config('courses.ffprobe_binary', 'ffprobe');

        $process = new Process([$binary, '-version']);
        $process->setTimeout(4);
        $process->run();

        return $this->hasFfprobe = $process->isSuccessful();
    }

    public function hasFfmpeg(): bool
    {
        if ($this->hasFfmpeg !== null) {
            return $this->hasFfmpeg;
        }

        $binary = (string) config('courses.ffmpeg_binary', 'ffmpeg');

        $process = new Process([$binary, '-version']);
        $process->setTimeout(4);
        $process->run();

        return $this->hasFfmpeg = $process->isSuccessful();
    }

    public function probeDuration(string $absoluteFilePath): ?float
    {
        if (! $this->hasFfprobe()) {
            return null;
        }

        $binary = (string) config('courses.ffprobe_binary', 'ffprobe');

        $process = new Process([
            $binary,
            '-v',
            'error',
            '-show_entries',
            'format=duration',
            '-of',
            'default=noprint_wrappers=1:nokey=1',
            $absoluteFilePath,
        ]);
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        $duration = trim($process->getOutput());
        if ($duration === '') {
            return null;
        }

        $durationFloat = (float) $duration;

        return $durationFloat > 0 ? $durationFloat : null;
    }

    public function createThumbnail(string $videoPath, string $thumbnailAbsolutePath): bool
    {
        if (! $this->hasFfmpeg()) {
            return false;
        }

        $directory = dirname($thumbnailAbsolutePath);
        if (! is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        $binary = (string) config('courses.ffmpeg_binary', 'ffmpeg');
        $process = new Process([
            $binary,
            '-hide_banner',
            '-loglevel',
            'error',
            '-y',
            '-ss',
            '00:00:02',
            '-i',
            $videoPath,
            '-frames:v',
            '1',
            '-q:v',
            '2',
            $thumbnailAbsolutePath,
        ]);
        $process->setTimeout(60);
        $process->run();

        return $process->isSuccessful() && is_file($thumbnailAbsolutePath);
    }

    public function createPreviewSprite(
        string $videoPath,
        string $spriteAbsolutePath,
        int $frameWidth,
        int $frameHeight,
        int $columns,
        int $rows,
        int $frameCount,
        float $sampleFps
    ): bool {
        if (! $this->hasFfmpeg()) {
            return false;
        }

        $directory = dirname($spriteAbsolutePath);
        if (! is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        $binary = (string) config('courses.ffmpeg_binary', 'ffmpeg');
        $fps = max($sampleFps, 0.0001);
        $filter = sprintf(
            'fps=%s,scale=%d:%d:force_original_aspect_ratio=decrease,pad=%d:%d:(ow-iw)/2:(oh-ih)/2:color=black,tile=%dx%d:nb_frames=%d',
            number_format($fps, 6, '.', ''),
            max(16, $frameWidth),
            max(16, $frameHeight),
            max(16, $frameWidth),
            max(16, $frameHeight),
            max(1, $columns),
            max(1, $rows),
            max(1, $frameCount)
        );

        $process = new Process([
            $binary,
            '-hide_banner',
            '-loglevel',
            'error',
            '-y',
            '-i',
            $videoPath,
            '-vf',
            $filter,
            '-frames:v',
            '1',
            '-q:v',
            '2',
            $spriteAbsolutePath,
        ]);
        $process->setTimeout(120);
        $process->run();

        return $process->isSuccessful() && is_file($spriteAbsolutePath);
    }
}
