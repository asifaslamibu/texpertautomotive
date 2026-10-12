<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'HVAC Equipment', 'slug' => 'hvac-equipment', 'icon' => 'ac',       'tagline' => 'Furnaces, AC units & complete systems', 'featured' => true],
            ['name' => 'HVAC Parts',     'slug' => 'hvac-parts',     'icon' => 'parts',    'tagline' => 'Coils, filters, motors & components',     'featured' => true],
            ['name' => 'Mini Splits',    'slug' => 'mini-splits',    'icon' => 'minisplit','tagline' => 'Ductless heating & cooling',               'featured' => true],
            ['name' => 'Air Quality',    'slug' => 'air-quality',    'icon' => 'air',      'tagline' => 'Purifiers, humidifiers & ventilation',     'featured' => true],
            ['name' => 'Plumbing',       'slug' => 'plumbing',       'icon' => 'plumbing', 'tagline' => 'Valves, fittings & water heaters',         'featured' => true],
            ['name' => 'Electrical',     'slug' => 'electrical',     'icon' => 'electric', 'tagline' => 'Wiring, breakers & controls',              'featured' => true],
            ['name' => 'Tools',          'slug' => 'tools',          'icon' => 'tools',    'tagline' => 'Pro-grade hand & power tools',             'featured' => true],
            ['name' => 'Pool Supplies',  'slug' => 'pool-supplies',  'icon' => 'pool',     'tagline' => 'Pumps, filters & maintenance',             'featured' => true],
        ];

        foreach ($categories as $i => $data) {
            Category::updateOrCreate(
                ['slug' => $data['slug']],
                array_merge($data, ['sort' => $i])
            );
        }
    }
}
