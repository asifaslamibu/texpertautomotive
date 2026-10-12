<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ImportLog;
use App\Models\Product;
use App\Support\ImageStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    /** Cached once per import: whether the optional upc/uom columns exist. */
    private ?bool $hasUpcUom = null;

    public function index()
    {
        return view('admin.import.index');
    }

    /** Columns the importer understands (header row of the sample — matches the master product sheet). */
    public static function templateColumns(): array
    {
        return ['Brand', 'ItemID', 'Product Title', 'Main Category', 'Sub Category', 'Child Category', 'SELL PRICE', 'ImageURL', 'Dimensions', 'Weight', 'Description', 'UPCCode', 'UOM'];
    }

    /** Example rows in the exact master-sheet layout. */
    public static function templateRows(): array
    {
        return [
            // Brand, ItemID, Product Title, Main Category, Sub Category, Child Category, SELL PRICE, ImageURL, Dimensions, Weight, Description, UPCCode, UOM
            ['ACT', 'ADP14120N100', 'ACT ADP14-120-N-100 Heavy Duty Cable Tie 14 in Nylon 66 Natural', 'Electrical', 'Wiring & Connectivity', 'Electrical Products', '24.00', 'https://www.alliancemro.com/site/images/products/ADP14120N100.jpg', '15.00 x 0.30 x 0.08', '1.36 Lbs', 'The ACT ADP14-120-N-100 is a heavy-duty cable tie. Construction is Nylon 66, natural colour, 14 in length, medium duty.', '840476015050', 'Pack'],
            ['NU-CALGON', '4298-22', 'Nu-Calgon 4298-22 PowerZyme Enzyme Condensate Drain Treatment 1 Quart', 'Tools & Supplies', 'Maintenance Supplies', 'Drain Treatments', '19.95', 'https://placehold.co/600x600/e8f0fe/1a56db?text=NU-CALGON-4298-22', '4 x 4 x 9 in', '2.3 lbs', 'Enzyme-based condensate drain treatment that digests the buildup clogging HVAC drain lines and pans.', '', 'Each'],
        ];
    }

    /** Stream a sample, Excel-ready CSV template (UTF-8 with BOM). */
    public function template(): StreamedResponse
    {
        $columns = self::templateColumns();
        $rows = self::templateRows();

        return response()->streamDownload(function () use ($columns, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel opens it correctly
            fputcsv($out, $columns);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, 'texperdeals-products-sample.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv' => ['required', 'file', 'max:20480'],
            'download_images' => ['nullable', 'boolean'],
            'main_category' => ['nullable', 'string', 'max:120'],
        ]);

        // accept by extension (Excel-saved CSVs report inconsistent MIME types)
        $ext = strtolower($request->file('csv')->getClientOriginalExtension());
        if (! in_array($ext, ['csv', 'txt'], true)) {
            return back()->withErrors(['csv' => 'Please upload a .csv file. In Excel use File → Save As → "CSV UTF-8 (Comma delimited)".']);
        }

        @set_time_limit(0);
        $downloadImages = $request->boolean('download_images');
        $mainCategory = trim((string) $request->input('main_category')) ?: null;

        // Keep a copy of exactly what was uploaded, so every import is auditable and
        // re-downloadable later. Stored outside /public — only reachable through the
        // admin download route. storage/ is not deployed over, so copies persist.
        $file = $request->file('csv');
        $originalName = $file->getClientOriginalName();
        $size = $file->getSize();
        $storedPath = $file->storeAs('imports', now()->format('Ymd_His').'_'.Str::random(6).'_'.Str::slug(pathinfo($originalName, PATHINFO_FILENAME)).'.'.$ext);

        $handle = fopen(Storage::path($storedPath), 'r');
        if (! $handle) {
            return back()->withErrors(['csv' => 'Could not read the uploaded file.']);
        }

        // header row -> normalized map
        $header = fgetcsv($handle);
        if (! $header) {
            return back()->withErrors(['csv' => 'The CSV appears to be empty.']);
        }
        $cols = [];
        foreach ($header as $i => $name) {
            $cols[$this->normalize($name)] = $i;
        }

        $catCache = [];     // path string => Category
        $brandCache = [];   // lowercased name => Brand
        $stats = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'categories' => 0, 'brands' => 0, 'images' => 0, 'rows' => 0];

        while (($row = fgetcsv($handle)) !== false) {
            $stats['rows']++;
            $get = fn (array $keys, $default = null) => $this->value($row, $cols, $keys, $default);

            $name = trim((string) $get(['name', 'post_title', 'title', 'product_name', 'product_title_seo', 'product_title']));
            if ($name === '') {
                $stats['skipped']++;
                continue;
            }

            // pricing — sale price is the current price, regular is the compare-at
            $regular = $this->money($get(['regular_price', 'price', 'regularprice', 'price_usd', 'sell_price', 'sellprice']));
            $sale = $this->money($get(['sale_price', 'saleprice']));
            $price = $sale ?: $regular ?: 0;
            $compare = ($sale && $regular && $regular > $sale) ? $regular : null;

            // category chain — either a single "Categories > path" column (WooCommerce)
            // or split Main / Sub / Child columns (master sheet). Optionally forced under a main category.
            $catCell = trim((string) $get(['categories', 'category', 'product_cat']));
            $segments = $catCell !== ''
                ? $this->buildChain($catCell)
                : array_values(array_filter(array_map('trim', [
                    (string) $get(['main_category', 'main_cat']),
                    (string) $get(['sub_category', 'sub_cat']),
                    (string) $get(['child_category', 'child_cat']),
                ]), fn ($s) => $s !== ''));
            $category = $this->resolveCategory($segments, $catCache, $stats, $mainCategory);

            // images — first is the main image, the rest become the gallery
            $allImages = $this->allImages((string) $get(['images', 'image', 'featured_image', 'image_url', 'imageurl', 'image_folder_link', 'image_link']));
            if ($downloadImages) {
                $allImages = array_map(function ($url) use (&$stats) {
                    $stored = ImageStore::download($url);
                    if ($stored && str_starts_with($stored, '/uploads/')) {
                        $stats['images']++;
                    }

                    return $stored ?: $url;
                }, $allImages);
            }
            $image = $allImages[0] ?? null;
            $gallery = array_values(array_slice($allImages, 1));

            // brand — stored as text on the product AND upserted into the brands table
            // so it appears on the Admin → Brands page and the storefront brand list.
            $brand = trim((string) $get(['brands', 'brand', 'pa_brand'])) ?: null;
            if ($brand) {
                $this->resolveBrand($brand, $brandCache, $stats);
            }

            $sku = trim((string) $get(['sku', 'product_sku', 'itemid', 'item_id', 'item_number']));
            $attrs = [
                'category_id'       => $category->id,
                'name'              => $name,
                'brand'             => $brand,
                'short_description' => trim((string) $get(['short_description', 'excerpt', 'post_excerpt'])) ?: null,
                'description'       => trim((string) $get(['description', 'post_content', 'content', 'product_description'])) ?: null,
                'price'             => $price,
                'compare_at_price'  => $compare,
                'weight_lbs'        => trim((string) $get(['weight', 'weight_lbs', 'weight_lb'])) ?: null,
                'dimensions'        => trim((string) $get(['dimension', 'dimensions', 'size'])) ?: null,
                'image'             => $image ?: 'https://placehold.co/600x600/e8f0fe/1a56db?text='.urlencode(Str::limit($name, 24, '')),
                'gallery'           => $gallery,
                'in_stock'          => $this->bool($get(['in_stock', 'in_stock?', 'stock_status', 'instock'], '1')),
                'featured'          => $this->bool($get(['is_featured', 'is_featured?', 'featured'], '0')),
            ];

            // UPC / UOM — only written on environments whose migration has run, so an
            // import never fails just because the columns are not there yet.
            if ($this->hasUpcUom ??= \Illuminate\Support\Facades\Schema::hasColumn('products', 'upc')) {
                $attrs['upc'] = trim((string) $get(['upc', 'upccode', 'upc_code', 'barcode'])) ?: null;
                $attrs['uom'] = trim((string) $get(['uom', 'unit', 'unit_of_measure'])) ?: null;
            }

            // match existing by SKU, else create new
            $existing = $sku ? Product::where('sku', $sku)->first() : null;
            if ($existing) {
                $existing->update($attrs);
                $stats['updated']++;
            } else {
                $attrs['sku'] = $sku ?: strtoupper(Str::random(3)).'-'.mt_rand(10000, 99999);
                $attrs['slug'] = $this->uniqueSlug($name);
                Product::create($attrs);
                $stats['created']++;
            }
        }
        fclose($handle);

        ImportLog::create([
            'user_id'          => Auth::id(),
            'user_name'        => Auth::user()?->name,
            'original_name'    => $originalName,
            'stored_path'      => $storedPath,
            'size'             => $size,
            'rows'             => $stats['rows'],
            'created_count'    => $stats['created'],
            'updated_count'    => $stats['updated'],
            'skipped_count'    => $stats['skipped'],
            'categories_count' => $stats['categories'],
            'brands_count'     => $stats['brands'],
            'images_count'     => $stats['images'],
            'download_images'  => $downloadImages,
        ]);

        $msg = "Import complete: {$stats['created']} created, {$stats['updated']} updated, {$stats['skipped']} skipped. "
            ."{$stats['categories']} categories created, {$stats['brands']} brands created, {$stats['images']} images downloaded.";

        return redirect()->route('admin.products.index')->with('status', $msg);
    }

    /** Import History — every past upload with its result and the saved file. */
    public function history()
    {
        $logs = ImportLog::with('user')->latest()->paginate(30);

        return view('admin.import.history', compact('logs'));
    }

    /** Download the original file that was uploaded for a past import. */
    public function download(ImportLog $log): StreamedResponse
    {
        abort_unless($log->fileExists(), 404, 'The uploaded file for this import is no longer available.');

        return Storage::download($log->stored_path, $log->original_name);
    }

    /* ---------------- helpers ---------------- */

    private function normalize(string $h): string
    {
        // lowercase, drop the UTF-8 BOM, then collapse any run of non-alphanumerics into a single underscore.
        // "Product Title (SEO)" -> product_title_seo, "Price (USD)" -> price_usd, "In Stock?" -> in_stock
        $h = strtolower(str_replace("\xEF\xBB\xBF", '', $h));

        return trim(preg_replace('/[^a-z0-9]+/', '_', $h), '_');
    }

    private function value(array $row, array $cols, array $keys, $default = null)
    {
        foreach ($keys as $key) {
            if (isset($cols[$key]) && isset($row[$cols[$key]]) && $row[$cols[$key]] !== '') {
                return $row[$cols[$key]];
            }
        }

        return $default;
    }

    private function money($v): float
    {
        return (float) preg_replace('/[^0-9.]/', '', (string) $v);
    }

    private function bool($v): bool
    {
        $v = strtolower(trim((string) $v));

        return in_array($v, ['1', 'yes', 'true', 'instock', 'in stock', 'y'], true);
    }

    private function firstImage(string $cell): ?string
    {
        return $this->allImages($cell)[0] ?? null;
    }

    /** All image URLs from an "Images" cell (comma/pipe separated). */
    private function allImages(string $cell): array
    {
        if (trim($cell) === '') {
            return [];
        }

        return collect(preg_split('/[,|]/', $cell))
            ->map(fn ($u) => trim($u))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Reconstruct the FULL category chain from a WooCommerce "Categories" cell.
     * WooCommerce lists each ancestor pair, often overlapping, e.g.
     *   "it-communication > Labels & Tags, Labels & Tags > Thermal Transfer Labels"
     * which is merged into the deepest root→leaf path:
     *   [it-communication, Labels & Tags, Thermal Transfer Labels]
     */
    private function buildChain(string $cell): array
    {
        $cell = trim($cell);
        if ($cell === '') {
            return [];
        }

        $childOf = [];   // child name => parent name
        $nodes = [];     // all node names
        foreach (array_filter(array_map('trim', explode(',', $cell))) as $path) {
            $segs = array_values(array_filter(array_map('trim', explode('>', $path))));
            foreach ($segs as $i => $seg) {
                $nodes[$seg] = true;
                if ($i > 0) {
                    $childOf[$seg] = $segs[$i - 1];
                }
            }
        }
        if (empty($nodes)) {
            return [];
        }

        // leaves = nodes that are never used as a parent
        $isParent = array_flip(array_values($childOf));
        $leaves = array_filter(array_keys($nodes), fn ($n) => ! isset($isParent[$n]));

        // pick the leaf whose chain to the root is longest
        $best = [];
        foreach ($leaves as $leaf) {
            $chain = [];
            $node = $leaf;
            $guard = 0;
            while ($node !== null && $guard++ < 25) {
                array_unshift($chain, $node);
                $node = $childOf[$node] ?? null;
            }
            if (count($chain) > count($best)) {
                $best = $chain;
            }
        }

        return $best;
    }

    /**
     * Resolve a pre-built segment chain (e.g. [Main, Sub, Child]) into a leaf Category,
     * creating the full hierarchy along the way. When $mainCategory is set it becomes the
     * root — replacing the chain's own root if it's the same category (by name or slug),
     * otherwise prepended.
     */
    private function resolveCategory(array $segments, array &$cache, array &$stats, ?string $mainCategory = null): Category
    {
        if ($mainCategory) {
            if (empty($segments)) {
                $segments = [$mainCategory];
            } elseif (strcasecmp($segments[0], $mainCategory) === 0 || Str::slug($segments[0]) === Str::slug($mainCategory)) {
                $segments[0] = $mainCategory; // tidy the slug-like root into the proper name
            } else {
                array_unshift($segments, $mainCategory);
            }
        }

        if (empty($segments)) {
            $segments = ['Uncategorized'];
        }

        $parentId = null;
        $pathKey = '';
        $leaf = null;

        foreach ($segments as $i => $segName) {
            $pathKey = $pathKey === '' ? $segName : $pathKey.' > '.$segName;

            if (isset($cache[$pathKey])) {
                $leaf = $cache[$pathKey];
                $parentId = $leaf->id;
                continue;
            }

            $category = Category::where('name', $segName)->where('parent_id', $parentId)->first();
            if (! $category) {
                $category = Category::create([
                    'parent_id' => $parentId,
                    'name'      => $segName,
                    'slug'      => $this->uniqueCategorySlug($segName),
                    'icon'      => $parentId ? 'box' : 'parts',
                    'tagline'   => null,
                    'featured'  => $parentId === null,
                    'sort'      => Category::max('sort') + 1,
                ]);
                $stats['categories']++;
            }

            $cache[$pathKey] = $category;
            $parentId = $category->id;
            $leaf = $category;
        }

        return $leaf;
    }

    /** Ensure a Brand row exists for this name (case-insensitive), creating it once per import. */
    private function resolveBrand(string $name, array &$cache, array &$stats): Brand
    {
        $key = mb_strtolower($name);
        if (isset($cache[$key])) {
            return $cache[$key];
        }

        $brand = Brand::whereRaw('LOWER(name) = ?', [$key])->first();
        if (! $brand) {
            $brand = Brand::create([
                'name'     => $name,
                'slug'     => $this->uniqueBrandSlug($name),
                'featured' => true,
                'sort'     => (int) Brand::max('sort') + 1,
            ]);
            $stats['brands']++;
        }

        return $cache[$key] = $brand;
    }

    private function uniqueBrandSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'brand';
        $slug = $base;
        $i = 1;
        while (Brand::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $i = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function uniqueCategorySlug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $slug = $base;
        $i = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
