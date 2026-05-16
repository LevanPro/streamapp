<?php

namespace App\Services\Courses;

class ScanSummary
{
    /**
     * @var array<string, int>
     */
    public array $stats = [
        'courses_created' => 0,
        'courses_updated' => 0,
        'courses_unchanged' => 0,
        'courses_missing' => 0,
        'sections_created' => 0,
        'sections_updated' => 0,
        'sections_unchanged' => 0,
        'sections_missing' => 0,
        'lessons_created' => 0,
        'lessons_updated' => 0,
        'lessons_unchanged' => 0,
        'lessons_missing' => 0,
        'resources_created' => 0,
        'resources_updated' => 0,
        'resources_unchanged' => 0,
        'resources_missing' => 0,
        'previews_dispatched' => 0,
        'errors' => 0,
    ];

    /**
     * @var list<string>
     */
    public array $errors = [];

    public function increment(string $key, int $value = 1): void
    {
        if (! array_key_exists($key, $this->stats)) {
            return;
        }

        $this->stats[$key] += $value;
    }

    public function addError(string $message): void
    {
        $this->stats['errors']++;
        $this->errors[] = $message;
    }
}
