<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'order_id', 'distributor_id', 'po_number', 'po_date',
        'distributor_name', 'distributor_phone', 'distributor_email',
        'billing_address', 'shipping_address',
        'items', 'tax', 'shipping', 'notes',
        'status', 'sent_at', 'approved_at',
    ];

    protected $casts = [
        'po_date' => 'date',
        'items' => 'array',
        'tax' => 'decimal:2',
        'shipping' => 'decimal:2',
        'sent_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function order(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function distributor(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Distributor::class);
    }

    /**
     * Build a draft purchase order from a placed customer order — the ordered
     * products become the line items and the customer becomes the recipient
     * shown in the PDF's "Customer Information" block.
     */
    public static function fromOrder(Order $order): self
    {
        $order->loadMissing('items', 'customer');
        $c = $order->customer;
        $address = $c
            ? trim(implode(', ', array_filter([$c->address, $c->city, $c->state, $c->zip])))
            : null;

        return static::create([
            'order_id' => $order->id,
            'distributor_id' => optional(Distributor::default())->id,
            'po_number' => $order->order_number ?: static::nextNumber(),
            'po_date' => optional($order->placed_at)->toDateString() ?? now()->toDateString(),
            'distributor_name' => $c->name ?? 'Customer',
            'distributor_phone' => $c->phone ?? null,
            'distributor_email' => $c->email ?? null,
            'billing_address' => $address ?: null,
            'shipping_address' => $address ?: null,
            'items' => $order->items->map(fn ($it) => [
                'description' => $it->name,
                'qty' => (float) $it->quantity,
                'price' => (float) $it->price,
                'discount' => 0,
            ])->all(),
            'tax' => $order->tax,
            'shipping' => $order->shipping,
            'notes' => $order->notes,
            'status' => 'draft',
        ]);
    }

    /** Normalised line items, each with computed line total. */
    public function lineItems(): array
    {
        return collect($this->items ?? [])
            ->map(function ($row) {
                $qty = (float) ($row['qty'] ?? 0);
                $price = (float) ($row['price'] ?? 0);
                $discount = (float) ($row['discount'] ?? 0);   // absolute amount off the line
                $total = max(0, ($qty * $price) - $discount);

                return [
                    'description' => trim((string) ($row['description'] ?? '')),
                    'qty' => $qty,
                    'price' => $price,
                    'discount' => $discount,
                    'total' => $total,
                ];
            })
            ->filter(fn ($r) => $r['description'] !== '' || $r['price'] > 0)
            ->values()
            ->all();
    }

    public function getSubtotalAttribute(): float
    {
        return array_sum(array_column($this->lineItems(), 'total'));
    }

    public function getTotalAttribute(): float
    {
        return $this->subtotal + (float) $this->tax + (float) $this->shipping;
    }

    /** Next sequential PO number, e.g. PO-0007. */
    public static function nextNumber(): string
    {
        $last = static::query()->max('id') ?? 0;

        return 'PO-'.str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT);
    }
}
