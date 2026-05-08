<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonProgressController extends Controller
{
    public function store(Request $request, Lesson $lesson): JsonResponse
    {
        abort_if($lesson->is_missing || $lesson->course?->is_missing, 404);

        $payload = $request->validate([
            'last_position_seconds' => ['required', 'numeric', 'min:0'],
            'duration_seconds' => ['nullable', 'numeric', 'min:0'],
            'percent_watched' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $progress = LessonProgress::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'lesson_id' => $lesson->id,
            ],
            [
                'last_position_seconds' => (float) $payload['last_position_seconds'],
                'duration_seconds' => isset($payload['duration_seconds']) ? (float) $payload['duration_seconds'] : null,
                'percent_watched' => isset($payload['percent_watched']) ? (float) $payload['percent_watched'] : null,
                'last_watched_at' => now(),
            ]
        );

        return response()->json([
            'ok' => true,
            'progress' => [
                'last_position_seconds' => $progress->last_position_seconds,
                'duration_seconds' => $progress->duration_seconds,
                'percent_watched' => $progress->percent_watched,
            ],
        ]);
    }
}
