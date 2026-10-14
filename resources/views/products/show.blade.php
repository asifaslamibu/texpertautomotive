@extends('layouts.app')

@section('title', \App\Support\Seo::productTitle($product))
@section('meta_description', \App\Support\Seo::productDescription($product))

@section('admin_edit_url', route('admin.products.edit', $product))
@section('admin_edit_label', 'Edit Product')

{{-- SEO: Product + Breadcrumb structured data (JSON-LD) --}}
@push('head')
@php
    $images = collect($product->images ?: [$product->image])->filter()->map(fn ($i) => media($i))->values()->all();

    $productLd = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'image' => $images ?: null,
        'description' => strip_tags($product->short_description ?: $product->description ?: $product->name),
        'sku' => $product->sku ?: null,
        'mpn' => $product->sku ?: null,
        'brand' => $product->brand ? ['@type' => 'Brand', 'name' => $product->brand] : null,
        'offers' => [
            '@type' => 'Offer',
            'url' => url()->current(),
            'priceCurrency' => strtoupper(\App\Models\Setting::get('stripe_currency', 'USD')),
            'price' => number_format((float) $product->price, 2, '.', ''),
            'availability' => $product->in_stock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'itemCondition' => 'https://schema.org/NewCondition',
            'seller' => ['@type' => 'Organization', 'name' => \App\Models\Setting::get('site_name', 'Texpert Automotive')],
        ],
        // only the reviews actually on the page, so the rich result matches what a shopper sees
        'aggregateRating' => ($product->reviews->count() > 0 && $avgRating > 0) ? [
            '@type' => 'AggregateRating',
            'ratingValue' => number_format((float) $avgRating, 1),
            'reviewCount' => $product->reviews->count(),
        ] : null,
    ], fn ($v) => $v !== null && $v !== '');

    $crumbs = [['name' => 'Home', 'item' => route('home')]];
    if ($product->category) {
        $crumbs[] = ['name' => $product->category->name, 'item' => route('products.index', ['category' => $product->category->slug])];
    }
    $crumbs[] = ['name' => $product->name, 'item' => url()->current()];

    $breadcrumbLd = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => collect($crumbs)->map(fn ($c, $i) => [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'name' => $c['name'],
            'item' => $c['item'],
        ])->all(),
    ];
