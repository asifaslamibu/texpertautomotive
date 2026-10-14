@extends('layouts.app')

@section('title', 'Car Audio, Electronics & Accessories — Texpert Automotive')
@section('meta_description', 'Texpert Automotive sells premium car audio, electronics and accessories — speakers, subwoofers, amplifiers, stereo receivers and installation kits from authorized brands, shipped fast.')

@section('content')
@php
    use Illuminate\Support\Str;

    $wrap = 'mx-auto max-w-7xl px-4 sm:px-6';
    // free Unsplash photography for the banners until the store uploads its own
    $photo = fn (string $id, int $w = 1200, ?int $h = null) => "https://images.unsplash.com/photo-{$id}?auto=format&q=70&w={$w}".($h ? "&h={$h}&fit=crop" : '');

    // ---- categories: real departments, else the car-audio set from the design ----
    $catIcons = ['audio' => 'screen', 'electronic' => 'screen', 'install' => 'frame', 'speaker' => 'speaker', 'sub' => 'speaker',
                 'amp' => 'amplifier', 'stereo' => 'radio', 'receiver' => 'radio', 'safety' => 'camera', 'security' => 'camera',
                 'camera' => 'camera', 'power' => 'cable', 'wiring' => 'cable', 'interior' => 'seat', 'exterior' => 'seat',
                 'care' => 'bottle', 'perform' => 'intake'];
    $iconFor = function (string $name) use ($catIcons) {
        foreach ($catIcons as $kw => $icon) {
            if (Str::contains(Str::lower($name), $kw)) return $icon;
        }
        return 'car';
    };
    // busiest sub-categories (each with a product photo) fill the row best; then departments; then the demo set
    $categories = $popularCategories->count() >= 5
        ? $popularCategories->take(10)->map(fn ($c) => (object) ['name' => $c->name, 'url' => route('products.index', ['category' => $c->slug]), 'image' => ($c->image ?: $c->sample_image) ? media($c->image ?: $c->sample_image) : null, 'icon' => $iconFor($c->name)])
        : ($departments->count()
        ? $departments->take(10)->map(fn ($d) => (object) ['name' => $d->name, 'url' => route('products.index', ['category' => $d->slug]), 'image' => $d->image ? media($d->image) : null, 'icon' => $iconFor($d->name)])
        : collect(['Car Audio & Mobile Electronics', 'Installation & Integration', 'Speakers & Subwoofers', 'Amplifiers', 'Stereo Receivers', 'Safety & Security', 'Power & Wiring', 'Interior & Exterior', 'Car Care', 'Performance'])
            ->map(fn ($n) => (object) ['name' => $n, 'url' => route('products.index', ['q' => $n]), 'image' => null, 'icon' => $iconFor($n)]));

    // ---- best sellers: curated featured products, else top rated, else the design's line-up (no photo → icon art) ----
    $bestSellers = $featuredProducts->count() ? $featuredProducts->take(6) : ($topRated->count() ? $topRated->take(6) : collect([
        ['Kenwood DMX4707S', '6.8" Touchscreen Receiver', 349.99, 128, 'screen'],
        ['JBL Stage 12" Subwoofer', '(1000W)', 129.99, 96, 'speaker'],
        ['Alpine S2-A60M', 'Mono Amplifier', 159.99, 74, 'amplifier'],
        ['Kicker 6.5" Component', 'Speaker System', 199.99, 112, 'speaker'],
        ['Pioneer Backup Camera', 'HD Night Vision', 89.99, 66, 'camera'],
        ['Metra Dash Kit', '(Select Vehicles)', 49.99, 41, 'frame'],
    ])->map(fn ($p) => (object) ['name' => $p[0], 'sub' => $p[1], 'price' => $p[2], 'reviews_count' => $p[3], 'rating' => 4.5, 'icon' => $p[4]]));

    // ---- promo cards: the catalogue's three biggest brands (real best discount + product photo)
    //      and its busiest category; the design's car-audio offers until there is a catalogue ----
    $promoStyles = [
        ['bg-gradient-to-br from-[#fbe3d1] to-[#f5c9a8] text-[#111]', 'text-[#111]/15'],
        ['bg-gradient-to-br from-[#0d1b33] to-[#1a2d52] text-white', 'text-white/15'],
        ['bg-gradient-to-br from-[#121212] to-[#2a2a2a] text-white', 'text-white/15'],
        ['bg-gradient-to-br from-[#c8102e] to-[#e0301e] text-white', 'text-white/20'],
    ];
    $topCategory = $popularCategories->first();
    $promos = ($promoBrands->count() >= 3 && $topCategory)
        ? $promoBrands->map(fn ($b) => (object) [
                'eyebrow' => $b->max_off > 0 ? 'Up to' : 'Shop',
                'big' => $b->max_off > 0 ? $b->max_off.'% OFF' : $b->brand, 'long' => $b->max_off <= 0,
                'sub' => $b->max_off > 0 ? Str::limit($b->brand, 28) : $b->products_count.' products',
                'url' => route('products.index', ['brands' => [$b->brand]]),
                'image' => $b->image ? media($b->image) : null, 'icon' => 'box',
            ])->push((object) [
                'eyebrow' => 'Shop', 'big' => $topCategory->name, 'long' => true, 'sub' => $topCategory->products_count.' products',
                'url' => route('products.index', ['category' => $topCategory->slug]),
                'image' => ($topCategory->image ?: $topCategory->sample_image) ? media($topCategory->image ?: $topCategory->sample_image) : null, 'icon' => 'box',
            ])
        : collect([
            ['Up to', '25% OFF', 'JBL Audio Products', 'speaker', ['brand' => 'JBL']],
            ['Up to', '20% OFF', 'Alpine Products', 'amplifier', ['brand' => 'Alpine']],
            ['Up to', '15% OFF', 'Kenwood Car Electronics', 'screen', ['brand' => 'Kenwood']],
            ['Shop', 'Installation Kits', 'Dash Kits, Harnesses & More', 'frame', ['q' => 'installation kit']],
        ])->map(fn ($c) => (object) ['eyebrow' => $c[0], 'big' => $c[1], 'sub' => $c[2], 'url' => route('products.index', $c[4]), 'image' => null, 'icon' => $c[3]]);

    // ---- brands: featured brands, else the design's ten ----
    $brandRow = $popularBrands->count()
        ? $popularBrands->take(10)->map(fn ($b) => (object) ['name' => $b->name, 'url' => $b->url, 'logo' => $b->logo ? media($b->logo) : null])
        : collect(['JBL', 'Alpine', 'Kenwood', 'Pioneer', 'Kicker', 'Sony', 'JL Audio', 'Focal', 'Metra', 'Crux'])
            ->map(fn ($n) => (object) ['name' => $n, 'url' => route('products.index', ['brand' => $n]), 'logo' => null]);

    // ---- articles ----
    $articles = $latestPosts->count()
        ? $latestPosts->map(fn ($p) => (object) ['title' => $p->title, 'excerpt' => $p->excerpt, 'image' => $p->image ? media($p->image) : null, 'url' => route('blog.show', $p)])
        : collect([
            ['Car Amplifier Buying Guide', 'How much power do you need and how many channels? Get expert advice.', '1606577924006-27d39b132ae2'],
            ['Choosing the Right Car Speakers', 'Learn about speaker types, sizes and what to look for for the best sound.', '1503376780353-7e6692767b70'],
            ['How to Upgrade Your Factory Stereo', 'Complete guide to modern car audio upgrades and installation options.', '1617814076367-b759c7d7e738'],
            ['Car Audio Installation Guide', 'Tips, tools and best practices for a clean and reliable installation.', '1487754180451-c456f719a1fc'],
        ])->map(fn ($a) => (object) ['title' => $a[0], 'excerpt' => $a[1], 'image' => $photo($a[2], 400, 300), 'url' => route('blog.index')]);

    $sliderMs = max(2, min(30, (int) \App\Models\Setting::get('hero_slider_duration', 5) ?: 5)) * 1000;
    $heading = 'text-xl font-extrabold tracking-tight text-[#111] sm:text-[22px]';
    $viewAll = 'inline-flex items-center gap-1 text-sm font-semibold text-blue-600 transition hover:text-amber-600';
