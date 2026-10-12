<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $guarded = [];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'placed_at' => 'datetime',
        'paid_at' => 'datetime',
        'analytics_tracked_at' => 'datetime',
    ];

    public const STATUSES = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];

    /**
     * shipping_service of an order no carrier could rate (too heavy / oversized):
     * placed without shipping; the team adds the cost and sends a payment link.
     */
    public const SHIPPING_QUOTE = 'Shipping quote pending';

    /** Still waiting for the team to price the shipping. */
    public function getShippingQuotePendingAttribute(): bool
    {
        return $this->shipping_service === self::SHIPPING_QUOTE && (float) $this->shipping <= 0;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function purchaseOrder(): HasOne
    {
        return $this->hasOne(PurchaseOrder::class);
    }

    /** Customer-facing pay-by-link URL (null until a token is set). */
    public function payUrl(): ?string
    {
        return $this->pay_token ? route('pay.show', $this->pay_token) : null;
    }

    public function getStatusColorAttribute(): string
    {
        return [
            'pending'    => 'amber',
            'processing' => 'blue',
            'shipped'    => 'indigo',
            'completed'  => 'emerald',
            'cancelled'  => 'rose',
        ][$this->status] ?? 'slate';
    }
}