@endphp
<script type="application/ld+json">{!! json_encode($productLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
<script type="application/ld+json">{!! json_encode($breadcrumbLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')

<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">

    {{-- breadcrumb --}}
    <nav class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
        <a href="{{ route('home') }}" class="hover:text-amber-600">Home</a><span class="text-slate-300">/</span>
        <a href="{{ route('products.index') }}" class="hover:text-amber-600">Products</a><span class="text-slate-300">/</span>
        <a href="{{ route('products.index', ['category' => $product->category->slug]) }}" class="hover:text-amber-600">{{ $product->category->name }}</a><span class="text-slate-300">/</span>
        <span class="font-semibold text-slate-700">{{ \Illuminate\Support\Str::limit($product->name, 40) }}</span>
    </nav>

    {{-- ============ TOP: gallery + info ============ --}}
    <div class="mt-6 grid gap-10 lg:grid-cols-2">

        {{-- gallery --}}
        @php $gallery = $product->images; @endphp
         <div class="flex gap-4" data-gallery>
            @if (count($gallery) > 1)
                <div class="flex flex-col gap-3">
                    @foreach ($gallery as $i => $img)
                        <button type="button" data-gallery-thumb data-img="{{ media($img) }}"
                                class="h-16 w-16 overflow-hidden rounded-md border-2 bg-white {{ $i === 0 ? 'border-amber-500' : 'border-slate-200 hover:border-slate-300' }}">
                            <img src="{{ media($img) }}" alt="" class="h-full w-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
            <div class="relative flex-1 rounded-md border border-slate-200 bg-white" data-zoom>
                <div class="overflow-hidden rounded-md">
                    <img data-gallery-main data-zoom-img src="{{ media($gallery[0] ?? $product->image) }}" alt="{{ $product->name }}" class="mx-auto h-80 w-full cursor-zoom-in object-contain p-4 sm:h-[520px]">
                </div>
                {{-- hover lens + zoom preview panel (shown to the right, image is never cropped) --}}
                <div data-zoom-lens class="pointer-events-none absolute hidden h-40 w-40 rounded-lg border-2 border-amber-400/70 bg-amber-200/10"></div>
                <div data-zoom-result class="pointer-events-none absolute left-full top-0 z-30 ml-4 hidden h-full w-full rounded-md border border-slate-200 bg-white bg-no-repeat shadow-2xl shadow-slate-400/30"></div>
                @if ($product->discount_percent)
                    <span class="absolute left-4 top-4 z-10 rounded bg-red-600 px-3 py-1 text-xs font-bold uppercase text-white">Save {{ $product->discount_percent }}%</span>
                @endif
            </div>
        </div>

        {{-- info --}}
        <div>
            @if ($product->badge)
                <div class="mb-3">
                    <x-brand-tag :tag="$product->badge" />
                </div>
            @endif

            <h1 class="text-2xl font-extrabold leading-tight text-[#111] sm:text-[1.75rem]">{{ $product->name }}</h1>

            {{-- brand + sku --}}
            <div class="mt-2.5 flex flex-wrap items-center gap-x-3 gap-y-2 border-b border-slate-200 pb-4 text-sm text-slate-500">
                @if ($product->brand)
                    <span class="flex items-center gap-2">
                        Brand:
                        <a href="{{ route('products.index', ['brand' => $product->brand]) }}" class="hover:opacity-80">
                            @if (!empty($brand?->logo))
                                <img src="{{ media($brand->logo) }}" alt="{{ $product->brand }}" class="h-7 w-auto max-w-[130px] object-contain">
                            @else
                                <span class="font-bold uppercase text-amber-600 hover:underline">{{ $product->brand }}</span>
                            @endif
                        </a>
                    </span>
                    <span class="inline-block h-4 w-px bg-slate-300"></span>
                @endif
                <span>SKU: <span class="font-bold text-slate-700">{{ $product->sku }}</span></span>
            </div>

            {{-- availability + shipping --}}
            <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1.5 text-sm">
                <span class="text-slate-500">Availability:</span>
                @if ($product->in_stock)
                    <span class="font-bold text-emerald-600">In Stock</span>
                    @if ($product->shows_stock_count)
                        <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">{{ $product->stock_qty }} available</span>
                    @endif
                @else
                    @php $callPhone = \App\Models\Setting::get('contact_phone'); @endphp
                    @if ($callPhone)
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $callPhone) }}" class="inline-flex items-center gap-1.5 font-bold text-amber-600 hover:text-amber-700">
                            <x-icon name="phone" class="h-4 w-4" /> Call for pricing
                        </a>
                    @else
                        <span class="font-bold text-amber-600">Call for pricing</span>
                    @endif
                @endif
                <span class="flex items-center gap-1.5 text-slate-500">
                    <x-icon name="truck" class="h-5 w-5 text-slate-700" />
                    Ships fast from our US warehouses
                </span>
                @if ($product->free_shipping)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">
                        <x-icon name="truck" class="h-4 w-4" />
                        Free Shipping
                    </span>
                @endif
            </div>

            {{-- rating --}}
            @if ($product->reviews->count())
                <a href="#reviews" class="mt-3 flex w-fit items-center gap-1.5 text-sm">
                    <span class="flex text-amber-400">
                        @for ($i = 0; $i < 5; $i++)<x-icon name="star" class="h-4 w-4 {{ $i < round($avgRating) ? 'fill-amber-400' : 'fill-slate-200 text-slate-200' }}" />@endfor
                    </span>
                    <span class="font-semibold text-slate-600">{{ $avgRating }}</span>
                    <span class="text-amber-600 hover:underline">({{ $product->reviews->count() }} reviews)</span>
                </a>
            @endif

            {{-- full-width divider --}}
            <div class="mt-4 border-t border-slate-200"></div>

            <div class="mt-5 flex items-baseline gap-3">
                <span class="text-3xl font-black text-[#111]">${{ number_format($product->price, 2) }}</span>
                <span class="text-sm text-slate-500">/each</span>
                @if ($product->compare_at_price && $product->compare_at_price > $product->price)
                    <span class="text-lg text-slate-400 line-through">${{ number_format($product->compare_at_price, 2) }}</span>
                @endif
            </div>

            @unless ($product->in_stock)
                {{-- out of stock: no cart, talk to us instead --}}
                @php
                    $oosPhone = \App\Models\Setting::get('contact_phone');
                    $oosEmail = \App\Models\Setting::get('contact_email');
                @endphp
                <div class="mt-6 rounded-md border border-amber-200 bg-amber-50 p-5">
                    <p class="font-bold text-amber-900">Currently out of stock</p>
                    <p class="mt-1 text-sm text-amber-800">Call or email us for pricing, lead time and alternatives — we source this part on request.</p>
                    <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                        @if ($oosPhone)
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $oosPhone) }}" class="flex flex-1 items-center justify-center gap-2 rounded bg-[#111] px-6 py-3.5 text-sm font-black uppercase tracking-wide text-white shadow-sm transition hover:bg-black">
                                <x-icon name="phone" class="h-5 w-5" /> Call for pricing
                            </a>
                        @endif
                        <a href="{{ route('part-request', ['sku' => $product->sku, 'product' => $product->name]) }}" class="flex flex-1 items-center justify-center gap-2 rounded bg-amber-500 px-6 py-3.5 text-sm font-black uppercase tracking-wide text-white shadow-sm transition hover:bg-amber-600">
                            Request this part
                        </a>
                    </div>
                    @if ($oosEmail)
                        <p class="mt-3 text-xs text-amber-800">Or email <a href="mailto:{{ $oosEmail }}?subject={{ urlencode('Availability: '.$product->name) }}" class="font-bold underline">{{ $oosEmail }}</a></p>
                    @endif
                </div>
            @else
            {{-- quantity + stacked add to cart / buy now --}}
            <form method="POST" action="{{ route('cart.add', $product) }}" data-cart-add class="mt-6 space-y-3">
                @csrf
                @php $maxQty = \App\Support\Cart::availableFor($product); @endphp
                <div class="flex flex-wrap items-center gap-3">
                    <div class="inline-flex items-center rounded border border-slate-300" data-qty>
                        <button type="button" data-qty-dec class="px-4 py-3 text-lg text-slate-500 hover:text-amber-600" aria-label="Decrease">−</button>
                        <input name="qty" value="1" inputmode="numeric" data-qty-input @if($maxQty) max="{{ $maxQty }}" @endif class="w-14 border-x border-slate-200 bg-transparent py-2.5 text-center text-sm font-semibold outline-none" aria-label="Quantity">
                        <button type="button" data-qty-inc class="px-4 py-3 text-lg text-slate-500 hover:text-amber-600" aria-label="Increase">+</button>
                    </div>
                    @if ($product->shows_stock_count)
                        <span class="text-sm font-semibold {{ $maxQty <= 5 ? 'text-amber-600' : 'text-slate-500' }}">
                            {{ $maxQty <= 5 ? 'Only '.$maxQty.' left' : $maxQty.' in stock' }} — you can order up to {{ $maxQty }}
                        </span>
                    @endif
                </div>

                <button type="submit" data-attn-cart class="attn-cart flex w-full items-center justify-center gap-2 rounded bg-amber-500 px-6 py-3.5 text-sm font-black uppercase tracking-wide text-white shadow-sm transition hover:bg-amber-600 active:scale-[.99]">
                    <x-icon name="cart" class="h-5 w-5" /> Add to Cart
                </button>
                <button type="submit" name="buy_now" value="1" data-buy-now class="flex w-full items-center justify-center gap-2 rounded bg-[#111] px-6 py-3.5 text-sm font-black uppercase tracking-wide text-white shadow-sm transition hover:bg-black active:scale-[.99]">
                    Buy Now
                </button>
            </form>
            @endunless

            {{-- accepted payments + secure checkout --}}
            <x-payment-icons secure class="mt-4 flex-wrap justify-center rounded-md border border-slate-100 bg-slate-50/70 px-3 py-3" />

            {{-- genuine / warranty reassurance --}}
            <div class="mt-3 flex flex-wrap items-center justify-center gap-x-5 gap-y-1.5 text-xs font-semibold text-slate-600">
                <span class="flex items-center gap-1.5"><x-icon name="check" class="h-4 w-4 text-amber-500" /> 100% Genuine Products</span>
                <span class="flex items-center gap-1.5"><x-icon name="check" class="h-4 w-4 text-amber-500" /> Manufacturer Warranty</span>
                <span class="flex items-center gap-1.5"><x-icon name="check" class="h-4 w-4 text-amber-500" /> Ships from USA</span>
            </div>

            {{-- trust badges --}}
            <div class="mt-5 grid grid-cols-3 gap-4 rounded-md border border-slate-200 bg-white px-4 py-5">
                <div class="flex flex-col items-center gap-2.5 text-center">
                    <span class="grid h-14 w-14 place-items-center rounded-full border-2 border-amber-500 text-amber-500">
                        <x-icon name="lock" class="h-6 w-6" stroke-width="2.5" />
                    </span>
                    <span class="text-xs font-semibold text-slate-700">Secure Payments</span>
                </div>
                <div class="flex flex-col items-center gap-2.5 text-center">
                    <span class="grid h-14 w-14 place-items-center rounded-full border-2 border-amber-500 leading-none text-amber-500">
                        <span class="text-center font-black"><span class="block text-base">30</span><span class="block text-[10px] tracking-wide">DAY</span></span>
                    </span>
                    <span class="text-xs font-semibold text-slate-700">Easy Returns</span>
                </div>
                <div class="flex flex-col items-center gap-2.5 text-center">
                    <span class="grid h-14 w-14 place-items-center rounded-full border-2 border-amber-500 text-amber-500">
                        <x-icon name="truck" class="h-6 w-6" stroke-width="2.5" />
                    </span>
                    <span class="text-xs font-semibold text-slate-700">Fast Shipping</span>
                </div>
            </div>

        </div>
    </div>

    {{-- ============ COLLAPSIBLE SECTIONS ============ --}}
    <div class="mt-12 overflow-hidden rounded-md border border-slate-200">

        {{-- Product Highlights --}}
        <details open class="acc border-b border-slate-200 bg-white">
            <summary class="flex items-center justify-between px-6 py-4">
                <span class="text-base font-extrabold text-[#111]">Product Highlights</span>
                <span class="acc-sign text-xl font-bold text-amber-500"></span>
            </summary>
            <div class="grid grid-cols-2 gap-4 px-6 pb-6 sm:grid-cols-4">
                @php
                    $highlights = [
                        ['shield', 'Warranty', 'Manufacturer backed'],
                        ['star', $avgRating.' / 5', $product->reviews->count().' reviews'],
                        ['check', $product->in_stock ? 'In Stock' : 'Call for pricing', $product->in_stock ? 'Ships fast' : 'Talk to our team'],
                        ['box', $product->brand ?: 'Genuine', 'Authentic part'],
                    ];
                @endphp
                @foreach ($highlights as [$icon, $title, $sub])
                    <div class="flex flex-col items-center rounded-md border border-slate-100 bg-slate-50 p-4 text-center">
                        <x-icon name="{{ $icon }}" class="h-7 w-7 text-amber-500" />
                        <span class="mt-2 text-sm font-bold text-slate-800">{{ $title }}</span>
                        <span class="text-xs text-slate-400">{{ $sub }}</span>
                    </div>
                @endforeach
            </div>
        </details>

        {{-- Description --}}
        <details open class="acc border-b border-slate-200 bg-white">
            <summary class="flex items-center justify-between px-6 py-4">
                <span class="text-base font-extrabold text-[#111]">Description</span>
                <span class="acc-sign text-xl font-bold text-amber-500"></span>
            </summary>
            <div class="px-6 pb-6">
                @if ($product->short_description)
                    <p class="font-semibold text-slate-700">{{ $product->short_description }}</p>
                @endif
                @php
                    $desc = trim((string) $product->description);
                    // Some imported descriptions store literal escape sequences (\n, \t) — turn them into real breaks.
                    $desc = str_replace(['\\r\\n', '\\n', '\\r', '\\t'], ' ', $desc);
                    // Safety: never render <script> from imported content.
                    $desc = preg_replace('#<script\b[^>]*>.*?</script>#is', '', $desc);
                    $isHtml = $desc !== '' && $desc !== strip_tags($desc); // contains real tags
                @endphp
                @if ($desc === '')
                    <p class="mt-3 leading-relaxed text-slate-600">No description available for this product.</p>
                @elseif ($isHtml)
                    <div class="description-html mt-3 leading-relaxed text-slate-600">{!! $desc !!}</div>
                @else
                    <p class="mt-3 leading-relaxed text-slate-600">{!! nl2br(e($desc)) !!}</p>
                @endif
            </div>
        </details>

        {{-- Specs --}}
        <details open class="acc border-b border-slate-200 bg-white">
            <summary class="flex items-center justify-between px-6 py-4">
                <span class="text-base font-extrabold text-[#111]">Specs</span>
                <span class="acc-sign text-xl font-bold text-amber-500"></span>
            </summary>
            <div class="px-6 pb-6">
                @php
                    $num = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');
                    $has = fn ($v) => $v !== null && trim((string) $v) !== '';

                    // identity
                    $specs = [
                        ['Brand', $product->brand ?: '—'],
                        ['SKU', $product->sku ?: '—'],
                        ['Category', $product->category->name ?? '—'],
                    ];
                    if ($has($product->upc)) $specs[] = ['UPC', $product->upc];
                    if ($has($product->uom)) $specs[] = ['Sold as', $product->uom];

                    // physical / shipping (coalesce so nothing shows twice)
                    $weight = $has($product->weight_lbs) ? $product->weight_lbs : $product->spec_roll_weight;
                    if ($has($weight)) $specs[] = ['Weight', $num($weight).' lbs'];
                    if ($has($product->dimensions)) $specs[] = ['Dimensions', $product->dimensions];
                    $caseQty = $has($product->case_qty) ? $product->case_qty : $product->spec_case_quantity;
                    if ($has($caseQty)) $specs[] = ['Case Quantity', $num($caseQty)];
                    $caseWt = $has($product->case_weight_lbs) ? $product->case_weight_lbs : $product->spec_case_weight;
                    if ($has($caseWt)) $specs[] = ['Case Weight', $num($caseWt).' lbs'];

                    // catalog specs — only the ones that have a value for this product
                    $specDefs = [
                        'spec_product_family' => 'Product Family',
                        'spec_product_type' => 'Product Type',
                        'spec_ribbon_width' => 'Ribbon Width',
                        'spec_ribbon_length' => 'Ribbon Length',
                        'spec_core_size' => 'Core Size',
                        'spec_color' => 'Color',
                        'spec_ribbon_type' => 'Ribbon Type',
                        'spec_coated_side' => 'Coated Side',
                        'spec_sold_by' => 'Sold By',
                        'spec_compatible_printers' => 'Compatible Printers',
                        'spec_print_method' => 'Print Method',
                        'spec_label_width' => 'Label Width',
                        'spec_label_height' => 'Label Height',
                        'spec_labels_per_roll' => 'Labels per Roll',
                        'spec_format' => 'Format',
                        'spec_finish' => 'Finish',
                        'spec_labels_across' => 'Labels Across',
                        'spec_adhesive' => 'Adhesive',
                        'spec_perforation' => 'Perforation',
                        'spec_outside_diameter' => 'Outside Diameter',
                        'spec_material' => 'Material',
                        'spec_dpi' => 'DPI',
                        'spec_connectivity' => 'Connectivity',
                        'spec_media_handling' => 'Media Handling',
                        'spec_print_width' => 'Print Width',
                        'spec_printer_type' => 'Printer Type',
                        'spec_flash_memory' => 'Flash Memory',
                        'spec_durability' => 'Durability',
                        'spec_geographical_availability' => 'Region',
                        'spec_battery' => 'Battery',
                        'spec_features_includes' => 'Features / Includes',
                        'spec_inlay_type' => 'Inlay Type',
                    ];
                    foreach ($specDefs as $col => $label) {
                        if ($has($product->{$col})) $specs[] = [$label, $product->{$col}];
                    }

                    $specs[] = ['Price', '$'.number_format($product->price, 2)];
                    $specs[] = ['Availability', $product->in_stock
                        ? 'In stock'.($product->shows_stock_count ? ' ('.$product->stock_qty.' available)' : '')
                        : 'Call for pricing'];
                    $specs[] = ['Rating', $avgRating.' / 5.0'];
                @endphp
                @php
                    // A small icon per row: a few labels get their own, the rest
                    // fall back to the generic "spec line" mark.
                    $specIcon = function (string $label) {
                        $l = strtolower($label);

                        return match (true) {
                            str_contains($l, 'weight') => 'gauge',
                            str_contains($l, 'price') => 'cart',
                            str_contains($l, 'rating') => 'star',
                            str_contains($l, 'availability') => 'check',
                            str_contains($l, 'brand') => 'shield',
                            str_contains($l, 'category') => 'parts',
                            str_contains($l, 'sku'), str_contains($l, 'upc'), str_contains($l, 'sold as'), str_contains($l, 'sold by'), str_contains($l, 'case') => 'box',
                            str_contains($l, 'printer'), str_contains($l, 'connectivity'), str_contains($l, 'memory'), str_contains($l, 'dpi') => 'computer',
                            str_contains($l, 'width'), str_contains($l, 'height'), str_contains($l, 'length'),
                            str_contains($l, 'diameter'), str_contains($l, 'size'), str_contains($l, 'dimensions') => 'tools',
                            str_contains($l, 'region'), str_contains($l, 'shipping') => 'truck',
                            default => null,
                        };
                    };
                @endphp
                <dl class="grid gap-x-10 sm:grid-cols-2">
                    @foreach ($specs as [$k, $v])
                        @php $icon = $specIcon($k); @endphp
                        <div class="flex items-center justify-between gap-4 border-b border-slate-100 py-2.5 text-sm">
                            <dt class="flex min-w-0 items-center gap-2.5 text-slate-500">
                                @if ($icon)
                                    <x-icon name="{{ $icon }}" class="h-4 w-4 shrink-0 text-amber-500" />
                                @else
                                    <svg class="h-4 w-4 shrink-0 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                        <path d="M8 6h13M8 12h13M8 18h13"/><circle cx="3.5" cy="6" r="1"/><circle cx="3.5" cy="12" r="1"/><circle cx="3.5" cy="18" r="1"/>
                                    </svg>
                                @endif
                                <span class="truncate font-medium text-slate-700">{{ $k }}</span>
                            </dt>
                            <dd class="shrink-0 text-right text-slate-500">{{ $v }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </details>

        {{-- Contact --}}
        <details class="acc bg-white">
            <summary class="flex items-center justify-between px-6 py-4">
                <span class="text-base font-extrabold text-[#111]">Contact</span>
                <span class="acc-sign text-xl font-bold text-amber-500"></span>
            </summary>
            @php $cPhone = \App\Models\Setting::get('contact_phone', '+1-254-358-0017'); $cToll = \App\Models\Setting::get('toll_free', '(945) 427-9519'); @endphp
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 px-6 pb-6 text-sm text-slate-600">
                <x-icon name="phone" class="h-5 w-5 text-amber-500" />
                Questions? Call us
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $cPhone) }}" class="font-bold text-amber-600">{{ $cPhone }}</a>
                @if ($cToll)
                    <span class="text-slate-300">·</span>
                    <span>Toll-Free <a href="tel:{{ preg_replace('/[^0-9+]/', '', $cToll) }}" class="font-bold text-amber-600">{{ $cToll }}</a></span>
                @endif
            </div>
        </details>
    </div>

    {{-- ============ YOU MAY ALSO NEED ============ --}}
    @if ($related->count())
        <section class="mt-14" data-slider data-no-autoplay>
            <div class="mb-5 flex items-center justify-between gap-3">
                <h2 class="text-xl font-extrabold tracking-tight text-[#111] sm:text-[22px]">You May Also Need</h2>
                <div class="flex gap-2">
                    <button type="button" data-slider-prev aria-label="Previous"
                            class="grid h-9 w-9 place-items-center rounded-full border border-slate-300 text-slate-600 transition hover:border-amber-400 hover:bg-amber-50 hover:text-amber-600">
                        <x-icon name="arrow" class="h-4 w-4 rotate-180" />
                    </button>
                    <button type="button" data-slider-next aria-label="Next"
                            class="grid h-9 w-9 place-items-center rounded-full border border-slate-300 text-slate-600 transition hover:border-amber-400 hover:bg-amber-50 hover:text-amber-600">
                        <x-icon name="arrow" class="h-4 w-4" />
                    </button>
                </div>
            </div>
            <div data-slider-track class="no-scrollbar flex snap-x gap-3 overflow-x-auto scroll-smooth pb-2">
                @foreach ($related as $item)
                    <div data-slide class="w-52 shrink-0 snap-start sm:w-56">
                        <x-product-card :product="$item" />
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ============ part request ============ --}}
    <section class="mt-10 flex flex-col items-start justify-between gap-4 rounded-md bg-[#141414] px-5 py-5 text-white sm:flex-row sm:items-center sm:px-7 sm:py-6">
        <div class="flex items-center gap-4">
            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-amber-500 text-white"><x-icon name="quote" class="h-5 w-5" /></span>
            <div>
                <p class="text-sm font-bold">Not sure this is the right part?</p>
                <p class="mt-0.5 text-sm text-white/75">Contact our support team before ordering and we'll help you verify the product details.</p>
            </div>
        </div>
        <a href="{{ route('part-request', ['product' => $product->name, 'sku' => $product->sku]) }}"
           class="shrink-0 rounded border-2 border-amber-500 px-6 py-2.5 text-sm font-bold text-white transition hover:bg-amber-500">Part Request Form</a>
    </section>

    {{-- ============ returns ============ --}}
    <section class="mt-10 rounded-md border-l-4 border-amber-400 bg-slate-50 px-5 py-5 sm:px-7 sm:py-6">
        <h2 class="text-xl font-black tracking-tight text-slate-900 sm:text-2xl">Need to return it?</h2>
        <p class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-base text-slate-600 sm:text-lg">
            <span>30-day returns</span>
            <span class="text-slate-900">&bull;</span>
            <span>Clear return process</span>
            <span class="text-slate-900">&bull;</span>
            <a href="{{ route('returns') }}" class="font-semibold text-amber-600 underline-offset-2 hover:underline">See our full policy</a>
        </p>
    </section>
