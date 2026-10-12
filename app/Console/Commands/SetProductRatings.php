<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SetProductRatings extends Command
{
    protected $signature = 'products:set-ratings
        {--low=3.8 : rating for the low bucket}
        {--percent=10 : percent of products in the low bucket}
        {--min=4.3 : minimum rating for the rest (must be above 4.2)}
        {--max=5.0 : maximum rating for the rest}
        {--wipe-reviews : delete individual review rows so the product-detail page also shows this rating}';

    protected $description = 'Set a controlled rating spread: X% of products at a low rating (default 3.8) and the rest above 4.2.';

    public function handle(): int
    {
        $low     = (float) $this->option('low');
        $percent = max(0.0, min(100.0, (float) $this->option('percent')));
        $min     = (float) $this->option('min');
        $max     = (float) $this->option('max');

        $total = DB::table('products')->count();
        if ($total === 0) {
            $this->warn('No products found.');
            return self::SUCCESS;
        }

        $lowCount = (int) floor($total * $percent / 100);
        $range = sprintf('ROUND(%.2f + (RAND() * (%.2f - %.2f)), 1)', $min, $max, $min);

        // 1) Everyone first gets a random rating in [min, max] (all above 4.2).
        $this->info("Setting all {$total} products to a random rating {$min}–{$max}…");
        DB::table('products')->update(['rating' => DB::raw($range)]);

        // 2) Make sure the stars actually render: give a realistic review count where none exists.
        DB::table('products')->where(function ($q) {
            $q->whereNull('reviews_count')->orWhere('reviews_count', 0);
        })->update(['reviews_count' => DB::raw('FLOOR(8 + (RAND() * 112))')]); // 8–119

        // 3) A random slice gets the low rating.
        $ids = DB::table('products')->inRandomOrder()->limit($lowCount)->pluck('id');
        foreach (array_chunk($ids->all(), 2000) as $chunk) {
            DB::table('products')->whereIn('id', $chunk)->update(['rating' => $low]);
        }

        // 4) Optionally wipe per-review rows so the detail page falls back to this column value.
        if ($this->option('wipe-reviews')) {
            $deleted = DB::table('reviews')->delete();
            $this->warn("Wiped {$deleted} individual review rows (detail page now uses the stored rating).");
        }

        $this->info(sprintf(
            'Done — %d products: %d at %.1f★, %d at %.1f–%.1f★.',
            $total, $lowCount, $low, $total - $lowCount, $min, $max
        ));

        return self::SUCCESS;
    }
}
