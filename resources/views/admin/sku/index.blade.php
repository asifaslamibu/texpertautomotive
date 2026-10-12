@extends('admin.layouts.app')

@section('title', 'SKU Update')
@section('heading', 'SKU Update')
@section('subheading', 'Fix product SKUs from a sheet — rows are matched to products by title')

@section('content')

{{-- loading overlay --}}
<div data-loading-overlay class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/70 backdrop-blur-sm">
    <div class="mx-4 max-w-sm rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-8 text-center shadow-2xl">
        <svg class="mx-auto h-12 w-12 animate-spin text-slate-200" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4z"></path>
        </svg>
        <h3 class="mt-5 text-lg font-bold text-white">Reading the sheet…</h3>
        <p class="mt-2 text-sm text-slate-400">Please keep this tab open and don't refresh.</p>
    </div>
</div>

<div class="mx-auto max-w-3xl space-y-6">

    @if ($result)
        <div class="rounded-2xl border p-6 shadow-sm {{ $result['dry_run'] ? 'border-amber-400/30 bg-amber-500/10' : 'border-emerald-400/30 bg-emerald-500/10' }}">
            <h2 class="font-bold text-white">
                {{ $result['dry_run'] ? 'Preview' : 'Applied' }} — “{{ $result['file'] }}”
            </h2>
            <p class="mt-1 text-xs text-slate-400">
                {{ $result['dry_run']
                    ? 'Nothing was saved. Check the changes below, then upload again with “Apply changes” ticked.'
                    : 'These SKUs are now saved on the products.' }}
            </p>

            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-xl bg-white/5 p-3"><p class="text-xs text-slate-400">Rows in file</p><p class="text-xl font-bold text-white">{{ number_format($result['rows']) }}</p></div>
                <div class="rounded-xl bg-white/5 p-3"><p class="text-xs text-slate-400">{{ $result['dry_run'] ? 'Would change' : 'SKUs changed' }}</p><p class="text-xl font-bold text-sky-300">{{ number_format($result['changed']) }}</p></div>
                <div class="rounded-xl bg-white/5 p-3"><p class="text-xs text-slate-400">Already correct</p><p class="text-xl font-bold text-emerald-300">{{ number_format($result['unchanged']) }}</p></div>
                <div class="rounded-xl bg-white/5 p-3"><p class="text-xs text-slate-400">Title not found</p><p class="text-xl font-bold text-amber-300">{{ number_format($result['unmatched']) }}</p></div>
            </div>

            @if ($result['conflicts'])
                <p class="mt-3 text-xs text-amber-200">{{ number_format($result['conflicts']) }} row(s) skipped: the same title appeared more than once with different SKUs.</p>
            @endif

            <p class="mt-4 text-xs text-slate-400">
                Sheets read:
                @foreach ($result['sheets'] as $sheet)
                    <span class="font-semibold text-slate-200">{{ $sheet['name'] }}</span> ({{ number_format($sheet['rows']) }}){{ $loop->last ? '' : ',' }}
                @endforeach
            </p>

            @if ($result['samples'])
                <details class="mt-4" open>
                    <summary class="cursor-pointer text-sm font-semibold text-sky-300">
                        {{ $result['dry_run'] ? 'Changes that would be made' : 'Changes made' }}
                        ({{ $result['changed'] > count($result['samples']) ? 'first '.count($result['samples']) : count($result['samples']) }})
                    </summary>
                    <div class="mt-2 max-h-72 overflow-y-auto rounded-lg bg-slate-950/50 p-3">
                        <table class="w-full text-xs">
                            <thead class="text-left text-slate-400">
                                <tr><th class="pb-2 pr-3">Product title</th><th class="pb-2 pr-3">Old SKU</th><th class="pb-2">New SKU</th></tr>
                            </thead>
                            <tbody class="divide-y divide-white/5 font-mono text-slate-300">
                                @foreach ($result['samples'] as $row)
                                    <tr>
                                        <td class="py-1.5 pr-3 font-sans">{{ \Illuminate\Support\Str::limit($row['title'], 60) }}</td>
                                        <td class="py-1.5 pr-3 text-rose-300">{{ $row['from'] ?: '—' }}</td>
                                        <td class="py-1.5 text-emerald-300">{{ $row['to'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @endif

            @if ($result['unmatched'])
                <details class="mt-3">
                    <summary class="cursor-pointer text-sm font-semibold text-amber-300">
                        Titles with no matching product{{ $result['unmatched'] > count($result['unmatched_titles']) ? ' (first '.count($result['unmatched_titles']).')' : '' }}
                    </summary>
                    <ul class="mt-2 max-h-60 list-inside list-disc overflow-y-auto rounded-lg bg-slate-950/50 p-3 text-xs leading-relaxed text-slate-300">
                        @foreach ($result['unmatched_titles'] as $title)<li>{{ $title }}</li>@endforeach
                    </ul>
                </details>
            @endif
        </div>
    @endif

    <form method="POST" action="{{ route('admin.sku.update') }}" enctype="multipart/form-data" data-loading-form class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        @csrf
        <h2 class="font-bold text-white">Upload a sheet</h2>
        <p class="mt-1 text-sm text-slate-400">Excel (.xlsx, all tabs) or .csv. Each sheet needs a <strong>Product Title</strong> column and an <strong>SKU / Item ID</strong> column.</p>

        <label class="mt-5 flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-white/10 bg-white/5 px-6 py-10 text-center transition hover:border-slate-400 hover:bg-white/10">
            <x-icon name="edit" class="h-10 w-10 text-slate-400" />
            <span class="mt-3 text-sm font-semibold text-slate-200">Click to choose a file</span>
            <span class="mt-1 text-xs text-slate-400">.xlsx or .csv up to 50MB</span>
            <input type="file" name="file" accept=".xlsx,.csv" required class="mt-4 text-sm" onchange="this.previousElementSibling.previousElementSibling.textContent = this.files[0]?.name || 'Click to choose a file'">
        </label>
        @error('file')<p class="mt-2 text-sm text-rose-300">{{ $message }}</p>@enderror

        <label class="mt-5 flex items-start gap-3 rounded-xl bg-white/5 p-4">
            <input type="checkbox" name="apply" value="1" class="mt-0.5 rounded border-white/10 text-slate-200">
            <span>
                <span class="block text-sm font-semibold text-slate-100">Apply changes</span>
                <span class="block text-xs text-slate-400">Leave this off for a preview first — you'll see exactly which SKUs would change, and nothing is saved.</span>
            </span>
        </label>

        <div class="mt-6 flex justify-end">
            <button class="rounded-lg bg-white px-6 py-2.5 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-200">Read sheet</button>
        </div>
    </form>

    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <h2 class="font-bold text-white">How it works</h2>
        <ul class="mt-3 list-disc space-y-1.5 pl-5 text-sm text-slate-300">
            <li>Each row is matched to a product by <strong>title</strong> — case and extra spaces are ignored, nothing else is guessed.</li>
            <li>The product's SKU is replaced with the sheet's SKU. A product whose SKU already matches is left alone.</li>
            <li>If the same title appears twice with different SKUs, both rows are skipped rather than guessing.</li>
            <li>Titles that match no product are listed so you can fix the wording on either side.</li>
            <li>The “All Inventory” tab is skipped, like it is for stock.</li>
        </ul>
    </div>
</div>

@endsection
