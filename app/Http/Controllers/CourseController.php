<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(): View
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

        return view('courses.index', [
            'courses' => $courses,
        ]);
    }

    public function show(Course $course): View
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

        return view('courses.show', [
            'course' => $course,
            'firstLesson' => $firstLesson,
        ]);
    }
}
