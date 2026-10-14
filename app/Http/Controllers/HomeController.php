<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;

class HomeController extends Controller
{
    public function index()
    {
        // The homepage runs a dozen catalogue queries; cache the whole set for a few
        // minutes (cleared automatically when a product/category/brand changes).
        // Banners stay live so slider edits are instant.
        $sections = \Illuminate\Support\Facades\Cache::remember('home_sections', now()->addMinutes(10), function () {
            // hidden categories (+ their subtrees) stay off every storefront surface
            $hiddenCatIds = Category::hiddenIds();

            // top-level departments (for "Shop by Department") — visible only, counting visible children
            $departments = Category::topLevel()->visible()
                ->withCount(Category::supportsHidden()
                    ? ['children as children_count' => fn ($q) => $q->where('hidden', false)]
                    : 'children')
                ->orderBy('sort')->get();

            // popular sub/leaf categories that actually have products (with counts + a sample image)
            $popularCategories = Category::whereNotNull('parent_id')->visible()
                ->when($hiddenCatIds, fn ($q) => $q->whereNotIn('id', $hiddenCatIds))
                ->withCount('products')
                ->orderByDesc('products_count')
                ->take(12)
                ->get();
            foreach ($popularCategories as $cat) {
                $cat->sample_image = Product::published()->where('category_id', $cat->id)->value('image');
            }

            // keep products out of any hidden category subtree
            $notHidden = fn ($q) => $q->when($hiddenCatIds, fn ($qq) => $qq->whereNotIn('category_id', $hiddenCatIds));

            // featured slider — admin-curated order (Featured Products manager); newest as fallback
            $featuredProducts = $notHidden(Product::with('category')->published()->where('featured', true))
                ->orderBy('featured_sort')->latest('id')->take(12)->get();
            if ($featuredProducts->count() < 4) {
                $featuredProducts = $notHidden(Product::with('category')->published())->latest('id')->take(10)->get();
            }

            // New Arrivals — newest from these brands (falls back to newest overall)
            $arrivalBrands = ['Fieldpiece', 'Navac', 'White-Rodgers', 'Rectorseal'];
            $newArrivals = $notHidden(Product::with('category')->published()->whereIn('brand', $arrivalBrands))
                ->latest('id')->take(12)->get();
            if ($newArrivals->isEmpty()) {
                $newArrivals = $notHidden(Product::with('category')->published())->latest('id')->take(10)->get();
            }
            $topRated = $notHidden(Product::with('category')->published())->orderByDesc('rating')->orderByDesc('reviews_count')->take(10)->get();

            // brand names that actually have published products (lowercased for matching)
            $brandsWithProducts = Product::published()
                ->whereNotNull('brand')->where('brand', '!=', '')
                ->distinct()->pluck('brand')
                ->map(fn ($b) => mb_strtolower($b))->all();

            $hasProducts = fn ($brand) => in_array(mb_strtolower($brand->name), $brandsWithProducts, true);

            // promo cards: the three biggest brands, each with its real best discount and
            // the photo of its most-discounted product
            $discount = 'CASE WHEN compare_at_price > price THEN (compare_at_price - price) / compare_at_price ELSE 0 END';
            $promoBrands = $notHidden(Product::published())
                ->whereNotNull('brand')->where('brand', '!=', '')
                ->selectRaw("brand, COUNT(*) AS products_count, ROUND(MAX({$discount}) * 100) AS max_off")
                ->groupBy('brand')->orderByDesc('products_count')->take(3)->get();
            foreach ($promoBrands as $promo) {
                $promo->image = $notHidden(Product::published())->where('brand', $promo->brand)
                    ->orderByRaw("{$discount} DESC")->orderByDesc('in_stock')->value('image');
            }

            return [
                'promoBrands' => $promoBrands,
                'departments' => $departments,
                'popularCategories' => $popularCategories,
                'featuredProducts' => $featuredProducts,
                'newArrivals' => $newArrivals,
                'topRated' => $topRated,
                'popularBrands' => Brand::where('featured', true)->orderBy('sort')->orderBy('name')->get()->filter($hasProducts)->values(),
                'marqueeBrands' => Brand::where('in_marquee', true)->orderBy('sort')->orderBy('name')->get()->filter($hasProducts)->values(),
                'brands' => Product::published()->whereNotNull('brand')->where('brand', '!=', '')->distinct()->orderBy('brand')->limit(16)->pluck('brand'),
                'reviews' => Review::published()->latest('id')->take(8)->get(),
            ];
        });

        // hero slider banners (admin-managed) — always live
        $sections['banners'] = Banner::active()->orderBy('sort')->orderBy('id')->get();

        // latest blog posts (live so new posts appear instantly; safe before the migration)
        $sections['latestPosts'] = \Illuminate\Support\Facades\Schema::hasTable('blogs')
            ? \App\Models\Blog::published()->orderByDesc('published_at')->orderByDesc('id')->take(4)->get()
            : collect();

        return view('home', $sections);
    }
}
