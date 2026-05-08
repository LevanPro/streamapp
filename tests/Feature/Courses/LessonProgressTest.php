<?php

namespace Tests\Feature\Courses;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_progress_is_stored_per_user_and_lesson(): void
    {
        $user = User::factory()->create();
        $course = Course::query()->create([
            'scan_root' => '/srv/courses',
            'scan_root_hash' => hash('sha256', '/srv/courses'),
            'relative_path' => 'Course A',
            'relative_path_hash' => hash('sha256', 'Course A'),
            'source_path' => '/srv/courses/Course A',
            'source_title' => 'Course A',
            'display_title' => 'Course A',
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
            'source_title' => 'lesson1',
            'display_title' => 'lesson1',
            'sort_index' => 1,
            'file_size_bytes' => 10,
            'mime_type' => 'video/mp4',
            'extension' => 'mp4',
            'is_missing' => false,
            'last_seen_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->postJson(route('progress.store', $lesson), [
                'last_position_seconds' => 84.5,
                'duration_seconds' => 200.0,
                'percent_watched' => 42.25,
            ]);

        $response->assertOk()->assertJsonPath('ok', true);

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
            'last_position_seconds' => 84.5,
            'duration_seconds' => 200.0,
            'percent_watched' => 42.25,
        ]);
    }
}