@endphp

{{-- ============================ HERO ============================ --}}
<section class="relative overflow-hidden bg-[#0b0b0b] text-white">
    @if ($banners->count())
        {{-- admin-managed slides --}}
        <div data-slider data-interval="{{ $sliderMs }}" class="relative">
            <div data-slider-track class="no-scrollbar flex snap-x snap-mandatory overflow-x-auto scroll-smooth">
                @foreach ($banners as $slide)
                    <div data-slide class="relative flex min-h-[340px] w-full shrink-0 snap-start items-center lg:min-h-[420px]">
                        @if ($slide->video)
                            <video class="absolute inset-0 hidden h-full w-full object-cover md:block" muted playsinline preload="none" data-speed="{{ $slide->video_speed ?? 1 }}" @if ($slide->image) poster="{{ media($slide->image) }}" @endif>
                                <source src="{{ media($slide->video) }}">
                            </video>
                        @elseif ($slide->image)
                            <img src="{{ media($slide->image) }}" alt="" class="absolute inset-0 h-full w-full object-cover" @if ($loop->first) fetchpriority="high" @endif>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-r from-black/85 via-black/50 to-transparent"></div>
                        <div class="{{ $wrap }} relative w-full py-12">
                            @if ($slide->title)<h1 class="max-w-xl text-4xl font-black uppercase italic leading-[0.95] sm:text-6xl">{{ $slide->title }}</h1>@endif
                            @if ($slide->subtitle)<p class="mt-4 max-w-md text-lg text-white/85">{{ $slide->subtitle }}</p>@endif
                            @if ($slide->cta_label)
                                <a href="{{ $slide->cta_url }}" class="mt-6 inline-flex items-center gap-2 rounded bg-amber-500 px-6 py-2.5 text-sm font-bold text-white transition hover:bg-amber-600">{{ $slide->cta_label }} <x-icon name="arrow" class="h-4 w-4" /></a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
            <div data-slider-dots class="absolute inset-x-0 bottom-20 flex justify-center gap-2"></div>
        </div>
    @else
        {{-- default hero --}}
        <img src="{{ $photo('1626668893632-6f3a4466d22f', 1800, 700) }}" alt="" fetchpriority="high" class="absolute inset-0 h-full w-full object-cover object-center">
        <div class="absolute inset-0 bg-gradient-to-r from-black via-black/70 to-black/10"></div>
        {{-- orange speed stripes, as in the brand artwork --}}
        <div class="pointer-events-none absolute -left-24 top-0 h-full w-72 -skew-x-[24deg] bg-gradient-to-b from-amber-500/0 via-amber-500/25 to-amber-500/0"></div>
        <div class="pointer-events-none absolute -left-10 top-0 h-full w-3 -skew-x-[24deg] bg-amber-500/70"></div>

        <div class="{{ $wrap }} relative flex min-h-[340px] items-center py-12 lg:min-h-[420px]">
            <div class="max-w-xl">
                <h1 class="text-[44px] font-black uppercase italic leading-[0.92] tracking-tight sm:text-7xl" style="text-shadow:0 4px 24px rgb(0 0 0 / .5)">
                    Upgrade<br><span class="text-amber-500">Your Drive</span>
                </h1>
                <p class="mt-5 max-w-sm text-lg leading-snug text-white/90">Premium Car Audio, Electronics &amp; Accessories for Every Vehicle.</p>
                <a href="{{ route('products.index') }}" class="mt-6 inline-flex items-center gap-2 rounded bg-amber-500 px-6 py-2.5 text-sm font-bold text-white shadow-lg shadow-amber-500/30 transition hover:bg-amber-600">
                    Shop Now <x-icon name="arrow" class="h-4 w-4" />
                </a>
            </div>
        </div>
    @endif

    {{-- feature strip --}}
    <div class="relative border-t border-white/10 bg-black/70 backdrop-blur-sm">
        <div class="{{ $wrap }} grid grid-cols-2 gap-x-4 gap-y-3 py-3 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ([
                ['badge', 'Top Brands', '100% Genuine Products'],
                ['truck', 'Fast Shipping', 'On Orders Over $99'],
                ['headset', 'Expert Support', 'Talk to a Product Specialist'],
                ['shield-check', 'Warranty Backed', 'Manufacturer Warranty'],
                ['lock', 'Secure Checkout', 'Shop with Confidence'],
            ] as [$icon, $title, $sub])
                <div class="flex items-center gap-2.5">
                    <x-icon name="{{ $icon }}" class="h-6 w-6 shrink-0 text-amber-500" />
                    <span class="leading-tight">
                        <span class="block text-xs font-bold">{{ $title }}</span>
                        <span class="block text-[10px] text-white/65">{{ $sub }}</span>
                    </span>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================ PROMO CARDS ============================ --}}
