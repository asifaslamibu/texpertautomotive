<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Coupon extends Model
{
    protected $guarded = [];

    protected $casts = [
        'value' => 'decimal:2',
        'min_subtotal' => 'decimal:2',
        'active' => 'boolean',
        'starts_at' => 'date',
        'expires_at' => 'date',
    ];

    public static function findByCode(?string $code): ?self
    {
        $code = strtoupper(trim((string) $code));

        return $code === '' ? null : static::where('code', $code)->first();
    }

    /**
     * Why this code cannot be used right now, in words a shopper can act on —
     * or null when it can.
     */
    public function reasonUnusable(float $subtotal): ?string
    {
        if (! $this->active) {
            return 'That code is no longer available.';
        }
        if ($this->starts_at && $this->starts_at->gt(Carbon::today())) {
            return 'That code is not active yet.';
        }
        if ($this->expires_at && $this->expires_at->lt(Carbon::today())) {
            return 'That code has expired.';
        }
        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return 'That code has already been used the maximum number of times.';
        }
        if ($subtotal < (float) $this->min_subtotal) {
            return 'That code needs an order of at least $'.number_format((float) $this->min_subtotal, 2).'.';
        }

        return null;
    }

    public function isUsable(float $subtotal): bool
    {
        return $this->reasonUnusable($subtotal) === null;
    }

    /** What this code takes off the given subtotal — never more than the subtotal itself. */
    public function discountFor(float $subtotal): float
    {
        if (! $this->isUsable($subtotal)) {
            return 0.0;
        }

        $off = $this->type === 'percent'
            ? $subtotal * ((float) $this->value / 100)
            : (float) $this->value;

        return round(min($off, $subtotal), 2);
    }

    /** How the saving reads on the order summary, e.g. "10% off" or "$5 off". */
    public function label(): string
    {
        return $this->type === 'percent'
            ? rtrim(rtrim(number_format((float) $this->value, 2), '0'), '.').'% off'
            : '$'.number_format((float) $this->value, 2).' off';
    }

    public function markUsed(): void
    {
        $this->increment('used_count');
    }
}
