<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            'hvac-equipment' => [
                ['Goodman 3 Ton 14.3 SEER2 Air Conditioner Condenser', 'Goodman', 1899, 2299],
                ['Daikin 2.5 Ton 15.2 SEER2 Heat Pump System', 'Daikin', 2649, 3099],
                ['Carrier Comfort 80% AFUE Gas Furnace 60K BTU', 'Carrier', 1349, 1599],
                ['Lennox Merit 14 SEER2 Central AC Unit', 'Lennox', 1749, null],
                ['Rheem Classic 16 SEER2 4 Ton Condenser', 'Rheem', 2199, 2549],
                ['Trane XR14 Single-Stage Air Conditioner', 'Trane', 2399, 2799],
            ],
            'hvac-parts' => [
                ['Universal Evaporator Coil 3 Ton Cased A-Coil', 'Allied', 389, 459],
                ['Capacitor Dual Run 45/5 MFD 440V', 'Genteq', 24, 34],
                ['Condenser Fan Motor 1/4 HP 230V', 'Fasco', 89, 119],
                ['MERV 11 Pleated Air Filter 16x25x1 (6-Pack)', 'Filtrete', 42, 59],
                ['Hard Start Kit Compressor Booster', 'Supco', 31, 45],
                ['Refrigerant Line Set 3/8 x 3/4 - 25ft', 'Mueller', 134, 169],
            ],
            'mini-splits' => [
                ['Daikin 12,000 BTU 19 SEER2 Ductless Mini Split', 'Daikin', 1099, 1349],
                ['Mitsubishi 9,000 BTU Hyper-Heat Single Zone', 'Mitsubishi', 1899, 2199],
                ['Pioneer 24,000 BTU 20 SEER Mini Split Heat Pump', 'Pioneer', 1299, 1549],
                ['MRCOOL DIY 18K BTU 4th Gen Ductless System', 'MRCOOL', 1549, 1799],
                ['Senville LETO 12,000 BTU Mini Split Inverter', 'Senville', 849, 999],
            ],
            'air-quality' => [
                ['Whole-House Bypass Humidifier 17 GPD', 'Aprilaire', 189, 239],
                ['Media Air Cleaner Cabinet MERV 16', 'Honeywell', 279, 329],
                ['UV-C Air Purifier Coil Sanitizer 24V', 'Fresh-Aire', 219, 259],
                ['HEPA Whole-Home Air Purifier 600 CFM', 'Carrier', 749, 899],
                ['Energy Recovery Ventilator (ERV) 150 CFM', 'Broan', 689, 829],
            ],
            'plumbing' => [
                ['40 Gallon Natural Gas Water Heater 40K BTU', 'Rheem', 629, 749],
                ['Tankless Water Heater 199K BTU Natural Gas', 'Navien', 1199, 1449],
                ['1/2 in. Copper Press Ball Valve', 'Apollo', 38, 49],
                ['PEX-A Expansion Tubing 3/4 in. x 100 ft', 'Uponor', 119, 149],
                ['Sump Pump 1/3 HP Cast Iron Submersible', 'Zoeller', 179, 219],
            ],
            'electrical' => [
                ['60 Amp AC Disconnect Non-Fused Outdoor', 'Siemens', 27, 39],
                ['Programmable Smart Thermostat Wi-Fi', 'Honeywell', 129, 169],
                ['24V 40VA Transformer Class II', 'Schneider', 18, 26],
                ['Whip Liquid-Tight Conduit 1/2 in. x 6 ft', 'Southwire', 14, 21],
                ['Single Pole Contactor 30 Amp 24V Coil', 'Packard', 22, 31],
            ],
            'tools' => [
                ['Digital HVAC Manifold Gauge Set 4-Valve', 'Yellow Jacket', 349, 429],
                ['Cordless Vacuum Pump 1.5 CFM 18V', 'Milwaukee', 289, 339],
                ['Refrigerant Leak Detector Heated Diode', 'Fieldpiece', 199, 249],
                ['Tubing Cutter & Flaring Tool Kit', 'Ridgid', 79, 99],
                ['Clamp Meter 600A True-RMS Digital', 'Fluke', 159, 199],
            ],
            'pool-supplies' => [
                ['Variable-Speed Pool Pump 1.65 THP', 'Pentair', 899, 1099],
                ['Cartridge Pool Filter 150 Sq Ft', 'Hayward', 249, 309],
                ['Salt Chlorine Generator System 40K Gal', 'Pentair', 749, 899],
                ['Robotic Pool Cleaner In-Ground', 'Dolphin', 899, 1099],
                ['Pool Heat Pump 110K BTU', 'Hayward', 2299, 2699],
            ],
        ];

        $palette = [
            'hvac-equipment' => ['e8f0fe', '1a56db'],
            'hvac-parts'     => ['fef3c7', 'b45309'],
            'mini-splits'    => ['e0f2fe', '0369a1'],
            'air-quality'    => ['dcfce7', '15803d'],
            'plumbing'       => ['ede9fe', '6d28d9'],
            'electrical'     => ['fee2e2', 'b91c1c'],
            'tools'          => ['f1f5f9', '334155'],
            'pool-supplies'  => ['cffafe', '0e7490'],
        ];

        foreach ($catalog as $slug => $items) {
            $category = Category::where('slug', $slug)->first();
            if (! $category) {
                continue;
            }

            [$bg, $fg] = $palette[$slug];

            foreach ($items as $idx => [$name, $brand, $price, $compare]) {
                $label = rawurlencode(Str::limit($brand.' '.Str::words($name, 3, ''), 28, ''));
                $productSlug = Str::slug($name).'-'.$category->id.$idx;

                Product::updateOrCreate(
                    ['slug' => $productSlug],
                    [
                        'category_id'      => $category->id,
                        'name'             => $name,
                        'brand'            => $brand,
                        'sku'              => strtoupper(Str::substr($brand, 0, 3)).'-'.str_pad((string) (($category->id * 100) + $idx), 5, '0', STR_PAD_LEFT),
                        'short_description'=> "Professional-grade {$category->name} from {$brand}. In stock and ready for same-day shipping.",
                        'description'      => "The {$name} is engineered for reliable, long-lasting performance in both residential and commercial applications. Backed by {$brand}'s industry-leading warranty and TexperDeals' expert support, it ships fast from our nationwide distribution network. Every unit is inspected before dispatch to ensure it meets professional installation standards.",
                        'price'            => $price,
                        'compare_at_price' => $compare,
                        'image'            => "https://placehold.co/600x600/{$bg}/{$fg}?text={$label}",
                        'rating'           => [4.5, 4.6, 4.7, 4.8, 4.9, 5.0][$idx % 6],
                        'reviews_count'    => 12 + ($idx * 7) + ($category->id * 5),
                        'in_stock'         => true,
                        'featured'         => $idx < 2,
                    ]
                );
            }
        }
    }
}