<section class="{{ $wrap }} mt-3">
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($promos as $i => $promo)
            @php [$bg, $art] = $promoStyles[$i % 4]; @endphp
            <a href="{{ $promo->url }}" data-reveal style="--reveal-delay:{{ $i * 60 }}ms"
               class="group relative flex min-h-[112px] items-center gap-3 overflow-hidden rounded-md px-5 py-4 transition hover:-translate-y-0.5 hover:shadow-xl {{ $bg }}">
                <span class="relative min-w-0 flex-1">
                    <span class="block text-sm font-bold">{{ $promo->eyebrow }}</span>
                    {{-- a percentage reads big; a brand or category name gets two smaller lines instead of being cut off --}}
                    <span class="{{ ($promo->long ?? false) ? 'line-clamp-2 text-lg leading-tight sm:text-xl' : 'block text-2xl leading-none sm:text-[26px]' }} font-black tracking-tight">{{ $promo->big }}</span>
                    <span class="mt-1 block truncate text-xs opacity-80">{{ $promo->sub }}</span>
                    <span class="mt-2.5 inline-flex w-fit items-center gap-1.5 rounded bg-amber-500 px-3 py-1.5 text-xs font-bold text-white">Shop Now <x-icon name="arrow" class="h-3.5 w-3.5" /></span>
                </span>
                @if ($promo->image)
                    {{-- product shots are on white: frame them so they sit cleanly on any card colour --}}
                    <span class="relative grid h-[88px] w-[88px] shrink-0 place-items-center overflow-hidden rounded-md bg-white p-1.5 shadow-lg ring-1 ring-black/5 transition duration-500 group-hover:scale-105">
                        <img src="{{ $promo->image }}" alt="" loading="lazy" class="max-h-full max-w-full object-contain">
                    </span>
                @else
                    <x-icon name="{{ $promo->icon }}" class="absolute -right-4 top-1/2 h-32 w-32 -translate-y-1/2 transition duration-500 group-hover:scale-110 {{ $art }}" />
                @endif
            </a>
        @endforeach
    </div>
