<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Sidebar filter tree — recursive + count-heavy, same on every listing page,
        // so it is cached (cleared when a category/product changes, see AppServiceProvider).
        $categories = \Illuminate\Support\Facades\Cache::remember('filter_categories', now()->addHours(6),
            fn () => Category::topLevel()->visible()->withCount('products')->with('visibleChildrenRecursive')->orderBy('sort')->get());

        // products under any hidden category (or hidden subtree) never surface on the storefront
        $hiddenCatIds = Category::hiddenIds();

        // category scope (selected category + its subcategories)
        $activeCategory = null;
        $scopeIds = null;
        if ($request->filled('category')) {
            $activeCategory = Category::with('visibleChildren')->where('slug', $request->category)->first();
            // A hidden category is not browsable — treat its URL as not found.
            abort_if($activeCategory && $activeCategory->hidden, 404);
            if ($activeCategory) {
                $scopeIds = $activeCategory->visibleDescendantIds();

                // a representative product image for each subcategory circle (looks in the whole subtree)
                foreach ($activeCategory->visibleChildren as $child) {
                    $child->sample_image = Product::whereIn('category_id', $child->visibleDescendantIds())->value('image');
                }
            }
        }

        // base scope (category + search) — shared by the listing AND the facet counts
        $term = trim((string) $request->input('q'));
        $base = fn () => Product::query()->published()
            ->when($hiddenCatIds, fn ($q) => $q->whereNotIn('category_id', $hiddenCatIds))
            ->when($scopeIds, fn ($q) => $q->whereIn('category_id', $scopeIds))
            ->when($term !== '', fn ($q) => \App\Support\ProductSearch::apply($q, $term));

        // ---- selected facets ----
        $selectedBrands = collect((array) $request->input('brands', []))
            ->merge($request->filled('brand') ? [$request->get('brand')] : []) // single ?brand= still works
            ->filter()->unique()->values()->all();
        $minPrice = $request->filled('min_price') ? max(0, (float) $request->min_price) : null;
        $maxPrice = $request->filled('max_price') ? (float) $request->max_price : null;
        $inStockOnly = $request->boolean('in_stock');
        $minRating = $request->filled('rating') ? (int) $request->rating : null;

        // ---- listing query = base + facets ----
        $query = $base()->with('category')
            ->when($selectedBrands, fn ($q) => $q->whereIn('brand', $selectedBrands))
            ->when($minPrice !== null, fn ($q) => $q->where('price', '>=', $minPrice))
            ->when($maxPrice !== null, fn ($q) => $q->where('price', '<=', $maxPrice))
            ->when($inStockOnly, fn ($q) => $q->where('in_stock', true))
            ->when($minRating, fn ($q) => $q->where('rating', '>=', $minRating));

        match ($request->get('sort')) {
            'price_asc'  => $query->orderBy('price'),
            'price_desc' => $query->orderByDesc('price'),
            'rating'     => $query->orderByDesc('rating'),
            default      => $term !== ''
                ? \App\Support\ProductSearch::orderByRelevance($query, $term)->orderByDesc('featured')->orderBy('id')
                : $query->orderByDesc('featured')->orderBy('id'),
        };

        // per-page: 50 / 60 / 100 / all
        $perPageOptions = [50, 60, 100];
        $perPageRaw = strtolower((string) $request->input('per_page', 50));
        $showAll = $perPageRaw === 'all';
        $perPage = $showAll ? 100000 : (in_array((int) $perPageRaw, $perPageOptions, true) ? (int) $perPageRaw : 50);

        $products = $query->paginate($perPage)->withQueryString();

        // ---- facet data for the sidebar ----
        $brandCounts = $base()->whereNotNull('brand')->where('brand', '!=', '')
            ->select('brand', DB::raw('COUNT(*) as c'))
            ->groupBy('brand')->orderBy('brand')->pluck('c', 'brand');
        $inStockCount = $base()->where('in_stock', true)->count();
        $priceBounds = $base()->selectRaw('MIN(price) as mn, MAX(price) as mx')->first();

        return view('products.index', compact(
            'products', 'categories', 'activeCategory',
            'brandCounts', 'selectedBrands', 'minPrice', 'maxPrice', 'inStockOnly', 'minRating',
            'inStockCount', 'priceBounds', 'perPageOptions', 'perPageRaw', 'showAll'
        ));
    }

    /** Live search suggestions for the header autocomplete (JSON). */
    public function suggest(Request $request)
    {
        $term = trim((string) $request->input('q'));
        if (mb_strlen($term) < 2) {
            return response()->json(['query' => $term, 'products' => []]);
        }

        $hiddenCatIds = Category::hiddenIds();
        $products = Product::query()->published()
            ->when($hiddenCatIds, fn ($q) => $q->whereNotIn('category_id', $hiddenCatIds))
            ->tap(fn ($q) => \App\Support\ProductSearch::apply($q, $term))
            ->tap(fn ($q) => \App\Support\ProductSearch::orderByRelevance($q, $term))
            ->orderByDesc('featured')->orderByDesc('rating')
            ->limit(8)
            ->get(['slug', 'name', 'brand', 'sku', 'price', 'image']);

        return response()->json([
            'query' => $term,
            'products' => $products->map(fn ($p) => [
                'name'  => $p->name,
                'brand' => $p->brand,
                'sku'   => $p->sku,
                'price' => '$'.number_format($p->price, 2),
                'image' => media($p->image),
                'url'   => route('products.show', $p),
            ]),
        ]);
    }

    public function show(Product $product)
    {
        // Drafts are only viewable by admins (who can preview from the admin bar).
        abort_if(! $product->published && ! (auth()->user()?->is_admin), 404);

        $product->load(['category', 'reviews' => fn ($q) => $q->where('is_published', true)->latest('id')]);

        $related = Product::with('category')->published()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->take(10)
            ->get();

        // review rating breakdown (5★ -> 1★)
        $reviews = $product->reviews;
        $reviewBreakdown = collect(range(5, 1))->mapWithKeys(fn ($s) => [$s => $reviews->where('rating', $s)->count()]);
        $avgRating = $reviews->count() ? round($reviews->avg('rating'), 2) : (float) $product->rating;

        // matching brand record (for its logo, if one is set)
        $brand = $product->brand
            ? \App\Models\Brand::where('name', $product->brand)->first()
            : null;

        return view('products.show', compact('product', 'related', 'reviewBreakdown', 'avgRating', 'brand'));
    }
}
