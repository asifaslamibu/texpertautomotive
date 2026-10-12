<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\SortsRecords;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use App\Support\ReviewStats;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    use SortsRecords;

    public function index(Request $request)
    {
        $query = $this->filtered($request);

        $this->applySort($query, $request, ['rating', 'is_published', 'created_at', 'author_name'], 'created_at', 'desc');

        $reviews = $query->paginate(15)->withQueryString();

        return view('admin.reviews.index', compact('reviews'));
    }

    public function bulkDestroy(Request $request)
    {
        $ids = (array) $request->input('ids', []);
        $productIds = Review::whereIn('id', $ids)->pluck('product_id')->all();
        $n = Review::whereIn('id', $ids)->delete();
        ReviewStats::sync($productIds);

        return back()->with('status', $n.' review(s) deleted.');
    }

    /**
     * Delete every review the current search matches — the same rows the list is
     * showing, not just the page in front of you. Seeded wording tends to repeat
     * across hundreds of products, and ticking 15 at a time is no way to remove it.
     */
    public function destroyMatching(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'min:4', 'max:200'],
            'rating' => ['nullable', 'in:1,2,3,4,5,low'],
            'filter' => ['nullable', 'in:published,unpublished,featured'],
        ], [], ['q' => 'search term']);

        // never wipe the whole list by accident: something has to narrow it down
        if (! $request->filled('q') && ! $request->filled('rating')) {
            return back()->withErrors(['q' => 'Search for something, or pick a star rating, before deleting in bulk.']);
        }

        $productIds = $this->filtered($request)->pluck('product_id')->all();
        $n = $this->filtered($request)->delete();
        ReviewStats::sync($productIds);

        return redirect()->route('admin.reviews.index')
            ->with('status', $n.' review(s) '.$this->describe($request).' deleted.');
    }

    /** The list the current search / rating / status filters describe. */
    private function filtered(Request $request)
    {
        $query = Review::with('product');

        if ($request->filled('filter')) {
            match ($request->filter) {
                'published'   => $query->where('is_published', true),
                'unpublished' => $query->where('is_published', false),
                'featured'    => $query->where('featured', true),
                default       => null,
            };
        }

        // "low" is the 1–2 star complaints together
        if ($request->filled('rating')) {
            $request->rating === 'low'
                ? $query->where('rating', '<=', 2)
                : $query->where('rating', (int) $request->rating);
        }

        if ($request->filled('q')) {
            $term = trim($request->q);
            $query->where(function ($sub) use ($term) {
                $sub->where('author_name', 'like', '%'.$term.'%')
                    ->orWhere('body', 'like', '%'.$term.'%');
            });
        }

        return $query;
    }

    /** Plain words for what was just deleted, for the confirmation message. */
    private function describe(Request $request): string
    {
        $parts = [];
        if ($request->filled('rating')) {
            $parts[] = $request->rating === 'low' ? 'rated 1–2 stars' : 'rated '.(int) $request->rating.' stars';
        }
        if ($request->filled('q')) {
            $parts[] = 'matching “'.trim($request->q).'”';
        }

        return implode(' and ', $parts);
    }

    public function create()
    {
        return view('admin.reviews.form', [
            'review' => new Review(['rating' => 5, 'is_published' => true]),
            'products' => Product::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $review = Review::create($this->validateData($request));
        ReviewStats::sync([$review->product_id]);

        return redirect()->route('admin.reviews.index')->with('status', 'Review added.');
    }

    public function edit(Review $review)
    {
        return view('admin.reviews.form', [
            'review' => $review,
            'products' => Product::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Review $review)
    {
        $review->update($this->validateData($request));
        ReviewStats::sync(array_unique([$review->product_id, $review->getOriginal('product_id')]));

        return redirect()->route('admin.reviews.index')->with('status', 'Review updated.');
    }

    public function destroy(Review $review)
    {
        $productId = $review->product_id;
        $review->delete();
        ReviewStats::sync([$productId]);

        return back()->with('status', 'Review deleted.');
    }

    public function toggle(Review $review)
    {
        $review->update(['is_published' => ! $review->is_published]);
        ReviewStats::sync([$review->product_id]);

        return back()->with('status', 'Review visibility updated.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'author_name' => ['required', 'string', 'max:120'],
            'author_role' => ['nullable', 'string', 'max:120'],
            'product_id' => ['nullable', 'exists:products,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'body' => ['required', 'string', 'max:1000'],
        ]) + [
            'is_published' => $request->boolean('is_published'),
            'featured' => $request->boolean('featured'),
        ];
    }
}
