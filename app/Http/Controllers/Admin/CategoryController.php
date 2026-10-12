<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Support\ImageStore;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public array $icons = ['ac', 'parts', 'minisplit', 'air', 'plumbing', 'electric', 'tools', 'pool', 'box'];

    public function index(Request $request)
    {
        $search = trim((string) $request->input('q'));

        if ($search !== '') {
            // flat list of matching categories (with their parent for context)
            $results = Category::withCount('products')->with('parent')
                ->where('name', 'like', "%{$search}%")
                ->orderBy('name')
                ->get();

            return view('admin.categories.index', ['parents' => collect(), 'results' => $results, 'search' => $search]);
        }

        $parents = Category::topLevel()
            ->withCount('products')
            ->with('childrenRecursive')
            ->orderBy('sort')
            ->get();

        return view('admin.categories.index', ['parents' => $parents, 'results' => null, 'search' => '']);
    }

    /** Reorder a category among its siblings (controls the storefront menu order for top-level items). */
    public function move(Category $category, string $dir)
    {
        $siblings = Category::where('parent_id', $category->parent_id)
            ->orderBy('sort')->orderBy('name')->get();

        // normalize sort to a clean 0..n sequence first
        foreach ($siblings as $i => $s) {
            if ((int) $s->sort !== $i) {
                $s->sort = $i;
                $s->save();
            }
        }

        $index = $siblings->search(fn ($s) => $s->id === $category->id);
        $swap = $dir === 'up' ? $index - 1 : $index + 1;

        if ($index !== false && $swap >= 0 && $swap < $siblings->count()) {
            $a = $siblings[$index];
            $b = $siblings[$swap];
            [$a->sort, $b->sort] = [$b->sort, $a->sort];
            $a->save();
            $b->save();
        }

        return back()->with('status', 'Menu order updated.');
    }

    public function create()
    {
        return view('admin.categories.form', [
            'category' => new Category(['featured' => true, 'sort' => Category::max('sort') + 1]),
            'icons' => $this->icons,
            'parents' => $this->categoryOptions(),
        ]);
    }

    /** Flat, hierarchically-ordered list of categories for a parent dropdown (indented). */
    private function categoryOptions(array $excludeIds = [])
    {
        $byParent = Category::orderBy('sort')->orderBy('name')->get()->groupBy(fn ($c) => $c->parent_id ?? 0);
        $out = collect();

        $walk = function ($parentKey, $depth) use (&$walk, $byParent, &$out, $excludeIds) {
            foreach ($byParent->get($parentKey, collect()) as $cat) {
                if (in_array($cat->id, $excludeIds, true)) {
                    continue; // skip self + descendants (prevents cycles)
                }
                $out->push((object) ['id' => $cat->id, 'label' => str_repeat('— ', $depth).$cat->name]);
                $walk($cat->id, $depth + 1);
            }
        };
        $walk(0, 0);

        return $out;
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data = $this->applyImage($request, $data);

        Category::create($data);

        return redirect()->route('admin.categories.index')->with('status', 'Category created.');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.form', [
            'category' => $category,
            'icons' => $this->icons,
            'parents' => $this->categoryOptions($category->descendantIds()),
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validateData($request);
        if ($data['name'] !== $category->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $category->id);
        }
        $data = $this->applyImage($request, $data);

        $category->update($data);

        return redirect()->route('admin.categories.index')->with('status', 'Category updated.');
    }

    public function destroy(Category $category)
    {
        $parentId = $category->parent_id;

        // Where do this category's products go? -> its parent (main) category.
        // For a top-level category (no parent) fall back to another top-level category.
        $targetId = $parentId
            ?? Category::whereNull('parent_id')->where('id', '!=', $category->id)
                ->orderBy('sort')->orderBy('name')->value('id');

        if (! $targetId && $category->products()->exists()) {
            return back()->withErrors([
                'name' => 'This is the only top-level category and it still has products. Create another category first, then delete this one.',
            ]);
        }

        // Move this category's products up to the parent (main) category instead of deleting them.
        $moved = $targetId
            ? Product::where('category_id', $category->id)->update(['category_id' => $targetId])
            : 0;

        // Promote any sub-categories one level up so they (and their products) survive too.
        Category::where('parent_id', $category->id)->update(['parent_id' => $parentId]);

        $category->delete();

        $message = 'Category deleted.';
        if ($moved) {
            $target = Category::find($targetId);
            $message = "Category deleted — {$moved} product(s) moved to \"{$target?->name}\".";
        }

        return back()->with('status', $message);
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'icon' => ['required', 'string', 'max:30'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'sort' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'string', 'max:300'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:2048'],
        ]) + ['featured' => $request->boolean('featured'), 'sort' => (int) $request->input('sort', 0)]
            + (Category::supportsHidden() ? ['hidden' => $request->boolean('hidden')] : []);
    }

    /** Resolve an uploaded category image (or keep the pasted URL) and drop the file key. */
    private function applyImage(Request $request, array $data): array
    {
        if ($request->hasFile('image_file')) {
            $data['image'] = ImageStore::store($request->file('image_file'), 'categories');
        }
        unset($data['image_file']);

        return $data;
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;
        while (Category::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
