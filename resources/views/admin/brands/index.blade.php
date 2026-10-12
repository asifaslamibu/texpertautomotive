@extends('admin.layouts.app')

@section('title', 'Brands')
@section('heading', 'Brands')
@section('subheading', 'Upload brand logos shown on the storefront')

@section('content')

<div class="mb-5 flex flex-wrap items-center gap-3">
    <form method="GET" class="relative flex-1 sm:max-w-xs">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search brands…"
               class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 py-2 pl-9 pr-3 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
        <span class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400"><x-icon name="search" class="h-4 w-4" /></span>
    </form>
    @if (request('q'))
        <a href="{{ route('admin.brands.index') }}" class="text-sm font-semibold text-slate-400 hover:text-white">Reset</a>
    @endif
    <a href="{{ route('admin.brands.create') }}" class="ml-auto rounded-lg bg-white px-4 py-2 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-200">+ Add Brand</a>
</div>

@if (request('q'))
    <p class="mb-3 text-sm text-slate-400">{{ $brands->total() }} result(s) for "<span class="font-semibold text-slate-200">{{ request('q') }}</span>"</p>
@endif

<div data-bulk class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur shadow-sm">
    <x-bulk-bar :action="route('admin.brands.bulk-destroy')">
        {{-- badge actions for the checked brands --}}
        <span class="flex flex-wrap items-center gap-2 border-l border-white/10 pl-3">
            <select data-bulk-tag-mode data-bulk-name="mode"
                    class="rounded-lg border border-white/10 bg-slate-950/60 px-2.5 py-1.5 text-xs font-semibold text-slate-100 outline-none focus:border-slate-400">
                <option value="set">Set badge</option>
                <option value="off">Switch badge off</option>
                <option value="clear">Clear badge</option>
            </select>
            <input data-bulk-tag data-bulk-name="tag" maxlength="60" placeholder="Badge text…"
                   class="w-48 rounded-lg border border-white/10 bg-slate-950/60 px-2.5 py-1.5 text-xs text-slate-100 placeholder:text-slate-500 outline-none focus:border-slate-400">
            <button type="button" data-bulk-submit
                    data-bulk-action="{{ route('admin.brands.bulk-tag') }}"
                    data-bulk-method="PATCH"
                    data-bulk-fields="[data-bulk-tag-mode], [data-bulk-tag]"
                    data-bulk-confirm="Apply this badge change to %n brand(s) and all their products?"
                    class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-900 transition hover:bg-white">
                Apply
            </button>
        </span>
    </x-bulk-bar>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-white/10 bg-white/5 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                <tr>
                    <th class="px-4 py-3"><input type="checkbox" data-bulk-all class="rounded border-white/20 bg-transparent"></th>
                    <th class="px-4 py-3">Logo</th>
                    <x-th-sort column="name" label="Name" />
                    <x-th-sort column="featured" label="Popular Brands" />
                    <x-th-sort column="in_marquee" label="Slider" />
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @forelse ($brands as $brand)
                    <tr class="hover:bg-white/5">
                        <td class="px-4 py-3"><input type="checkbox" name="ids[]" value="{{ $brand->id }}" data-bulk-item class="rounded border-white/20 bg-transparent"></td>
                        <td class="px-4 py-3">
                            <div class="flex h-12 w-16 items-center justify-center rounded-lg border border-white/10 bg-white/5 p-1.5">
                                @if ($brand->logo)
                                    <img src="{{ media($brand->logo) }}" alt="{{ $brand->name }}" class="max-h-full max-w-full object-contain">
                                @else
                                    <span class="text-[10px] font-black uppercase tracking-tight text-slate-400">{{ \Illuminate\Support\Str::limit($brand->name, 10) }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-bold text-slate-100">{{ $brand->name }}</p>
                            <p class="text-xs text-slate-400">{{ $brand->slug }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.brands.toggle', [$brand, 'featured']) }}" data-brand-toggle>
                                @csrf @method('PATCH')
                                <button type="submit" data-toggle-pill class="rounded-full border px-3 py-1 text-[11px] font-bold {{ $brand->featured ? 'border-emerald-400/30 bg-emerald-500/15 text-emerald-300' : 'border-white/10 bg-white/5 text-slate-400' }}">{{ $brand->featured ? 'On' : 'Off' }}</button>
                            </form>
                        </td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.brands.toggle', [$brand, 'in_marquee']) }}" data-brand-toggle>
                                @csrf @method('PATCH')
                                <button type="submit" data-toggle-pill class="rounded-full border px-3 py-1 text-[11px] font-bold {{ $brand->in_marquee ? 'border-emerald-400/30 bg-emerald-500/15 text-emerald-300' : 'border-white/10 bg-white/5 text-slate-400' }}">{{ $brand->in_marquee ? 'On' : 'Off' }}</button>
                            </form>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.brands.edit', $brand) }}" class="rounded-lg border border-white/10 px-3 py-1.5 text-xs font-semibold text-slate-300 hover:border-white/15 hover:text-white">Edit</a>
                                <form method="POST" action="{{ route('admin.brands.destroy', $brand) }}" onsubmit="return confirm('Delete this brand?')">
                                    @csrf @method('DELETE')
                                    <button class="rounded-lg border border-white/10 px-2.5 py-1.5 text-xs font-semibold text-rose-300 hover:border-rose-400/30 hover:bg-rose-500/15" aria-label="Delete">&times;</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-slate-400">No brands yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6">{{ $brands->links('pagination.admin') }}</div>

@push('scripts')
<script>
// The badge text only applies to "Set badge" — grey it out for the other modes.
(function () {
    const mode = document.querySelector('[data-bulk-tag-mode]');
    const text = document.querySelector('[data-bulk-tag]');
    if (!mode || !text) return;

    const sync = () => {
        const set = mode.value === 'set';
        text.disabled = ! set;
        text.classList.toggle('opacity-40', ! set);
    };
    mode.addEventListener('change', sync);
    sync();
})();
</script>
@endpush

@endsection
