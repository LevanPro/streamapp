<?php

namespace App\Jobs;

use App\Models\Lesson;
use App\Services\Courses\LessonPreviewGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateLessonPreview implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 300;

    public int $tries = 2;

    public function __construct(public readonly int $lessonId)
    {
    }

    public function handle(LessonPreviewGenerator $generator): void
    {
        $lesson = Lesson::query()->with('course')->find($this->lessonId);
        if ($lesson === null) {
            return;
        }

        $generator->generate($lesson);
    }

    public function uniqueId(): string
    {
        return (string) $this->lessonId;
    }
}
