<?php

namespace App\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Eloquent user provider that also recognises a single code-defined "master" admin.
 * The master account exists only in config (config/masteradmin.php) — there is NO
 * database row for it. Everything else falls through to the normal Eloquent provider.
 */
class MasterUserProvider extends EloquentUserProvider
{
    /** Sentinel id for the virtual master user (well above any real auto-increment id). */
    public const MASTER_ID = 999000001;

    public function retrieveById($identifier)
    {
        if ((string) $identifier === (string) self::MASTER_ID) {
            return $this->masterUser();
        }

        return parent::retrieveById($identifier);
    }

    public function retrieveByToken($identifier, $token)
    {
        if ((string) $identifier === (string) self::MASTER_ID) {
            return null; // master account doesn't use "remember me" tokens
        }

        return parent::retrieveByToken($identifier, $token);
    }

    public function retrieveByCredentials(array $credentials)
    {
        $email = $credentials['email'] ?? null;
        if ($email !== null && $this->masterEmail() !== '' && strcasecmp($email, $this->masterEmail()) === 0) {
            return $this->masterUser();
        }

        return parent::retrieveByCredentials($credentials);
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        if ($user instanceof User && (int) $user->getAuthIdentifier() === self::MASTER_ID) {
            $expected = (string) config('masteradmin.password');
            $given = (string) ($credentials['password'] ?? '');

            // No master password configured: the account cannot be logged into at all.
            if ($expected === '') {
                return false;
            }

            // A bcrypt/argon hash is checked properly; a plain value is compared in
            // constant time so the comparison itself gives nothing away.
            return str_starts_with($expected, '$2y$') || str_starts_with($expected, '$argon')
                ? password_verify($given, $expected)
                : hash_equals($expected, $given);
        }

        return parent::validateCredentials($user, $credentials);
    }

    protected function masterEmail(): string
    {
        return (string) config('masteradmin.email');
    }

    /** Build the in-memory master user (super admin) — never persisted. */
    protected function masterUser(): User
    {
        $user = new User([
            'name' => 'Master Admin',
            'email' => $this->masterEmail(),
            'is_admin' => true,
        ]);
        $user->id = self::MASTER_ID;
        $user->exists = true;

        // Attach a virtual super-admin role so isSuperAdmin()/permissions resolve to full access.
        $role = new Role(['name' => 'Administrator', 'is_super' => true, 'read_only' => false, 'permissions' => []]);
        $user->setRelation('role', $role);

        return $user;
    }
}
