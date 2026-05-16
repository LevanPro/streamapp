<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseResource extends Model
{
    protected $table = 'resources';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'file_size_bytes' => 'int',
            'is_missing' => 'bool',
            'last_seen_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'course_section_id');
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_missing', false);
    }

    /**
     * Front-end payload shared by the course & lesson Inertia pages.
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        $extension = strtolower((string) ($this->extension
            ?? pathinfo($this->filename, PATHINFO_EXTENSION)));

        $kind = match (true) {
            $extension === 'pdf' => 'pdf',
            in_array($extension, ['txt', 'go'], true) => 'text',
            default => 'download',
        };

        return [
            'id' => $this->id,
            'display_title' => $this->display_title,
            'extension' => $extension,
            'kind' => $kind,
            'size_mb' => round($this->file_size_bytes / 1048576, 2),
        ];
    }
}
