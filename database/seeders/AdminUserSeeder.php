<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure the system roles exist, then make the main user a super Administrator.
        $this->callOnce(RoleSeeder::class);

        $admin = Role::where('slug', 'administrator')->first();

        User::updateOrCreate(
            ['email' => 'admin@texperdeals.com'],
            [
                'name' => 'Store Admin',
                'password' => Hash::make('password'),
                'is_admin' => true,
                'role_id' => $admin?->id,
            ]
        );
    }
}
