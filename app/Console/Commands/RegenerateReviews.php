<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\Review;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RegenerateReviews extends Command
{
    protected $signature = 'reviews:regenerate {--keep : keep existing reviews instead of wiping them first}';

    protected $description = 'Generate realistic, varied customer reviews for every product and sync each product\'s rating + review count.';

    /** Review bodies grouped by star rating — varied, product-neutral so they fit any catalog item. */
    private array $bodies = [
        5 => [
            'Exactly what I needed and it works flawlessly. Would buy again without hesitation.',
            'Top quality — feels well built and performs even better than I expected.',
            'Fast shipping and the product is genuinely excellent. Highly recommend.',
            'Been using this daily for a month with zero issues. Money well spent.',
            'Fit perfectly and did the job the first time. Couldn\'t ask for more.',
            'Great value for the quality you get. This is now my go-to.',
            'Arrived quickly, packaged well, and works like a charm. Five stars.',
            'Solid, reliable, and exactly as described. Very happy with this purchase.',
            'Outstanding. Replaced a cheaper one and the difference is night and day.',
            'Genuine OEM quality. Installed in minutes and running perfectly.',
            'Impressed with how sturdy this is. Does everything I hoped for.',
            'Works great, price was fair, and delivery was faster than expected.',
            'No complaints at all — did the job perfectly and feels durable.',
            'Excellent product and great customer service. Will order again.',
            'Perfect fit and finish. You can tell it\'s a quality part.',
            'Exceeded my expectations. Well worth every penny.',
        ],
        4 => [
            'Good quality and works well. A little pricey but worth it.',
            'Does the job nicely. Packaging could be better but no damage.',
            'Happy with it overall. Took a bit longer to arrive than I\'d like.',
            'Works as described. Not perfect but solid for the price.',
            'Good product. Would have given five stars but the finish had a small mark.',
            'Reliable and does what it should. Shipping was a touch slow.',
            'Pretty good value. Fit was slightly tight but it went in fine.',
            'Solid purchase. Works well, just wish it came with instructions.',
            'Quality is good. Does exactly what I bought it for.',
            'No real complaints. Works fine and feels well made.',
            'Decent build and it does the job. Happy enough to reorder.',
            'Works great so far. Only minor gripe is the price.',
            'Good part, fit as expected. Delivery was on time.',
            'Satisfied with the quality. A dependable choice.',
        ],
        3 => [
            'It\'s okay. Does the job but nothing special for the price.',
            'Average product. Works, but I expected a bit more.',
            'Decent enough but the quality feels a little cheaper than the photos.',
            'Functional. Took some effort to get it fitted correctly.',
            'Works, though shipping took longer than I\'d hoped.',
            'Middle of the road. Gets the job done but I might try another brand next time.',
            'Fine for occasional use. Not sure it would hold up to heavy use.',
            'It works, but the instructions were confusing.',
            'Okay value. Does what it says, just not impressed either way.',
            'Acceptable quality. A couple of minor issues but usable.',
        ],
        2 => [
            'Disappointed. It works but the quality isn\'t what I expected.',
            'Not the best fit — needed some adjusting to make it work.',
            'Feels flimsier than I hoped for the price.',
            'It does the job but stopped working smoothly after a short while.',
            'Underwhelming. Expected better based on the description.',
        ],
        1 => [
            'Did not work for my application. Had to return it.',
            'Poor quality — stopped working within a couple of weeks.',
            'Wrong fit for what was listed. Frustrating experience.',
            'Arrived damaged and did not perform as described.',
            'Would not recommend. Feels cheaply made.',
        ],
    ];

    private array $firstNames = [
        'James', 'Michael', 'Robert', 'David', 'William', 'Richard', 'Joseph', 'Thomas', 'Chris', 'Daniel',
        'Matthew', 'Anthony', 'Mark', 'Steven', 'Paul', 'Kevin', 'Brian', 'George', 'Edward', 'Ronald',
        'Mary', 'Jennifer', 'Linda', 'Patricia', 'Susan', 'Karen', 'Nancy', 'Lisa', 'Sarah', 'Emily',
        'Ashley', 'Michelle', 'Amanda', 'Melissa', 'Rebecca', 'Laura', 'Megan', 'Olivia', 'Rachel', 'Priya',
        'Carlos', 'Miguel', 'Luis', 'Andre', 'Marcus', 'Derek', 'Trevor', 'Hassan', 'Omar', 'Wei',
    ];

    private array $roles = [
        'Verified Buyer', 'Verified Buyer', 'Verified Buyer', 'Verified Buyer',
        'HVAC Technician', 'Contractor', 'Maintenance Tech', 'Facilities Manager', 'Homeowner', 'Business Owner',
    ];

    public function handle(): int
    {
        if (! $this->option('keep')) {
            $this->info('Wiping existing reviews…');
            DB::table('reviews')->delete();
        }

        $letters = range('A', 'Z');
        $total = Product::count();
        $this->info("Generating realistic reviews for {$total} products…");
        $bar = $this->output->createProgressBar($total);

        $buffer = [];
        $reviewCount = 0;

        Product::select('id')->orderBy('id')->chunk(200, function ($products) use (&$buffer, &$reviewCount, $letters, $bar) {
            foreach ($products as $product) {
                // Each product has a "true quality" so its reviews cluster realistically.
                $quality = mt_rand(36, 49) / 10;                 // 3.6 – 4.9
                $count = $this->reviewCountForProduct();          // natural long-tail spread

                for ($i = 0; $i < $count; $i++) {
                    $star = $this->weightedStar($quality);
                    $body = $this->bodies[$star][array_rand($this->bodies[$star])];
                    $name = $this->firstNames[array_rand($this->firstNames)].' '.$letters[array_rand($letters)].'.';

                    $buffer[] = [
                        'product_id' => $product->id,
                        'author_name' => $name,
                        'author_role' => 'Verified Buyer',
                        'rating' => $star,
                        'body' => $body,
                        'is_published' => true,
                        'featured' => false,
                        'created_at' => Carbon::now()->subDays(mt_rand(1, 720))->subHours(mt_rand(0, 23))->subMinutes(mt_rand(0, 59)),
                        'updated_at' => Carbon::now(),
                    ];
                    $reviewCount++;

                    if (count($buffer) >= 1000) {
                        DB::table('reviews')->insert($buffer);
                        $buffer = [];
                    }
                }
                $bar->advance();
            }
        });

        if ($buffer) {
            DB::table('reviews')->insert($buffer);
        }
        $bar->finish();
        $this->newLine(2);

        // Sync each product's stored rating + review count from its actual reviews.
        $this->info('Syncing product ratings & counts…');
        $agg = DB::table('reviews')
            ->select('product_id', DB::raw('COUNT(*) as c'), DB::raw('AVG(rating) as a'))
            ->groupBy('product_id')
            ->get();

        foreach ($agg as $row) {
            Product::where('id', $row->product_id)->update([
                'rating' => round($row->a, 1),
                'reviews_count' => $row->c,
            ]);
        }

        $this->info("Done — {$reviewCount} reviews across {$agg->count()} products.");

        return self::SUCCESS;
    }

    /** Natural long-tail: most products get a handful, a few get many. */
    private function reviewCountForProduct(): int
    {
        $roll = mt_rand(1, 100);

        return match (true) {
            $roll <= 55 => mt_rand(3, 14),    // typical
            $roll <= 85 => mt_rand(15, 35),   // popular
            $roll <= 96 => mt_rand(36, 70),   // very popular
            default     => mt_rand(71, 140),  // best sellers
        };
    }

    /** Pick a 1–5 star rating weighted by the product's underlying quality. */
    private function weightedStar(float $quality): int
    {
        // weights for [1,2,3,4,5]
        $weights = $quality >= 4.5
            ? [1, 1, 5, 22, 71]
            : ($quality >= 4.0 ? [2, 4, 12, 32, 50] : [4, 8, 20, 33, 35]);

        $roll = mt_rand(1, array_sum($weights));
        $acc = 0;
        foreach ($weights as $idx => $w) {
            $acc += $w;
            if ($roll <= $acc) {
                return $idx + 1;
            }
        }

        return 5;
    }
}
