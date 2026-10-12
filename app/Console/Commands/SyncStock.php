<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Support\StockSync;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

/**
 * Pulls the saved Google Sheet and syncs product stock.
 *
 * The scheduler runs this hourly; it only syncs when auto-sync is on and the
 * interval chosen on Admin → Stock Update has passed. --force syncs regardless.
 *
 *   php artisan stock:sync --force
 */
class SyncStock extends Command
{
    protected $signature = 'stock:sync {--force : sync now even if auto-sync is off or not due}';

    protected $description = 'Sync product stock from the saved Google Sheet.';

    public function handle(): int
    {
        $url = Setting::get(StockSync::URL_KEY);
        if (! $url) {
            $this->info('No Google Sheet link saved.');

            return self::SUCCESS;
        }

        if (! $this->option('force')) {
            if (! Setting::get(StockSync::AUTO_KEY)) {
                $this->info('Auto-sync is off.');

                return self::SUCCESS;
            }

            $last = StockSync::lastResult();
            $hours = max(1, (int) Setting::get(StockSync::HOURS_KEY, 6));
            // a few minutes' slack so an hourly scheduler doesn't skip a whole slot
            if ($last && Carbon::parse($last['at'])->addHours($hours)->subMinutes(5)->isFuture()) {
                $this->info('Not due yet.');

                return self::SUCCESS;
            }
        }

        try {
            $r = StockSync::fromGoogleSheet($url, $this->option('force') ? 'manual' : 'auto');
        } catch (Throwable $e) {
            $this->error($e->getMessage());
            report($e);

            return self::FAILURE;
        }

        $this->info("Synced {$r['rows']} SKUs: {$r['in_stock']} in stock, {$r['out_of_stock']} out of stock, {$r['unmatched_count']} not found.");

        return self::SUCCESS;
    }
}