</section>

{{-- ============================ SHOP BY CATEGORY ============================ --}}
<section class="{{ $wrap }} mt-8">
    <div class="mb-3 flex items-end justify-between">
        <h2 class="{{ $heading }}">Shop by Category</h2>
        <a href="{{ route('products.index') }}" class="{{ $viewAll }}">View All Categories <x-icon name="arrow" class="h-4 w-4" /></a>
    </div>
    <div class="grid grid-cols-3 gap-2 sm:grid-cols-5 lg:grid-cols-10">
        @foreach ($categories as $i => $cat)
            <a href="{{ $cat->url }}" data-reveal style="--reveal-delay:{{ $i * 40 }}ms"
               class="group flex flex-col items-center rounded-md border border-slate-200 bg-white px-2 pb-3 pt-3 text-center transition hover:-translate-y-0.5 hover:border-amber-500 hover:shadow-lg">
                <span class="grid h-16 w-full place-items-center">
                    @if ($cat->image)
                        <img src="{{ $cat->image }}" alt="{{ $cat->name }}" loading="lazy" class="max-h-16 max-w-full object-contain transition group-hover:scale-105">
                    @else
                        <x-icon name="{{ $cat->icon }}" class="h-11 w-11 text-[#222] transition group-hover:scale-110 group-hover:text-amber-500" />
                    @endif
                </span>
                <span class="mt-2 text-[11.5px] font-semibold leading-tight text-[#111]">{{ $cat->name }}</span>
            </a>
        @endforeach
    </div>
</section>

