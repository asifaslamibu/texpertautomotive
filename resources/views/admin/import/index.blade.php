@extends('admin.layouts.app')

@section('title', 'Import Products')
@section('heading', 'Import Products')
@section('subheading', 'Bulk-import from the master product sheet (or a WooCommerce CSV export)')

@section('content')

<div class="mb-5 flex justify-end">
    <a href="{{ route('admin.import.history') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-white/15 px-4 py-2 text-sm font-semibold text-slate-100 transition hover:bg-white/10">
        <x-icon name="refresh" class="h-4 w-4" /> Import History
    </a>
</div>

{{-- import loading overlay --}}
<div data-loading-overlay class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/70 backdrop-blur-sm">
    <div class="mx-4 max-w-sm rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-8 text-center shadow-2xl">
        <svg class="mx-auto h-12 w-12 animate-spin text-slate-200" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4z"></path>
        </svg>
        <h3 class="mt-5 text-lg font-bold text-white">Importing products…</h3>
        <p class="mt-2 text-sm text-slate-400">This can take a few minutes for large files with image downloads. Please keep this tab open and don't refresh.</p>
        <div class="mt-4 h-1.5 w-full overflow-hidden rounded-full bg-white/5">
            <div class="h-full w-1/3 animate-pulse rounded-full bg-white"></div>
        </div>
    </div>
</div>

<div class="mx-auto max-w-3xl space-y-6">

    {{-- uploader --}}
    <form method="POST" action="{{ route('admin.import.run') }}" enctype="multipart/form-data" data-loading-form class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        @csrf
        <h2 class="font-bold text-white">Upload CSV file</h2>
        <p class="mt-1 text-sm text-slate-400">Upload the master product sheet (the one with Brand, SKU, Product Title, Main/Sub/Child Category, Price, Description, Image Folder Link).</p>
        <p class="mt-1 text-xs text-slate-200">Got an Excel file (.xlsx)? In Excel: <strong>File → Save As → “CSV UTF-8 (Comma delimited) (*.csv)”</strong>, then upload that .csv.</p>

        <label class="mt-5 flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-white/10 bg-white/5 px-6 py-10 text-center transition hover:border-slate-400 hover:bg-white/10">
            <x-icon name="box" class="h-10 w-10 text-slate-400" />
            <span class="mt-3 text-sm font-semibold text-slate-200">Click to choose a CSV file</span>
            <span class="mt-1 text-xs text-slate-400">.csv up to 20MB</span>
            <input type="file" name="csv" accept=".csv,text/csv" required class="mt-4 text-sm" onchange="this.previousElementSibling.previousElementSibling.textContent = this.files[0]?.name || 'Click to choose a CSV file'">
        </label>

        <div class="mt-5">
            <label class="mb-1.5 block text-sm font-semibold text-slate-100">Put all products under one main category (optional)</label>
            <input type="text" name="main_category" value="{{ old('main_category') }}" placeholder="e.g. IT & Communication"
                   class="w-full rounded-lg border border-white/10 bg-slate-950/50 px-3 py-2.5 text-sm text-slate-100 placeholder:text-slate-500 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            <p class="mt-1 text-xs text-slate-400">If set, every imported product is nested under this category (<em>Main &gt; the product's most specific category</em>). Leave blank to use the CSV's category tree as-is.</p>
        </div>

        <label class="mt-5 flex items-start gap-3 rounded-xl bg-white/5 p-4">
            <input type="checkbox" name="download_images" value="1" class="mt-0.5 rounded border-white/10 text-slate-200">
            <span>
                <span class="block text-sm font-semibold text-slate-100">Download images to this server</span>
                <span class="block text-xs text-slate-400">Fetches each product's image URL and saves it under /uploads/products. Leave this <strong>off</strong> for large files — it is slow and can time the import out; the remote URLs are used directly instead.</span>
            </span>
        </label>

        <p class="mt-4 flex items-start gap-2 rounded-xl border border-amber-400/30 bg-amber-500/10 p-3.5 text-xs text-amber-200">
            <span class="font-bold">Large file?</span>
            <span>On shared hosting a single import must finish within about a minute. Keep files to roughly <strong>500 rows</strong> per upload (split a big sheet into a few files) and leave “Download images” off. Re-importing is safe — rows are matched by <code class="font-mono">ItemID</code>, so nothing duplicates.</span>
        </p>

        <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-4">
                <a href="{{ route('admin.import.template') }}" class="text-sm font-semibold text-slate-200 hover:text-white">↓ Download sample template</a>
                <a href="{{ route('admin.products.export') }}" class="text-sm font-semibold text-amber-300 hover:text-amber-200">⇩ Export all products (CSV)</a>
            </div>
            <button class="rounded-lg bg-white px-6 py-2.5 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-200">Import Products</button>
        </div>
    </form>

    {{-- how it works --}}
    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <h2 class="font-bold text-white">How it maps your data</h2>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                    <tr><th class="py-2 pr-4">CSV column</th><th class="py-2">Maps to</th></tr>
                </thead>
                <tbody class="divide-y divide-white/10 text-slate-300">
                    <tr><td class="py-2 pr-4 font-mono text-xs">Product Title</td><td class="py-2">Product name</td></tr>
                    <tr><td class="py-2 pr-4 font-mono text-xs">ItemID</td><td class="py-2">SKU (matched on re-import to update)</td></tr>
                    <tr><td class="py-2 pr-4 font-mono text-xs">Brand</td><td class="py-2">Brand (auto-created if new)</td></tr>
                    <tr><td class="py-2 pr-4 font-mono text-xs">SELL PRICE</td><td class="py-2">Price (a leading <code>$</code> is fine)</td></tr>
                    <tr><td class="py-2 pr-4 font-mono text-xs">Main / Sub / Child Category</td><td class="py-2"><code>Main &gt; Sub &gt; Child</code> → 3-level tree (auto-created)</td></tr>
                    <tr><td class="py-2 pr-4 font-mono text-xs">ImageURL</td><td class="py-2">Main product image (comma-separate several URLs for a gallery)</td></tr>
                    <tr><td class="py-2 pr-4 font-mono text-xs">Dimensions</td><td class="py-2">Product dimensions (e.g. <code>15 x 0.30 x 0.08</code>)</td></tr>
                    <tr><td class="py-2 pr-4 font-mono text-xs">Weight</td><td class="py-2">Product weight (e.g. <code>1.36 Lbs</code>)</td></tr>
                    <tr><td class="py-2 pr-4 font-mono text-xs">Description</td><td class="py-2">Product description</td></tr>
                    <tr><td class="py-2 pr-4 font-mono text-xs">UPCCode</td><td class="py-2">Barcode / UPC</td></tr>
                    <tr><td class="py-2 pr-4 font-mono text-xs">UOM</td><td class="py-2">Unit of measure (e.g. <code>Pack</code>, <code>Each</code>)</td></tr>
                </tbody>
            </table>
        </div>
        <p class="mt-4 rounded-lg bg-white/10 px-4 py-3 text-xs text-slate-200">
            <strong>Tip:</strong> Categories come from three columns — <code class="font-mono">Main Category</code>, <code class="font-mono">Sub Category</code>, <code class="font-mono">Child Category</code> — which auto-create the full tree (e.g. <code class="font-mono">Electrical &gt; Wiring &amp; Connectivity &gt; Electrical Products</code>). The <em>ImageURL</em> column is used as the product's main image; put several image URLs separated by commas for a gallery. Rows are matched by <code class="font-mono">ItemID</code>, so re-importing the same file updates products instead of duplicating them. Download the sample for the exact format.
        </p>
    </div>
</div>

@endsection
