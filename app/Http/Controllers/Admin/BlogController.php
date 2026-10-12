<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Support\ImageStore;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    use \App\Http\Controllers\Concerns\SortsRecords;

    public function index(Request $request)
    {
        $query = Blog::query();

        if ($request->filled('q')) {
            $query->where('title', 'like', "%{$request->q}%");
        }

        $this->applySort($query, $request, ['title', 'published', 'published_at', 'created_at'], 'created_at', 'desc');

        $blogs = $query->paginate(15)->withQueryString();

        return view('admin.blogs.index', compact('blogs'));
    }

    public function create()
    {
        return view('admin.blogs.form', ['blog' => new Blog(['published' => true, 'published_at' => now()])]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['slug'] = $this->uniqueSlug($data['title']);
        $data = $this->applyImage($request, $data);

        Blog::create($data);

        return redirect()->route('admin.blogs.index')->with('status', 'Post created.');
    }

    public function edit(Blog $blog)
    {
        return view('admin.blogs.form', compact('blog'));
    }

    public function update(Request $request, Blog $blog)
    {
        $data = $this->validateData($request);
        if ($data['title'] !== $blog->title) {
            $data['slug'] = $this->uniqueSlug($data['title'], $blog->id);
        }
        $data = $this->applyImage($request, $data);

        $blog->update($data);

        return redirect()->route('admin.blogs.index')->with('status', 'Post updated.');
    }

    public function destroy(Blog $blog)
    {
        $blog->delete();

        return back()->with('status', 'Post deleted.');
    }

    public function toggle(Blog $blog)
    {
        $blog->update(['published' => ! $blog->published]);

        return back()->with('status', 'Post visibility updated.');
    }

    /** Upload an image for use inside the post body; returns its URL as JSON. */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        $path = ImageStore::store($request->file('file'), 'blogs');

        return response()->json(['url' => '/'.ltrim((string) $path, '/')]);
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'author' => ['nullable', 'string', 'max:120'],
            'excerpt' => ['nullable', 'string', 'max:400'],
            'body' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:300'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'published_at' => ['nullable', 'date'],
        ]) + ['published' => $request->boolean('published')];

        unset($data['image_file']);

        return $data;
    }

    private function applyImage(Request $request, array $data): array
    {
        if ($request->hasFile('image_file')) {
            $data['image'] = ImageStore::store($request->file('image_file'), 'blogs');
        }

        return $data;
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'post';
        $slug = $base;
        $i = 2;
        while (Blog::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
