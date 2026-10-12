<?php

namespace App\Support;

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Bulk stock sync from the supplier master sheet (uploaded file or Google Sheet).
 *
 * Every sheet (or the CSV) whose header row has an SKU column and a "Stock"
 * column is read. Rows are matched to products by SKU; a positive stock sets
 * that quantity and marks the product in stock, while 0 or a blank cell marks
 * it out of stock. Products that are not in the file are left untouched.
 */
class StockSync
{
    public const URL_KEY = 'stock_sheet_url';

    public const AUTO_KEY = 'stock_sync_auto';

    public const HOURS_KEY = 'stock_sync_hours';

    public const LAST_KEY = 'stock_sync_last';

    private const SKU_HEADERS = ['sku', 'item_id', 'itemid', 'item', 'item_code', 'itemcode', 'item_number'];

    private const STOCK_HEADERS = ['stock', 'stock_qty', 'qty', 'quantity'];

    private const TITLE_HEADERS = ['product_title', 'product_title_seo', 'title', 'name', 'product_name', 'product'];

    /** Tabs never synced, whatever their columns (lowercased names). */
    private const SKIP_SHEETS = ['all inventory'];

    /** Download the Google Sheet (all tabs, as .xlsx) and sync from it. */
    public static function fromGoogleSheet(string $url, string $trigger): array
    {
        $response = Http::timeout(120)->get(self::exportUrl($url));

        // a private sheet answers with Google's HTML sign-in page instead of a zip
        if (! $response->successful() || ! str_starts_with($response->body(), 'PK')) {
            throw new RuntimeException('Could not download the Google Sheet. Make sure it is shared as "Anyone with the link – Viewer".');
        }

        $path = 'stock-imports/'.now()->format('Ymd_His').'_google-sheet.xlsx';
        Storage::put($path, $response->body());

        return self::fromFile(Storage::path($path), 'xlsx', 'Google Sheet', $trigger);
    }

    /** Any Google Sheets link (edit/view/share) → its .xlsx export URL. */
    public static function exportUrl(string $url): string
    {
        if (! preg_match('~docs\.google\.com/spreadsheets/d/([a-zA-Z0-9_-]+)~', $url, $m)) {
            throw new RuntimeException('That does not look like a Google Sheets link.');
        }

        return "https://docs.google.com/spreadsheets/d/{$m[1]}/export?format=xlsx";
    }

    /** Sync from a local .xlsx / .csv file and remember the result. */
    public static function fromFile(string $path, string $ext, string $source, string $trigger): array
    {
        if (! Schema::hasColumn('products', 'stock_qty')) {
            throw new RuntimeException('The stock_qty column is missing — run the add_stock_qty_to_products_table migration first.');
        }

        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        [$stock, $sheets, $skipped] = $ext === 'xlsx' ? self::readXlsx($path) : self::readCsv($path);
        if (! $sheets) {
            throw new RuntimeException('No sheet with a "Stock" column and a product title (or Item ID) column was found.');
        }

        // title (normalised) => [product ids], and the same for SKUs as a fallback
        $byTitle = [];
        $bySku = [];
        Product::query()->select('id', 'sku', 'name')
            ->chunkById(2000, function ($chunk) use (&$byTitle, &$bySku) {
                foreach ($chunk as $p) {
                    if (($key = self::key((string) $p->name)) !== '') {
                        $byTitle[$key][] = $p->id;
                    }
                    if (($key = self::key((string) $p->sku)) !== '') {
                        $bySku[$key][] = $p->id;
                    }
                }
            });

        $byQty = [];      // qty => [product ids] (qty 0 = out of stock)
        $unmatched = [];
        $matchedByTitle = 0;
        $matchedBySku = 0;

        foreach ($stock as $key => $row) {
            $ids = $byTitle[$key] ?? null;
            if ($ids) {
                $matchedByTitle++;
            } else {
                // the sheet's title is not one of ours — try its SKU
                $skuKey = self::key($row['sku'] ?? '');
                $ids = $skuKey !== '' ? ($bySku[$skuKey] ?? null) : null;
                if ($ids) {
                    $matchedBySku++;
                }
            }

            if (! $ids) {
                $unmatched[] = $row['title'] ?: $row['sku'];
                continue;
            }

            foreach ($ids as $id) {
                $byQty[$row['qty']][] = $id;
            }
        }

        $inStock = 0;
        $outOfStock = 0;
        DB::transaction(function () use ($byQty, &$inStock, &$outOfStock) {
            foreach ($byQty as $qty => $ids) {
                $ids = array_values(array_unique($ids));
                foreach (array_chunk($ids, 1000) as $chunk) {
                    Product::whereIn('id', $chunk)->update([
                        'stock_qty' => $qty,
                        'in_stock' => $qty > 0,
                        'updated_at' => now(),
                    ]);
                }
                $qty > 0 ? $inStock += count($ids) : $outOfStock += count($ids);
            }
        });

        $result = [
            'source' => $source,
            'trigger' => $trigger,
            'at' => now()->toIso8601String(),
            'sheets' => $sheets,
            'rows' => count($stock),
            'skipped' => $skipped,
            'matched_by_title' => $matchedByTitle,
            'matched_by_sku' => $matchedBySku,
            'in_stock' => $inStock,
            'out_of_stock' => $outOfStock,
            'unmatched_count' => count($unmatched),
            'unmatched' => array_slice($unmatched, 0, 200),
        ];
        Setting::put(self::LAST_KEY, json_encode($result));

        return $result;
    }

