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
        $lesson->load([
            'course',
            'section',
            'resources' => fn ($query) => $query->where('is_missing', false)->orderBy('sort_index'),
        ]);
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
                            ->orderBy('sort_index'),
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

        [$previousLesson, $nextLesson] = $this->findAdjacentLessons($course, $lesson);

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
            'activeResources' => $lesson->resources,
        ]);
    }

    /**
     * @return array{0: ?Lesson, 1: ?Lesson}
     */
    private function findAdjacentLessons(Course $course, Lesson $current): array
    {
        $previous = null;
        $found = false;

        foreach ($course->sections as $section) {
            foreach ($section->lessons as $sectionLesson) {
                if ($found) {
                    return [$previous, $sectionLesson];
                }
                if ($sectionLesson->id === $current->id) {
                    $found = true;
                    continue;
                }
                $previous = $sectionLesson;
            }
        }

        return [$previous, null];
    }
}
