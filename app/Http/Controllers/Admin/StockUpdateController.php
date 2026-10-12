<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Support\StockSync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/** Admin page for syncing product stock from the supplier master sheet. */
class StockUpdateController extends Controller
{
    public function index()
    {
        return view('admin.stock.index', [
            'result' => StockSync::lastResult(),
            'sheetUrl' => Setting::get(StockSync::URL_KEY),
            'auto' => (bool) Setting::get(StockSync::AUTO_KEY),
            'hours' => (int) Setting::get(StockSync::HOURS_KEY, 6),
        ]);
    }

    /** Upload an .xlsx / .csv and sync from it. */
    public function update(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'max:51200']]);

        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['xlsx', 'csv', 'txt'], true)) {
            return back()->withErrors(['file' => 'Please upload an Excel (.xlsx) or .csv file.']);
        }

        $storedPath = $file->storeAs('stock-imports', now()->format('Ymd_His').'_'.Str::random(6).'.'.$ext);

        try {
            StockSync::fromFile(Storage::path($storedPath), $ext, $file->getClientOriginalName(), 'upload');
        } catch (Throwable $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        return redirect()->route('admin.stock.index')->with('status', 'Stock updated.');
    }

    /** Save the Google Sheet link and the auto-sync on/off switch. */
    public function saveSheet(Request $request)
    {
        $data = $request->validate([
            'sheet_url' => ['nullable', 'url', 'max:500'],
            'hours' => ['required', 'integer', 'in:1,2,3,6,12,24'],
        ]);

        $url = trim((string) ($data['sheet_url'] ?? ''));
        if ($url !== '') {
            try {
                StockSync::exportUrl($url);
            } catch (Throwable $e) {
                return back()->withErrors(['sheet_url' => $e->getMessage()])->withInput();
            }
        }

        Setting::put(StockSync::URL_KEY, $url ?: null);
        Setting::put(StockSync::AUTO_KEY, $url !== '' && $request->boolean('auto') ? '1' : '0');
        Setting::put(StockSync::HOURS_KEY, (string) $data['hours']);

        return back()->with('status', 'Google Sheet settings saved.');
    }

    /** Pull the saved Google Sheet right now. */
    public function syncNow()
    {
        $url = Setting::get(StockSync::URL_KEY);
        if (! $url) {
            return back()->withErrors(['sheet_url' => 'Save a Google Sheet link first.']);
        }

        try {
            StockSync::fromGoogleSheet($url, 'manual');
        } catch (Throwable $e) {
            return back()->withErrors(['sheet_url' => $e->getMessage()]);
        }

        return back()->with('status', 'Stock synced from the Google Sheet.');
    }
}
