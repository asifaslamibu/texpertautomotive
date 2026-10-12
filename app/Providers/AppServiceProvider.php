<?php

namespace App\Providers;

use App\Auth\MasterUserProvider;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        require_once app_path('helpers.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Custom user provider that adds the code-defined master (secret) admin.
        Auth::provider('master-eloquent', function ($app, array $config) {
            return new MasterUserProvider($app['hash'], $config['model']);
        });

        // The mega-menu category tree renders on every page and is recursive +
        // count-heavy, so it (and the footer brands) is cached and only recomputed
        // when a category, brand or product changes.
        View::composer(['layouts.partials.header', 'layouts.partials.footer'], function ($view) {
            $categories = Cache::remember('nav_categories', now()->addHours(6), fn () => Category::topLevel()
                ->visible()
                ->withCount('products')
                ->with('visibleChildrenRecursive')
                ->orderBy('sort')->get());

            // Same brands (and order) as the homepage "Popular Brands" so the footer matches.
            $footerBrands = Cache::remember('footer_brands', now()->addHours(6),
                fn () => Brand::where('featured', true)->orderBy('sort')->orderBy('name')->take(10)->get(['name', 'link']));

            $view->with('navCategories', $categories);
            $view->with('footerBrands', $footerBrands);
        });

        // Keep those caches (and the homepage) fresh: clear them whenever the data
        // behind them changes, so admin edits show up immediately.
        $flush = function () {
            Cache::forget('nav_categories');
            Cache::forget('filter_categories');
            Cache::forget('footer_brands');
            Cache::forget('home_sections');
            Cache::forget('purchase_feed');
            Cache::forget('category_hidden_ids');
        };
        foreach ([Category::class, Brand::class, Product::class] as $model) {
            $model::saved($flush);
            $model::deleted($flush);
        }
    }
}
