<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Review;
use App\Support\ReviewStats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductReviewController extends Controller
{
    /** A shopper's own review. Held back until someone on the team has read it. */
    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'author_name' => ['required', 'string', 'min:2', 'max:120'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['required', 'string', 'min:10', 'max:1000'],
        ], [
            'author_name.required' => 'Please tell us your name.',
            'rating.required' => 'Please pick a star rating.',
            'body.required' => 'Please write your review.',
            'body.min' => 'Please write a little more — at least 10 characters.',
        ]);

        Review::create($data + [
            'product_id' => $product->id,
            'author_role' => 'Verified Buyer',
            'is_published' => false,
            'featured' => false,
        ]);

        return redirect()->route('products.show', $product)
            ->with('status', 'Thanks for your review! It will appear once our team has checked it.')
            ->withFragment('reviews');
    }

    /**
     * "Was this review helpful?" — one vote per visitor per review, and clicking
     * the same thumb again takes the vote back.
     */
    public function helpful(Request $request, Review $review)
    {
        $helpful = $request->validate([
            'helpful' => ['required', 'boolean'],
        ])['helpful'];

        $key = hash('sha256', $request->ip().'|'.$request->userAgent().'|'.config('app.key'));

        DB::transaction(function () use ($review, $helpful, $key) {
            $existing = DB::table('review_votes')
                ->where('review_id', $review->id)->where('voter_key', $key)->first();

            if ($existing && (bool) $existing->helpful === $helpful) {
                // same thumb again: undo the vote
                DB::table('review_votes')->where('id', $existing->id)->delete();
                $review->decrement($helpful ? 'helpful_yes' : 'helpful_no');

                return;
            }

            if ($existing) {
                // switched sides
                DB::table('review_votes')->where('id', $existing->id)
                    ->update(['helpful' => $helpful, 'updated_at' => now()]);
                $review->decrement($helpful ? 'helpful_no' : 'helpful_yes');
                $review->increment($helpful ? 'helpful_yes' : 'helpful_no');

                return;
            }

            DB::table('review_votes')->insert([
                'review_id' => $review->id,
                'voter_key' => $key,
                'helpful' => $helpful,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $review->increment($helpful ? 'helpful_yes' : 'helpful_no');
        });

        $review->refresh();

        // counters never read below zero, whatever an old row left behind
        return response()->json([
            'yes' => max(0, (int) $review->helpful_yes),
            'no' => max(0, (int) $review->helpful_no),
            'voted' => DB::table('review_votes')
                ->where('review_id', $review->id)->where('voter_key', $key)->value('helpful'),
        ]);
    }
}
