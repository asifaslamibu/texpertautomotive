<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

class BlogController extends Controller
{
    public function index()
    {
        // The FTP deploy ships before the migration runs — show an empty state
        // (not a 500) until the blogs table exists on the server.
        if (! Schema::hasTable('blogs')) {
            $posts = new LengthAwarePaginator(collect(), 0, 12);
            $posts->setPath(request()->url());

            return view('blog.index', compact('posts'));
        }

        $posts = Blog::published()->orderByDesc('published_at')->orderByDesc('id')->paginate(12);

        return view('blog.index', compact('posts'));
    }

    public function show(Blog $blog)
    {
        abort_unless($blog->published || auth()->user()?->is_admin, 404);

        $recentPosts = Blog::published()
            ->where('id', '!=', $blog->id)
            ->orderByDesc('published_at')
            ->take(5)
            ->get();

        // Detect brand in title or body for sidebar featured products
        $matchedBrand = null;
        $knownBrands = ['Goodman', 'Fieldpiece', 'Appion', 'Yellow Jacket', 'NAVAC', 'Nu-Calgon', 'Copeland', 'SUPCO', 'ICM', 'Honeywell', 'Zebra'];
        foreach ($knownBrands as $kb) {
            if (stripos($blog->title, $kb) !== false || stripos((string)$blog->body, $kb) !== false) {
                $matchedBrand = $kb;
                break;
            }
        }

        $featuredProducts = collect();
        if ($matchedBrand && Schema::hasTable('products')) {
            $featuredProducts = Product::published()
                ->where('brand', 'like', '%' . $matchedBrand . '%')
                ->whereNotNull('image')->where('image', '!=', '')
                ->take(4)
                ->get();
        }

        if ($featuredProducts->count() < 4 && Schema::hasTable('products')) {
            $excludeIds = $featuredProducts->pluck('id')->all();
            $moreProducts = Product::published()
                ->whereNotIn('id', $excludeIds)
                ->whereNotNull('image')->where('image', '!=', '')
                ->where('featured', true)
                ->take(4 - $featuredProducts->count())
                ->get();

            if ($moreProducts->isEmpty()) {
                $moreProducts = Product::published()
                    ->whereNotIn('id', $excludeIds)
                    ->whereNotNull('image')->where('image', '!=', '')
                    ->take(4 - $featuredProducts->count())
                    ->get();
            }

            $featuredProducts = $featuredProducts->concat($moreProducts);
        }

        $related = Blog::published()->where('id', '!=', $blog->id)
            ->orderByDesc('published_at')->take(3)->get();

        return view('blog.show', compact('blog', 'recentPosts', 'featuredProducts', 'related'));
    }
}
