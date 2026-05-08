<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseSection extends Model
{
    public const ROOT_RELATIVE_PATH = '__root__';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_missing' => 'bool',
            'last_seen_at' => 'datetime',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(CourseResource::class);
    }

    public function getIsRootAttribute(): bool
    {
        return $this->relative_path === self::ROOT_RELATIVE_PATH;
    }
}
