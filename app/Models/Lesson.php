<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lesson extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'float',
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

    public function resources(): HasMany
    {
        return $this->hasMany(CourseResource::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_missing', false);
    }
}
