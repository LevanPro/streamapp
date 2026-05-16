<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseResource;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LessonController extends Controller
{
    public function show(Request $request, Lesson $lesson): Response
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

        return Inertia::render('Lessons/Show', [
            'course' => [
                'id' => $course->id,
                'display_title' => $course->display_title,
            ],
            'lesson' => [
                'id' => $lesson->id,
                'display_title' => $lesson->display_title,
                'section_title' => $lesson->section?->display_title ?? 'General',
                'duration_seconds' => $lesson->duration_seconds,
                'mime_type' => $lesson->mime_type ?: 'video/mp4',
                'has_poster' => (bool) $lesson->thumbnail_path,
                'has_storyboard' => (bool) $lesson->preview_manifest_path,
            ],
            'savedPosition' => (float) ($progress?->last_position_seconds ?? 0),
            'previousLessonId' => $previousLesson?->id,
            'nextLessonId' => $nextLesson?->id,
            'activeResources' => $lesson->resources
                ->map(fn (CourseResource $resource): array => $resource->toPayload())
                ->values(),
            'sidebar' => $course->sections->map(fn (CourseSection $section): array => [
                'id' => $section->id,
                'title' => $section->relative_path === CourseSection::ROOT_RELATIVE_PATH
                    ? 'General'
                    : $section->display_title,
                'lessons' => $section->lessons->map(fn (Lesson $sectionLesson): array => [
                    'id' => $sectionLesson->id,
                    'display_title' => $sectionLesson->display_title,
                    'duration_seconds' => $sectionLesson->duration_seconds,
                ])->values(),
            ])->values(),
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
