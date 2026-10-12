<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedCard extends Model
{
    protected $guarded = [];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** "Visa •••• 4242" */
    public function getLabelAttribute(): string
    {
        return ucfirst($this->brand ?: 'Card').' •••• '.($this->last4 ?: '????');
    }

    /** "08 / 2027" */
    public function getExpiryAttribute(): string
    {
        if (! $this->exp_month || ! $this->exp_year) {
            return '—';
        }

        return str_pad((string) $this->exp_month, 2, '0', STR_PAD_LEFT).' / '.$this->exp_year;
    }
}
