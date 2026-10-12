<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Turns the free-text `spec_compatible_printers` column ("ZT411, ZT410, ZE500-4")
 * into a browsable printer index, and answers "which products fit printer X?".
 *
 * Two wrinkles the raw data forces us to handle:
 *  - Series tokens. A product may list "ZT400 Series" rather than each of ZT410/ZT411/
 *    ZT420/ZT421, so a lookup for ZT411 must still find it (and vice-versa).
 *  - Prose tokens. A few rows carry a whole phrase ("Zebra's ZT411 On-Metal RFID
 *    Printer"), so the model is matched on a word boundary inside the token too.
 */
class PrinterCompatibility
{
    /** Product-type buckets used for the filter chips on the results page. */
    private const TYPE_BUCKETS = [
        'Labels'      => ['Label', 'Tag'],
        'Ribbons'     => ['Ribbon'],
        'Wristbands'  => ['Wristband'],
        'Receipts'    => ['Receipt'],
        'Parts'       => ['Printer Part', 'Printer Part (Restricted)', 'Platen Roller'],
        'Batteries'   => ['Battery'],
        'Accessories' => ['Accessory'],
    ];

    /** Every printer that appears in the catalogue, de-duplicated and counted. */
    public static function models(): Collection
    {
        $seen = [];

        foreach (self::sourceRows() as $row) {
            foreach (self::tokens($row->spec_compatible_printers) as $token) {
                $key = self::normalize($token);
                if (! isset($seen[$key])) {
                    $seen[$key] = ['label' => $token, 'slug' => self::slug($token), 'count' => 0];
                }
                $seen[$key]['count']++;
            }
        }

        return collect($seen)
            ->values()
            ->map(fn (array $m) => $m + ['family' => self::family($m['label'])])
            ->sortBy([['family', 'asc'], ['label', 'asc']])
            ->values();
    }

    /** Models grouped into the families the landing page renders as sections. */
    public static function families(): Collection
    {
        return self::models()->groupBy('family');
    }

    /** Resolve a URL slug ("zt411") back to its model entry, or null. */
    public static function findBySlug(string $slug): ?array
    {
        return self::models()->firstWhere('slug', $slug);
    }

    /** Every published product that fits the given printer model. */
    public static function productsFor(string $model): Collection
    {
        return self::sourceRows()
            ->filter(fn (Product $p) => self::fits($p->spec_compatible_printers, $model))
            ->values();
    }

    /** Bucket a product into a filter chip ("Labels", "Parts", …). */
    public static function bucket(Product $product): string
    {
        foreach (self::TYPE_BUCKETS as $bucket => $types) {
            if (in_array((string) $product->spec_product_type, $types, true)) {
                return $bucket;
            }
        }

        return 'Other';
    }

    /** Does this product's compatibility cell cover $model? */
    public static function fits(?string $cell, string $model): bool
    {
        $model = self::normalize($model);

        foreach (self::tokens((string) $cell) as $token) {
            $token = self::normalize($token);

            if ($token === $model) {
                return true;
            }

            // "Zebra's ZT411 On-Metal RFID Printer" covers ZT411
            if (self::containsWord($token, $model) || self::containsWord($model, $token)) {
                return true;
            }

            // "ZT400 Series" covers ZT411 — and picking "ZT400 Series" lists the ZT411 stock
            if (self::seriesCovers($token, $model) || self::seriesCovers($model, $token)) {
                return true;
            }
        }

        return false;
    }

    /* ---------------- internals ---------------- */

    /** Products that declare any compatibility at all — the whole working set (~230 rows). */
    private static function sourceRows(): Collection
    {
        static $rows = null;

        return $rows ??= Product::query()
            ->where('published', 1)
            ->whereNotNull('spec_compatible_printers')
            ->where('spec_compatible_printers', '<>', '')
            ->with('category')
            ->get();
    }

    /** Split a compatibility cell into individual printer tokens. */
    private static function tokens(string $cell): array
    {
        return collect(preg_split('/[,;\/]+/', $cell))
            ->map(fn ($t) => trim($t))
            ->filter()
            ->values()
            ->all();
    }

    private static function normalize(string $s): string
    {
        return strtoupper(preg_replace('/\s+/', ' ', trim($s)));
    }

    /** Str::slug() silently drops "+", which would collide ZQ610+ with ZQ610. */
    private static function slug(string $label): string
    {
        return Str::slug(str_replace('+', ' plus ', $label));
    }

    /** Is $needle present in $haystack as a standalone word? */
    private static function containsWord(string $haystack, string $needle): bool
    {
        if ($haystack === $needle || $needle === '') {
            return false;
        }

        return (bool) preg_match('/(?<![A-Z0-9+-])'.preg_quote($needle, '/').'(?![A-Z0-9+-])/', $haystack);
    }

    /**
     * Does the series token $series cover the concrete model $model?
     * "ZT400 SERIES" → prefixes [ZT400, ZT4]; "ZD SERIES" → [ZD].
     */
    private static function seriesCovers(string $series, string $model): bool
    {
        foreach (self::seriesPrefixes($series) as $prefix) {
            if ($prefix !== $model && str_starts_with($model, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /** @return string[] */
    private static function seriesPrefixes(string $token): array
    {
        $t = str_replace('-', ' ', $token);
        if (! preg_match('/\bSERIES\b/', $t)) {
            return [];
        }

        $stem = trim(preg_replace('/\bSERIES\b/', '', $t));
        $stem = rtrim($stem, '+ ');
        $stem = str_replace(' ', '', $stem);
        if ($stem === '') {
            return [];
        }

        $prefixes = [$stem];

        // A round "N00" series also covers the whole decade: ZT400 → ZT4xx
        if (preg_match('/^([A-Z]+)(\d)00$/', $stem, $m)) {
            $prefixes[] = $m[1].$m[2];
        }

        return $prefixes;
    }

    /** Coarse family used to group the model list on the landing page. */
    private static function family(string $model): string
    {
        $m = self::normalize($model);

        return match (true) {
            (bool) preg_match('/(^HC100|[\s-]HC\b|\bHC\b)/', $m)          => 'Healthcare',
            (bool) preg_match('/^(ZQ|QLN|MZ|IMZ|RW|P4T)/', $m)            => 'Mobile printers',
            (bool) preg_match('/^(ZT|ZE|RXI|XI|105SL|ZM|Z4M|220XI)/', $m) => 'Industrial printers',
            (bool) preg_match('/^(ZD|GK|GX|G-|G\b|TLP|EPL|ZPL)/', $m)     => 'Desktop printers',
            default                                                       => 'Other',
        };
    }
}
