<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            BrandSeeder::class,
            RoleSeeder::class,
            BannerSeeder::class,
            AdminUserSeeder::class,
            CustomerSeeder::class,
            OrderSeeder::class,
            ReviewSeeder::class,
            ProductReviewSeeder::class,
            SettingSeeder::class,
        ]);
    }
}
