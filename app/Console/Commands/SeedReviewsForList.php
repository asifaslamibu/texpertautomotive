<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SeedReviewsForList extends Command
{
    protected $signature = 'products:seed-reviews-list
        {--min=120 : minimum reviews per product}
        {--max=140 : maximum reviews per product}
        {--keep : append instead of replacing existing reviews for these products}';

    protected $description = 'Seed 120–140 realistic reviews for a fixed list of featured products and sync each rating + count.';

    /** The products to seed (matched by exact name first, then a LIKE contains). */
    private array $names = [
        'Fieldpiece SC260 Clamp Meter',
        'Fieldpiece SM482V Wireless Digital Manifold',
        'Pro1 IAQ T705 Programmable Thermostat',
        'Yellow Jacket 29985 PLUS2 R410A Hoses',
        'Yellow Jacket 42035',
        'Yellow Jacket 49968',
        'Fieldpiece JL3KM2',
        'Fieldpiece MG44',
        'NAVAC NVR2',
        'NAVAC NEVK2',
        'Pro1 IAQ T701 Thermostat',
        'Intermatic AG3000',
        'Nu-Calgon 4291-18',
        'Nu-Calgon 4287-34',
        'Nu-Calgon 4171-75',
        'Rectorseal 83939',
        'Harris SB861 Solder',
        'Fieldpiece SDMN5 Dual Input Manometer',
        'UEi INF165C Digital Infrared Thermometer',
        'Fieldpiece SPK3 Folding Pocket Thermometer',
        'Honeywell M847D-ZONE/U Damper Actuator',
        'Honeywell TH6320WF2003/U T6 Pro',
        'Mars M10404 Condenser Fan Motor',
        'Johnson Controls P100AP-2C Pressure Switch',
    ];

    /** Product-neutral review bodies grouped by star rating. */
    private array $bodies = [
        5 => [
            'Exactly what I needed and it works flawlessly. Would buy again without hesitation.',
            'Top quality — feels well built and performs even better than I expected.',
            'Fast shipping and the product is genuinely excellent. Highly recommend.',
            'Been using this daily on the job for a month with zero issues. Money well spent.',
            'Accurate, reliable, and did the job the first time. Couldn\'t ask for more.',
            'Great value for the quality you get. This is now my go-to on every call.',
            'Arrived quickly, packaged well, and works like a charm. Five stars.',
            'Solid, dependable, and exactly as described. Very happy with this purchase.',
            'Outstanding. Replaced a cheaper one and the difference is night and day.',
            'Genuine pro-grade quality. Up and running in minutes and performing perfectly.',
            'Impressed with how sturdy this is. Does everything I hoped for and more.',
            'Works great, price was fair, and delivery was faster than expected.',
            'No complaints at all — did the job perfectly and feels built to last.',
            'Excellent product and great customer service. Will order again.',
            'Readings are spot on and consistent. A tool I trust in the field.',
            'Exceeded my expectations. Well worth every penny.',
            'My whole crew wants one now. Fast, accurate and tough.',
            'Best purchase I\'ve made for my kit this year. Highly recommended.',
        ],
        4 => [
            'Good quality and works well. A little pricey but worth it.',
            'Does the job nicely. Packaging could be better but no damage.',
            'Happy with it overall. Took a bit longer to arrive than I\'d like.',
            'Works as described. Not perfect but solid for the price.',
            'Would have given five stars but the finish had a small mark.',
            'Reliable and does what it should. Shipping was a touch slow.',
            'Pretty good value. Setup was slightly fiddly but it works fine.',
            'Solid purchase. Works well, just wish it came with better instructions.',
            'Quality is good. Does exactly what I bought it for.',
            'No real complaints. Works fine and feels well made.',
            'Decent build and it does the job. Happy enough to reorder.',
            'Works great so far. Only minor gripe is the price.',
            'Good tool, performs as expected. Delivery was on time.',
            'Satisfied with the quality. A dependable choice for the money.',
        ],
        3 => [
            'It\'s okay. Does the job but nothing special for the price.',
            'Average product. Works, but I expected a bit more.',
            'Decent enough but the quality feels a little lighter than the photos.',
            'Functional. Took some effort to get it set up correctly.',
            'Works, though shipping took longer than I\'d hoped.',
            'Middle of the road. Gets the job done but I might try another brand next time.',
            'Fine for occasional use. Not sure it would hold up to heavy daily use.',
            'Okay value. Does what it says, just not impressed either way.',
        ],
        2 => [
            'Disappointed. It works but the quality isn\'t what I expected.',
            'Arrived a little scuffed. Still usable but not happy about it.',
            'Feels flimsier than I hoped for the price.',
            'Underwhelming. Expected better based on the description.',
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
        $min = max(1, (int) $this->option('min'));
        $max = max($min, (int) $this->option('max'));
        $letters = range('A', 'Z');

        $totalReviews = 0;
        $done = 0;
        $missing = [];

        foreach ($this->names as $name) {
            $product = $this->findProduct($name);

            if (! $product) {
                $missing[] = $name;
                continue;
            }

            if (! $this->option('keep')) {
                DB::table('reviews')->where('product_id', $product->id)->delete();
            }

            $count = mt_rand($min, $max);
            $buffer = [];
            for ($i = 0; $i < $count; $i++) {
                $star = $this->weightedStar();
                $body = $this->bodies[$star][array_rand($this->bodies[$star])];
                $author = $this->firstNames[array_rand($this->firstNames)].' '.$letters[array_rand($letters)].'.';

                $buffer[] = [
                    'product_id'  => $product->id,
                    'author_name' => $author,
                    'author_role' => 'Verified Buyer',
                    'rating'      => $star,
                    'body'        => $body,
                    'is_published' => true,
                    'featured'    => false,
                    'created_at'  => Carbon::now()->subDays(mt_rand(1, 730))->subHours(mt_rand(0, 23))->subMinutes(mt_rand(0, 59)),
                    'updated_at'  => Carbon::now(),
                ];
            }
            foreach (array_chunk($buffer, 500) as $chunk) {
                DB::table('reviews')->insert($chunk);
            }

            // Sync stored rating + count from this product's actual reviews.
            $agg = DB::table('reviews')->where('product_id', $product->id)
                ->selectRaw('COUNT(*) c, AVG(rating) a')->first();
            $product->rating = round($agg->a, 1);
            $product->reviews_count = (int) $agg->c;
            $product->save();

            $totalReviews += $count;
            $done++;
            $this->line(sprintf('  %-52s +%d reviews  (%.1f★)', mb_strimwidth($product->name, 0, 50, '…'), $count, $product->rating));
        }

        $this->newLine();
        $this->info("Done — {$totalReviews} reviews across {$done} products.");
        if ($missing) {
            $this->warn('Not found ('.count($missing).'): '.implode(' | ', $missing));
        }

        return self::SUCCESS;
    }

    /**
     * Find a product for a given display name, tolerating word-order and
     * middle-token differences. Tries, in order: exact name, whole-string
     * contains, all-words-present, then the distinctive model token on
     * name or SKU.
     */
    private function findProduct(string $name): ?Product
    {
        if ($p = Product::where('name', $name)->first()) {
            return $p;
        }
        if ($p = Product::where('name', 'like', '%'.$name.'%')->first()) {
            return $p;
        }

        $words = array_values(array_filter(preg_split('/\s+/', $name)));

        // all words present (order-independent)
        $p = Product::query()
            ->where(function ($q) use ($words) {
                foreach ($words as $w) {
                    $q->where('name', 'like', '%'.$w.'%');
                }
            })
            ->orderBy('id')
            ->first();
        if ($p) {
            return $p;
        }

        // distinctive model token: longest word containing a digit
        $tokens = array_filter($words, fn ($w) => preg_match('/\d/', $w) && mb_strlen($w) >= 3);
        usort($tokens, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        foreach ($tokens as $tok) {
            $p = Product::where('name', 'like', '%'.$tok.'%')
                ->orWhere('sku', 'like', '%'.$tok.'%')
                ->orderBy('id')->first();
            if ($p) {
                return $p;
            }
        }

        return null;
    }

    /** Weighted toward 4–5 stars so the average stays comfortably above 4.2. */
    private function weightedStar(): int
    {
        // weights for [2,3,4,5] -> avg ≈ 4.4
        $weights = [2 => 1, 3 => 2, 4 => 5, 5 => 12];
        $roll = mt_rand(1, array_sum($weights));
        $acc = 0;
        foreach ($weights as $star => $w) {
            $acc += $w;
            if ($roll <= $acc) {
                return $star;
            }
        }

        return 5;
    }
}
