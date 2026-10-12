<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Administrator',
                'slug' => 'administrator',
                'description' => 'Full, unrestricted access to every section, including staff & roles. Cannot be deleted.',
                'permissions' => array_keys(Role::PERMISSIONS),
                'read_only' => false,
                'is_super' => true,
            ],
            [
                'name' => 'Website Manager',
                'slug' => 'website-manager',
                'description' => 'Manages catalogue, orders, reviews and storefront settings.',
                'permissions' => ['products', 'categories', 'brands', 'import', 'orders', 'customers', 'reviews', 'settings'],
                'read_only' => false,
                'is_super' => false,
            ],
            [
                'name' => 'Virtual Assistant',
                'slug' => 'virtual-assistant',
                'description' => 'Imports and manages products, categories and brands.',
                'permissions' => ['products', 'categories', 'brands', 'import'],
                'read_only' => false,
                'is_super' => false,
            ],
            [
                'name' => 'Auditor',
                'slug' => 'auditor',
                'description' => 'Read-only access for auditing — can view everything but cannot make changes.',
                'permissions' => ['products', 'categories', 'brands', 'orders', 'customers', 'reviews', 'settings'],
                'read_only' => true,
                'is_super' => false,
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
