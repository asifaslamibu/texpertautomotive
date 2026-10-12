<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        if (Banner::count() > 0) {
            return; // don't overwrite admin-managed banners
        }

        $defaults = [
            ['eyebrow' => 'Built for', 'title' => 'HVAC/R Pros', 'subtitle' => 'Who Demand More', 'cta_label' => 'Shop Tools & Equipment', 'cta_link' => '', 'sort' => 0],
            ['eyebrow' => 'Top brands,', 'title' => 'Wholesale Prices', 'subtitle' => 'On Thousands of Products', 'cta_label' => 'Browse the Catalog', 'cta_link' => '', 'sort' => 1],
            ['eyebrow' => 'Fast & reliable', 'title' => 'Shipping Nationwide', 'subtitle' => 'On In-Stock Items', 'cta_label' => 'Start Shopping', 'cta_link' => '', 'sort' => 2],
        ];

        foreach ($defaults as $row) {
            Banner::create($row + ['active' => true]);
        }
    }
}
