<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Distributor extends Model
{
    protected $fillable = [
        'name', 'company', 'email', 'phone', 'address', 'notes', 'is_default', 'active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'active' => 'boolean',
    ];

    public function purchaseOrders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /** The distributor auto-assigned to new POs (falls back to the first active one). */
    public static function default(): ?self
    {
        return static::query()->where('active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();
    }
}