    public static function lastResult(): ?array
    {
        return json_decode((string) Setting::get(self::LAST_KEY), true) ?: null;
    }

    private static function readXlsx(string $path): array
    {
        $reader = new XlsxReader($path);
        $stock = [];
        $sheets = [];
        $skipped = 0;

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
                        continue 2; // not a stock sheet
                    }
                    continue;
                }
                $count += self::collect($row, $cols, $stock, $skipped);
            }
            if ($cols) {
                $sheets[] = ['name' => $name, 'rows' => $count];
            }
        }

        return [$stock, $sheets, $skipped];
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

        $stock = [];
        $count = 0;
        $skipped = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $count += self::collect($row, $cols, $stock, $skipped);
        }
        fclose($handle);

        return [$stock, [['name' => 'CSV', 'rows' => $count]], $skipped];
    }

    /**
     * Where the title, SKU and stock live in this sheet. A stock column plus at
     * least one way of naming the product is enough.
     */
    private static function detectColumns(array $header): ?array
    {
        $sku = $title = $qty = null;
        foreach ($header as $i => $name) {
            $norm = trim(preg_replace('/[^a-z0-9]+/', '_', strtolower((string) $name)), '_');
            if ($title === null && in_array($norm, self::TITLE_HEADERS, true)) {
                $title = $i;
            } elseif ($sku === null && in_array($norm, self::SKU_HEADERS, true)) {
                $sku = $i;
            } elseif ($qty === null && in_array($norm, self::STOCK_HEADERS, true)) {
                $qty = $i;
            }
        }

        return $qty !== null && ($title !== null || $sku !== null)
            ? ['sku' => $sku, 'title' => $title, 'qty' => $qty]
            : null;
    }

    /**
     * Add one row to the stock map.
     *
     * @return int 1 when the row counted, 0 when it had no SKU or no usable stock
     */
    private static function collect(array $row, array $cols, array &$stock, int &$skipped): int
    {
        $sku = $cols['sku'] !== null ? self::cleanSku((string) ($row[$cols['sku']] ?? '')) : '';
        $title = $cols['title'] !== null ? trim((string) ($row[$cols['title']] ?? '')) : '';

        // the title is what we match on; the SKU is only a fallback
        $key = $title !== '' ? self::key($title) : self::key($sku);
        if ($key === '') {
            return 0;
        }

        $raw = str_replace(',', '', trim((string) ($row[$cols['qty']] ?? '')));

        // A spreadsheet error (#N/A from a VLOOKUP that found nothing, #REF!, …)
        // means "we don't know", not "none left" — leave that product alone.
        if (self::isNoData($raw)) {
            $skipped++;

            return 0;
        }

        // blank, 0, negative or non-numeric => out of stock
        $qty = is_numeric($raw) ? max(0, (int) floor((float) $raw)) : 0;

        // the same product on several supplier sheets: keep the highest quantity
        if (! isset($stock[$key]) || $qty > $stock[$key]['qty']) {
            $stock[$key] = ['sku' => $sku, 'title' => $title, 'qty' => $qty];
        }

        return 1;
    }

    /** Spreadsheet error values and "n/a" text — a cell with no answer in it. */
    private static function isNoData(string $raw): bool
    {
        if ($raw === '') {
            return false;   // genuinely blank means out of stock
        }

        return str_starts_with($raw, '#') || in_array(strtolower($raw), ['n/a', 'na', 'null', '-', '--'], true);
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
