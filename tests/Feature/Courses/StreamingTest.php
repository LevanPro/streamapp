<?php

namespace Tests\Feature\Courses;

use App\Models\Course;
use App\Models\CourseResource;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StreamingTest extends TestCase
{
    use RefreshDatabase;

    private string $scanRoot;
    private array $temporaryPaths = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->scanRoot = storage_path('framework/testing/streaming_'.uniqid());
        mkdir($this->scanRoot, 0775, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->scanRoot);
        foreach ($this->temporaryPaths as $path) {
            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            if (is_file($path)) {
                @unlink($path);
            }
        }
        parent::tearDown();
    }

    public function test_unauthenticated_users_cannot_access_streaming_routes(): void
    {
        [$lesson, $resource] = $this->seedCourseMedia();

        $this->get(route('stream.lessons', $lesson))
            ->assertRedirect('/login');

        $this->get(route('stream.resources', $resource))
            ->assertRedirect('/login');
    }

    public function test_authenticated_user_receives_x_accel_redirect_headers(): void
    {
        [$lesson, $resource] = $this->seedCourseMedia();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('stream.lessons', $lesson))
            ->assertOk()
            ->assertHeader('Accept-Ranges', 'bytes')
            ->assertHeader('X-Accel-Redirect', '/_protected_media/Course%20A/lesson1.mp4');

        $this->actingAs($user)
            ->get(route('stream.resources', $resource))
            ->assertOk()
            ->assertHeader(
                'Content-Disposition',
                "inline; filename=\"slides.pdf\"; filename*=UTF-8''slides.pdf"
            );
    }

    public function test_authenticated_user_can_fetch_lesson_preview_manifest_and_sprite(): void
    {
        [$lesson] = $this->seedCourseMedia();
        $user = User::factory()->create();

        $relativeDir = 'course-previews/test-'.uniqid();
        $absoluteDir = storage_path('app/private/'.$relativeDir);
        mkdir($absoluteDir, 0775, true);
        $this->temporaryPaths[] = $absoluteDir;

        $relativeSpritePath = $relativeDir.'/sprite.jpg';
        $relativeManifestPath = $relativeDir.'/manifest.json';

        file_put_contents(storage_path('app/private/'.$relativeSpritePath), 'sprite-bytes');
        file_put_contents(storage_path('app/private/'.$relativeManifestPath), json_encode([
            'version' => 1,
            'duration_seconds' => 120,
            'interval_seconds' => 10,
            'frame_count' => 12,
            'columns' => 6,
            'rows' => 2,
            'frame_width' => 160,
            'frame_height' => 90,
            'sprite_path' => $relativeSpritePath,
        ], JSON_THROW_ON_ERROR));

        $lesson->update([
            'preview_manifest_path' => $relativeManifestPath,
        ]);

        $this->actingAs($user)
            ->get(route('lessons.preview.manifest', $lesson))
            ->assertOk()
            ->assertJsonPath('frame_count', 12)
            ->assertJsonPath('sprite_url', route('lessons.preview.sprite', $lesson));

        $this->actingAs($user)
            ->get(route('lessons.preview.sprite', $lesson))
            ->assertOk()
            ->assertHeader('Cache-Control', 'private, max-age=86400');
    }

    public function test_traversal_like_paths_are_rejected(): void
    {
        $user = User::factory()->create();
        $course = Course::query()->create([
            'scan_root' => $this->scanRoot,
            'scan_root_hash' => hash('sha256', str_replace('\\', '/', $this->scanRoot)),
            'relative_path' => 'Course A',
            'relative_path_hash' => hash('sha256', 'Course A'),
            'source_path' => $this->scanRoot.'/Course A',
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
            'source_path' => $course->source_path,
            'source_title' => 'General',
            'display_title' => 'General',
            'sort_index' => 0,
            'is_missing' => false,
            'last_seen_at' => now(),
        ]);
        $lesson = Lesson::query()->create([
            'course_id' => $course->id,
            'course_section_id' => $section->id,
            'relative_path' => '../secret.mp4',
            'relative_path_hash' => hash('sha256', '../secret.mp4'),
            'source_path' => $course->source_path.'/../secret.mp4',
            'filename' => 'secret.mp4',
            'source_title' => 'secret',
            'display_title' => 'secret',
            'sort_index' => 1,
            'file_size_bytes' => 10,
            'mime_type' => 'video/mp4',
            'extension' => 'mp4',
            'is_missing' => false,
            'last_seen_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('stream.lessons', $lesson))
            ->assertNotFound();
    }

    public function test_txt_and_go_resources_can_be_previewed_as_text(): void
    {
        $user = User::factory()->create();
        $coursePath = $this->scanRoot.'/Course Preview';
        mkdir($coursePath, 0775, true);
        file_put_contents($coursePath.'/notes.txt', "line1\nline2");

        $normalizedRoot = str_replace('\\', '/', realpath($this->scanRoot) ?: $this->scanRoot);
        $course = Course::query()->create([
            'scan_root' => $normalizedRoot,
            'scan_root_hash' => hash('sha256', $normalizedRoot),
            'relative_path' => 'Course Preview',
            'relative_path_hash' => hash('sha256', 'Course Preview'),
            'source_path' => $coursePath,
            'source_title' => 'Course Preview',
            'display_title' => 'Course Preview',
            'sort_index' => 1,
            'is_missing' => false,
            'last_seen_at' => now(),
        ]);

        $resource = CourseResource::query()->create([
            'course_id' => $course->id,
            'course_section_id' => null,
            'lesson_id' => null,
            'relative_path' => 'notes.txt',
            'relative_path_hash' => hash('sha256', 'notes.txt'),
            'source_path' => $coursePath.'/notes.txt',
            'filename' => 'notes.txt',
            'display_title' => 'notes.txt',
            'sort_index' => 1,
            'file_size_bytes' => 10,
            'mime_type' => 'text/plain',
            'extension' => 'txt',
            'is_missing' => false,
            'last_seen_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('resources.preview', $resource))
            ->assertOk()
            ->assertJsonPath('filename', 'notes.txt')
            ->assertJsonPath('content', "line1\nline2");
    }

    /**
     * @return array{Lesson, CourseResource}
     */
    private function seedCourseMedia(): array
    {
        $coursePath = $this->scanRoot.'/Course A';
        mkdir($coursePath, 0775, true);
        file_put_contents($coursePath.'/lesson1.mp4', 'video');
        file_put_contents($coursePath.'/slides.pdf', 'pdf');

        $normalizedRoot = str_replace('\\', '/', realpath($this->scanRoot) ?: $this->scanRoot);
        $course = Course::query()->create([
            'scan_root' => $normalizedRoot,
            'scan_root_hash' => hash('sha256', $normalizedRoot),
            'relative_path' => 'Course A',
            'relative_path_hash' => hash('sha256', 'Course A'),
            'source_path' => $coursePath,
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
            'source_path' => $coursePath,
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
            'source_path' => $coursePath.'/lesson1.mp4',
            'filename' => 'lesson1.mp4',
            'source_title' => 'lesson1',
            'display_title' => 'lesson1',
            'sort_index' => 1,
            'file_size_bytes' => 5,
            'mime_type' => 'video/mp4',
            'extension' => 'mp4',
            'is_missing' => false,
            'last_seen_at' => now(),
        ]);
        $resource = CourseResource::query()->create([
            'course_id' => $course->id,
            'course_section_id' => $section->id,
            'lesson_id' => $lesson->id,
            'relative_path' => 'slides.pdf',
            'relative_path_hash' => hash('sha256', 'slides.pdf'),
            'source_path' => $coursePath.'/slides.pdf',
            'filename' => 'slides.pdf',
            'display_title' => 'slides.pdf',
            'sort_index' => 1,
            'file_size_bytes' => 3,
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'is_missing' => false,
            'last_seen_at' => now(),
        ]);

        return [$lesson, $resource];
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$entry;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } elseif (is_file($path)) {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
