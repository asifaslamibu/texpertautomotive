<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductReviewSeeder extends Seeder
{
    /** Give every product 3–5 published reviews, then sync each product's rating + reviews_count. */
    public function run(): void
    {
        $bodies = [
            'Works exactly as expected. Quiet, efficient, and well built.',
            'Solid build quality and it arrived quickly — well packaged.',
            'Great value for the price. Would order again without hesitation.',
            'Installation was straightforward and it has been reliable so far.',
            'Exactly what I needed for the job. Fair price and fast shipping.',
            'Performs as described. Happy with the purchase overall.',
            'Good quality part, fit perfectly the first time.',
            'Shipped same day and showed up ahead of schedule. Impressed.',
            'Been using it for a few weeks now with zero issues.',
            'Exactly as pictured. Genuine part, not a knock-off.',
            'Customer support helped me confirm compatibility before I bought. Five stars.',
            'Does the job well. A little pricey but worth it for the quality.',
            'Replaced my old one and the difference is night and day.',
            'Durable and easy to work with. Recommended for any tech.',
            'No complaints — exactly what the listing promised.',
            'Fast delivery, fair price, and it works great. Will buy here again.',
        ];

        $people = [
            'Marcus R.', 'Priya P.', 'Carlos M.', 'Emily C.', 'Robert W.', 'Nina B.',
            'David L.', 'Sarah K.', 'James T.', 'Maria G.', 'Kevin H.', 'Linda S.',
            'Tom B.', 'Aisha N.', 'Greg D.', 'Rachel F.', 'Victor O.', 'Hannah W.',
            'Derek P.', 'Sophia M.', 'Brian C.', 'Olivia R.', 'Frank J.', 'Megan A.',
        ];

        $roles = ['Verified Buyer', 'HVAC Tech', 'Contractor', 'Plumber', 'Homeowner', 'Facilities Manager'];

        // ratings weighted toward 4–5 stars
        $ratingPool = [5, 5, 5, 5, 5, 4, 4, 4, 4, 3];

        // existing per-product review counts so re-running only tops up to the target
        $existing = DB::table('reviews')
            ->whereNotNull('product_id')
            ->select('product_id', DB::raw('COUNT(*) as c'))
            ->groupBy('product_id')
            ->pluck('c', 'product_id');

        $now = now();
        $batch = [];
        $made = 0;

        Product::select('id')->orderBy('id')->chunk(500, function ($products) use (
            &$batch, &$made, $bodies, $people, $roles, $ratingPool, $existing, $now
        ) {
            foreach ($products as $product) {
                $target = random_int(3, 5);
                $have = (int) ($existing[$product->id] ?? 0);
                $need = max(0, $target - $have);

                for ($i = 0; $i < $need; $i++) {
                    $created = $now->copy()->subDays(random_int(1, 540))->subHours(random_int(0, 23));
                    $batch[] = [
                        'product_id'   => $product->id,
                        'author_name'  => $people[array_rand($people)],
                        'author_role'  => $roles[array_rand($roles)],
                        'rating'       => $ratingPool[array_rand($ratingPool)],
                        'body'         => $bodies[array_rand($bodies)],
                        'is_published' => true,
                        'featured'     => false,
                        'created_at'   => $created,
                        'updated_at'   => $created,
                    ];
                    $made++;
                }

                // flush in chunks to keep memory + query size sane
                if (count($batch) >= 1000) {
                    DB::table('reviews')->insert($batch);
                    $batch = [];
                }
            }
        });

        if ($batch) {
            DB::table('reviews')->insert($batch);
        }

        // sync each product's stored rating + reviews_count from its published reviews
        DB::statement('
            UPDATE products p
            LEFT JOIN (
                SELECT product_id, COUNT(*) AS c, AVG(rating) AS a
                FROM reviews
                WHERE is_published = 1 AND product_id IS NOT NULL
                GROUP BY product_id
            ) r ON r.product_id = p.id
            SET p.reviews_count = COALESCE(r.c, 0),
                p.rating = ROUND(COALESCE(r.a, 0), 1)
        ');

        $this->command?->info("Created {$made} new reviews; product ratings & counts synced.");
    }
}
