<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class BrandController extends Controller
{
    /** Public "Shop by Brand" directory. */
    public function index()
    {
        // product counts per brand name (published only)
        $productCounts = Product::published()
            ->whereNotNull('brand')->where('brand', '!=', '')
            ->select('brand', DB::raw('COUNT(*) as c'))
            ->groupBy('brand')
            ->pluck('c', 'brand');

        $managed = Brand::orderBy('name')->get();
        $managedLower = $managed->pluck('name')->map(fn ($n) => mb_strtolower($n))->all();

        // unified directory: managed brands (with logos) + any product brand not yet managed.
        // Only brands that actually have published products are listed.
        $brands = collect();

        foreach ($managed as $b) {
            $count = (int) ($productCounts[$b->name] ?? 0);
            if ($count === 0) {
                continue; // hide brands with no products
            }
            $brands->push((object) [
                'name'  => $b->name,
                'logo'  => $b->logo,
                'count' => $count,
                'url'   => route('products.index', ['brand' => $b->name]),
            ]);
        }

        foreach ($productCounts as $name => $count) {
            if (in_array(mb_strtolower($name), $managedLower, true)) {
                continue; // already covered by a managed brand
            }
            $brands->push((object) [
                'name'  => $name,
                'logo'  => null,
                'count' => (int) $count,
                'url'   => route('products.index', ['brand' => $name]),
            ]);
        }

        // sort A–Z, group by first letter (digits/symbols under "#")
        $brands = $brands->sortBy(fn ($b) => mb_strtolower($b->name))->values();

        $featured = $brands->filter(fn ($b) => $b->logo)->take(12)->values();

        $grouped = $brands->groupBy(function ($b) {
            $first = mb_strtoupper(mb_substr($b->name, 0, 1));
            return ctype_alpha($first) ? $first : '#';
        });

        $totalProducts = $brands->sum('count');

        return view('brands.index', compact('brands', 'featured', 'grouped', 'totalProducts'));
    }
}
