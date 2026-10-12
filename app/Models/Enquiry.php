<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enquiry extends Model
{
    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
        'is_read' => 'boolean',
    ];

    public const TYPES = [
        'contact'        => 'Contact',
        'part_request'   => 'Part Request',
        'return_request' => 'Return Request',
        'wholesale'      => 'Wholesale',
        'chatbot'        => 'Chatbot',
    ];

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst(str_replace('_', ' ', $this->type));
    }

    public function getTypeColorAttribute(): string
    {
        return [
            'contact'        => 'sky',
            'part_request'   => 'amber',
            'return_request' => 'rose',
            'wholesale'      => 'emerald',
            'chatbot'        => 'violet',
        ][$this->type] ?? 'slate';
    }
}
