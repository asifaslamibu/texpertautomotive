<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\SortsRecords;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Support\ImageStore;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    use SortsRecords;

    public function index(Request $request)
    {
        $query = Product::with('category');

        if ($request->filled('q')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$request->q}%")
                ->orWhere('brand', 'like', "%{$request->q}%")
                ->orWhere('sku', 'like', "%{$request->q}%"));
        }
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }
        if ($request->filled('stock')) {
            $query->where('in_stock', $request->stock === 'in');
        }
        if ($request->filled('featured')) {
            $query->where('featured', $request->featured === 'yes');
        }
        if ($request->filled('status')) {
            $query->where('published', $request->status === 'published');
        }
        // Weight problems throw off shipping quotes. "missing" = no weight at all;
        // "suspect" = under 1 lb although the box is big (e.g. a machine at 0.85 lb).
        if (in_array($request->weight, ['missing', 'suspect'], true)) {
            $ids = [];
            Product::query()->select('id', 'weight_lbs', 'dimensions')->chunkById(2000, function ($chunk) use (&$ids, $request) {
                foreach ($chunk as $p) {
                    preg_match('/([\d.]+)/', (string) $p->weight_lbs, $m);
                    $weight = isset($m[1]) ? (float) $m[1] : 0.0;
                    if ($request->weight === 'missing') {
                        if ($weight <= 0) {
                            $ids[] = $p->id;
                        }
                        continue;
                    }
                    $dims = \App\Support\Parcels::dimensionsOf($p);
                    if ($weight > 0 && $weight < 1 && $dims && $dims[0] * $dims[1] * $dims[2] > 300) {
                        $ids[] = $p->id;
                    }
                }
            });
            $query->whereIn('id', $ids ?: [0]);
        }

        $this->applySort($query, $request, ['name', 'sku', 'price', 'published', 'created_at'], 'id', 'desc');

        // per-page: 15 / 50 / 100 / all
        $perPageOptions = [15, 50, 100];
        $perPageRaw = strtolower((string) $request->input('per_page', 15));
        $showAll = $perPageRaw === 'all';
        $perPage = $showAll ? 100000 : (in_array((int) $perPageRaw, $perPageOptions, true) ? (int) $perPageRaw : 15);

        $products = $query->paginate($perPage)->withQueryString();
        $categories = Category::orderBy('name')->get();
        $brands = Product::whereNotNull('brand')->where('brand', '!=', '')->distinct()->orderBy('brand')->pluck('brand');

        return view('admin.products.index', compact('products', 'categories', 'brands', 'perPageOptions', 'perPageRaw', 'showAll'));
    }

    /**
     * Export every product as a CSV in the same layout the importer reads, so the
     * sheet round-trips: export → edit in Excel → re-import (matched by ItemID).
     */
    public function export(): StreamedResponse
    {
        $columns = ['Brand', 'ItemID', 'Product Title', 'Main Category', 'Sub Category', 'Child Category', 'SELL PRICE', 'ImageURL', 'Dimensions', 'Weight', 'Description', 'UPCCode', 'UOM'];

        // id => {name, parent_id} for the whole tree, so each product's category
        // chain is resolved in memory rather than with a query per row.
        $cats = Category::get(['id', 'name', 'parent_id'])->keyBy('id');

        $chain = function (?int $catId) use ($cats): array {
            $names = [];
            $guard = 0;
            while ($catId && isset($cats[$catId]) && $guard++ < 12) {
                array_unshift($names, $cats[$catId]->name);
                $catId = $cats[$catId]->parent_id;
            }

            return $names;
        };

        return response()->streamDownload(function () use ($columns, $chain) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel opens it cleanly
            fputcsv($out, $columns);

            Product::orderBy('id')->chunk(500, function ($products) use ($out, $chain) {
                foreach ($products as $p) {
                    $names = $chain($p->category_id);
                    $count = count($names);
                    $main = $names[0] ?? '';
                    $child = $count >= 3 ? $names[$count - 1] : '';
                    $sub = $count >= 3 ? $names[$count - 2] : ($names[1] ?? '');

                    // main image + gallery as absolute URLs, comma-separated
                    $images = array_filter(array_merge([$p->image], is_array($p->gallery) ? $p->gallery : []));
                    $images = array_map(fn ($u) => Str::startsWith($u, ['http://', 'https://']) ? $u : url($u), $images);

                    fputcsv($out, [
                        $p->brand,
                        $p->sku,
                        $p->name,
                        $main,
                        $sub,
                        $child,
                        number_format((float) $p->price, 2, '.', ''),
                        implode(',', $images),
                        $p->dimensions,
                        $p->weight_lbs,
                        (string) $p->description,
                        $p->upc,
                        $p->uom,
                    ]);
                }
            });

            fclose($out);
        }, 'texperdeals-products-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Find duplicate products so staff can review and remove the extras — grouped
     * either by SKU (a true duplicate) or by product name (often a variant worth
     * checking). Import de-dupes by SKU, so name duplicates are the common case.
     */
    public function duplicates(Request $request)
    {
        $by = $request->query('by') === 'name' ? 'name' : 'sku';

        // keys that sit on more than one product
        $dupeKeys = Product::query()
            ->when($by === 'sku', fn ($q) => $q->whereNotNull('sku')->where('sku', '<>', ''))
            ->select($by)
            ->selectRaw('COUNT(*) as n')
            ->groupBy($by)
            ->havingRaw('COUNT(*) > 1')
            ->orderByDesc('n')
            ->limit(500)
            ->pluck('n', $by);

        // pull every product in those groups, then group them in memory for the view
        $groups = Product::with('category')
            ->whereIn($by, $dupeKeys->keys())
            ->orderBy($by)->orderBy('id')
            ->get()
            ->groupBy($by)
            ->sortByDesc(fn ($g) => $g->count());

        $totalGroups = $dupeKeys->count();
        $totalExtra = $dupeKeys->sum() - $totalGroups;   // how many rows could be removed

        return view('admin.products.duplicates', compact('groups', 'by', 'totalGroups', 'totalExtra'));
    }

    /** Delete several products at once (bulk action). */
    public function bulkDestroy(Request $request)
    {
        $ids = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ])['ids'];

        $count = Product::whereIn('id', $ids)->delete();

        return back()->with('status', "{$count} product(s) deleted.");
    }

    /** Set the badge on many products at once from the products list. */
    public function bulkTag(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'tag_mode' => ['required', 'in:inherit,custom,none'],
            'tag' => ['nullable', 'string', 'max:60'],
        ]);

        $tag = trim((string) ($data['tag'] ?? ''));
        if ($data['tag_mode'] === 'custom' && $tag === '') {
            return back()->withErrors(['tag' => 'Enter the badge text to apply.']);
        }

        $count = Product::whereIn('id', $data['ids'])->update([
            'tag_mode' => $data['tag_mode'],
            'tag' => $data['tag_mode'] === 'custom' ? $tag : null,
        ]);

        $what = match ($data['tag_mode']) {
            'custom' => "badge set to “{$tag}”",
            'none' => 'badge removed',
            default => 'badge reset to the brand default',
        };

        return back()->with('status', "{$count} product(s) — {$what}.");
    }

    public function create()
    {
        return view('admin.products.form', [
            'product' => new Product(['in_stock' => true, 'rating' => 5]),
            'categories' => $this->categoryOptions(),
        ]);
    }

    /** Flat, hierarchically-ordered category list for the dropdown (indented by depth). */
    private function categoryOptions()
    {
        $byParent = Category::orderBy('sort')->orderBy('name')->get()->groupBy(fn ($c) => $c->parent_id ?? 0);
        $out = collect();
        $walk = function ($key, $depth) use (&$walk, $byParent, &$out) {
            foreach ($byParent->get($key, collect()) as $c) {
                $out->push((object) ['id' => $c->id, 'label' => str_repeat('— ', $depth).$c->name]);
                $walk($c->id, $depth + 1);
            }
        };
        $walk(0, 0);

        return $out;
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['slug'] = $this->uniqueSlug($data['name']);

        if ($request->hasFile('image_file')) {
            $data['image'] = ImageStore::store($request->file('image_file'));
        }
        $data['image'] = ($data['image'] ?? null) ?: 'https://placehold.co/600x600/e8f0fe/1a56db?text='.urlencode(Str::limit($data['name'], 24, ''));

        Product::create($data);

        return redirect()->route('admin.products.index')->with('status', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        return view('admin.products.form', [
            'product' => $product,
            'categories' => $this->categoryOptions(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validateData($request);
        if ($data['name'] !== $product->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $product->id);
        }

        if ($request->hasFile('image_file')) {
            $data['image'] = ImageStore::store($request->file('image_file'));
        } elseif (empty($data['image'])) {
            unset($data['image']); // keep existing image if nothing provided
        }

        $product->update($data);

        return redirect()->route('admin.products.index')->with('status', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return back()->with('status', 'Product deleted.');
    }

    /** WordPress-style quick edit — update a few key fields inline from the list. */
    public function quickUpdate(Request $request, Product $product)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'in_stock' => ['nullable', 'boolean'],
            'featured' => ['nullable', 'boolean'],
            'published' => ['nullable', 'boolean'],
        ]);

        $product->update([
            'category_id' => $data['category_id'],
            'price' => $data['price'],
            'in_stock' => $request->boolean('in_stock'),
            'featured' => $request->boolean('featured'),
            'published' => $request->boolean('published'),
        ]);

        return back()->with('status', "\"{$product->name}\" updated.");
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'brand' => ['nullable', 'string', 'max:120'],
            'sku' => ['nullable', 'string', 'max:60'],
            'tag' => ['nullable', 'string', 'max:60'],
            'tag_mode' => ['nullable', 'in:inherit,custom,none'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'image' => ['nullable', 'string', 'max:500'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['string', 'max:500'],
            'gallery_files' => ['nullable', 'array', 'max:20'],
            'gallery_files.*' => ['image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'gallery_text' => ['nullable', 'string'],
            'rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'reviews_count' => ['nullable', 'integer', 'min:0'],
            'in_stock' => ['nullable', 'boolean'],
            'stock_qty' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'free_shipping' => ['nullable', 'boolean'],
            'featured' => ['nullable', 'boolean'],
            'published' => ['nullable', 'boolean'],
        ]) + [
            'in_stock' => $request->boolean('in_stock'),
            'free_shipping' => $request->boolean('free_shipping'),
            'featured' => $request->boolean('featured'),
            'published' => $request->boolean('published'),
        ];

        unset($data['image_file']); // handled separately, not a DB column

        // Stock qty: a number drives the in-stock flag (same rule as Stock Update);
        // blank keeps the manual switch. Skipped where the column isn't migrated yet.
        unset($data['stock_qty']);
        if (\Illuminate\Support\Facades\Schema::hasColumn('products', 'stock_qty')) {
            $qty = $request->input('stock_qty');
            if ($qty === null || $qty === '') {
                $data['stock_qty'] = null;
            } else {
                $data['stock_qty'] = (int) $qty;
                $data['in_stock'] = $data['stock_qty'] > 0;
            }
        }

        // Badge: only the custom mode keeps its text, so switching back to
        // inherit/none does not leave a stale override behind.
        $data['tag_mode'] = $data['tag_mode'] ?? 'inherit';
        $data['tag'] = $data['tag_mode'] === 'custom' ? (trim((string) ($data['tag'] ?? '')) ?: null) : null;

        // Gallery = kept images (in the order shown) + new uploads + any pasted URLs (one per line)
        $uploaded = collect($request->file('gallery_files', []))
            ->map(fn ($file) => ImageStore::store($file));
        $urls = collect(preg_split('/\r\n|\r|\n/', (string) ($data['gallery_text'] ?? '')))
            ->map(fn ($l) => trim($l));
        $data['gallery'] = collect($data['gallery'] ?? [])
            ->merge($uploaded)->merge($urls)
            ->filter()->unique()->values()->all();
        unset($data['gallery_files'], $data['gallery_text']);

        return $data;
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;
        while (Product::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
