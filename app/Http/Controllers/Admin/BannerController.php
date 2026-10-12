<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Support\ImageStore;
use Illuminate\Http\Request;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::orderBy('sort')->orderBy('id')->get();

        return view('admin.banners.index', compact('banners'));
    }

    public function create()
    {
        return view('admin.banners.form', ['banner' => new Banner(['active' => true, 'sort' => (int) Banner::max('sort') + 1])]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        if ($request->hasFile('image_file')) {
            $data['image'] = ImageStore::store($request->file('image_file'), 'banners');
        }
        if ($request->hasFile('video_file')) {
            $data['video'] = ImageStore::store($request->file('video_file'), 'banners');
        }

        Banner::create($data);

        return redirect()->route('admin.banners.index')->with('status', 'Banner created.');
    }

    public function edit(Banner $banner)
    {
        return view('admin.banners.form', compact('banner'));
    }

    public function update(Request $request, Banner $banner)
    {
        $data = $this->validateData($request);

        if ($request->hasFile('image_file')) {
            $data['image'] = ImageStore::store($request->file('image_file'), 'banners');
        }
        if ($request->hasFile('video_file')) {
            $data['video'] = ImageStore::store($request->file('video_file'), 'banners');
        }

        $banner->update($data);

        return redirect()->route('admin.banners.index')->with('status', 'Banner updated.');
    }

    public function destroy(Banner $banner)
    {
        $banner->delete();

        return back()->with('status', 'Banner deleted.');
    }

    public function toggle(Banner $banner)
    {
        $banner->update(['active' => ! $banner->active]);

        return back()->with('status', 'Banner visibility updated.');
    }

    public function move(Banner $banner, string $dir)
    {
        $items = Banner::orderBy('sort')->orderBy('id')->get();

        foreach ($items as $i => $b) {
            if ((int) $b->sort !== $i) {
                $b->sort = $i;
                $b->save();
            }
        }

        $index = $items->search(fn ($b) => $b->id === $banner->id);
        $swap = $dir === 'up' ? $index - 1 : $index + 1;

        if ($index !== false && $swap >= 0 && $swap < $items->count()) {
            $a = $items[$index];
            $b = $items[$swap];
            [$a->sort, $b->sort] = [$b->sort, $a->sort];
            $a->save();
            $b->save();
        }

        return back()->with('status', 'Banner order updated.');
    }

    private function validateData(Request $request): array
    {
        $data = $request->validate([
            'eyebrow' => ['nullable', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:160'],
            'subtitle' => ['nullable', 'string', 'max:200'],
            'cta_label' => ['nullable', 'string', 'max:60'],
            'cta_link' => ['nullable', 'string', 'max:300'],
            'image' => ['nullable', 'string', 'max:300'],
            'image_file' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif,svg', 'max:4096'],
            'video' => ['nullable', 'string', 'max:300'],
            'video_file' => ['nullable', 'mimetypes:video/mp4,video/webm,video/ogg', 'mimes:mp4,webm,ogg', 'max:30720'],
            'video_speed' => ['nullable', 'numeric', 'min:0.25', 'max:4'],
            'video_fit' => ['nullable', 'in:cover,contain'],
            'sort' => ['nullable', 'integer', 'min:0'],
        ]) + [
            'active' => $request->boolean('active'),
            'sort' => (int) $request->input('sort', 0),
        ];

        $data['title'] = $data['title'] ?? ''; // allow image-only slides (title column is NOT NULL)
        $data['video_speed'] = $data['video_speed'] ?? 1; // 1x default (column is NOT NULL)
        $data['video_fit'] = $data['video_fit'] ?? 'cover'; // fill by default (column is NOT NULL)
        unset($data['image_file'], $data['video_file']);

        return $data;
    }
}
