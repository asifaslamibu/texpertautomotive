<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_published' => 'boolean',
        'featured' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }
}
