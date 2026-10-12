<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use Notifiable;

    protected $guarded = [];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function getTotalSpentAttribute(): float
    {
        return (float) $this->orders()->whereNotIn('status', ['cancelled'])->sum('total');
    }

    /** Has this customer set a password (registered, not just a guest checkout)? */
    public function hasAccount(): bool
    {
        return ! empty($this->password);
    }
}
