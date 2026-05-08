<?php

namespace Tests\Feature\Courses;

use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CourseScanCommandTest extends TestCase
{
    use RefreshDatabase;

    private string $scanRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scanRoot = storage_path('framework/testing/courses_'.uniqid());
        mkdir($this->scanRoot, 0775, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->scanRoot);
        parent::tearDown();
    }

    public function test_command_scans_and_syncs_course_metadata(): void
    {
        $this->makeFile('Course One/root-video.mp4');
        $this->makeFile('Course One/readme.txt');
        $this->makeFile('Course One/01 Intro/01 hello.mp4');
        $this->makeFile('Course One/01 Intro/note.pdf');
        $this->makeFile('Course Two/video-1.mkv');

        Artisan::call('courses:scan', [
            'path' => $this->scanRoot,
            '--no-thumbnails' => true,
        ]);

        $this->assertDatabaseCount('courses', 2);
        $this->assertDatabaseCount('course_sections', 3);
        $this->assertDatabaseCount('lessons', 3);
        $this->assertDatabaseCount('resources', 2);
    }

    public function test_manual_display_title_is_preserved_on_rescan_and_deleted_files_are_marked_missing(): void
    {
        $this->makeFile('Course One/root-video.mp4');
        $this->makeFile('Course One/01 Intro/01 hello.mp4');

        Artisan::call('courses:scan', [
            'path' => $this->scanRoot,
            '--no-thumbnails' => true,
        ]);

        /** @var Lesson $lesson */
        $lesson = Lesson::query()->where('filename', '01 hello.mp4')->firstOrFail();
        $lesson->update(['display_title' => 'Custom Title']);

        Artisan::call('courses:scan', [
            'path' => $this->scanRoot,
            '--no-thumbnails' => true,
        ]);

        $lesson->refresh();
        $this->assertSame('Custom Title', $lesson->display_title);

        unlink($this->scanRoot.'/Course One/root-video.mp4');

        Artisan::call('courses:scan', [
            'path' => $this->scanRoot,
            '--no-thumbnails' => true,
        ]);

        $this->assertDatabaseHas('lessons', [
            'filename' => 'root-video.mp4',
            'is_missing' => true,
        ]);
    }

    private function makeFile(string $relativePath, string $content = 'test-data'): void
    {
        $absolutePath = $this->scanRoot.'/'.$relativePath;
        $directory = dirname($absolutePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($absolutePath, $content);
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $items = scandir($directory);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$item;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } elseif (is_file($path)) {
                unlink($path);
            }
        }

        rmdir($directory);
    }
}
