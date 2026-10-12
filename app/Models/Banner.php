<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $guarded = [];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /** Resolved CTA link, defaulting to the products page. */
    public function getCtaUrlAttribute(): string
    {
        $link = trim((string) $this->cta_link);
        if ($link === '') {
            return route('products.index');
        }
        if (\Illuminate\Support\Str::startsWith($link, ['http://', 'https://', '/'])) {
            return $link;
        }

        return route('products.index', ['q' => $link]);
    }
}
