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
}
