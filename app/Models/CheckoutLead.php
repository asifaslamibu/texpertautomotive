<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A checkout that was started — saved field by field as the buyer types, so an
 * abandoned one can be followed up. Converts into an order (see `order_id`).
 */
class CheckoutLead extends Model
{
    protected $guarded = [];

    protected $casts = [
        'items' => 'array',
        'cart_total' => 'decimal:2',
        'ordered_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** Started a checkout but never placed the order. */
    public function scopeAbandoned($query)
    {
        return $query->whereNull('order_id');
    }

    public function getIsAbandonedAttribute(): bool
    {
        return $this->order_id === null;
    }

    /** Name/email/phone — enough to actually follow up. */
    public function getHasContactAttribute(): bool
    {
        return filled($this->email) || filled($this->phone);
    }
}
