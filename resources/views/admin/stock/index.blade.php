@extends('admin.layouts.app')

@section('title', 'Stock Update')
@section('heading', 'Stock Update')
@section('subheading', 'Upload the supplier master sheet — stock is matched to products by title')

@section('content')

{{-- loading overlay --}}
<div data-loading-overlay class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/70 backdrop-blur-sm">
    <div class="mx-4 max-w-sm rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-8 text-center shadow-2xl">
        <svg class="mx-auto h-12 w-12 animate-spin text-slate-200" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4z"></path>
        </svg>
        <h3 class="mt-5 text-lg font-bold text-white">Updating stock…</h3>
        <p class="mt-2 text-sm text-slate-400">Please keep this tab open and don't refresh.</p>
    </div>
</div>

<div class="mx-auto max-w-3xl space-y-6">

    @if ($result)
        <div class="rounded-2xl border border-emerald-400/30 bg-emerald-500/10 p-6 shadow-sm">
            @php $at = \Illuminate\Support\Carbon::parse($result['at']); @endphp
            <h2 class="font-bold text-white">Last sync: {{ $result['source'] }}</h2>
            <p class="mt-1 text-xs text-slate-400">
                {{ $at->format('M j, Y g:i A') }} ({{ $at->diffForHumans() }}) ·
                {{ ['upload' => 'File upload', 'manual' => 'Sync Now', 'auto' => 'Auto sync'][$result['trigger']] ?? $result['trigger'] }}
            </p>
            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-xl bg-white/5 p-3"><p class="text-xs text-slate-400">Products in file</p><p class="text-xl font-bold text-white">{{ number_format($result['rows']) }}</p></div>
                <div class="rounded-xl bg-white/5 p-3"><p class="text-xs text-slate-400">In stock</p><p class="text-xl font-bold text-emerald-300">{{ number_format($result['in_stock']) }}</p></div>
                <div class="rounded-xl bg-white/5 p-3"><p class="text-xs text-slate-400">Out of stock</p><p class="text-xl font-bold text-rose-300">{{ number_format($result['out_of_stock']) }}</p></div>
                <div class="rounded-xl bg-white/5 p-3"><p class="text-xs text-slate-400">Not found</p><p class="text-xl font-bold text-amber-300">{{ number_format($result['unmatched_count']) }}</p></div>
                @if (isset($result['matched_by_title']))
                    <div class="rounded-xl bg-white/5 p-3"><p class="text-xs text-slate-400">Matched by title</p><p class="text-xl font-bold text-sky-300">{{ number_format($result['matched_by_title']) }}</p></div>
                @endif
                @if (! empty($result['matched_by_sku']))
                    <div class="rounded-xl bg-white/5 p-3"><p class="text-xs text-slate-400">Matched by SKU</p><p class="text-xl font-bold text-slate-300">{{ number_format($result['matched_by_sku']) }}</p></div>
                @endif
                @if (! empty($result['skipped']))
                    <div class="rounded-xl bg-white/5 p-3"><p class="text-xs text-slate-400">No stock value</p><p class="text-xl font-bold text-slate-300">{{ number_format($result['skipped']) }}</p></div>
                @endif
            </div>
            <p class="mt-4 text-xs text-slate-400">
                Sheets read:
                @foreach ($result['sheets'] as $sheet)
                    <span class="font-semibold text-slate-200">{{ $sheet['name'] }}</span> ({{ number_format($sheet['rows']) }}){{ $loop->last ? '' : ',' }}
                @endforeach
            </p>
            @if ($result['unmatched_count'])
                <details class="mt-4">
                    <summary class="cursor-pointer text-sm font-semibold text-amber-300">
                        Rows with no matching product{{ $result['unmatched_count'] > count($result['unmatched']) ? ' (first '.count($result['unmatched']).')' : '' }}
                    </summary>
                    <p class="mt-2 max-h-60 overflow-y-auto rounded-lg bg-slate-950/50 p-3 font-mono text-xs leading-relaxed text-slate-300">{{ implode(', ', $result['unmatched']) }}</p>
                </details>
            @endif
        </div>
    @endif

    {{-- Google Sheet --}}
    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <h2 class="font-bold text-white">Google Sheet</h2>
        <p class="mt-1 text-sm text-slate-400">Paste the sheet link once. All tabs are downloaded and synced. The sheet must be shared as <strong>“Anyone with the link – Viewer”</strong>.</p>

        <form method="POST" action="{{ route('admin.stock.sheet') }}" class="mt-5 space-y-4">
            @csrf
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-100">Sheet link</label>
                <input type="url" name="sheet_url" value="{{ old('sheet_url', $sheetUrl) }}" placeholder="https://docs.google.com/spreadsheets/d/…/edit"
                       class="w-full rounded-lg border border-white/10 bg-slate-950/50 px-3 py-2.5 text-sm text-slate-100 placeholder:text-slate-500 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                @error('sheet_url')
                    <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap items-center gap-3 rounded-xl bg-white/5 p-4">
                <label class="flex items-center gap-2 text-sm font-semibold text-slate-100">
                    <input type="checkbox" name="auto" value="1" @checked(old('auto', $auto)) class="h-4 w-8 rounded-full border-white/10 bg-slate-950/50 text-slate-100 focus:ring-slate-400">
                    Auto sync {{ $auto ? 'ON' : 'OFF' }} — every
                </label>
                <select name="hours" class="rounded-lg border border-white/10 bg-slate-950/50 px-3 py-2 text-sm text-slate-100 outline-none focus:border-slate-400">
                    @foreach ([1, 2, 3, 6, 12, 24] as $h)
                        <option value="{{ $h }}" @selected((int) old('hours', $hours) === $h)>{{ $h }} {{ Str::plural('hour', $h) }}</option>
                    @endforeach
                </select>
                <p class="w-full text-xs text-slate-400">Leave off to sync only with the “Sync Now” button. Auto sync needs the Laravel scheduler running (<code class="font-mono">php artisan schedule:run</code> every minute).</p>
            </div>

            <div class="flex justify-end">
                <button class="rounded-lg border border-white/15 px-5 py-2.5 text-sm font-semibold text-slate-100 transition hover:bg-white/10">Save settings</button>
            </div>
        </form>

        @if ($sheetUrl)
            <form method="POST" action="{{ route('admin.stock.sync') }}" data-loading-form class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-white/10 pt-4">
                @csrf
                <a href="{{ $sheetUrl }}" target="_blank" rel="noopener" class="text-sm font-semibold text-slate-300 hover:text-white">Open sheet ↗</a>
                <button class="rounded-lg bg-white px-6 py-2.5 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-200">Sync Now</button>
            </form>
        @endif
    </div>

    <form method="POST" action="{{ route('admin.stock.update') }}" enctype="multipart/form-data" data-loading-form class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        @csrf
        <h2 class="font-bold text-white">Or upload a file</h2>
        <p class="mt-1 text-sm text-slate-400">Excel (.xlsx) with one or more supplier sheets, or a .csv. Each sheet needs a <strong>Product Title</strong> (or SKU / Item ID) column and a <strong>Stock</strong> column.</p>

        <label class="mt-5 flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-white/10 bg-white/5 px-6 py-10 text-center transition hover:border-slate-400 hover:bg-white/10">
            <x-icon name="refresh" class="h-10 w-10 text-slate-400" />
            <span class="mt-3 text-sm font-semibold text-slate-200">Click to choose a file</span>
            <span class="mt-1 text-xs text-slate-400">.xlsx or .csv up to 50MB</span>
            <input type="file" name="file" accept=".xlsx,.csv" required class="mt-4 text-sm" onchange="this.previousElementSibling.previousElementSibling.textContent = this.files[0]?.name || 'Click to choose a file'">
        </label>
        @error('file')
            <p class="mt-2 text-sm text-rose-300">{{ $message }}</p>
        @enderror

        <div class="mt-6 flex justify-end">
            <button class="rounded-lg bg-white px-6 py-2.5 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-200">Update Stock</button>
        </div>
    </form>

    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <h2 class="font-bold text-white">How it works</h2>
        <ul class="mt-3 list-disc space-y-1.5 pl-5 text-sm text-slate-300">
            <li>Each row is matched to a product by <strong>product title</strong> (case and spacing are ignored). If the title is not one of ours, the row's SKU / Item ID is tried next.</li>
            <li><strong>Stock above 0</strong> → that quantity is saved and the product is marked <span class="text-emerald-300">In stock</span>.</li>
            <li><strong>Stock 0 or blank</strong> → the product is marked <span class="text-rose-300">Out of stock</span>.</li>
            <li>If the same SKU is on more than one sheet, the highest stock is used.</li>
            <li>The “All Inventory” tab is never synced, and other tabs without a Stock column are skipped. Products not in the file are not changed.</li>
            <li>A Stock cell showing a formula error (<code class="font-mono">#N/A</code>, <code class="font-mono">#REF!</code>) or “n/a” means <em>unknown</em>, so that product is left exactly as it is — only a real 0 or an empty cell marks it out of stock.</li>
        </ul>
    </div>
</div>
@endsection
