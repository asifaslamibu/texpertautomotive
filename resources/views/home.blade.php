@extends('layouts.app')

@section('title', 'HVAC, Plumbing & Printer Supplies Online — Texpert Deals')
@section('meta_description', 'Texpert Deals stocks HVAC, plumbing, electrical and Zebra printer supplies for US contractors — genuine, warranty-backed parts from 1,000+ brands, shipped fast.')

@section('content')

{{-- ============================ HERO (popular brands + banner slider) ============================ --}}
@php
    $heroFeatures = [['shield', 'Premium Quality'], ['check', 'Accurate & Reliable'], ['box', 'Built to Last']];
    $sliderSeconds = (int) \App\Models\Setting::get('hero_slider_duration', 5);
    $sliderMs = max(2, min(30, $sliderSeconds ?: 5)) * 1000;
    // admin-managed hero banners, with a sensible fallback if none are configured
    $slides = ($banners ?? collect())->count()
        ? $banners
        : collect([
            (object) ['eyebrow' => 'Built for', 'title' => 'HVAC/R Contractors', 'subtitle' => 'Who Demand More', 'cta_label' => 'Shop Tools & Equipment', 'cta_url' => route('products.index'), 'image' => null],
            (object) ['eyebrow' => 'Top brands,', 'title' => 'Wholesale Prices', 'subtitle' => 'On Thousands of Products', 'cta_label' => 'Browse the Catalog', 'cta_url' => route('products.index'), 'image' => null],
            (object) ['eyebrow' => 'Fast & reliable', 'title' => 'Shipping Nationwide', 'subtitle' => 'On In-Stock Items', 'cta_label' => 'Start Shopping', 'cta_url' => route('products.index'), 'image' => null],
        ]);