{{-- ============================ BEST SELLING ============================ --}}
<section class="{{ $wrap }} mt-8">
    <div class="mb-3 flex items-end justify-between">
        <h2 class="{{ $heading }}">Best Selling Products</h2>
        <a href="{{ route('products.index', ['sort' => 'rating']) }}" class="{{ $viewAll }}">View All Products <x-icon name="arrow" class="h-4 w-4" /></a>
    </div>
    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
        @foreach ($bestSellers as $i => $p)
            @php
                $isModel = $p instanceof \App\Models\Product;
                $url = $isModel ? route('products.show', $p) : route('products.index', ['q' => $p->name]);
                $stars = (float) $p->rating;
            @endphp
            <div data-reveal style="--reveal-delay:{{ $i * 50 }}ms" class="group relative flex flex-col rounded-md border border-slate-200 bg-white p-3 transition hover:border-amber-500 hover:shadow-lg">
                <span class="absolute right-3 top-3 z-10 text-blue-600" aria-hidden="true"><x-icon name="heart" class="h-4 w-4" /></span>
                <a href="{{ $url }}" class="grid aspect-square place-items-center overflow-hidden">
                    @if ($isModel)
                        <img src="{{ media($p->image) }}" alt="{{ $p->name }}" loading="lazy" class="h-full w-full object-contain transition duration-500 group-hover:scale-105">
                    @else
                        <x-icon name="{{ $p->icon }}" class="h-20 w-20 text-[#1f1f1f] transition duration-500 group-hover:scale-105" />
                    @endif
                </a>
                <a href="{{ $url }}" class="mt-2 line-clamp-2 min-h-9 text-[12.5px] font-semibold leading-[18px] text-[#111] transition hover:text-amber-600">
                    {{ $p->name }}@if (! $isModel && $p->sub)<br><span class="font-normal">{{ $p->sub }}</span>@endif
                </a>
                <p class="mt-1.5 text-lg font-extrabold text-[#111]">${{ number_format($p->price, 2) }}</p>
                <div class="flex items-center gap-1">
                    <span class="flex text-amber-500">
                        @for ($s = 1; $s <= 5; $s++)
                            <x-icon name="star" solid class="h-3.5 w-3.5 {{ $s <= round($stars) ? '' : 'opacity-25' }}" />
                        @endfor
                    </span>
                    <span class="text-[11px] text-slate-500">({{ $p->reviews_count }})</span>
                </div>
                @if ($isModel && $p->in_stock)
                    <form method="POST" action="{{ route('cart.add', $p) }}" data-cart-add class="mt-3">
                        @csrf
                        <button type="submit" class="w-full rounded bg-amber-500 py-2 text-xs font-bold text-white transition hover:bg-amber-600">Add to Cart</button>
                    </form>
                @else
                    <a href="{{ $url }}" class="mt-3 block rounded bg-amber-500 py-2 text-center text-xs font-bold text-white transition hover:bg-amber-600">{{ $isModel ? 'View Product' : 'Add to Cart' }}</a>
                @endif
            </div>
        @endforeach
    </div>
</section>

{{-- ============================ SHOP BY BRAND ============================ --}}
<section class="{{ $wrap }} mt-8">
    <div class="mb-3 flex items-end justify-between">
        <h2 class="{{ $heading }}">Shop by Brand</h2>
        <a href="{{ route('brands.index') }}" class="{{ $viewAll }}">View All Products <x-icon name="arrow" class="h-4 w-4" /></a>
    </div>
    <div class="grid grid-cols-3 gap-2 sm:grid-cols-5 lg:grid-cols-10">
        @foreach ($brandRow as $i => $brand)
            <a href="{{ $brand->url }}" title="{{ $brand->name }}"
               class="group grid h-16 min-w-0 place-items-center rounded-md border border-slate-200 bg-white px-3 transition hover:-translate-y-0.5 hover:border-amber-500 hover:shadow-lg">
                @if ($brand->logo)
                    <img src="{{ $brand->logo }}" alt="{{ $brand->name }}" loading="lazy" class="max-h-9 max-w-full object-contain">
                @else
                    <span class="max-w-full truncate pr-1 text-base font-black uppercase italic tracking-tight text-[#1a1a1a] transition group-hover:text-amber-500 lg:text-lg {{ $i === 0 ? 'text-amber-500' : '' }}">{{ $brand->name }}</span>
                @endif
            </a>
        @endforeach
    </div>
