@extends('layouts.app')

@php
    // Titles/descriptions describe whichever slice of the catalogue is on screen —
    // a category, a brand filter or a search — so each listing reads distinctly
    // instead of sharing one generic line.
    $seoSite = \App\Support\Seo::siteName();
    $seoCount = number_format($products->total());

    if ($activeCategory) {
        $seoSubject = $activeCategory->name;
        $seoDesc = $activeCategory->tagline
            ?: "Shop {$seoCount} {$activeCategory->name} products at {$seoSite} — genuine, warranty-backed parts with fast US shipping and expert support.";
    } elseif (request('brand')) {
        $seoSubject = request('brand').' Products';
        $seoDesc = "Shop {$seoCount} genuine ".request('brand')." products at {$seoSite} — warranty-backed parts with fast US shipping and expert support.";
    } elseif (request('q')) {
        $seoSubject = 'Search: '.request('q');
        $seoDesc = "{$seoCount} results for “".request('q')."” at {$seoSite} — genuine car audio, electronics and accessories, shipped fast.";
    } else {
        $seoSubject = 'All Products';
        $seoDesc = "Shop {$seoCount} car audio, electronics and accessories at {$seoSite} — genuine, warranty-backed products with fast US shipping.";
    }
@endphp

@section('title', \App\Support\Seo::title($seoSubject))
@section('meta_description', \App\Support\Seo::clamp($seoDesc))
{{-- Filtered, sorted, paged and search views are near-duplicates of the clean
     listing, so they are followed but kept out of the index. --}}
@if (request()->hasAny(['brands', 'min_price', 'max_price', 'rating', 'sort', 'per_page', 'page', 'q']))
    @section('robots', 'noindex, follow')
@endif

@section('content')

{{-- breadcrumb / heading --}}
<section class="relative overflow-hidden bg-[#111] text-white">
    <div class="pointer-events-none absolute -left-16 top-0 h-full w-40 -skew-x-[24deg] bg-gradient-to-b from-amber-500/0 via-amber-500/25 to-amber-500/0"></div>
    <div class="pointer-events-none absolute -left-4 top-0 h-full w-2 -skew-x-[24deg] bg-amber-500/80"></div>
    <div class="relative mx-auto max-w-7xl px-4 py-7 sm:px-6">
        <nav class="flex items-center gap-2 text-sm text-white/60">
            <a href="{{ route('home') }}" class="hover:text-amber-500">Home</a>
            <span>/</span>
            <a href="{{ route('products.index') }}" class="hover:text-amber-500">Products</a>
            @if ($activeCategory)
                <span>/</span>
                <span class="font-semibold text-white">{{ $activeCategory->name }}</span>
            @endif
        </nav>
        <h1 class="mt-2 text-3xl font-black uppercase italic tracking-tight sm:text-4xl">
            {{ $activeCategory?->name ?? (request('q') ? 'Search: “'.request('q').'”' : 'All Products') }}
        </h1>
        <p class="mt-1 text-sm text-white/70">
            {{ $activeCategory?->tagline ?: number_format($products->total()).' products · Genuine brands · Fast shipping' }}
        </p>
    </div>
</section>

