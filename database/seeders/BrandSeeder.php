<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = Product::whereNotNull('brand')
            ->selectRaw('brand, COUNT(*) as c')
            ->groupBy('brand')
            ->orderByDesc('c')
            ->pluck('brand');

        foreach ($brands as $i => $name) {
            $name = trim($name);
            if ($name === '') {
                continue;
            }

            $slug = Str::slug($name) ?: 'brand-'.$i;
            // ensure unique slug
            $base = $slug;
            $n = 1;
            while (Brand::where('slug', $slug)->where('name', '!=', $name)->exists()) {
                $slug = $base.'-'.$n++;
            }

            Brand::updateOrCreate(
                ['name' => $name],
                ['slug' => $slug, 'featured' => true, 'sort' => $i]
            );
        }
    }
}
