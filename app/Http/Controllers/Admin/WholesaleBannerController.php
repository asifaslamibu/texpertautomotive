<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\ImageStore;
use Illuminate\Http\Request;

/**
 * The "Apply for Wholesale" banner on the homepage. It is a single background
 * image (stored in the `home_wallpaper` setting) plus its heading/subtitle/CTA,
 * managed from the Sliders hub rather than the general Settings page.
 */
class WholesaleBannerController extends Controller
{
    private const KEYS = [
        'home_wallpaper', 'home_wallpaper_heading', 'home_wallpaper_subtitle',
        'home_wallpaper_cta', 'home_wallpaper_link', 'home_wallpaper_overlay',
    ];

    public function edit()
    {
        $settings = collect(self::KEYS)->mapWithKeys(fn ($k) => [$k => Setting::get($k)]);

        return view('admin.sliders.wallpaper', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'home_wallpaper'          => ['nullable', 'string', 'max:300'],
            'home_wallpaper_file'     => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'home_wallpaper_heading'  => ['nullable', 'string', 'max:120'],
            'home_wallpaper_subtitle' => ['nullable', 'string', 'max:300'],
            'home_wallpaper_cta'      => ['nullable', 'string', 'max:60'],
            'home_wallpaper_link'     => ['nullable', 'string', 'max:300'],
            'home_wallpaper_overlay'  => ['nullable', 'integer', 'min:0', 'max:90'],
        ]);

        if ($request->hasFile('home_wallpaper_file')) {
            $data['home_wallpaper'] = ImageStore::store($request->file('home_wallpaper_file'), 'banners');
        }
        unset($data['home_wallpaper_file']);

        foreach (self::KEYS as $key) {
            Setting::put($key, $data[$key] ?? '');
        }

        return back()->with('status', 'Wholesale banner updated.');
    }
}
