<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $guarded = [];

    protected $casts = [
        'permissions' => 'array',
        'read_only' => 'boolean',
        'is_super' => 'boolean',
    ];

    /**
     * The complete catalogue of admin sections a role can be granted.
     * Key = permission slug used in routes/middleware, value = human label.
     */
    public const PERMISSIONS = [
        'products'   => 'Products',
        'categories' => 'Categories',
        'brands'     => 'Brands',
        'import'     => 'Import (CSV)',
        'stock'      => 'Stock Update',
        'orders'     => 'Orders',
        'purchase_orders' => 'Purchase Orders',
        'customers'  => 'Customers',
        'enquiries'  => 'Enquiries',
        'reviews'    => 'Reviews',
        'blogs'      => 'Blog',
        'subscribers' => 'Subscribers',
        'coupons'    => 'Discount Codes',
        'settings'   => 'Settings',
        'staff'      => 'Staff & Roles',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** Does this role grant the given permission? Super roles grant everything. */
    public function grants(string $permission): bool
    {
        return $this->is_super || in_array($permission, $this->permissions ?? [], true);
    }

    /** Label list for the permissions this role holds (for display). */
    public function permissionLabels(): array
    {
        if ($this->is_super) {
            return array_values(self::PERMISSIONS);
        }

        return collect($this->permissions ?? [])
            ->map(fn ($p) => self::PERMISSIONS[$p] ?? $p)
            ->all();
    }
}