</div>

{{-- ============ PRODUCT REVIEWS ============ --}}
<section id="reviews" class="mt-14 bg-slate-50 py-14">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <h2 class="text-2xl font-black tracking-tight text-slate-900">Product Reviews</h2>

        @if (session('status'))
            <div class="mt-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>
        @endif

        <div class="mt-6 grid gap-8 lg:grid-cols-[280px_1fr]">
            {{-- summary --}}
            <div class="rounded-md border border-slate-200 bg-white p-6">
                <div class="text-center">
                    <p class="text-5xl font-black text-slate-900">{{ $avgRating }}</p>
                    <div class="mt-1 flex justify-center text-amber-400">
                        @for ($i = 0; $i < 5; $i++)<x-icon name="star" class="h-5 w-5 {{ $i < round($avgRating) ? 'fill-amber-400' : 'fill-slate-200 text-slate-200' }}" />@endfor
                    </div>
                    <p class="mt-1 text-sm text-slate-500">out of 5 · {{ $product->reviews->count() }} reviews</p>
                </div>
                <div class="mt-5 space-y-1.5">
                    @foreach ($reviewBreakdown as $star => $count)
                        @php $pct = $product->reviews->count() ? round($count / $product->reviews->count() * 100) : 0; @endphp
                        <div class="flex items-center gap-2 text-xs">
                            <span class="w-3 text-slate-500">{{ $star }}</span>
                            <x-icon name="star" class="h-3 w-3 fill-amber-400 text-amber-400" />
                            <span class="h-2 flex-1 overflow-hidden rounded-full bg-slate-200">
                                <span class="block h-full rounded-full bg-amber-400" style="width: {{ $pct }}%"></span>
                            </span>
                            <span class="w-6 text-right text-slate-400">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
                <button type="button" data-review-open class="mt-6 block w-full rounded bg-amber-500 py-2.5 text-center text-sm font-bold text-white transition hover:bg-amber-600">Write a Review</button>
            </div>

            {{-- review list --}}
            <div>
                @forelse ($product->reviews as $review)
                    <article class="border-b border-slate-200 py-5">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="flex text-amber-400">
                                    @for ($s = 0; $s < $review->rating; $s++)<x-icon name="star" class="h-4 w-4 fill-amber-400" />@endfor
                                </div>
                                <p class="mt-1 leading-relaxed text-slate-700">{{ $review->body }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <p class="text-sm font-semibold text-slate-800">{{ $review->author_name }}</p>
                                {{-- one plain label for everyone; job titles are not shown --}}
                                <p class="text-xs text-emerald-600">✓ Verified Buyer</p>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center gap-4 text-xs text-slate-400" data-helpful-url="{{ url('/reviews/'.$review->id.'/helpful') }}">
                            <span>Was this review helpful?</span>
                            <button type="button" data-vote="1" class="flex items-center gap-1 rounded-full px-2 py-1 transition hover:bg-amber-50 hover:text-amber-600 aria-pressed:bg-amber-50 aria-pressed:font-semibold aria-pressed:text-amber-600" aria-pressed="false">👍 <span data-yes>{{ (int) $review->helpful_yes }}</span></button>
                            <button type="button" data-vote="0" class="flex items-center gap-1 rounded-full px-2 py-1 transition hover:bg-amber-50 hover:text-amber-600 aria-pressed:bg-amber-50 aria-pressed:font-semibold aria-pressed:text-amber-600" aria-pressed="false">👎 <span data-no>{{ (int) $review->helpful_no }}</span></button>
                        </div>
                    </article>
                @empty
                    <div class="rounded-md border border-dashed border-slate-300 bg-white py-16 text-center">
                        <x-icon name="star" class="mx-auto h-10 w-10 text-slate-300" />
                        <p class="mt-3 font-semibold text-slate-700">No reviews yet</p>
                        <p class="mt-1 text-sm text-slate-500">Be the first to review this product.</p>
                        <button type="button" data-review-open class="mt-4 inline-block rounded bg-amber-500 px-5 py-2 text-sm font-bold text-white hover:bg-amber-600">Write a Review</button>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- write a review: hidden until asked for, and kept open when it comes back with errors --}}
        <div id="write-review" data-review-form class="mt-8 rounded-md border border-slate-200 bg-white p-6 {{ $errors->any() ? '' : 'hidden' }}">
            <h3 class="text-lg font-black text-slate-900">Write a review</h3>
            <p class="mt-1 text-sm text-slate-500">Tell other buyers how this part worked for you. Reviews appear once our team has checked them.</p>

            <form method="POST" action="{{ url('/products/'.$product->slug.'/review') }}" class="mt-5 space-y-4">
                @csrf

                <div>
                    <span class="block text-sm font-semibold text-slate-700">Your rating</span>
                    <div class="star-picker mt-1.5 flex flex-row-reverse justify-end">
                        @foreach ([5, 4, 3, 2, 1] as $star)
                            <input type="radio" id="star-{{ $star }}" name="rating" value="{{ $star }}" class="sr-only" @checked(old('rating', 5) == $star)>
                            <label for="star-{{ $star }}" class="cursor-pointer p-0.5" title="{{ $star }} star{{ $star > 1 ? 's' : '' }}">
                                <span class="sr-only">{{ $star }} stars</span>
                                <x-icon name="star" class="h-7 w-7 fill-current" />
                            </label>
                        @endforeach
                    </div>
                    @error('rating')<p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="review-name" class="block text-sm font-semibold text-slate-700">Your name</label>
                    <input id="review-name" name="author_name" value="{{ old('author_name') }}" required maxlength="120"
                           class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:ring-amber-400 sm:max-w-sm">
                    @error('author_name')<p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="review-body" class="block text-sm font-semibold text-slate-700">Your review</label>
                    <textarea id="review-body" name="body" rows="4" required minlength="10" maxlength="1000"
                              class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-amber-400 focus:ring-amber-400">{{ old('body') }}</textarea>
                    @error('body')<p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p>@enderror
                </div>

                @include('partials.recaptcha')

                <div class="flex items-center gap-3">
                    <button class="rounded bg-amber-500 px-6 py-2.5 text-sm font-bold text-white transition hover:bg-amber-600">Submit review</button>
                    <button type="button" data-review-cancel class="text-sm font-semibold text-slate-500 hover:text-slate-700">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</section>