</section>

{{-- ============================ VEHICLE FINDER + CUSTOM QUOTE ============================ --}}
<section class="{{ $wrap }} mt-8">
    <div class="grid gap-3 lg:grid-cols-[1.6fr_1fr]">
        {{-- fit finder --}}
        <div class="relative overflow-hidden rounded-md bg-[#141414] p-5 text-white sm:p-6">
            <img src="{{ $photo('1563720223185-11003d516935', 900, 400) }}" alt="" loading="lazy" class="absolute inset-y-0 right-0 hidden h-full w-1/2 object-cover opacity-80 sm:block">
            <div class="absolute inset-0 hidden bg-gradient-to-r from-[#141414] via-[#141414]/95 to-transparent sm:block"></div>
            <div class="relative flex gap-4">
                <x-icon name="car" class="hidden h-10 w-10 shrink-0 text-white sm:block" />
                <div class="min-w-0 flex-1">
                    <h3 class="text-xl font-extrabold">Not sure what fits your vehicle?</h3>
                    <p class="mt-1 text-sm text-white/80">Enter your vehicle details to find compatible products.</p>
                    <form action="{{ route('products.index') }}" method="GET" data-fit-finder class="mt-4 flex flex-wrap gap-2">
                        <input type="hidden" name="q">
                        <select data-fit="year" aria-label="Year" class="h-10 min-w-[100px] flex-1 rounded bg-white px-3 text-sm text-slate-700 outline-none sm:max-w-[150px]">
                            <option value="">Year</option>
                            @for ($y = (int) date('Y') + 1; $y >= 1990; $y--)<option>{{ $y }}</option>@endfor
                        </select>
                        <select data-fit="make" aria-label="Make" class="h-10 min-w-[100px] flex-1 rounded bg-white px-3 text-sm text-slate-700 outline-none sm:max-w-[150px]">
                            <option value="">Make</option>
                            @foreach (['Acura', 'BMW', 'Chevrolet', 'Chrysler', 'Dodge', 'Ford', 'GMC', 'Honda', 'Hyundai', 'Jeep', 'Kia', 'Lexus', 'Mazda', 'Mercedes-Benz', 'Nissan', 'Ram', 'Subaru', 'Tesla', 'Toyota', 'Volkswagen'] as $make)<option>{{ $make }}</option>@endforeach
                        </select>
                        <input data-fit="model" aria-label="Model" placeholder="Model" class="h-10 min-w-[100px] flex-1 rounded bg-white px-3 text-sm text-slate-700 outline-none sm:max-w-[150px]">
                        <button type="submit" class="h-10 rounded bg-amber-500 px-5 text-sm font-bold text-white transition hover:bg-amber-600">Go</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- custom quote --}}
        <div class="relative overflow-hidden rounded-md bg-[#141414] p-5 text-white sm:p-6">
            <img src="{{ $photo('1617814076367-b759c7d7e738', 600, 400) }}" alt="" loading="lazy" class="absolute inset-y-0 right-0 h-full w-1/2 object-cover opacity-60">
            <div class="absolute inset-0 bg-gradient-to-r from-[#141414] via-[#141414]/90 to-transparent"></div>
            <div class="relative flex gap-4">
                <x-icon name="quote" class="h-10 w-10 shrink-0 text-white" />
                <div>
                    <h3 class="text-xl font-extrabold">Need a custom quote?</h3>
                    <p class="mt-1 max-w-[260px] text-sm text-white/80">Bulk orders, installation products or can't find what you need? We'll help.</p>
                    <a href="{{ route('contact.index', ['subject' => 'Request a Quote']) }}" class="mt-4 inline-block rounded border-2 border-amber-500 px-5 py-1.5 text-sm font-bold text-white transition hover:bg-amber-500">Get Quote</a>
                </div>
            </div>
        </div>
    </div>
</section>
@push('scripts')
<script>
    (() => {
        // the catalogue search takes a single query: join what the shopper picked
        const form = document.querySelector('[data-fit-finder]');
        if (!form) return;
        form.addEventListener('submit', () => {
            form.elements.q.value = ['year', 'make', 'model']
                .map((k) => form.querySelector(`[data-fit="${k}"]`).value.trim()).filter(Boolean).join(' ');
        });
    })();