@endphp
<section class="bg-slate-100">
    <div class="mx-auto grid max-w-7xl items-stretch gap-5 px-4 py-6 sm:px-6 lg:grid-cols-[260px_1fr]">

        {{-- popular brands --}}
        <aside data-reveal class="hidden rounded-2xl border border-slate-200 bg-white p-3 lg:flex lg:flex-col">
            <span class="inline-block self-start rounded-md border-b-2 border-amber-400 bg-amber-50 px-3 py-1 text-sm font-bold text-slate-800">Popular Brands</span>
            <div class="mt-3 grid flex-1 auto-rows-fr grid-cols-2 gap-2">
                @forelse ($popularBrands->take(10) as $brand)
                    <a href="{{ $brand->url }}" title="{{ $brand->name }}"
                       class="grid h-full min-h-[3.5rem] place-items-center overflow-hidden rounded-lg border border-slate-200 px-2 text-center transition hover:border-amber-300 hover:bg-amber-50">
                        @if ($brand->logo)
                            <img src="{{ media($brand->logo) }}" alt="{{ $brand->name }}" class="max-h-9 max-w-full object-contain">
                        @else
                            <span class="text-[13px] font-black uppercase leading-tight tracking-tight text-slate-500">{{ \Illuminate\Support\Str::limit($brand->name, 12) }}</span>
                        @endif
                    </a>
                @empty
                    @foreach ($brands->take(10) as $brand)
                        <a href="{{ route('products.index', ['brand' => $brand]) }}" class="grid h-full min-h-[3.5rem] place-items-center rounded-lg border border-slate-200 px-2 text-center text-[13px] font-black uppercase leading-tight tracking-tight text-slate-500 transition hover:border-amber-300 hover:bg-amber-50 hover:text-amber-600">{{ \Illuminate\Support\Str::limit($brand, 12) }}</a>
                    @endforeach
                @endforelse
            </div>
        </aside>

        {{-- banner slider --}}
        <div data-reveal data-slider data-interval="{{ $sliderMs }}" class="group/slider relative overflow-hidden rounded-2xl">
            <div data-slider-track class="no-scrollbar flex snap-x snap-mandatory overflow-x-auto scroll-smooth">
                @php $heroOverlay = max(0, min(90, (int) \App\Models\Setting::get('hero_overlay', 40))); @endphp
                @foreach ($slides as $slide)
                    <div data-slide class="relative flex min-h-[340px] w-full shrink-0 snap-start flex-col justify-center overflow-hidden bg-slate-900 p-8 text-white sm:p-12 lg:min-h-[460px]">
                        @php $slideVideo = $slide->video ?? null; @endphp
                        @if ($slideVideo)
                            @php $videoFit = (($slide->video_fit ?? 'cover') === 'contain') ? 'object-contain' : 'object-cover'; @endphp
                            {{-- Hidden on phones (JS won't play it there) so the multi-MB clip is never
                                 downloaded on mobile; the slide text shows on the dark background instead. --}}
                            <video class="absolute inset-0 hidden h-full w-full md:block {{ $videoFit }}" muted {{ $slides->count() > 1 ? '' : 'loop' }} playsinline preload="none"
                                   data-speed="{{ $slide->video_speed ?? 1 }}"
                                   @if ($slide->image) poster="{{ media($slide->image) }}" @endif>
                                <source src="{{ media($slideVideo) }}">
                            </video>
                            @if ($heroOverlay > 0)
                                <div class="absolute inset-0 bg-black" style="opacity: {{ $heroOverlay / 100 }};"></div>
                            @endif
                        @elseif ($slide->image)
                            <img src="{{ media($slide->image) }}" alt="" class="absolute inset-0 h-full w-full object-cover">
                            @if ($heroOverlay > 0)
                                <div class="absolute inset-0 bg-black" style="opacity: {{ $heroOverlay / 100 }};"></div>
                            @endif
                        @endif

                        @php $hasText = $slide->eyebrow || $slide->title || $slide->subtitle || $slide->cta_label; @endphp
                        @if ($hasText)
                        <div class="relative max-w-xl [text-shadow:0_2px_10px_rgb(0_0_0/45%)]">
                            @if ($slide->eyebrow)<p class="text-lg font-bold uppercase tracking-wide text-white/90">{{ $slide->eyebrow }}</p>@endif
                            @if ($slide->title)<h1 class="mt-1 text-4xl font-black uppercase leading-[0.95] tracking-tight text-amber-400 sm:text-5xl">{{ $slide->title }}</h1>@endif
                            @if ($slide->subtitle)<p class="mt-1 text-2xl font-black uppercase tracking-tight sm:text-3xl">{{ $slide->subtitle }}</p>@endif

                            <div class="mt-6 flex flex-wrap gap-x-6 gap-y-3">
                                @foreach ($heroFeatures as [$icon, $label])
                                    <span class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-slate-300">
                                        <x-icon name="{{ $icon }}" class="h-4 w-4 text-amber-400" /> {{ $label }}
                                    </span>
                                @endforeach
                            </div>

                            @if ($slide->cta_label)
                                <a href="{{ $slide->cta_url }}"
                                   class="sheen mt-7 inline-block rounded-full bg-amber-400 px-7 py-3 text-sm font-black uppercase tracking-wide text-slate-900 transition hover:bg-amber-500">
                                    {{ $slide->cta_label }}
                                </a>
                            @endif
                        </div>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- arrows --}}
            <button type="button" data-slider-prev aria-label="Previous" class="absolute left-3 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-white/15 text-white backdrop-blur transition hover:bg-amber-400 hover:text-slate-900">
                <x-icon name="arrow" class="h-5 w-5 rotate-180" />
            </button>
            <button type="button" data-slider-next aria-label="Next" class="absolute right-3 top-1/2 grid h-10 w-10 -translate-y-1/2 place-items-center rounded-full bg-white/15 text-white backdrop-blur transition hover:bg-amber-400 hover:text-slate-900">
                <x-icon name="arrow" class="h-5 w-5" />
            </button>
            {{-- dots --}}
            <div data-slider-dots class="absolute inset-x-0 bottom-4 flex justify-center gap-2"></div>
        </div>
    </div>
</section>

{{-- ============================ BRAND MARQUEE (below hero) ============================ --}}
@php
    $marqueeList = $marqueeBrands->count()
        ? $marqueeBrands
        : $brands->map(fn ($b) => (object) ['name' => $b, 'logo' => null, 'url' => route('products.index', ['brand' => $b])]);
