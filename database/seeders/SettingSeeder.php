<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'site_name'          => 'Texpert Deals',
            'site_description'   => 'Texpert Deals is a Texas-based wholesale distributor putting 1,000+ trusted brands in one catalog at wholesale prices. Backed by deep industry experience and strong manufacturer and distribution relationships, we ship genuine, OEM, warranty-backed products — same-day on 100,000+ in-stock items — with volume discounts, Net-30 terms for contractors, and expert support behind every order. Reliability, quality, and customer satisfaction, on every order.',
            'color_primary'      => '#f59e0b',
            'color_secondary'    => '#0f172a',
            'announcement'       => '🔥 Bulk wholesale pricing • 5% off your first order — use code DEALS5',
            'announcement_code'  => 'DEALS5',
            'free_shipping_note' => 'Fast shipping nationwide',
            'hero_badge'         => 'Now shipping for 2026',
            'hero_title'         => 'The smarter way to buy wholesale online.',
            'hero_subtitle'      => "Automotive, HVAC, tools, aviation, marine, water systems, electronics & outdoor gear — bulk pricing, fast shipping, and expert support on every order.",
            'contact_phone'      => '+1-254-358-0017',
            'contact_email'      => 'sales@texpertdeals.us',
            'contact_address'    => '2990 Congressman Ln, Dallas, TX 75220',
            'social_facebook'    => 'https://www.facebook.com/Texpertdeals.us',
            'social_instagram'   => 'https://www.instagram.com/texpertdeals.us/',
            'social_linkedin'    => 'https://www.linkedin.com/company/texpert-deals/',
            'whatsapp'           => '+1 (254) 358-0017',
        ];

        // Settings the admin may have customised — only seed if missing.
        $seedIfMissing = ['announcement', 'announcement_code', 'free_shipping_note', 'hero_badge', 'hero_title', 'hero_subtitle'];

        foreach ($defaults as $key => $value) {
            if (in_array($key, $seedIfMissing, true) && Setting::where('key', $key)->exists()) {
                continue;
            }
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
