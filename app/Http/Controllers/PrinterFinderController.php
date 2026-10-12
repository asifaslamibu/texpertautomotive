<?php

namespace App\Http\Controllers;

use App\Support\PrinterCompatibility;
use Illuminate\Http\Request;

class PrinterFinderController extends Controller
{
    /** Landing page: pick your printer. */
    public function index()
    {
        $models = PrinterCompatibility::models();

        return view('printer-finder.index', [
            'families'    => $models->groupBy('family'),
            'modelCount'  => $models->count(),
            'allModels'   => $models,
        ]);
    }

    /** Results page: everything that fits the chosen printer. */
    public function show(Request $request, string $model)
    {
        $entry = PrinterCompatibility::findBySlug($model);
        abort_if(! $entry, 404);

        $products = PrinterCompatibility::productsFor($entry['label']);

        // Filter chips are built from what this printer actually has, so we never
        // offer a bucket that would come back empty.
        $buckets = $products
            ->groupBy(fn ($p) => PrinterCompatibility::bucket($p))
            ->map->count()
            ->sortDesc();

        $activeType = $request->query('type');
        if ($activeType && $buckets->has($activeType)) {
            $products = $products->filter(fn ($p) => PrinterCompatibility::bucket($p) === $activeType)->values();
        } else {
            $activeType = null;
        }

        return view('printer-finder.show', [
            'model'      => $entry,
            'products'   => $products,
            'buckets'    => $buckets,
            'activeType' => $activeType,
            'totalCount' => $buckets->sum(),
        ]);
    }
}
