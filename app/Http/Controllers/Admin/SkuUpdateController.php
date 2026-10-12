<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\SkuSync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/** Fix wrong product SKUs from a sheet, matched to products by title. */
class SkuUpdateController extends Controller
{
    public function index()
    {
        return view('admin.sku.index', ['result' => session('sku_result')]);
    }

    public function update(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'max:51200']]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['xlsx', 'csv', 'txt'], true)) {
            return back()->withErrors(['file' => 'Please upload an Excel (.xlsx) or .csv file.']);
        }

        $stored = $file->storeAs('sku-imports', now()->format('Ymd_His').'_'.Str::random(6).'.'.$ext);

        try {
            // "Apply" writes; anything else is a preview of the same read
            $result = SkuSync::fromFile(Storage::path($stored), $ext, ! $request->boolean('apply'));
        } catch (Throwable $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $result['file'] = $file->getClientOriginalName();

        return redirect()->route('admin.sku.index')->with('sku_result', $result);
    }
}