@endphp
@if ($marqueeList->count())
    <section class="border-b border-slate-200 bg-white py-8">
        <p class="mb-6 text-center text-xs font-bold uppercase tracking-widest text-slate-400">Trusted by contractors nationwide — shop top brands</p>
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <div data-brand-scroller class="group relative">
                {{-- soft edge fades --}}
                <div class="pointer-events-none absolute inset-y-0 left-0 z-10 w-14 bg-gradient-to-r from-white to-transparent"></div>
                <div class="pointer-events-none absolute inset-y-0 right-0 z-10 w-14 bg-gradient-to-l from-white to-transparent"></div>

                {{-- side arrows --}}
                <button type="button" data-brand-prev aria-label="Previous brands" class="absolute left-0 top-1/2 z-20 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-full border border-slate-300 bg-white text-slate-600 shadow-sm transition hover:border-amber-400 hover:text-amber-600 active:scale-95"><x-icon name="arrow" class="h-4 w-4 rotate-180" /></button>
                <button type="button" data-brand-next aria-label="Next brands" class="absolute right-0 top-1/2 z-20 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-full border border-slate-300 bg-white text-slate-600 shadow-sm transition hover:border-amber-400 hover:text-amber-600 active:scale-95"><x-icon name="arrow" class="h-4 w-4" /></button>

                <div data-brand-track class="no-scrollbar flex gap-4 overflow-x-auto scroll-smooth px-11">
                    @foreach ($marqueeList->concat($marqueeList) as $brand)
                        <a href="{{ $brand->url }}" title="{{ $brand->name }}" class="grid h-16 w-40 shrink-0 place-items-center overflow-hidden rounded-xl border border-slate-200 bg-white p-3 transition hover:border-amber-300">
                            @if ($brand->logo)
                                <img src="{{ media($brand->logo) }}" alt="{{ $brand->name }}" class="max-h-10 max-w-full object-contain">
                            @else
                                <span class="text-base font-black tracking-tight text-slate-400">{{ $brand->name }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endif

{{-- ============================ TRUST BAR ============================ --}}
<section class="border-b border-slate-200 bg-white">
    <div class="mx-auto grid max-w-7xl grid-cols-1 divide-slate-200 px-4 sm:px-6 sm:grid-cols-3 sm:divide-x">
        @foreach ([['truck','3–5 Day Shipping','On 100,000+ in-stock items'],['shield','Genuine Products','OEM & warranty backed'],['phone','Expert Support','Mon–Fri 9–6 EST']] as $i => [$icon,$title,$desc])
            <div data-reveal style="--reveal-delay:{{ $i * 80 }}ms" class="flex items-center justify-center gap-3 px-3 py-5 text-left">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-amber-50 text-amber-600"><x-icon name="{{ $icon }}" class="h-6 w-6" /></span>
                <div>
                    <p class="text-sm font-bold text-slate-800">{{ $title }}</p>
                    <p class="text-xs text-slate-500">{{ $desc }}</p>
                </div>
            </div>
        @endforeach
    </div>
</section>

{{-- ============================ SHOP BY DEPARTMENT ============================ --}}
<section class="mx-auto max-w-7xl px-4 py-16 sm:px-6">
    <h2 data-reveal class="mb-12 text-center text-3xl font-black uppercase tracking-tight text-slate-900">Shop by Department</h2>
    @php
        // Pick an icon from the department NAME (first matching keyword wins).
        $deptIconMap = [
            'computer' => 'computer', 'laptop' => 'computer', 'pc' => 'computer', 'it' => 'computer',
            'part' => 'parts',
            'tool' => 'tools',
            'electric' => 'electric', 'electronic' => 'electric', 'light' => 'electric',
            'plumb' => 'plumbing', 'water' => 'plumbing', 'pipe' => 'plumbing',
            'pool' => 'pool', 'spa' => 'pool',
            'mini' => 'minisplit', 'split' => 'minisplit',
            'hvac' => 'ac', 'air' => 'ac', 'cool' => 'ac', 'heat' => 'ac', 'refriger' => 'ac', 'equipment' => 'ac',
            'phone' => 'phone', 'communic' => 'phone',
            'truck' => 'truck', 'ship' => 'truck',
        ];
        $deptIcon = function ($dept) use ($deptIconMap) {
            $name = \Illuminate\Support\Str::lower($dept->name);
            foreach ($deptIconMap as $kw => $icon) {
                if (str_contains($name, $kw)) return $icon;
            }
            return $dept->icon ?: 'box';
        };
    @endphp
    <div class="flex flex-wrap justify-center gap-x-6 gap-y-10">
        @foreach ($departments->take(8) as $i => $dept)
            <a href="{{ route('products.index', ['category' => $dept->slug]) }}"
               data-reveal style="--reveal-delay:{{ ($i % 8) * 50 }}ms"
               class="group flex w-32 flex-col items-center text-center sm:w-36">
                <span class="flex aspect-square w-full items-center justify-center overflow-hidden rounded-full bg-white shadow-[0_10px_30px_-10px_rgba(15,23,42,0.18)] ring-1 ring-slate-100 transition duration-300 group-hover:-translate-y-1.5 group-hover:shadow-[0_18px_40px_-12px_rgba(15,23,42,0.28)]">
                    @if (!empty($dept->image))
                        <img src="{{ media($dept->image) }}" alt="{{ $dept->name }}" loading="lazy"
                             class="h-full w-full object-contain p-5 transition duration-500 group-hover:scale-105">
                    @else
                        <span class="grid h-14 w-14 place-items-center rounded-full bg-slate-100 text-slate-500">
                            <x-icon name="{{ $deptIcon($dept) }}" class="h-7 w-7" />
                        </span>
                    @endif
                </span>
                <span class="mt-4 text-sm font-semibold text-slate-800 transition group-hover:text-amber-600">{{ $dept->name }}</span>
            </a>
        @endforeach
    </div>
</section>

{{-- ============================ PROMO BANNER ============================ --}}
@if (\App\Models\Setting::get('promo_banner_enabled', '1') !== '0')
<section class="mx-auto max-w-7xl px-4 pb-4 sm:px-6">
    <div data-reveal class="sheen relative overflow-hidden rounded-3xl bg-gradient-to-r from-amber-400 to-amber-500 px-8 py-10 text-slate-900 sm:px-12">
        <div class="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rounded-full bg-white/20"></div>
        <div class="relative flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <p class="text-sm font-bold uppercase tracking-wider">Limited time</p>
                <h3 class="mt-1 text-2xl font-black sm:text-3xl">Save 5% on your first order — code <span class="rounded bg-slate-900 px-2 py-0.5 text-amber-400">{{ \App\Models\Setting::get('announcement_code', 'DEALS5') }}</span></h3>
                <p class="mt-1 text-slate-800/80">Genuine parts, fast tracked shipping from US warehouses.</p>
            </div>
            <a href="{{ route('products.index') }}" class="shrink-0 rounded-full bg-slate-900 px-7 py-3 text-sm font-bold text-white transition hover:bg-slate-800">Shop Deals</a>
        </div>
    </div>
</section>
@endif

{{-- ============================ SHOP BY CATEGORY ============================ --}}
@if ($popularCategories->count())
    <section class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
        <div data-reveal class="mb-6 flex items-end justify-between sm:mb-8">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-amber-500">Popular right now</p>
                <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">Shop by Category</h2>
                <p class="mt-1 text-sm text-slate-500">Find the right parts and supplies for your needs</p>
            </div>
            <a href="{{ route('products.index') }}" class="hidden items-center gap-1 text-sm font-semibold text-amber-600 hover:text-amber-700 sm:flex">All categories <x-icon name="arrow" class="h-4 w-4" /></a>
        </div>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($popularCategories as $i => $cat)
                <a href="{{ route('products.index', ['category' => $cat->slug]) }}"
                   data-reveal style="--reveal-delay:{{ ($i % 4) * 60 }}ms"
                   class="card-zoom group flex items-center gap-2.5 overflow-hidden rounded-2xl border border-slate-200 bg-white p-2.5 transition hover:-translate-y-1 hover:border-amber-200 hover:shadow-lg sm:gap-4 sm:p-3">
                    <span class="h-11 w-11 shrink-0 overflow-hidden rounded-xl bg-slate-50 sm:h-16 sm:w-16">
                        <img src="{{ media($cat->sample_image) ?: 'https://placehold.co/120/fef3c7/b45309?text=%E2%80%A2' }}" alt="" class="h-full w-full object-cover">
                    </span>
                    {{-- names wrap to a second line rather than being cut to "Access…" --}}
                    <span class="min-w-0 flex-1">
                        <span class="block text-[13px] font-bold leading-tight text-slate-800 group-hover:text-amber-600 sm:text-base sm:leading-snug">{{ $cat->name }}</span>
                        <span class="mt-0.5 block text-[11px] text-slate-400 sm:text-xs">{{ $cat->products_count }} products</span>
                    </span>
                    {{-- the chevron costs width a long category name needs on a phone --}}
                    <x-icon name="chevron" class="hidden h-4 w-4 shrink-0 -rotate-90 text-slate-300 group-hover:text-amber-500 sm:block" />
                </a>
            @endforeach
        </div>
    </section>
@endif

{{-- ============================ FEATURED DEALS ============================ --}}
<div class="bg-slate-50">
    <x-product-row title="Featured Deals" eyebrow="Best sellers" :products="$featuredProducts" :link="route('products.index')" />
</div>

{{-- ============================ NEW ARRIVALS ============================ --}}
<x-product-row title="New Arrivals" eyebrow="Just landed" :products="$newArrivals" :link="route('products.index')" />

{{-- ============================ TOP RATED ============================ --}}
<div class="bg-slate-50">
    <x-product-row title="Top Rated Products" eyebrow="Customer favorites" :products="$topRated" :link="route('products.index', ['sort' => 'rating'])" />
</div>

{{-- ============================ PROMO SPLIT ============================ --}}
<section class="mx-auto max-w-7xl px-4 py-16 sm:px-6">
    <div class="grid gap-5 md:grid-cols-2">
        <a href="{{ route('bulk-order') }}" data-reveal class="sheen group relative flex min-h-52 flex-col justify-end overflow-hidden rounded-3xl bg-gradient-to-br from-slate-800 to-black p-8 text-white">
            <div class="absolute -right-10 -top-10 h-44 w-44 rounded-full bg-amber-400/10 transition group-hover:scale-125"></div>
            <span class="relative w-fit rounded-full bg-amber-400 px-3 py-1 text-[11px] font-bold text-slate-900">Bulk pricing</span>
            <h3 class="relative mt-4 text-2xl font-black">Wholesale &amp; Contractor Deals</h3>
            <p class="relative mt-1 text-slate-300">Volume discounts and Net-30 terms for contractors &amp; businesses.</p>
            <span class="relative mt-5 inline-flex items-center gap-1 text-sm font-bold text-amber-400">Request a quote <x-icon name="arrow" class="h-4 w-4 transition group-hover:translate-x-1" /></span>
        </a>
        <a href="{{ route('products.index') }}" data-reveal style="--reveal-delay:120ms" class="sheen group relative flex min-h-52 flex-col justify-end overflow-hidden rounded-3xl bg-gradient-to-br from-amber-400 to-amber-500 p-8 text-slate-900">
            <div class="absolute -left-10 -bottom-10 h-44 w-44 rounded-full bg-white/20 transition group-hover:scale-125"></div>
            <span class="relative w-fit rounded-full bg-slate-900 px-3 py-1 text-[11px] font-bold text-amber-400">New arrivals</span>
            <h3 class="relative mt-4 text-2xl font-black">Fresh Stock, Fast Shipping</h3>
            <p class="relative mt-1 text-slate-800/80">The latest gear — in stock and ready to ship.</p>
            <span class="relative mt-5 inline-flex items-center gap-1 text-sm font-bold">Shop new <x-icon name="arrow" class="h-4 w-4 transition group-hover:translate-x-1" /></span>
        </a>
    </div>
</section>

{{-- ============================ WHY SHOP WITH US ============================ --}}
@php
    // background picture and the five icons come from Admin → Settings
    $whyBg = \App\Models\Setting::get('why_bg');
    $whyDefaults = ['shield-check', 'tag', 'box', 'truck', 'headset'];
    $whyText = [
        ['Trusted Brands', 'Get genuine HVAC equipment and supplies from top brands in the industry.'],
        ['Competitive Prices', 'Enjoy great value with competitive pricing on all your HVAC needs.'],
        ['Reliable Inventory', 'Count on us for in-stock products and a wide selection of top brands.'],
        ['Fast Fulfillment', 'Get your orders shipped quickly and keep your projects on track.'],
        ['Responsive Support', 'Real people, real help — whenever you need us.'],
    ];
    $whyCards = [];
    foreach ($whyText as $i => [$title, $desc]) {
        $n = $i + 1;
        $whyCards[] = [
            // an uploaded picture wins over the built-in icon
            'image' => \App\Models\Setting::get("why_img_{$n}"),
            'icon' => \App\Models\Setting::get("why_icon_{$n}", $whyDefaults[$i]) ?: $whyDefaults[$i],
            'title' => $title,
            'desc' => $desc,
        ];
    }
@endphp
<section class="relative isolate overflow-hidden bg-gradient-to-b from-white via-sky-50 to-sky-100/70">
    @if ($whyBg)
        <img src="{{ media($whyBg) }}" alt="" aria-hidden="true"
             class="absolute inset-0 -z-10 h-full w-full object-cover">
    @endif

    <div class="mx-auto max-w-7xl px-4 py-12 text-center sm:px-6 sm:py-14">
        {{-- a rule each side of the small line, as on the reference --}}
        <p data-reveal class="flex items-center justify-center gap-3 text-[11px] font-black uppercase tracking-[0.18em] text-slate-700 sm:text-xs">
            <span class="hidden h-px w-14 bg-amber-400 sm:block"></span>
            Why shop HVAC equipment online with
            <span class="hidden h-px w-14 bg-amber-400 sm:block"></span>
        </p>
        <h2 data-reveal class="mt-2 text-3xl font-black uppercase tracking-tight text-slate-900 sm:text-4xl">
            Texpert <span class="text-amber-500">Deals?</span>
        </h2>
        <p data-reveal class="mx-auto mt-3 max-w-3xl text-sm text-slate-500 sm:text-[15px]">
            Shop trusted HVAC brands at competitive prices, with reliable inventory and fast fulfillment.
            Get the parts and equipment you need, backed by responsive support you can count on.
        </p>

        <div class="mx-auto mt-6 grid max-w-5xl gap-3 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ($whyCards as $i => $card)
                <div data-reveal style="--reveal-delay:{{ ($i % 5) * 70 }}ms"
                     class="rounded-lg border border-white/60 bg-white/95 px-3.5 py-4 shadow-sm backdrop-blur-sm transition hover:-translate-y-1 hover:shadow-md">
                    @if ($card['image'])
                        <span class="mx-auto grid h-10 w-10 place-items-center">
                            <img src="{{ media($card['image']) }}" alt="" class="h-10 w-10 object-contain">
                        </span>
                    @else
                        <span class="mx-auto grid h-10 w-10 place-items-center rounded-full bg-amber-400 text-slate-900">
                            <x-icon name="{{ $card['icon'] }}" class="h-5 w-5" solid />
                        </span>
                    @endif
                    <h3 class="mt-2.5 text-[13px] font-black text-slate-900">{{ $card['title'] }}</h3>
                    <p class="mt-1.5 text-[11px] leading-snug text-slate-500">{{ $card['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- trust strip --}}
    <div class="bg-slate-900 py-4 text-white">
        <div class="mx-auto flex max-w-7xl flex-col items-center gap-5 px-4 sm:px-6 lg:flex-row lg:gap-8">
            <div class="flex shrink-0 items-center gap-3 lg:w-64">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-amber-400 text-slate-900">
                    <x-icon name="shield-check" class="h-6 w-6" solid />
                </span>
                <div class="text-center lg:text-left">
                    <p class="text-sm font-black leading-tight">Your Trusted<br class="hidden lg:block"> HVAC Supply Partner</p>
                    <p class="mt-1 text-[9px] font-semibold uppercase tracking-[0.15em] text-slate-400">Quality products &bull; Better prices &bull; On-time delivery</p>
                </div>
            </div>

            <div class="grid flex-1 grid-cols-2 gap-x-4 gap-y-4 sm:grid-cols-3 lg:grid-cols-6 lg:divide-x lg:divide-white/10">
                @foreach ([
                    ['star', 'Quality Products'],
                    ['tag', 'Better Prices'],
                    ['clock', 'On-Time Delivery'],
                    ['shield-check', '100% Genuine Products'],
                    ['box', 'Fast & Reliable Shipping'],
                    ['headset', 'Expert Support Always'],
                ] as [$icon, $label])
                    <div class="flex flex-col items-center gap-1 px-2 text-center">
                        <x-icon name="{{ $icon }}" class="h-5 w-5 text-amber-400" solid />
                        <span class="text-[11px] font-bold leading-tight">{{ $label }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ============================ REVIEWS SLIDER ============================ --}}
@php
    $testimonials = $reviews->count()
        ? $reviews->map(fn ($r) => ['name' => $r->author_name, 'role' => 'Verified Buyer', 'rating' => $r->rating, 'body' => $r->body])
        : collect([
            ['name' => 'Tonya Beck', 'role' => 'Contractor', 'rating' => 5, 'body' => 'Had the exact part in stock and shipped quickly. Saved my install timeline.'],
            ['name' => 'Lee Krohn', 'role' => 'HVAC Tech', 'rating' => 5, 'body' => 'Great selection and support helped me pick the right unit. Five stars.'],
            ['name' => 'Janet Thompson', 'role' => 'Homeowner', 'rating' => 5, 'body' => 'Best prices I found online. Fast, reliable delivery every time.'],
            ['name' => 'Andy Sawyer', 'role' => 'Plumber', 'rating' => 4, 'body' => 'Arrived perfectly packed and exactly as described. Will reorder.'],
            ['name' => 'Maya Joshi', 'role' => 'Facilities Mgr', 'rating' => 5, 'body' => 'Net-30 terms and bulk pricing make reordering effortless.'],
        ]);
@endphp
<section class="bg-slate-50 py-16">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <div data-reveal class="mb-10 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-amber-500">Loved by 50,000+ customers</p>
                <h2 class="mt-1 text-3xl font-black tracking-tight text-slate-900">What Our Customers Say</h2>
            </div>
            <div class="flex gap-2">
                <button type="button" data-slider-prev aria-label="Previous" class="grid h-11 w-11 place-items-center rounded-full border border-slate-300 bg-white text-slate-600 transition hover:border-amber-400 hover:text-amber-600 active:scale-95"><x-icon name="arrow" class="h-5 w-5 rotate-180" /></button>
                <button type="button" data-slider-next aria-label="Next" class="grid h-11 w-11 place-items-center rounded-full border border-slate-300 bg-white text-slate-600 transition hover:border-amber-400 hover:text-amber-600 active:scale-95"><x-icon name="arrow" class="h-5 w-5" /></button>
            </div>
        </div>
        <div data-reveal data-slider class="group/slider relative">
            <div data-slider-track class="no-scrollbar flex snap-x snap-mandatory gap-5 overflow-x-auto scroll-smooth pb-2">
                @foreach ($testimonials as $t)
                    <figure data-slide class="flex w-[86%] shrink-0 snap-start flex-col rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:w-[calc(50%-10px)] lg:w-[calc(33.333%-14px)]">
                        <div class="flex text-amber-400">
                            @for ($s = 0; $s < $t['rating']; $s++)<x-icon name="star" class="h-4 w-4 fill-amber-400" />@endfor
                        </div>
                        <blockquote class="mt-3 flex-1 text-[15px] leading-relaxed text-slate-600">“{{ $t['body'] }}”</blockquote>
                        <figcaption class="mt-5 flex items-center gap-3 border-t border-slate-100 pt-4">
                            <span class="grid h-10 w-10 place-items-center rounded-full bg-gradient-to-br from-slate-800 to-black font-bold text-amber-400">{{ substr($t['name'], 0, 1) }}</span>
                            <span>
                                <span class="block text-sm font-semibold text-slate-800">{{ $t['name'] }}</span>
                                <span class="block text-xs text-slate-400">{{ $t['role'] }}</span>
                            </span>
                        </figcaption>
                    </figure>
                @endforeach
            </div>
            <div data-slider-dots class="mt-7 flex justify-center gap-2"></div>
        </div>
    </div>
</section>

{{-- ============================ WHOLESALE PROGRAM BANNER ============================ --}}
@php
    // Full-bleed wholesale banner. Managed from Admin → Sliders → Wholesale Banner
    // (image + text); a warehouse placeholder shows until an image is uploaded.
    $wallpaper = \App\Models\Setting::get('home_wallpaper');
    $wallpaperImg = $wallpaper
        ? media($wallpaper)
        : 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=1920&q=80';
    $wallHeading  = \App\Models\Setting::get('home_wallpaper_heading') ?: 'Apply for|Wholesale';
    $wallSubtitle = \App\Models\Setting::get('home_wallpaper_subtitle') ?: 'Get exclusive wholesale pricing on premium HVAC tools, parts, and maintenance products.';
    $wallCta      = \App\Models\Setting::get('home_wallpaper_cta') ?: 'Apply for Wholesale';
    $wallLink     = \App\Models\Setting::get('home_wallpaper_link') ?: 'https://drive.google.com/file/d/15dBhYjEqmQQyUhlImXpeU57J6pdxfljY/view?usp=drive_link';
    $wallExternal = \Illuminate\Support\Str::startsWith($wallLink, ['http://', 'https://']) && ! \Illuminate\Support\Str::contains($wallLink, request()->getHost());
    // heading may carry a "|" to split the plain first line from the amber second line
    [$wallHead1, $wallHead2] = array_pad(explode('|', $wallHeading, 2), 2, null);

    // darkness of the left→right overlay (0 = bright image, 90 = very dark left side)
    $wallOv = \App\Models\Setting::get('home_wallpaper_overlay');
    $wallOv = ($wallOv === null || $wallOv === '') ? 75 : max(0, min(90, (int) $wallOv));
    $ovL = $wallOv / 100;                 // darkest, at the left where the text sits
    $ovM = round($wallOv * 0.6) / 100;    // mid
    $ovR = round($wallOv * 0.18) / 100;   // lightest, at the right
@endphp
<section data-reveal class="relative mb-16 overflow-hidden bg-slate-900">
    <img src="{{ $wallpaperImg }}" alt="Wholesale program"
         class="absolute inset-0 h-full w-full object-cover">
    <div class="absolute inset-0" style="background: linear-gradient(to right, rgba(0,0,0,{{ $ovL }}) 0%, rgba(0,0,0,{{ $ovM }}) 55%, rgba(0,0,0,{{ $ovR }}) 100%);"></div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6">
        <div class="max-w-2xl py-14 text-white sm:py-20">
            {{-- eyebrow --}}
            <div class="flex items-center gap-3">
                <span class="text-xs font-black uppercase tracking-[0.2em] text-amber-400">Wholesale Program</span>
                <span class="h-px w-16 bg-amber-400/60"></span>
            </div>

            {{-- heading --}}
            <h2 class="mt-5 text-4xl font-black leading-[1.05] tracking-tight sm:text-6xl">
                {{ $wallHead1 }}@if ($wallHead2)<br><span class="text-amber-400">{{ $wallHead2 }}</span>@endif
            </h2>
            <span class="mt-5 block h-1 w-16 rounded bg-white/70"></span>

            {{-- subtitle --}}
            <p class="mt-5 max-w-md text-base leading-relaxed text-slate-200 sm:text-lg">
                {{ $wallSubtitle }}
            </p>

            {{-- buttons --}}
            <div class="mt-8 flex flex-wrap gap-4">
                <a href="{{ $wallLink }}" @if ($wallExternal) target="_blank" rel="noopener" @endif
                   class="group inline-flex items-center gap-3 rounded-lg bg-amber-400 px-7 py-3.5 text-sm font-black uppercase tracking-wide text-slate-900 transition hover:bg-amber-300">
                    <x-icon name="user" class="h-5 w-5" />
                    {{ $wallCta }}
                    <x-icon name="arrow" class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                </a>
                <a href="{{ route('contact.index') }}"
                   class="group inline-flex items-center gap-3 rounded-lg border border-white/40 px-7 py-3.5 text-sm font-black uppercase tracking-wide text-white transition hover:bg-white/10">
                    <x-icon name="phone" class="h-5 w-5" />
                    Contact Sales
                    <x-icon name="arrow" class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                </a>
            </div>

            {{-- feature strip --}}
            <div class="mt-10 flex flex-wrap items-center gap-x-6 gap-y-4 text-sm">
                @foreach ([
                    ['star', 'Exclusive', 'Wholesale Pricing'],
                    ['truck', 'Fast &', 'Reliable Delivery'],
                    ['chat', 'Dedicated', 'Support'],
                ] as $i => [$icon, $line1, $line2])
                    @if ($i > 0)<span class="hidden h-8 w-px bg-white/20 sm:block"></span>@endif
                    <div class="flex items-center gap-3">
                        <x-icon name="{{ $icon }}" class="h-6 w-6 shrink-0 text-amber-400" />
                        <span class="font-bold leading-tight text-white">{{ $line1 }}<br>{{ $line2 }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- ============================ LATEST BLOG POSTS ============================ --}}
@if (($latestPosts ?? collect())->count())
<section class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
    <div data-reveal class="mb-10 text-center">
        <h2 class="text-2xl font-black uppercase tracking-tight text-slate-900 sm:text-3xl">Latest Posts</h2>
        <p class="mt-2 text-sm text-slate-500">Visit our blog to learn more HVAC tips and tricks!</p>
    </div>

    <div class="grid gap-6 md:grid-cols-3">
        @foreach ($latestPosts as $i => $post)
            <article data-reveal style="--reveal-delay:{{ $i * 80 }}ms"
                     class="group flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition duration-300 hover:-translate-y-1 hover:border-amber-200 hover:shadow-xl hover:shadow-slate-200/70">
                <a href="{{ route('blog.show', $post) }}" class="block aspect-[16/10] overflow-hidden bg-slate-100">
                    @if ($post->image)
                        <img src="{{ media($post->image) }}" alt="{{ $post->title }}" loading="lazy" decoding="async" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                    @else
                        <span class="grid h-full w-full place-items-center text-slate-300"><x-icon name="edit" class="h-10 w-10" /></span>
                    @endif
                </a>
                <div class="flex flex-1 flex-col p-6">
                    <a href="{{ route('blog.show', $post) }}" class="line-clamp-2 text-lg font-black uppercase leading-snug tracking-tight text-slate-900 transition group-hover:text-amber-600">{{ $post->title }}</a>
                    <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ optional($post->date)->format('M j, Y') }}</p>
                    @if ($post->excerpt)
                        <p class="mt-3 line-clamp-3 text-sm text-slate-500">{{ $post->excerpt }}</p>
                    @endif
                    <a href="{{ route('blog.show', $post) }}" class="mt-4 inline-flex items-center gap-1.5 text-sm font-bold text-amber-600">
                        View details <x-icon name="arrow" class="h-4 w-4" />
                    </a>
                </div>
            </article>
        @endforeach
    </div>

    <div class="mt-8 text-center">
        <a href="{{ route('blog.index') }}" class="inline-flex items-center gap-2 rounded-full border border-slate-300 px-6 py-2.5 text-sm font-bold text-slate-800 transition hover:border-amber-300 hover:bg-amber-50">
            View all posts <x-icon name="arrow" class="h-4 w-4" />
        </a>
    </div>
