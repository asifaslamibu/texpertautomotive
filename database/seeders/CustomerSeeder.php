<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Tonya Beck', 'Lee Krohn', 'Janet Thompson', 'Andy Sawyer', 'Marcus Reed',
            'Priya Patel', 'Carlos Mendez', 'Sara Lindqvist', 'David Okafor', 'Emily Chen',
            'Robert Walsh', 'Nina Brooks', 'James Carter', 'Aisha Rahman', 'Tom Nguyen',
            'Laura Hughes', 'Victor Romano', 'Hannah Webb', 'Greg Olsen', 'Maya Joshi',
            'Frank DeLuca', 'Olivia Park', 'Derek Shaw', 'Bianca Russo', 'Sam Whitaker',
        ];
        $companies = ['', '', 'Acme HVAC Co.', 'Cool Air Pros', 'Reliable Plumbing', '', 'Summit Mechanical', '', 'BlueLine Contractors', ''];
        $cities = [['Latrobe', 'PA', '15650'], ['Austin', 'TX', '73301'], ['Denver', 'CO', '80201'], ['Phoenix', 'AZ', '85001'], ['Tampa', 'FL', '33601'], ['Columbus', 'OH', '43004'], ['Reno', 'NV', '89501'], ['Raleigh', 'NC', '27601']];

        foreach ($names as $i => $name) {
            [$city, $state, $zip] = $cities[$i % count($cities)];
            Customer::updateOrCreate(
                ['email' => Str::slug($name, '.').'@example.com'],
                [
                    'name' => $name,
                    'phone' => '('.rand(200, 999).') '.rand(200, 999).'-'.rand(1000, 9999),
                    'company' => $companies[$i % count($companies)] ?: null,
                    'address' => rand(100, 9999).' '.['Unity', 'Maple', 'Oak', 'Pine', 'Main', 'Cedar'][$i % 6].' St',
                    'city' => $city,
                    'state' => $state,
                    'zip' => $zip,
                ]
            );
        }
    }
}