<section class="border-b border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">

        {{-- subcategories: a thumbnail + name list on mobile, circles on desktop --}}
        @if ($activeCategory && $activeCategory->visibleChildren->count())
            @php
                // long category trees run for screens on end: show the first few
                // and keep the rest behind "View more"
                $subcats = $activeCategory->visibleChildren;
                $mobileShown = 6;
            @endphp

            {{-- mobile list (below the heading) --}}
            <div data-subcats-list class="mt-4 divide-y divide-slate-200 border-y border-slate-200 lg:hidden">
                @foreach ($subcats as $i => $child)
                    <a href="{{ route('products.index', ['category' => $child->slug]) }}"
                       class="flex items-center gap-3 py-2 {{ $i >= $mobileShown ? 'hidden' : '' }}" @if ($i >= $mobileShown) data-subcat-extra @endif>
                        <span class="grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-lg bg-white ring-1 ring-slate-100">
                            @if ($child->sample_image)
                                <img src="{{ media($child->sample_image) }}" alt="{{ $child->name }}" loading="lazy" class="h-8 w-8 object-contain">
                            @else
                                <x-icon name="{{ $child->icon ?: 'box' }}" class="h-5 w-5 text-slate-400" />
                            @endif
                        </span>
                        <span class="min-w-0 flex-1 truncate text-sm font-bold uppercase tracking-wide text-slate-800">{{ $child->name }}</span>
                        <x-icon name="chevron" class="h-4 w-4 shrink-0 -rotate-90 text-slate-400" />
                    </a>
                @endforeach
            </div>
            @if ($subcats->count() > $mobileShown)
                <button type="button" data-subcats-toggle="list"
                        class="mt-3 w-full rounded-lg border border-slate-200 py-2 text-sm font-bold text-slate-700 transition hover:border-amber-400 hover:text-amber-600 lg:hidden">
                    View more ({{ $subcats->count() - $mobileShown }})
                </button>
            @endif

            {{-- desktop circles: one row, the rest behind the button --}}
            <div data-subcats-circles class="mt-6 hidden max-h-[11.5rem] flex-wrap justify-center gap-x-8 gap-y-6 overflow-hidden sm:justify-start lg:flex">
                @foreach ($subcats as $child)
                    <a href="{{ route('products.index', ['category' => $child->slug]) }}"
                       class="group flex w-28 flex-col items-center text-center">
                        <span class="grid h-24 w-28 place-items-center overflow-hidden rounded-md border border-slate-200 bg-white transition group-hover:-translate-y-1 group-hover:border-amber-500 group-hover:shadow-lg">
                            @if ($child->sample_image)
                                <img src="{{ media($child->sample_image) }}" alt="{{ $child->name }}" loading="lazy" class="h-20 w-20 object-contain">
                            @else
                                <x-icon name="{{ $child->icon ?: 'box' }}" class="h-10 w-10 text-slate-400" />
                            @endif
                        </span>
                        <span class="mt-3 text-xs font-bold uppercase tracking-wide leading-tight text-slate-700 transition group-hover:text-amber-600">{{ $child->name }}</span>
                    </a>
                @endforeach
            </div>
            <button type="button" data-subcats-toggle="circles" hidden
                    class="mx-auto mt-5 hidden rounded-full border border-slate-200 bg-white px-6 py-2 text-xs font-black uppercase tracking-wide text-slate-700 transition hover:border-amber-400 hover:text-amber-600 lg:block">
                View more
            </button>
            <div class="h-6"></div>
        @endif
    </div>
</section>