</section>
@endif

{{-- ============================ NEWSLETTER CTA ============================ --}}
<section class="mx-auto max-w-7xl px-4 pb-20 sm:px-6">
    <div data-reveal class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-black via-slate-900 to-slate-900 px-8 py-14 text-center text-white">
        <div class="pointer-events-none absolute -right-16 -top-16 h-56 w-56 animate-blob bg-amber-500/20 blur-2xl"></div>
        <div class="pointer-events-none absolute -left-16 -bottom-16 h-56 w-56 animate-blob bg-amber-400/10 blur-2xl" style="animation-delay:-5s"></div>
        <div class="relative mx-auto max-w-xl">
            <h2 class="text-3xl font-black tracking-tight">Get $5 off your first order</h2>
            <p class="mt-3 text-slate-300">Join our list for exclusive deals, new arrivals, and pro tips.</p>
            <form method="POST" action="{{ route('newsletter.subscribe') }}" data-newsletter class="mx-auto mt-7 flex max-w-md overflow-hidden rounded-full bg-white p-1.5 shadow-xl">
                @csrf
                <input type="hidden" name="source" value="home">
                <input type="email" name="email" required placeholder="Enter your email" class="w-full bg-transparent px-5 text-sm text-slate-700 outline-none">
                <button class="sheen shrink-0 rounded-full bg-amber-400 px-6 py-2.5 text-sm font-bold text-slate-900 transition hover:bg-amber-500">Subscribe</button>
            </form>
            <p data-newsletter-msg class="mt-3 hidden text-sm font-semibold text-amber-300"></p>
            <p class="mt-3 text-xs text-slate-400">No spam. Unsubscribe anytime.</p>
        </div>
    </div>
</section>

@endsection
