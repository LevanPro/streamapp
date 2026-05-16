<?php

namespace Tests\Feature\Courses;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonPreviewStoryboardTest extends TestCase
{
    use RefreshDatabase;

    private string $manifestRelativePath = 'course-previews/storyboard-test/manifest.json';

    protected function tearDown(): void
    {
        $dir = storage_path('app/private/course-previews/storyboard-test');
        if (is_dir($dir)) {
            @unlink($dir.'/manifest.json');
            @rmdir($dir);
        }

        parent::tearDown();
    }

    private function makeLesson(?string $manifestPath): Lesson
    {
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

        return Lesson::query()->create([
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
            'preview_manifest_path' => $manifestPath,
            'is_missing' => false,
            'last_seen_at' => now(),
        ]);
    }

    public function test_storyboard_vtt_is_built_from_manifest(): void
    {
        $absolute = storage_path('app/private/'.$this->manifestRelativePath);
        @mkdir(dirname($absolute), 0775, true);
        file_put_contents($absolute, json_encode([
            'version' => 1,
            'duration_seconds' => 25,
            'interval_seconds' => 10,
            'frame_count' => 3,
            'columns' => 2,
            'rows' => 2,
            'frame_width' => 160,
            'frame_height' => 90,
            'sprite_path' => 'course-previews/storyboard-test/sprite.jpg',
        ]));

        $user = User::factory()->create();
        $lesson = $this->makeLesson($this->manifestRelativePath);

        $response = $this->actingAs($user)
            ->get(route('lessons.preview.storyboard', $lesson));

        $response->assertOk();
        $this->assertStringContainsString(
            'text/vtt',
            $response->headers->get('Content-Type')
        );

        $body = $response->getContent();
        $this->assertStringStartsWith('WEBVTT', $body);
        $this->assertStringContainsString('00:00:00.000 --> 00:00:10.000', $body);
        $this->assertStringContainsString('00:00:20.000 --> 00:00:25.000', $body);
        // Frame layout: 2 columns x 160x90 frames.
        $this->assertStringContainsString('#xywh=0,0,160,90', $body);
        $this->assertStringContainsString('#xywh=160,0,160,90', $body);
        $this->assertStringContainsString('#xywh=0,90,160,90', $body);
        $this->assertStringContainsString(
            "/lessons/{$lesson->id}/preview-sprite",
            $body
        );
    }

    public function test_storyboard_returns_404_without_manifest(): void
    {
        $user = User::factory()->create();
        $lesson = $this->makeLesson(null);

        $this->actingAs($user)
            ->get(route('lessons.preview.storyboard', $lesson))
            ->assertNotFound();
    }

    public function test_storyboard_requires_authentication(): void
    {
        $lesson = $this->makeLesson(null);

        $this->get(route('lessons.preview.storyboard', $lesson))
            ->assertRedirect('/login');
    }
}