</script>
@endpush

{{-- ============================ TRUST ROW ============================ --}}
<section class="{{ $wrap }} mt-8">
    <div class="grid grid-cols-2 gap-x-4 gap-y-6 border-y border-slate-200 py-6 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ([
            ['return', 'Easy Returns', 'Hassle-free returns on eligible items'],
            ['truck', 'Fast Shipping', 'Orders over $99 ship fast'],
            ['badge', 'Genuine Products', 'Authorized brands only'],
            ['shield-check', 'Warranty Backed', 'Manufacturer warranty on all major brands'],
            ['headset', 'Expert Support', 'Talk to car audio experts'],
            ['lock', 'Secure Checkout', 'Your payment information is always safe'],
        ] as [$icon, $title, $sub])
            <div class="flex items-start gap-3">
                <x-icon name="{{ $icon }}" class="h-8 w-8 shrink-0 text-amber-500" />
                <span>
                    <span class="block text-[13px] font-bold text-[#111]">{{ $title }}</span>
                    <span class="mt-0.5 block text-xs leading-snug text-slate-500">{{ $sub }}</span>
                </span>
            </div>
        @endforeach
    </div>
</section>

{{-- ============================ ARTICLES & BUYING GUIDES ============================ --}}
<section class="{{ $wrap }} mt-8 mb-10">
    <div class="mb-3 flex items-end justify-between">
        <h2 class="{{ $heading }}">Articles &amp; Buying Guides</h2>
        <a href="{{ route('blog.index') }}" class="{{ $viewAll }}">View All Articles <x-icon name="arrow" class="h-4 w-4" /></a>
    </div>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($articles as $i => $post)
            <a href="{{ $post->url }}" data-reveal style="--reveal-delay:{{ $i * 60 }}ms" class="group flex gap-3">
                <span class="h-[78px] w-[104px] shrink-0 overflow-hidden rounded bg-slate-100">
                    @if ($post->image)
                        <img src="{{ $post->image }}" alt="{{ $post->title }}" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                    @endif
                </span>
                <span class="min-w-0">
                    <span class="line-clamp-2 text-[13px] font-bold leading-tight text-[#111] transition group-hover:text-amber-600">{{ $post->title }}</span>
                    @if ($post->excerpt)<span class="mt-1 line-clamp-2 block text-xs leading-snug text-slate-500">{{ $post->excerpt }}</span>@endif
                    <span class="mt-1.5 inline-flex items-center gap-1 text-xs font-semibold text-amber-600">Read More <x-icon name="arrow" class="h-3.5 w-3.5 transition group-hover:translate-x-0.5" /></span>
                </span>
            </a>
        @endforeach
    </div>
</section>

{{-- ============================ BRAND BANNER ============================ --}}
<section class="relative overflow-hidden bg-[#0d0d0d] text-white">
    {{-- photo fades into the dark panel from the left instead of showing a hard edge --}}
    <img src="{{ $photo('1503376780353-7e6692767b70', 1400, 500) }}" alt="" loading="lazy" class="absolute inset-y-0 right-0 hidden h-full w-3/5 object-cover opacity-75 md:block"
         style="-webkit-mask-image:linear-gradient(to right,transparent,#000 55%);mask-image:linear-gradient(to right,transparent,#000 55%)">
    <div class="{{ $wrap }} relative flex flex-col gap-6 py-10 md:flex-row md:items-center md:gap-10">
        <div class="shrink-0 md:border-r md:border-white/15 md:pr-10">
            @include('layouts.partials.logo-mark', ['size' => 'lg'])
        </div>
        <div class="max-w-xl">
            <h2 class="text-xl font-extrabold">Built for a Better Driving Experience</h2>
            <p class="mt-2 text-sm leading-relaxed text-white/80">At Texpert Automotive, we bring you the best car audio, electronics and accessories from trusted brands. Whether you're upgrading your sound system, adding security, or enhancing your ride, we've got you covered.</p>
        </div>
        <a href="{{ route('about') }}" class="shrink-0 self-start rounded bg-amber-500 px-6 py-2.5 text-sm font-bold text-white transition hover:bg-amber-600 md:self-center">Learn More</a>
    </div>
</section>

@endsection
