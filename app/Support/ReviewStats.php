<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Keeps products.rating / products.reviews_count in step with the reviews that
 * are actually published. Without this, deleting reviews leaves a product still
 * claiming "138 reviews" above a section that shows none.
 */
class ReviewStats
{
    /**
     * Recalculate the stored rating and review count.
     *
     * @param  array<int>|null  $productIds  null recalculates every product
     * @return int products touched
     */
    public static function sync(?array $productIds = null): int
    {
        $ids = $productIds === null ? null : array_values(array_unique(array_filter($productIds)));
        if ($ids === []) {
            return 0;
        }

        // published reviews per product
        $agg = DB::table('reviews')
            ->select('product_id', DB::raw('COUNT(*) as c'), DB::raw('AVG(rating) as a'))
            ->where('is_published', true)
            ->whereNotNull('product_id')
            ->when($ids, fn ($q) => $q->whereIn('product_id', $ids))
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $touched = 0;

        // products that still have reviews
        foreach ($agg as $productId => $row) {
            $touched += DB::table('products')->where('id', $productId)->update([
                'rating' => round($row->a, 1),
                'reviews_count' => $row->c,
            ]);
        }

        // products whose reviews are all gone: clear the stale numbers
        DB::table('products')
            ->when($ids, fn ($q) => $q->whereIn('id', $ids))
            ->whereNotIn('id', $agg->keys()->all() ?: [0])
            ->where(fn ($q) => $q->where('reviews_count', '>', 0)->orWhere('rating', '>', 0))
            ->update(['rating' => 0, 'reviews_count' => 0]);

        return $touched;
    }
}