<div class="mx-auto max-w-7xl gap-6 px-4 py-8 sm:px-6 lg:flex">

    {{-- ===== Sidebar filters ===== --}}
    @php
        $hasFilters = $selectedBrands || $minPrice !== null || $maxPrice !== null || $inStockOnly || $minRating;
        $filterCount = count($selectedBrands) + ($minPrice !== null ? 1 : 0) + ($maxPrice !== null ? 1 : 0) + ($inStockOnly ? 1 : 0) + ($minRating ? 1 : 0);
        $clearUrl = route('products.index', array_filter(['category' => $activeCategory?->slug, 'q' => request('q'), 'sort' => request('sort')]));
    @endphp
    {{-- Static sidebar on desktop; a slide-over popup on mobile (toggled by the Filters button). --}}
    <aside data-filter-panel class="fixed inset-0 z-50 lg:relative lg:z-auto lg:mb-0 lg:block lg:w-64 lg:shrink-0">
        {{-- dim overlay (mobile only) --}}
        <div data-filter-close class="filter-overlay absolute inset-0 bg-slate-900/50 lg:hidden"></div>

        {{-- panel --}}
        <div class="filter-drawer absolute inset-y-0 left-0 flex w-[86%] max-w-sm flex-col bg-slate-50 shadow-2xl lg:static lg:w-auto lg:max-w-none lg:bg-transparent lg:shadow-none">
            {{-- mobile header --}}
            <div class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3.5 lg:hidden">
                <span class="text-base font-black text-slate-900">Filters</span>
                <button type="button" data-filter-close class="grid h-9 w-9 place-items-center rounded-full text-slate-500 hover:bg-slate-100" aria-label="Close filters">
                    <span class="text-2xl leading-none">&times;</span>
                </button>
            </div>

            {{-- scrollable content --}}
            <div class="flex-1 space-y-4 overflow-y-auto p-4 lg:overflow-visible lg:p-0">
        <form method="GET" id="filters-form" data-filter-form class="space-y-4">
            {{-- carry context so each filter submit keeps category / search / sort --}}
            @if ($activeCategory)<input type="hidden" name="category" value="{{ $activeCategory->slug }}">@endif
            @if (request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
            @if (request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
            @if (request('per_page'))<input type="hidden" name="per_page" value="{{ request('per_page') }}">@endif

            {{-- active filters summary --}}
            @if ($hasFilters)
                <div class="flex items-center justify-between rounded-md border border-amber-200 bg-amber-50 px-4 py-2.5">
                    <span class="text-xs font-bold uppercase tracking-wide text-amber-700">Filters active</span>
                    <a href="{{ $clearUrl }}" class="text-xs font-semibold text-amber-700 underline hover:text-amber-800">Clear all</a>
                </div>
            @endif

            {{-- Categories --}}
            <details open class="acc overflow-hidden rounded-md border border-slate-200 bg-white">
                <summary class="flex items-center justify-between px-5 py-3.5">
                    <span class="text-[13px] font-extrabold uppercase tracking-wide text-[#111]">Categories</span>
                    <span class="acc-sign text-lg font-bold text-amber-500"></span>
                </summary>
                <div class="px-5 pb-4">
                    <div class="mb-1 flex justify-end">
                        <button type="button" data-acc-toggle-all class="text-xs font-semibold text-amber-600 hover:text-amber-700">Expand all</button>
                    </div>
                    <ul class="space-y-0.5">
                        <li>
                            <label class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-slate-50">
                                <input type="checkbox" onchange="if (this.checked) window.location = '{{ route('products.index') }}'"
                                       @checked(! $activeCategory)
                                       class="rounded border-slate-300 text-amber-600 focus:ring-amber-400">
                                <span class="flex-1 {{ ! $activeCategory ? 'font-semibold text-amber-600' : 'text-slate-600' }}">All Products</span>
                            </label>
                        </li>
                        @include('products._cat_filter', ['items' => $categories, 'depth' => 0, 'activeCategory' => $activeCategory])
                    </ul>
                </div>
            </details>

            {{-- Brand --}}
            @if ($brandCounts->count())
                <details open class="acc overflow-hidden rounded-md border border-slate-200 bg-white">
                    <summary class="flex items-center justify-between px-5 py-3.5">
                        <span class="text-[13px] font-extrabold uppercase tracking-wide text-[#111]">Brand</span>
                        <span class="acc-sign text-lg font-bold text-amber-500"></span>
                    </summary>
                    <div class="px-5 pb-4">
                        @if ($brandCounts->count() > 8)
                            <input type="text" data-brand-search placeholder="Search brands…"
                                   class="mb-2 w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm outline-none focus:border-amber-400">
                        @endif
                        <ul class="max-h-64 space-y-0.5 overflow-y-auto pr-1">
                            @foreach ($brandCounts as $brand => $count)
                                <li data-brand-item>
                                    <label class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-slate-600 hover:bg-slate-50">
                                        <input type="checkbox" name="brands[]" value="{{ $brand }}" onchange="this.form.submit()"
                                               @checked(in_array($brand, $selectedBrands))
                                               class="rounded border-slate-300 text-amber-600 focus:ring-amber-400">
                                        <span class="flex-1 truncate" data-brand-name>{{ $brand }}</span>
                                        <span class="text-xs text-slate-400">({{ $count }})</span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </details>
            @endif

            {{-- Price --}}
            <details open class="acc overflow-hidden rounded-md border border-slate-200 bg-white">
                <summary class="flex items-center justify-between px-5 py-3.5">
                    <span class="text-[13px] font-extrabold uppercase tracking-wide text-[#111]">Price</span>
                    <span class="acc-sign text-lg font-bold text-amber-500"></span>
                </summary>
                <div class="px-5 pb-4">
                    <div class="flex items-center gap-2">
                        <input type="number" name="min_price" value="{{ $minPrice }}" placeholder="Min" min="0" step="1"
                               class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm outline-none focus:border-amber-400">
                        <span class="text-slate-400">–</span>
                        <input type="number" name="max_price" value="{{ $maxPrice }}" placeholder="Max" min="0" step="1"
                               class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm outline-none focus:border-amber-400">
                    </div>
                    @if ($priceBounds && $priceBounds->mx)
                        <p class="mt-2 text-xs text-slate-400">Range: ${{ number_format(floor($priceBounds->mn)) }} – ${{ number_format(ceil($priceBounds->mx)) }}</p>
                    @endif
                    <button type="submit" class="mt-3 w-full rounded bg-amber-500 py-2 text-xs font-bold uppercase tracking-wide text-white transition hover:bg-amber-600">Apply</button>
                </div>
            </details>

            {{-- Availability --}}
            <details open class="acc overflow-hidden rounded-md border border-slate-200 bg-white">
                <summary class="flex items-center justify-between px-5 py-3.5">
                    <span class="text-[13px] font-extrabold uppercase tracking-wide text-[#111]">Availability</span>
                    <span class="acc-sign text-lg font-bold text-amber-500"></span>
                </summary>
                <div class="px-5 pb-4">
                    <label class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-slate-600 hover:bg-slate-50">
                        <input type="checkbox" name="in_stock" value="1" onchange="this.form.submit()" @checked($inStockOnly)
                               class="rounded border-slate-300 text-amber-600 focus:ring-amber-400">
                        <span class="flex-1">In Stock</span>
                        <span class="text-xs text-slate-400">({{ $inStockCount }})</span>
                    </label>
                </div>
            </details>

        </form>

                {{-- desktop sidebar only — on mobile this card moves below the products --}}
                <div class="mt-5 hidden lg:block">
                    @include('products._help')
                </div>
            </div>{{-- /scrollable --}}

            {{-- sticky apply bar (mobile popup only) --}}
            <div class="flex shrink-0 items-center gap-3 border-t border-slate-200 bg-white p-4 lg:hidden">
                @if ($hasFilters)
                    <a href="{{ $clearUrl }}" class="shrink-0 rounded-lg border border-slate-300 px-4 py-3 text-sm font-bold text-slate-700">Clear</a>
                @endif
                <button type="submit" form="filters-form" class="flex-1 rounded bg-amber-500 py-3 text-sm font-black uppercase tracking-wide text-white transition hover:bg-amber-600">
                    Show {{ number_format($products->total()) }} products
                </button>
            </div>
        </div>{{-- /panel --}}
    </aside>

    {{-- ===== Product grid ===== --}}
    <div class="flex-1">

        {{-- mobile toolbar: Sort pill + Filters pills (matches the reference) --}}
        <div class="mb-5 flex items-center justify-between gap-3 lg:hidden">
            <form method="GET" class="relative">
                @if ($activeCategory)<input type="hidden" name="category" value="{{ $activeCategory->slug }}">@endif
                @if (request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
                @foreach ($selectedBrands as $b)<input type="hidden" name="brands[]" value="{{ $b }}">@endforeach
                @if ($minPrice !== null)<input type="hidden" name="min_price" value="{{ $minPrice }}">@endif
                @if ($maxPrice !== null)<input type="hidden" name="max_price" value="{{ $maxPrice }}">@endif
                @if ($inStockOnly)<input type="hidden" name="in_stock" value="1">@endif
                @if ($minRating)<input type="hidden" name="rating" value="{{ $minRating }}">@endif
                @if (request('per_page'))<input type="hidden" name="per_page" value="{{ request('per_page') }}">@endif
                <select name="sort" onchange="this.form.submit()"
                        class="appearance-none rounded-full border border-slate-200 bg-white py-2.5 pl-5 pr-10 text-xs font-black uppercase tracking-wide text-slate-700 outline-none focus:border-amber-400">
                    <option value="" @selected(! request('sort'))>Featured</option>
                    <option value="price_asc" @selected(request('sort')==='price_asc')>Price: Low–High</option>
                    <option value="price_desc" @selected(request('sort')==='price_desc')>Price: High–Low</option>
                    <option value="rating" @selected(request('sort')==='rating')>Top Rated</option>
                </select>
                <x-icon name="chevron" class="pointer-events-none absolute right-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" />
            </form>

            <div class="flex items-center gap-2">
                <button type="button" data-filter-open class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white px-5 py-2.5 text-xs font-black uppercase tracking-wide text-slate-700 transition hover:border-amber-400 hover:text-amber-600">
                    Filters
                    @if ($filterCount)<span class="grid h-4 min-w-4 place-items-center rounded-full bg-amber-500 px-1 text-[10px] font-bold text-white">{{ $filterCount }}</span>@endif
                </button>

                {{-- grid / list view toggle --}}
                <div class="inline-flex shrink-0 items-center rounded-full border border-slate-200 bg-white p-0.5" role="group" aria-label="View mode">
                    <button type="button" data-view="grid" aria-label="Grid view" class="grid h-8 w-8 place-items-center rounded-full text-slate-500">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-4 w-4"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                    </button>
                    <button type="button" data-view="list" aria-label="List view" class="grid h-8 w-8 place-items-center rounded-full text-slate-500">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="h-4 w-4"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- desktop toolbar --}}
        <form method="GET" class="mb-6 hidden flex-wrap items-center justify-between gap-4 rounded-md border border-slate-200 bg-white px-5 py-3 lg:flex">
            {{-- preserve every active filter when changing the sort order --}}
            @if ($activeCategory)<input type="hidden" name="category" value="{{ $activeCategory->slug }}">@endif
            @if (request('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
            @foreach ($selectedBrands as $b)<input type="hidden" name="brands[]" value="{{ $b }}">@endforeach
            @if ($minPrice !== null)<input type="hidden" name="min_price" value="{{ $minPrice }}">@endif
            @if ($maxPrice !== null)<input type="hidden" name="max_price" value="{{ $maxPrice }}">@endif
            @if ($inStockOnly)<input type="hidden" name="in_stock" value="1">@endif
            @if ($minRating)<input type="hidden" name="rating" value="{{ $minRating }}">@endif
            @if (request('per_page'))<input type="hidden" name="per_page" value="{{ request('per_page') }}">@endif
            <p class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                Showing <span class="font-semibold text-slate-800">{{ $products->total() }}</span> products
                @foreach ($selectedBrands as $b)
                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700">
                        {{ $b }}
                        <a href="{{ route('products.index', array_filter([
                                'category' => $activeCategory?->slug, 'q' => request('q'), 'sort' => request('sort'),
                                'brands' => collect($selectedBrands)->reject(fn ($x) => $x === $b)->values()->all() ?: null,
                                'min_price' => $minPrice, 'max_price' => $maxPrice,
                                'in_stock' => $inStockOnly ? 1 : null, 'rating' => $minRating,
                           ])) }}" class="text-amber-500 hover:text-amber-700" aria-label="Remove {{ $b }}">&times;</a>
                    </span>
                @endforeach
            </p>
            
            <div class="flex flex-wrap items-center gap-3">
                <label class="flex items-center gap-2 text-sm">
                    <span class="text-slate-500">Show</span>
                    <select name="per_page" onchange="this.form.submit()"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium outline-none focus:border-amber-400">
                        @foreach ($perPageOptions as $opt)
                            <option value="{{ $opt }}" @selected(! $showAll && (int) $perPageRaw === $opt)>{{ $opt }}</option>
                        @endforeach
                        <option value="all" @selected($showAll)>All</option>
                    </select>
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <span class="text-slate-500">Sort by</span>
                    <select name="sort" onchange="this.form.submit()"
                            class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium outline-none focus:border-amber-400">
                        <option value="" @selected(! request('sort'))>Featured</option>
                        <option value="price_asc" @selected(request('sort')==='price_asc')>Price: Low to High</option>
                        <option value="price_desc" @selected(request('sort')==='price_desc')>Price: High to Low</option>
                        <option value="rating" @selected(request('sort')==='rating')>Top Rated</option>
                    </select>
                </label>
            </div>
        </form>

        @if ($products->count())
            <div data-products-grid class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($products as $product)
                    <div data-reveal class="min-w-0" style="--reveal-delay:{{ ($loop->index % 4) * 60 }}ms">
                        <x-product-card :product="$product" />
                    </div>
                @endforeach
            </div>

            <div class="mt-10 flex flex-col items-center gap-3">
                <p class="text-sm text-slate-500">Showing <span data-shown-count>{{ $products->count() }}</span> of {{ $products->total() }} products</p>
                @if ($products->hasMorePages())
                    <div data-load-more-wrap>
                        <button type="button" data-load-more data-next="{{ $products->nextPageUrl() }}"
                                class="rounded bg-amber-500 px-10 py-3 text-sm font-black uppercase tracking-wide text-white transition hover:bg-amber-600 disabled:cursor-wait disabled:opacity-60">
                            Load More
                        </button>
                    </div>
                @endif
            </div>
        @else
            <div class="rounded-md border border-dashed border-slate-300 bg-white py-20 text-center">
                <x-icon name="search" class="mx-auto h-10 w-10 text-slate-300" />
                <p class="mt-4 font-semibold text-slate-700">No products found</p>
                <p class="mt-1 text-sm text-slate-500">Try a different category or search term.</p>
                <a href="{{ route('products.index') }}" class="mt-5 inline-block rounded bg-amber-500 px-5 py-2 text-sm font-bold text-white transition hover:bg-amber-600">View all products</a>
            </div>
        @endif

        {{-- help card — bottom of the page on mobile (in the sidebar on desktop) --}}
        <div class="mt-10 lg:hidden">
            @include('products._help')
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Subcategories: one row to start with, the rest on request.
    (function () {
        const circles = document.querySelector('[data-subcats-circles]');
        const list    = document.querySelector('[data-subcats-list]');

        const circleBtn = document.querySelector('[data-subcats-toggle="circles"]');
        if (circles && circleBtn) {
            // only worth a button when there is in fact a second row
            if (circles.scrollHeight > circles.clientHeight + 4) {
                circleBtn.hidden = false;
                circleBtn.addEventListener('click', () => {
                    const open = circles.classList.toggle('max-h-[11.5rem]');
                    circleBtn.textContent = open ? 'View more' : 'View less';
                });
            }
        }

        const listBtn = document.querySelector('[data-subcats-toggle="list"]');
        if (list && listBtn) {
            listBtn.addEventListener('click', () => {
                const extra = list.querySelectorAll('[data-subcat-extra]');
                const hidden = extra[0]?.classList.contains('hidden');
                extra.forEach((el) => el.classList.toggle('hidden', !hidden));
                listBtn.textContent = hidden ? 'View less' : `View more (${extra.length})`;
            });
        }
    })();

    // Mobile filter slide-over: the aside is off-canvas until "Filters" is tapped.
    (function () {
        const panel = document.querySelector('[data-filter-panel]');
        if (!panel) return;
        const open  = () => { panel.classList.add('is-open'); document.body.style.overflow = 'hidden'; };
        const close = () => { panel.classList.remove('is-open'); document.body.style.overflow = ''; };

        document.querySelectorAll('[data-filter-open]').forEach((b) => b.addEventListener('click', open));
        panel.querySelectorAll('[data-filter-close]').forEach((b) => b.addEventListener('click', close));
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && panel.classList.contains('is-open')) close();
        });

        // allow a #filters deep-link to open the popup on mobile
        if (location.hash === '#filters' && window.matchMedia('(max-width: 1023px)').matches) open();
    })();

    // Mobile grid/list view toggle — remembered per browser.
    (function () {
        const grid = document.querySelector('[data-products-grid]');
        const btns = document.querySelectorAll('[data-view]');
        if (!grid || !btns.length) return;

        const apply = (mode) => {
            grid.classList.toggle('is-list', mode === 'list');
            btns.forEach((b) => {
                const active = b.dataset.view === mode;
                b.classList.toggle('bg-slate-900', active);
                b.classList.toggle('text-white', active);
                b.classList.toggle('text-slate-500', !active);
                b.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
        };

        let mode = new URLSearchParams(location.search).get('view');
        if (mode !== 'grid' && mode !== 'list') {
            try { mode = localStorage.getItem('shop_view') || 'grid'; } catch (e) { mode = 'grid'; }
        }
        apply(mode);

        btns.forEach((b) => b.addEventListener('click', () => {
            try { localStorage.setItem('shop_view', b.dataset.view); } catch (e) {}
            apply(b.dataset.view);
        }));
    })();
</script>
@endpush

@endsection
