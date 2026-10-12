<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Updates product SKUs from a spreadsheet, matching each row to a product by
 * its title (product name). Export your Excel sheet as CSV first (UTF-8), then:
 *
 *   php artisan products:update-skus path/to/file.csv --dry   # preview
 *   php artisan products:update-skus path/to/file.csv         # apply
 *
 * The header row is auto-detected: the title column is the one whose header
 * contains "title" or "name"; the SKU column contains "itemcode", "item code"
 * or "sku". Override with --title-col / --sku-col (header name or 0-based index).
 * Matching is exact on a normalised title (trimmed, collapsed spaces, case-
 * insensitive) so it never guesses; unmatched rows are reported, never touched.
 */
class UpdateSkusFromCsv extends Command
{
    protected $signature = 'products:update-skus
        {file : path to the CSV file}
        {--title-col= : header name or 0-based index of the product-title column}
        {--sku-col= : header name or 0-based index of the SKU/itemcode column}
        {--dry : preview matches without writing}';

    protected $description = 'Update product SKUs from a CSV, matching rows to products by title.';

    public function handle(): int
    {
        $path = $this->argument('file');
        if (! is_file($path)) {
            // allow a path relative to the app root
            $alt = base_path($path);
            if (is_file($alt)) {
                $path = $alt;
            } else {
                $this->error("File not found: {$path}");

                return self::FAILURE;
            }
        }

        $fh = fopen($path, 'r');
        if (! $fh) {
            $this->error('Could not open the file.');

            return self::FAILURE;
        }

        // ---- header row ----
        $header = fgetcsv($fh);
        if (! $header) {
            $this->error('The file is empty.');

            return self::FAILURE;
        }
        // strip UTF-8 BOM on the first header cell
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        $header = array_map(fn ($h) => trim((string) $h), $header);

        $titleIdx = $this->resolveColumn($header, $this->option('title-col'), ['title', 'name', 'product']);
        $skuIdx   = $this->resolveColumn($header, $this->option('sku-col'), ['itemcode', 'item code', 'item_code', 'sku', 'code']);

        if ($titleIdx === null || $skuIdx === null) {
            $this->error('Could not detect columns. Headers found: '.implode(' | ', $header));
            $this->line('Use --title-col and --sku-col (header name or 0-based index).');

            return self::FAILURE;
        }
        $this->info("Title column: \"{$header[$titleIdx]}\"  |  SKU column: \"{$header[$skuIdx]}\"");

        // ---- index products by normalised name ----
        $this->line('Indexing products…');
        $byName = [];   // normalised name => [ids]
        Product::select('id', 'name')->orderBy('id')->chunk(1000, function ($rows) use (&$byName) {
            foreach ($rows as $p) {
                $byName[$this->norm($p->name)][] = $p->id;
            }
        });

        $dry = (bool) $this->option('dry');
        $updated = 0;
        $rowsSeen = 0;
        $unmatched = [];
        $ambiguous = 0;

        while (($row = fgetcsv($fh)) !== false) {
            if ($row === [null] || count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue; // blank line
            }
            $rowsSeen++;
            $title = trim((string) ($row[$titleIdx] ?? ''));
            $sku   = trim((string) ($row[$skuIdx] ?? ''));
            if ($title === '' || $sku === '') {
                continue;
            }

            $ids = $byName[$this->norm($title)] ?? null;
            if (! $ids) {
                $unmatched[] = $title;
                continue;
            }
            if (count($ids) > 1) {
                $ambiguous++;
            }

            if (! $dry) {
                DB::table('products')->whereIn('id', $ids)->update(['sku' => $sku]);
            }
            $updated += count($ids);
        }
        fclose($fh);

        $this->newLine();
        $this->info(sprintf('%s%d product SKU(s) %s from %d row(s).',
            $dry ? '[dry] ' : '', $updated, $dry ? 'would update' : 'updated', $rowsSeen));
        if ($ambiguous) {
            $this->warn("{$ambiguous} title(s) matched more than one product (all matching products were set to that SKU).");
        }
        if ($unmatched) {
            $this->warn(count($unmatched).' row(s) had no matching product title.');
            $out = storage_path('app/sku-update-unmatched.txt');
            file_put_contents($out, implode(PHP_EOL, $unmatched));
            $this->line('Unmatched titles written to: '.$out);
            foreach (array_slice($unmatched, 0, 10) as $t) {
                $this->line('  - '.$t);
            }
            if (count($unmatched) > 10) {
                $this->line('  … and '.(count($unmatched) - 10).' more (see file).');
            }
        }

        return self::SUCCESS;
    }

    /** Resolve a column to an index from an explicit option or header keywords. */
    private function resolveColumn(array $header, $opt, array $keywords): ?int
    {
        if ($opt !== null && $opt !== '') {
            if (is_numeric($opt)) {
                $i = (int) $opt;

                return isset($header[$i]) ? $i : null;
            }
            foreach ($header as $i => $h) {
                if (strcasecmp($h, (string) $opt) === 0) {
                    return $i;
                }
            }

            return null;
        }
        foreach ($header as $i => $h) {
            $hl = strtolower($h);
            foreach ($keywords as $k) {
                if (str_contains($hl, $k)) {
                    return $i;
                }
            }
        }

        return null;
    }

    /** Normalise a title for reliable matching. */
    private function norm(string $s): string
    {
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $s = preg_replace('/\s+/u', ' ', $s);        // collapse whitespace

        return mb_strtolower(trim($s));
    }
}
