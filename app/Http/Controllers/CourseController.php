<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseResource;
use App\Models\CourseSection;
use App\Models\Lesson;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(): Response
    {
        $courses = Course::query()
            ->visible()
            ->withCount([
                'lessons as lessons_count' => fn ($query) => $query->where('is_missing', false),
                'resources as resources_count' => fn ($query) => $query->where('is_missing', false),
            ])
            ->orderBy('sort_index')
            ->orderBy('display_title')
            ->get();

        return Inertia::render('Courses/Index', [
            'courses' => $courses->map(fn (Course $course): array => [
                'id' => $course->id,
                'display_title' => $course->display_title,
                'relative_path' => $course->relative_path,
                'lessons_count' => $course->lessons_count,
                'resources_count' => $course->resources_count,
            ])->values(),
            'coursesRoot' => config('courses.root'),
        ]);
    }

    public function show(Course $course): Response
    {
        abort_if($course->is_missing, 404);

        $course->load([
            'sections' => fn ($query) => $query
                ->where('is_missing', false)
                ->orderBy('sort_index')
                ->with([
                    'lessons' => fn ($lessonsQuery) => $lessonsQuery
                        ->where('is_missing', false)
                        ->orderBy('sort_index'),
                    'resources' => fn ($resourcesQuery) => $resourcesQuery
                        ->where('is_missing', false)
                        ->whereNull('lesson_id')
                        ->orderBy('sort_index'),
                ]),
            'resources' => fn ($query) => $query
                ->where('is_missing', false)
                ->whereNull('course_section_id')
                ->whereNull('lesson_id')
                ->orderBy('sort_index'),
        ]);

        $firstLesson = Lesson::query()
            ->select('lessons.*')
            ->join('course_sections', 'course_sections.id', '=', 'lessons.course_section_id')
            ->where('lessons.course_id', $course->id)
            ->where('lessons.is_missing', false)
            ->where('course_sections.is_missing', false)
            ->orderBy('course_sections.sort_index')
            ->orderBy('lessons.sort_index')
            ->first();

        return Inertia::render('Courses/Show', [
            'course' => [
                'id' => $course->id,
                'display_title' => $course->display_title,
                'relative_path' => $course->relative_path,
            ],
            'courseResources' => $course->resources
                ->map(fn (CourseResource $resource): array => $resource->toPayload())
                ->values(),
            'sections' => $course->sections->map(fn (CourseSection $section): array => [
                'id' => $section->id,
                'title' => $section->relative_path === CourseSection::ROOT_RELATIVE_PATH
                    ? 'General'
                    : $section->display_title,
                'lessons' => $section->lessons->map(fn (Lesson $lesson): array => [
                    'id' => $lesson->id,
                    'display_title' => $lesson->display_title,
                    'duration_seconds' => $lesson->duration_seconds,
                ])->values(),
                'resources' => $section->resources
                    ->map(fn (CourseResource $resource): array => $resource->toPayload())
                    ->values(),
            ])->values(),
            'firstLessonId' => $firstLesson?->id,
        ]);
    }
}
