<?php

namespace App\Console\Commands;

use App\Support\ReviewStats;
use Illuminate\Console\Command;

class SyncReviewCounts extends Command
{
    protected $signature = 'reviews:sync-counts';

    protected $description = 'Recalculate every product\'s rating and review count from its published reviews.';

    public function handle(): int
    {
        $this->info('Recalculating ratings and review counts…');
        ReviewStats::sync();
        $this->info('Done.');

        return self::SUCCESS;
    }
}
