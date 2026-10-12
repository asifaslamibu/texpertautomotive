<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ImportLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'download_images' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Is the saved copy of the uploaded file still on disk? (default 'local' disk) */
    public function fileExists(): bool
    {
        return $this->stored_path && Storage::exists($this->stored_path);
    }
}
