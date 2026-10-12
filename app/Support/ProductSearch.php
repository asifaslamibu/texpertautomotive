<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Storefront product search. Splits the query into words and requires every word
 * to appear (in the name, brand, SKU or description) — so "zebra ribbon" matches
 * products that mention both — and matches singular/plural forms of each word, so
 * "capacitors" finds "capacitor" and "batteries" finds "battery".
 */
class ProductSearch
{
    /** Columns each word is matched against. */
    private const FIELDS = ['name', 'brand', 'sku', 'description'];

    public static function apply(Builder $query, string $term): Builder
    {
        $tokens = preg_split('/\s+/', trim($term), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($tokens as $token) {
            if (mb_strlen($token) < 2) {
                continue;
            }
            $variants = self::variants($token);

            // AND across words, OR across each word's variants × fields
            $query->where(function ($w) use ($variants) {
                foreach ($variants as $v) {
                    $like = '%'.$v.'%';
                    foreach (self::FIELDS as $field) {
                        $w->orWhere($field, 'like', $like);
                    }
                }
            });
        }

        return $query;
    }

    /** Rank products whose NAME/SKU match the query above description-only matches. */
    public static function orderByRelevance(Builder $query, string $term): Builder
    {
        $first = preg_split('/\s+/', trim($term), -1, PREG_SPLIT_NO_EMPTY)[0] ?? '';
        if ($first === '' || mb_strlen($first) < 2) {
            return $query;
        }

        $whens = [];
        $binds = [];
        foreach (self::variants($first) as $v) {
            $whens[] = 'name LIKE ?';
            $binds[] = '%'.$v.'%';
            $whens[] = 'sku LIKE ?';
            $binds[] = '%'.$v.'%';
        }

        return $query->orderByRaw('CASE WHEN '.implode(' OR ', $whens).' THEN 0 ELSE 1 END', $binds);
    }

    /** A word plus its singular/plural forms, e.g. capacitors → [capacitors, capacitor]. */
    private static function variants(string $token): array
    {
        $t = mb_strtolower($token);
        $set = [$t];

        if (preg_match('/^(.+?)ies$/', $t, $m)) {
            $set[] = $m[1].'y';                       // batteries → battery
        } elseif (preg_match('/(?:ses|xes|zes|ches|shes)$/', $t)) {
            $set[] = mb_substr($t, 0, -2);            // boxes → box, switches → switch
        } elseif (Str::endsWith($t, 's') && mb_strlen($t) > 3) {
            $set[] = mb_substr($t, 0, -1);            // capacitors → capacitor
        }

        if (! Str::endsWith($t, 's')) {
            $set[] = $t.'s';                          // capacitor → capacitors
        }

        return array_values(array_unique($set));
    }
}
