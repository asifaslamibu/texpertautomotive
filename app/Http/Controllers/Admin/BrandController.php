<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\SortsRecords;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Support\ImageStore;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BrandController extends Controller
{
    use SortsRecords;

    public function index(Request $request)
    {
        $query = Brand::query()
            ->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.trim($request->q).'%'));

        $this->applySort($query, $request, ['name', 'featured', 'in_marquee', 'created_at'], 'name', 'asc');

        $brands = $query->paginate(24)->withQueryString();

        return view('admin.brands.index', compact('brands'));
    }

    public function bulkDestroy(Request $request)
    {
        $ids = (array) $request->input('ids', []);
        $n = Brand::whereIn('id', $ids)->delete();

        return back()->with('status', $n.' brand(s) deleted.');
    }

    /** Badge several brands at once — each one covers its whole product range. */
    public function bulkTag(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
            'mode' => ['required', 'in:set,off,clear'],
            'tag' => ['nullable', 'string', 'max:60'],
        ]);

        $tag = trim((string) ($data['tag'] ?? ''));
        if ($data['mode'] === 'set' && $tag === '') {
            return back()->withErrors(['tag' => 'Enter the badge text to apply.']);
        }

        $attrs = match ($data['mode']) {
            'set' => ['tag' => $tag, 'tag_enabled' => true],
            'off' => ['tag_enabled' => false],          // keeps the wording
            'clear' => ['tag' => null, 'tag_enabled' => false],
        };

        $brands = Brand::whereIn('id', $data['ids'])->get();
        foreach ($brands as $brand) {
            $brand->update($attrs);
        }

        // Products follow their brand by name, so report the reach of the change.
        $affected = Product::whereIn('brand', $brands->pluck('name'))->count();

        $what = match ($data['mode']) {
            'set' => "badge set to “{$tag}”",
            'off' => 'badge switched off',
            'clear' => 'badge cleared',
        };

        return back()->with('status', $brands->count()." brand(s) — {$what} (affects {$affected} product(s)).");
    }

    public function create()
    {
        return view('admin.brands.form', ['brand' => new Brand(['featured' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['slug'] = $this->uniqueSlug($data['name']);

        if ($request->hasFile('logo_file')) {
            $data['logo'] = ImageStore::store($request->file('logo_file'), 'brands');
        }

        Brand::create($data);

        return redirect()->route('admin.brands.index')->with('status', 'Brand created.');
    }

    public function edit(Brand $brand)
    {
        return view('admin.brands.form', compact('brand'));
    }

    public function update(Request $request, Brand $brand)
    {
        $data = $this->validateData($request, $brand);
        if ($data['name'] !== $brand->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $brand->id);
        }

        if ($request->hasFile('logo_file')) {
            $data['logo'] = ImageStore::store($request->file('logo_file'), 'brands');
        }

        $brand->update($data);

        return redirect()->route('admin.brands.index')->with('status', 'Brand updated.');
    }

    public function destroy(Brand $brand)
    {
        $brand->delete();

        return back()->with('status', 'Brand deleted.');
    }

    public function toggle(Request $request, Brand $brand, string $field)
    {
        abort_unless(in_array($field, ['featured', 'in_marquee'], true), 404);

        $brand->update([$field => ! $brand->$field]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['field' => $field, 'value' => $brand->$field]);
        }

        return back()->with('status', 'Brand visibility updated.');
    }

    private function validateData(Request $request, ?Brand $brand = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('brands', 'name')->ignore($brand?->id)],
            'link' => ['nullable', 'url', 'max:300'],
            'tag' => ['nullable', 'string', 'max:60'],
            'logo' => ['nullable', 'string', 'max:300'],
            'logo_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:2048'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]) + [
            'featured' => $request->boolean('featured'),
            'in_marquee' => $request->boolean('in_marquee'),
            'tag_enabled' => $request->boolean('tag_enabled'),
            'sort' => (int) $request->input('sort', 0),
        ];

        unset($data['logo_file']);

        // A blank field means "no badge" — store null rather than an empty string.
        $data['tag'] = trim((string) ($data['tag'] ?? '')) ?: null;

        return $data;
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'brand';
        $slug = $base;
        $i = 1;
        while (Brand::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
