<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Live posts only: published and not scheduled for the future. */
    public function scopePublished($query)
    {
        return $query->where('published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function getDateAttribute()
    {
        return $this->published_at ?: $this->created_at;
    }

    public function readMinutes(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags((string) $this->body)) / 200));
    }
}
