<?php

namespace Tests\Feature\Courses;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoursePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_index_and_lesson_pages_render_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $course = Course::query()->create([
            'scan_root' => '/srv/courses',
            'scan_root_hash' => hash('sha256', '/srv/courses'),
            'relative_path' => 'Course A',
            'relative_path_hash' => hash('sha256', 'Course A'),
            'source_path' => '/srv/courses/Course A',
            'source_title' => 'Course A',
            'display_title' => 'My Course A',
            'sort_index' => 1,
            'is_missing' => false,
            'last_seen_at' => now(),
        ]);
        $section = CourseSection::query()->create([
            'course_id' => $course->id,
            'relative_path' => CourseSection::ROOT_RELATIVE_PATH,
            'relative_path_hash' => hash('sha256', CourseSection::ROOT_RELATIVE_PATH),
            'source_path' => '/srv/courses/Course A',
            'source_title' => 'General',
            'display_title' => 'General',
            'sort_index' => 0,
            'is_missing' => false,
            'last_seen_at' => now(),
        ]);
        $lesson = Lesson::query()->create([
            'course_id' => $course->id,
            'course_section_id' => $section->id,
            'relative_path' => 'lesson1.mp4',
            'relative_path_hash' => hash('sha256', 'lesson1.mp4'),
            'source_path' => '/srv/courses/Course A/lesson1.mp4',
            'filename' => 'lesson1.mp4',
            'source_title' => 'Lesson 1',
            'display_title' => 'Lesson 1',
            'sort_index' => 1,
            'file_size_bytes' => 10,
            'mime_type' => 'video/mp4',
            'extension' => 'mp4',
            'is_missing' => false,
            'last_seen_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('courses.index'))
            ->assertOk()
            ->assertSee('My Course A');

        $this->actingAs($user)
            ->get(route('courses.show', $course))
            ->assertOk()
            ->assertSee('Lesson 1');

        $this->actingAs($user)
            ->get(route('lessons.show', $lesson))
            ->assertOk()
            ->assertSee('Lesson 1');
    }
}
