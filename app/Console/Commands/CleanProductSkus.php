<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class CleanProductSkus extends Command
{
    protected $signature = 'products:clean-skus {--dry-run : only show what would change, without saving}';

    protected $description = 'Strip a trailing time (e.g. "4168-08-01 0:00:00" -> "4168-08-01") from product SKUs.';

    public function handle(): int
    {
        // Matches a trailing " H:MM:SS" / " HH:MM:SS" time token at the end of the SKU.
        $pattern = '/\s+\d{1,2}:\d{2}:\d{2}$/';
        $dry = (bool) $this->option('dry-run');

        $updated = 0;
        $examples = [];

        Product::query()
            ->where('sku', 'like', '% %:%:%')   // rough prefilter: has a space and colons
            ->orderBy('id')
            ->chunkById(500, function ($rows) use (&$updated, &$examples, $pattern, $dry) {
                foreach ($rows as $p) {
                    $old = (string) $p->sku;
                    $new = rtrim(preg_replace($pattern, '', $old));
                    if ($new === $old || $new === '') {
                        continue; // nothing to strip, or would empty the SKU — skip
                    }
                    if (count($examples) < 8) {
                        $examples[] = "  {$old}  ->  {$new}";
                    }
                    if (! $dry) {
                        $p->sku = $new;
                        $p->save();
                    }
                    $updated++;
                }
            });

        if ($examples) {
            $this->line(($dry ? 'Would change:' : 'Changed:'));
            foreach ($examples as $e) {
                $this->line($e);
            }
            if ($updated > count($examples)) {
                $this->line('  … and '.($updated - count($examples)).' more.');
            }
        }

        $this->info(($dry ? 'Dry run — ' : 'Done — ')."{$updated} SKU(s) ".($dry ? 'would be cleaned.' : 'cleaned.'));

        return self::SUCCESS;
    }
}
