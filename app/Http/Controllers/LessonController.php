<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LessonController extends Controller
{
    public function show(Request $request, Lesson $lesson): View
    {
        $lesson->load(['course', 'section']);
        abort_if($lesson->is_missing || $lesson->course->is_missing, 404);

        $course = Course::query()
            ->whereKey($lesson->course_id)
            ->with([
                'sections' => fn ($query) => $query
                    ->where('is_missing', false)
                    ->orderBy('sort_index')
                    ->with([
                        'lessons' => fn ($lessonQuery) => $lessonQuery
                            ->where('is_missing', false)
                            ->orderBy('sort_index')
                            ->with([
                                'resources' => fn ($resourcesQuery) => $resourcesQuery
                                    ->where('is_missing', false)
                                    ->orderBy('sort_index'),
                            ]),
                        'resources' => fn ($resourceQuery) => $resourceQuery
                            ->where('is_missing', false)
                            ->whereNull('lesson_id')
                            ->orderBy('sort_index'),
                    ]),
                'resources' => fn ($query) => $query
                    ->where('is_missing', false)
                    ->whereNull('course_section_id')
                    ->whereNull('lesson_id')
                    ->orderBy('sort_index'),
            ])
            ->firstOrFail();

        $playlist = [];
        foreach ($course->sections as $section) {
            foreach ($section->lessons as $sectionLesson) {
                $playlist[] = $sectionLesson;
            }
        }

        $currentIndex = collect($playlist)->search(fn (Lesson $item) => $item->id === $lesson->id);
        $activeLesson = $currentIndex !== false ? $playlist[$currentIndex] : null;
        $activeResources = $activeLesson?->resources ?? collect();
        if ($activeResources->isEmpty()) {
            $activeResources = $lesson->resources()
                ->where('is_missing', false)
                ->orderBy('sort_index')
                ->get();
        }

        $previousLesson = $currentIndex !== false && $currentIndex > 0 ? $playlist[$currentIndex - 1] : null;
        $nextLesson = $currentIndex !== false && $currentIndex < count($playlist) - 1 ? $playlist[$currentIndex + 1] : null;

        $progress = LessonProgress::query()
            ->where('user_id', $request->user()->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        return view('lessons.show', [
            'course' => $course,
            'lesson' => $lesson,
            'progress' => $progress,
            'previousLesson' => $previousLesson,
            'nextLesson' => $nextLesson,
            'activeResources' => $activeResources,
        ]);
    }
}
