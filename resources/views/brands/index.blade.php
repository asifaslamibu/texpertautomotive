@extends('layouts.app')

@section('title', 'All Brands — Texpert Deals')
@section('meta_description', 'Browse all brands at Texpert Deals and shop products by your favourite manufacturer — bulk pricing, fast shipping and expert support.')

@section('content')

@include('pages._hero', [
    'eyebrow' => 'Shop by brand',
    'title' => 'All Brands',
    'subtitle' => 'Browse every brand we carry and jump straight to their products. '.number_format($brands->count()).' brands · '.number_format($totalProducts).' products.',
])

<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6" data-brands>

    {{-- search + jump bar --}}
    <div class="sticky top-[68px] z-20 -mx-4 mb-10 border-y border-slate-200 bg-white/95 px-4 py-3 backdrop-blur sm:mx-0 sm:rounded-2xl sm:border sm:px-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400">
                    <x-icon name="search" class="h-4 w-4" />
                </span>
                <input type="search" data-brand-search autocomplete="off" placeholder="Search brands…"
                       class="w-full rounded-full border border-slate-300 bg-slate-50 py-2.5 pl-10 pr-4 text-sm outline-none transition focus:border-amber-400 focus:bg-white focus:ring-4 focus:ring-amber-100">
            </div>
            <div class="hidden flex-wrap gap-1 lg:flex" data-alpha-nav>
                @foreach ($grouped->keys() as $letter)
                    <a href="#brand-{{ $letter }}" class="grid h-7 w-7 place-items-center rounded-md text-xs font-bold text-slate-500 transition hover:bg-amber-50 hover:text-amber-600">{{ $letter }}</a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- featured brands (with logos) --}}
    @if ($featured->isNotEmpty())
        <section class="mb-12" data-featured-brands>
            <h2 class="mb-5 text-lg font-black tracking-tight text-slate-900">Popular brands</h2>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                @foreach ($featured as $brand)
                    <a href="{{ $brand->url }}" data-brand-card data-brand-name="{{ mb_strtolower($brand->name) }}"
                       class="group flex flex-col items-center rounded-2xl border border-slate-200 bg-white p-5 text-center shadow-sm transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-lg">
                        <span class="grid h-16 w-full place-items-center">
                            <img src="{{ media($brand->logo) }}" alt="{{ $brand->name }}" class="max-h-12 max-w-full object-contain transition group-hover:scale-105">
                        </span>
                        <span class="mt-3 line-clamp-1 text-sm font-bold text-slate-800 group-hover:text-amber-600">{{ $brand->name }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- full A–Z directory --}}
    <section data-all-brands>
        <h2 class="mb-5 text-lg font-black tracking-tight text-slate-900">All brands A–Z</h2>

        @forelse ($grouped as $letter => $items)
            <div class="mb-8 scroll-mt-32" id="brand-{{ $letter }}" data-letter-group>
                <div class="mb-3 flex items-center gap-3">
                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-slate-900 text-sm font-black text-white">{{ $letter }}</span>
                    <span class="h-px flex-1 bg-slate-200"></span>
                </div>
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($items as $brand)
                        <a href="{{ $brand->url }}" data-brand-card data-brand-name="{{ mb_strtolower($brand->name) }}"
                           class="group flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 transition hover:border-amber-300 hover:bg-amber-50/40">
                            <span class="flex min-w-0 items-center gap-3.5">
                                @if ($brand->logo)
                                    <span class="grid h-14 w-16 shrink-0 place-items-center overflow-hidden rounded-lg border border-slate-100 bg-white p-1.5">
                                        <img src="{{ media($brand->logo) }}" alt="{{ $brand->name }}" class="max-h-full max-w-full object-contain">
                                    </span>
                                @else
                                    <span class="grid h-14 w-16 shrink-0 place-items-center rounded-lg bg-slate-100 text-lg font-black text-slate-500">{{ mb_strtoupper(mb_substr($brand->name, 0, 1)) }}</span>
                                @endif
                                <span class="truncate text-sm font-semibold text-slate-800 group-hover:text-amber-600">{{ $brand->name }}</span>
                            </span>
                            <span class="shrink-0 text-xs font-medium text-slate-400">{{ $brand->count }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-16 text-center text-slate-400">No brands yet — add brands in the admin or import products to populate this page.</p>
        @endforelse

        {{-- empty state when search filters everything out --}}
        <p data-brands-empty class="hidden rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-16 text-center text-slate-400">
            No brands match your search.
        </p>
    </section>
</div>

@push('scripts')
<script>
(function () {
    const root = document.querySelector('[data-brands]');
    if (!root) return;
    const input = root.querySelector('[data-brand-search]');
    const cards = Array.from(root.querySelectorAll('[data-brand-card]'));
    const groups = Array.from(root.querySelectorAll('[data-letter-group]'));
    const featured = root.querySelector('[data-featured-brands]');
    const emptyMsg = root.querySelector('[data-brands-empty]');

    function apply() {
        const q = input.value.trim().toLowerCase();
        let any = false;

        cards.forEach((c) => {
            const match = !q || c.dataset.brandName.includes(q);
            c.classList.toggle('hidden', !match);
            if (match) any = true;
        });

        // hide letter groups with no visible card; hide "Popular" while searching
        groups.forEach((g) => {
            const visible = g.querySelector('[data-brand-card]:not(.hidden)');
            g.classList.toggle('hidden', !visible);
        });
        if (featured) featured.classList.toggle('hidden', q.length > 0);

        if (emptyMsg) emptyMsg.classList.toggle('hidden', any);
    }

    input.addEventListener('input', apply);
})();
</script>
@endpush

@endsection
