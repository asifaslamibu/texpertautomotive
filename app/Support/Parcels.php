<?php

namespace App\Support;

use App\Models\Product;

/**
 * Splits a cart into the boxes it would really ship in, so carriers quote the
 * right size and weight instead of one tiny box holding everything.
 *
 *  - a heavy or long item (an air handler, a recovery machine) ships in its own
 *    box, sized from its own dimensions;
 *  - small items are packed together, several boxes if needed, each kept under
 *    MAX_BOX_WEIGHT so no box goes past what parcel carriers take.
 *
 * It is an estimate — good enough to get a realistic rate, not a packing plan.
 */
class Parcels
{
    /** An item this heavy (lbs) ships on its own. */
    public const OWN_BOX_WEIGHT = 15.0;

    /** An item this long (inches) ships on its own. */
    public const OWN_BOX_LENGTH = 18.0;

    /** Small items are grouped into boxes of at most this weight (lbs). */
    public const MAX_BOX_WEIGHT = 40.0;

    /** Size assumed for a small item with no dimensions recorded (in). */
    private const DEFAULT_SMALL = [8.0, 6.0, 4.0];

    /** Size assumed for a heavy item with no dimensions recorded (in). */
    private const DEFAULT_LARGE = [18.0, 14.0, 12.0];

    /** Room for packing material around each item, per side (in). */
    private const PADDING = 1.0;

    /** Keep the number of carrier calls sane for very large carts. */
    private const MAX_PARCELS = 12;

    /**
     * @param  iterable<array{product: Product, qty: int}>  $lines
     * @return array<int, array{length: float, width: float, height: float, weight: float}>
     */
    public static function forLines(iterable $lines): array
    {
        $own = [];
        $small = [];

        foreach ($lines as $line) {
            $product = $line['product'];
            $weight = self::weightOf($product);
            $dims = self::dimensionsOf($product);

            for ($i = 0; $i < (int) $line['qty']; $i++) {
                $big = $weight >= self::OWN_BOX_WEIGHT || ($dims && $dims[0] >= self::OWN_BOX_LENGTH);
                if ($big) {
                    [$l, $w, $h] = $dims ?? self::DEFAULT_LARGE;
                    $own[] = self::box($l + 2 * self::PADDING, $w + 2 * self::PADDING, $h + 2 * self::PADDING, $weight);
                } else {
                    $small[] = ['weight' => $weight, 'dims' => $dims ?? self::DEFAULT_SMALL];
                }
            }
        }

        $parcels = array_merge($own, self::packSmall($small));

        // A huge cart: fold the lightest extra boxes into the last one rather than
        // making dozens of carrier calls. Rare, and the rate stays in the right range.
        while (count($parcels) > self::MAX_PARCELS) {
            usort($parcels, fn ($a, $b) => $b['weight'] <=> $a['weight']);
            $extra = array_pop($parcels);
            $last = array_pop($parcels);
            $parcels[] = self::box(
                max($last['length'], $extra['length']),
                max($last['width'], $extra['width']),
                $last['height'] + $extra['height'],
                $last['weight'] + $extra['weight'],
            );
        }

        return array_values($parcels);
    }

    /** Pack small items into as few boxes as the weight limit allows. */
    private static function packSmall(array $items): array
    {
        if (! $items) {
            return [];
        }

        // heaviest first so the boxes fill evenly
        usort($items, fn ($a, $b) => $b['weight'] <=> $a['weight']);

        $groups = [];
        foreach ($items as $item) {
            foreach ($groups as &$g) {
                if ($g['weight'] + $item['weight'] <= self::MAX_BOX_WEIGHT) {
                    $g['weight'] += $item['weight'];
                    $g['items'][] = $item;
                    continue 2;
                }
            }
            unset($g);
            $groups[] = ['weight' => $item['weight'], 'items' => [$item]];
        }

        return array_map(function ($g) {
            // box volume = the items' volume plus room for packing, and never
            // shorter than the longest item in it
            $volume = 0.0;
            $longest = 0.0;
            foreach ($g['items'] as $it) {
                [$l, $w, $h] = $it['dims'];
                $volume += ($l + self::PADDING) * ($w + self::PADDING) * ($h + self::PADDING);
                $longest = max($longest, $l + 2 * self::PADDING);
            }
            $volume *= 1.2;

            $side = max(4.0, pow($volume, 1 / 3));
            $length = max($longest, $side);
            $width = $side;
            $height = max(4.0, $volume / ($length * $width));

            return self::box($length, $width, $height, $g['weight']);
        }, $groups);
    }

    private static function box(float $l, float $w, float $h, float $weight): array
    {
        $sides = [ceil($l), ceil($w), ceil($h)];
        rsort($sides);

        return [
            'length' => (float) $sides[0],
            'width' => (float) $sides[1],
            'height' => (float) $sides[2],
            'weight' => round(max(0.5, $weight), 2),
        ];
    }

    /** Weight in lbs ("2.3 lbs", "40", "1.36 Lbs"); the cart default when missing. */
    public static function weightOf(Product $product): float
    {
        preg_match('/([\d.]+)/', (string) ($product->weight_lbs ?? ''), $m);

        return isset($m[1]) && (float) $m[1] > 0 ? (float) $m[1] : Cart::DEFAULT_ITEM_WEIGHT;
    }

    /**
     * Dimensions as [longest, middle, shortest] inches from text like
     * `24.75" x 19.75" x 57.0"` or "15.00 x 0.30 x 0.08"; null when not usable.
     */
    public static function dimensionsOf(Product $product): ?array
    {
        preg_match_all('/(\d+(?:\.\d+)?)/', (string) ($product->dimensions ?? ''), $m);
        $nums = array_map('floatval', $m[1] ?? []);
        if (count($nums) < 3) {
            return null;
        }

        $dims = array_slice($nums, 0, 3);
        rsort($dims);
        if ($dims[2] <= 0) {
            $dims[2] = 0.5;
        }

        return $dims[0] > 0 ? $dims : null;
    }
}