@push('scripts')
<style>
    .description-html { word-break: break-word; }
    .description-html p { margin: 0 0 10px; }
    .description-html h2, .description-html h3, .description-html h4 { font-weight: 700; color: #0f172a; margin: 16px 0 8px; font-size: 1rem; }
    .description-html ul, .description-html ol { padding-left: 1.25rem; margin: 8px 0; }
    .description-html ul { list-style: disc; }
    .description-html ol { list-style: decimal; }
    .description-html a { color: #d97706; text-decoration: underline; word-break: break-all; }
    .description-html table { width: 100%; border-collapse: collapse; margin: 12px 0; font-size: .875rem; }
    .description-html th, .description-html td { border: 1px solid #e2e8f0; padding: 7px 10px; text-align: left; vertical-align: top; }
    .description-html th { background: #f8fafc; font-weight: 600; color: #334155; white-space: nowrap; }
    .description-html img { max-width: 100%; height: auto; }

    /* star picker: the row is reversed, so a checked star also colours the ones drawn to its left */
    .star-picker label { color: #cbd5e1; transition: color .12s; }
    .star-picker input:checked ~ label { color: #f59e0b; }
    .star-picker:hover label { color: #cbd5e1; }
    .star-picker label:hover, .star-picker label:hover ~ label { color: #fbbf24; }
</style>
<script>
    (function () {
        // add_to_cart is fired from the server response (see CartController@add).
        window.trackEcom && window.trackEcom.view_item(@json(\App\Support\Ecom::addToCart($product, 1)));

        // "Add to Cart" pulses to draw attention, then settles once the user
        // engages with it (hover / focus / tap) or after a short while.
        var attn = document.querySelector('[data-attn-cart]');
        if (attn) {
            var calm = function () { attn.classList.add('is-engaged'); };
            ['pointerenter', 'focus', 'click', 'touchstart'].forEach(function (ev) {
                attn.addEventListener(ev, calm, { once: true, passive: true });
            });
            setTimeout(calm, 12000);
        }
    })();
</script>
@endpush

@push('scripts')
<script>
    (function () {
        document.querySelectorAll('[data-zoom]').forEach(function (z) {
            var img = z.querySelector('[data-zoom-img]');
            var lens = z.querySelector('[data-zoom-lens]');
            var result = z.querySelector('[data-zoom-result]');
            if (!img || !result || !lens) return;

            var ZOOM = 1.8;
            var enabled = function () { return window.matchMedia('(min-width: 1024px)').matches; };
            var setBg = function () { result.style.backgroundImage = 'url("' + (img.currentSrc || img.src) + '")'; };
            var hide = function () { result.classList.add('hidden'); lens.classList.add('hidden'); };

            z.addEventListener('mouseenter', function () { if (enabled()) { setBg(); } });
            z.addEventListener('mouseleave', hide);
            // Where the picture is actually drawn inside the <img> (object-contain + padding), in page px.
            var drawn = function () {
                var r = img.getBoundingClientRect(), cs = getComputedStyle(img);
                var pl = parseFloat(cs.paddingLeft), pt = parseFloat(cs.paddingTop);
                var cw = r.width - pl - parseFloat(cs.paddingRight), ch = r.height - pt - parseFloat(cs.paddingBottom);
                var s = Math.min(cw / (img.naturalWidth || cw), ch / (img.naturalHeight || ch));
                var w = (img.naturalWidth || cw) * s, h = (img.naturalHeight || ch) * s;
                return { left: r.left + pl + (cw - w) / 2, top: r.top + pt + (ch - h) / 2, width: w, height: h };
            };
            var clamp = function (v, min, max) { return Math.max(min, Math.min(max, v)); };

            z.addEventListener('mousemove', function (e) {
                if (!enabled()) { hide(); return; }
                var d = drawn();
                var x = e.clientX - d.left, y = e.clientY - d.top;
                if (x < 0 || y < 0 || x > d.width || y > d.height) { hide(); return; }

                setBg();
                result.classList.remove('hidden');
                lens.classList.remove('hidden');

                // panel shows the picture at ZOOM× its on-screen size, centred on the cursor
                var pw = result.clientWidth, ph = result.clientHeight;
                var bw = d.width * ZOOM, bh = d.height * ZOOM;
                var bx = bw > pw ? clamp(pw / 2 - x * ZOOM, pw - bw, 0) : (pw - bw) / 2;
                var by = bh > ph ? clamp(ph / 2 - y * ZOOM, ph - bh, 0) : (ph - bh) / 2;
                result.style.backgroundSize = bw + 'px ' + bh + 'px';
                result.style.backgroundPosition = bx + 'px ' + by + 'px';

                // lens marks exactly the part shown in the panel
                var cr = z.getBoundingClientRect();
                var lw = Math.min(pw / ZOOM, d.width), lh = Math.min(ph / ZOOM, d.height);
                lens.style.width = lw + 'px';
                lens.style.height = lh + 'px';
                lens.style.left = (d.left - cr.left + (bw > pw ? -bx / ZOOM : 0)) + 'px';
                lens.style.top = (d.top - cr.top + (bh > ph ? -by / ZOOM : 0)) + 'px';
            });

            // keep the zoom image in sync when the gallery thumbnail changes the main image
            new MutationObserver(setBg).observe(img, { attributes: true, attributeFilter: ['src'] });
        });
    })();

    // ---- write a review -------------------------------------------------
    (function () {
        var box = document.querySelector('[data-review-form]');
        if (!box) return;

        var open = function () {
            box.classList.remove('hidden');
            box.scrollIntoView({ behavior: 'smooth', block: 'center' });
            var first = box.querySelector('#review-name');
            if (first) setTimeout(function () { first.focus(); }, 300);
        };

        document.querySelectorAll('[data-review-open]').forEach(function (btn) {
            btn.addEventListener('click', open);
        });
        var cancel = box.querySelector('[data-review-cancel]');
        if (cancel) cancel.addEventListener('click', function () { box.classList.add('hidden'); });

        // came back with validation errors, or linked to directly
        if (!box.classList.contains('hidden') || location.hash === '#write-review') open();
    })();

    // ---- was this review helpful? ---------------------------------------
    (function () {
        var token = document.querySelector('meta[name="csrf-token"]');
        document.querySelectorAll('[data-helpful-url]').forEach(function (row) {
            var busy = false;
            row.querySelectorAll('[data-vote]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (busy) return;
                    busy = true;
                    fetch(row.dataset.helpfulUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token ? token.content : '',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ helpful: btn.dataset.vote === '1' })
                    })
                        .then(function (r) { return r.ok ? r.json() : Promise.reject(r); })
                        .then(function (d) {
                            row.querySelector('[data-yes]').textContent = d.yes;
                            row.querySelector('[data-no]').textContent = d.no;
                            row.querySelectorAll('[data-vote]').forEach(function (b) {
                                var mine = d.voted !== null && d.voted !== undefined
                                    && (b.dataset.vote === '1') === !!d.voted;
                                b.setAttribute('aria-pressed', mine ? 'true' : 'false');
                            });
                        })
                        .catch(function () {})
                        .finally(function () { busy = false; });
                });
            });
        });
    })();
</script>
@endpush

@endsection
