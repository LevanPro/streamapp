<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_missing' => 'bool',
            'last_seen_at' => 'datetime',
        ];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(CourseSection::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(CourseResource::class);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_missing', false);
    }
}
