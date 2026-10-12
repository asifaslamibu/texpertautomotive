<?php

namespace App\Support;

use App\Models\Product;
use RuntimeException;

/**
 * Fix product SKUs from a sheet, matching each row to a product by its title.
 *
 * Titles are compared with case and spacing ignored — nothing else is guessed,
 * so a row that does not name one of our products is reported, never applied.
 * Runs as a preview first: the same read, without writing anything.
 */
class SkuSync
{
    private const TITLE_HEADERS = ['product_title', 'product_title_seo', 'title', 'name', 'product_name', 'product'];

    private const SKU_HEADERS = ['sku', 'item_id', 'itemid', 'item', 'item_code', 'itemcode', 'item_number'];

    /** Tabs never read, whatever their columns (lowercased names). */
    private const SKIP_SHEETS = ['all inventory'];

    /**
     * @return array{
     *     dry_run: bool, sheets: array, rows: int, changed: int, unchanged: int,
     *     unmatched: int, conflicts: int, samples: array, unmatched_titles: array
     * }
     */
    public static function fromFile(string $path, string $ext, bool $dryRun = true): array
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        [$rows, $sheets, $conflicts] = $ext === 'xlsx' ? self::readXlsx($path) : self::readCsv($path);
        if (! $sheets) {
            throw new RuntimeException('No sheet with both a product title column and an SKU / Item ID column was found.');
        }

        // title (normalised) => [product id => current sku]
        $byTitle = [];
        Product::query()->select('id', 'name', 'sku')->chunkById(2000, function ($chunk) use (&$byTitle) {
            foreach ($chunk as $p) {
                if (($key = self::key((string) $p->name)) !== '') {
                    $byTitle[$key][$p->id] = (string) $p->sku;
                }
            }
        });

        $changes = [];          // product id => new sku
        $samples = [];
        $unchanged = 0;
        $unmatchedTitles = [];

        foreach ($rows as $key => $row) {
            if (! isset($byTitle[$key])) {
                $unmatchedTitles[] = $row['title'];
                continue;
            }

            foreach ($byTitle[$key] as $id => $currentSku) {
                if (self::key($currentSku) === self::key($row['sku'])) {
                    $unchanged++;
                    continue;
                }

                $changes[$id] = $row['sku'];
                if (count($samples) < 50) {
                    $samples[] = ['title' => $row['title'], 'from' => $currentSku, 'to' => $row['sku']];
                }
            }
        }

        if (! $dryRun && $changes) {
            foreach (array_chunk($changes, 500, true) as $chunk) {
                foreach ($chunk as $id => $sku) {
                    Product::where('id', $id)->update(['sku' => $sku, 'updated_at' => now()]);
                }
            }
        }

        return [
            'dry_run' => $dryRun,
            'sheets' => $sheets,
            'rows' => count($rows),
            'changed' => count($changes),
            'unchanged' => $unchanged,
            'unmatched' => count($unmatchedTitles),
            'conflicts' => $conflicts,
            'samples' => $samples,
            'unmatched_titles' => array_slice($unmatchedTitles, 0, 100),
        ];
    }

    /** @return array{0: array<string, array{title: string, sku: string}>, 1: array, 2: int} */
    private static function readXlsx(string $path): array
    {
        $reader = new XlsxReader($path);
        $rows = [];
        $sheets = [];
        $conflicts = 0;

        foreach ($reader->sheetNames() as $name) {
            if (in_array(mb_strtolower(trim($name)), self::SKIP_SHEETS, true)) {
                continue;
            }

            $cols = null;
            $count = 0;
            foreach ($reader->rows($name) as $row) {
                if ($cols === null) {
                    $cols = self::detectColumns($row);
                    if (! $cols) {
                        continue 2;
                    }
                    continue;
                }
                $count += self::collect($row, $cols, $rows, $conflicts);
            }
            if ($cols) {
                $sheets[] = ['name' => $name, 'rows' => $count];
            }
        }

        return [$rows, $sheets, $conflicts];
    }

    private static function readCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        $header = fgetcsv($handle);
        if ($header && isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }
        $cols = $header ? self::detectColumns($header) : null;
        if (! $cols) {
            fclose($handle);

            return [[], [], 0];
        }

        $rows = [];
        $count = 0;
        $conflicts = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $count += self::collect($row, $cols, $rows, $conflicts);
        }
        fclose($handle);

        return [$rows, [['name' => 'CSV', 'rows' => $count]], $conflicts];
    }

    private static function detectColumns(array $header): ?array
    {
        $title = $sku = null;
        foreach ($header as $i => $name) {
            $norm = trim(preg_replace('/[^a-z0-9]+/', '_', strtolower((string) $name)), '_');
            if ($title === null && in_array($norm, self::TITLE_HEADERS, true)) {
                $title = $i;
            } elseif ($sku === null && in_array($norm, self::SKU_HEADERS, true)) {
                $sku = $i;
            }
        }

        return $title !== null && $sku !== null ? ['title' => $title, 'sku' => $sku] : null;
    }

    /** Add a row; returns 1 when it carried both a title and an SKU. */
    private static function collect(array $row, array $cols, array &$rows, int &$conflicts): int
    {
        $title = trim((string) ($row[$cols['title']] ?? ''));
        $sku = self::cleanSku((string) ($row[$cols['sku']] ?? ''));
        if ($title === '' || $sku === '') {
            return 0;
        }

        $key = self::key($title);

        // the same title twice with different SKUs: trust neither, drop the row
        if (isset($rows[$key]) && self::key($rows[$key]['sku']) !== self::key($sku)) {
            unset($rows[$key]);
            $conflicts++;

            return 1;
        }

        $rows[$key] = ['title' => $title, 'sku' => $sku];

        return 1;
    }

    /**
     * Excel keeps numeric SKUs as numbers, so they arrive as "1861.0" or, once
     * they are long enough, in scientific notation: "1.3102404E7" → "13102404".
     */
    private static function cleanSku(string $sku): string
    {
        $sku = trim($sku);
        if ($sku === '') {
            return '';
        }

        if (preg_match('/^\d+(\.\d+)?[eE][+-]?\d+$/', $sku)) {
            return rtrim(rtrim(sprintf('%.10F', (float) $sku), '0'), '.');
        }

        return preg_match('/^\d+\.0+$/', $sku) ? preg_replace('/\.0+$/', '', $sku) : $sku;
    }

    /** Matching key: case and spacing never decide whether two titles are the same. */
    private static function key(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $value)));
    }
}
