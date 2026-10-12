<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        // Featured homepage testimonials (not tied to a product)
        $featured = [
            ['Tonya Beck', 'Contractor', 5, 'Had the exact part in stock and shipped same day. Saved my install timeline.'],
            ['Lee Krohn', 'HVAC Tech', 5, 'Great selection and support helped me pick the right unit. Five stars.'],
            ['Janet Thompson', 'Homeowner', 5, 'Best prices I found online for HVAC equipment. Fast, reliable delivery.'],
            ['Andy Sawyer', 'Plumber', 4, 'Mini split arrived perfectly packed and exactly as described. Will reorder.'],
        ];

        foreach ($featured as $i => [$name, $role, $rating, $body]) {
            Review::updateOrCreate(
                ['author_name' => $name, 'product_id' => null],
                ['author_role' => $role, 'rating' => $rating, 'body' => $body, 'is_published' => true, 'featured' => true]
            );
        }

        // A few product-specific reviews
        $bodies = [
            'Works exactly as expected. Quiet and efficient.',
            'Solid build quality and arrived quickly.',
            'Good value for the price — would buy again.',
            'Installation was straightforward. Happy so far.',
            'Exactly what I needed, fair price.',
        ];
        $people = ['Marcus R.', 'Priya P.', 'Carlos M.', 'Emily C.', 'Robert W.', 'Nina B.'];

        Product::inRandomOrder()->take(15)->get()->each(function ($product) use ($bodies, $people) {
            foreach (range(1, rand(1, 2)) as $k) {
                Review::create([
                    'product_id'  => $product->id,
                    'author_name' => $people[array_rand($people)],
                    'author_role' => 'Verified Buyer',
                    'rating'      => [4, 5, 5, 5][array_rand([0, 1, 2, 3])],
                    'body'        => $bodies[array_rand($bodies)],
                    'is_published'=> true,
                    'featured'    => false,
                ]);
            }
        });
    }
}
