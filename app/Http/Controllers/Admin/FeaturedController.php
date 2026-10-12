<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class FeaturedController extends Controller
{
    /** Manage which products appear in the homepage "Featured Deals" slider, and their order. */
    public function index(Request $request)
    {
        $featured = Product::with('category')
            ->where('featured', true)
            ->orderBy('featured_sort')->orderByDesc('id')
            ->get();

        $search = trim((string) $request->q);
        $results = null;
        if ($search !== '') {
            $results = Product::where('featured', false)
                ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%"))
                ->orderBy('name')
                ->limit(20)
                ->get();
        }

        return view('admin.featured.index', compact('featured', 'results', 'search'));
    }

    /** Add a product to the featured slider (appended to the end). */
    public function add(Product $product)
    {
        $product->update([
            'featured' => true,
            'featured_sort' => (int) Product::where('featured', true)->max('featured_sort') + 1,
        ]);

        return back()->with('status', "Added \"{$product->name}\" to the featured slider.");
    }

    /** Remove a product from the featured slider. */
    public function remove(Product $product)
    {
        $product->update(['featured' => false]);

        return back()->with('status', 'Removed from the featured slider.');
    }

    /** Reorder a featured product up or down within the slider. */
    public function move(Product $product, string $dir)
    {
        $items = Product::where('featured', true)
            ->orderBy('featured_sort')->orderByDesc('id')->get();

        // normalize to a clean 0..n sequence first
        foreach ($items as $i => $p) {
            if ((int) $p->featured_sort !== $i) {
                $p->featured_sort = $i;
                $p->save();
            }
        }

        $index = $items->search(fn ($p) => $p->id === $product->id);
        $swap = $dir === 'up' ? $index - 1 : $index + 1;

        if ($index !== false && $swap >= 0 && $swap < $items->count()) {
            $a = $items[$index];
            $b = $items[$swap];
            [$a->featured_sort, $b->featured_sort] = [$b->featured_sort, $a->featured_sort];
            $a->save();
            $b->save();
        }

        return back()->with('status', 'Slider order updated.');
    }
}
