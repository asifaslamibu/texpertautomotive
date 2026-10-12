<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /** Full, unrestricted access (the "main"/super admin). */
    public function isSuperAdmin(): bool
    {
        return $this->is_admin && (bool) $this->role?->is_super;
    }

    /** The code-defined master (secret) admin — only one who sees Stripe/payments. */
    public function isMaster(): bool
    {
        $email = (string) config('masteradmin.email');

        return $this->is_admin && $email !== '' && strcasecmp((string) $this->email, $email) === 0;
    }

    /** May this user access the given admin section? */
    public function hasPermission(string $permission): bool
    {
        if (! $this->is_admin) {
            return false;
        }
        if ($this->isSuperAdmin()) {
            return true;
        }

        return (bool) $this->role?->grants($permission);
    }

    /** View-only staff (e.g. an auditor) — may see their sections but not modify data. */
    public function isReadOnly(): bool
    {
        return ! $this->isSuperAdmin() && (bool) $this->role?->read_only;
    }

    /** Keys of every section this user can reach (for nav filtering). */
    public function allowedSections(): array
    {
        if ($this->isSuperAdmin()) {
            return array_keys(Role::PERMISSIONS);
        }

        return array_values(array_intersect(array_keys(Role::PERMISSIONS), $this->role?->permissions ?? []));
    }
}
