@php
    $supportPhone = \App\Models\Setting::get('contact_phone', '1-844-290-6641');
    $supportPhoneTel = preg_replace('/[^0-9+]/', '', $supportPhone);
    $tollFree = \App\Models\Setting::get('toll_free');
    $tollFreeTel = preg_replace('/[^0-9+]/', '', (string) $tollFree);
    $freeShipAt = \App\Models\Setting::get('free_shipping_threshold', 99);
    $loggedIn = auth('customer')->check();
@endphp

{{-- ============ Orange info strip ============ --}}
@if (\App\Models\Setting::get('announcement_enabled', '1') !== '0')
<div class="bg-amber-500 text-white">
    <div class="mx-auto flex max-w-7xl items-center justify-center gap-6 px-4 py-2 text-[12px] font-semibold sm:px-6 lg:justify-between">
        <div class="flex items-center gap-3">
            <x-icon name="truck" class="h-5 w-5 shrink-0" />
            <span>Free shipping on orders over ${{ $freeShipAt }}</span>
            <span class="hidden h-4 w-px bg-white/40 sm:block"></span>
            <span class="hidden sm:inline">Delivery in 2 days or less <a href="{{ route('shipping') }}" class="ml-1 text-[11px] font-medium opacity-80 underline-offset-2 hover:underline">See details</a></span>
        </div>
        <div class="hidden items-center gap-8 xl:flex">
            <span class="flex items-center gap-2"><x-icon name="badge" class="h-5 w-5" /> Genuine Parts &amp; Authorized Brands</span>
            <span class="flex items-center gap-2"><x-icon name="shield" class="h-5 w-5" /> Warranty Backed</span>
        </div>
        <div class="hidden items-center gap-6 lg:flex">
            <a href="tel:{{ $supportPhoneTel }}" class="flex items-center gap-2 hover:opacity-90">
                <x-icon name="phone" class="h-5 w-5" />
                <span class="leading-tight">{{ $supportPhone }}<span class="block text-[10px] font-medium opacity-80">Opens at 9am EST</span></span>
            </a>
            <button type="button" onclick="var t=document.querySelector('[data-chat-toggle]'); if(t){t.click()} else {location.href='{{ route('contact.index') }}'}" class="flex items-center gap-2 text-left hover:opacity-90">
                <x-icon name="chat" class="h-5 w-5" />
                <span class="leading-tight">Chat<span class="block text-[10px] font-medium opacity-80">with a product expert</span></span>
            </button>
        </div>
    </div>
</div>
@endif

{{-- ============ Main header ============ --}}
{{-- Pages that define the `plain_header` section (checkout) get a header that
     stays at the top of the page instead of following the scroll. --}}
@php $stickyHeader = ! View::hasSection('plain_header'); @endphp
<header @if($stickyHeader) data-header @endif class="site-header z-30 bg-[#111] {{ $stickyHeader ? 'sticky top-0' : 'relative' }}">
    <div class="mx-auto max-w-7xl px-3 sm:px-6">
        <div class="header-row flex items-center gap-4 py-3.5 lg:gap-8">

            <button type="button" data-menu-toggle class="-ml-1 p-2 text-white lg:hidden" aria-label="Open menu">
                <x-icon name="menu" class="h-6 w-6" />
            </button>

            {{-- logo --}}
            <a href="{{ route('home') }}" class="flex shrink-0 items-center">
                @php $headerLogo = \App\Models\Setting::get('header_logo'); @endphp
                @if ($headerLogo)
                    <img src="{{ media($headerLogo) }}" alt="{{ \App\Models\Setting::get('site_name', 'Texpert Automotive') }}" class="h-11 w-auto">
                @else
                    @include('layouts.partials.logo-mark', ['size' => 'lg'])
                @endif
            </a>

            {{-- search --}}
            <form action="{{ route('products.index') }}" method="GET" class="relative hidden min-w-0 flex-1 md:block" data-search>
                <div class="flex h-11 overflow-hidden rounded bg-white">
                    <input type="search" name="q" value="{{ request('q') }}" autocomplete="off" data-search-input
                           placeholder="Search products by name, brand, part # or SKU..."
                           class="min-w-0 flex-1 px-4 text-sm text-slate-800 outline-none">
                    <button type="submit" class="grid w-12 shrink-0 place-items-center bg-amber-500 text-white transition hover:bg-amber-600" aria-label="Search">
                        <x-icon name="search" class="h-5 w-5" />
                    </button>
                </div>
                <div data-search-results class="absolute left-0 right-0 top-full z-50 mt-2 hidden max-h-[70vh] overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-2xl shadow-slate-900/30"></div>
            </form>

            {{-- account / orders / cart / quote --}}
            <div class="ml-auto flex shrink-0 items-center gap-1 sm:gap-4 md:ml-0">
                <a href="{{ $loggedIn ? route('account.index') : route('account.login') }}" class="flex flex-col items-center gap-0.5 px-2 text-white/90 transition hover:text-amber-500">
                    <x-icon name="user" class="h-6 w-6" />
                    <span class="hidden text-[11px] font-medium sm:block">Account</span>
                </a>
                <a href="{{ $loggedIn ? route('account.index') : route('track') }}" class="hidden flex-col items-center gap-0.5 px-2 text-white/90 transition hover:text-amber-500 sm:flex">
                    <x-icon name="orders" class="h-6 w-6" />
                    <span class="text-[11px] font-medium">Orders</span>
                </a>
                <a href="{{ route('cart.index') }}" data-cart-open class="relative flex flex-col items-center gap-0.5 px-2 text-white/90 transition hover:text-amber-500" aria-label="Cart">
                    <x-icon name="cart" class="h-6 w-6" />
                    <span class="hidden text-[11px] font-medium sm:block">Cart</span>
                    <span data-cart-count class="absolute -top-1.5 right-0 grid h-4 min-w-4 place-items-center rounded-full bg-amber-500 px-1 text-[10px] font-bold text-white {{ \App\Support\Cart::count() > 0 ? '' : 'hidden' }}">{{ \App\Support\Cart::count() }}</span>
                </a>
                <a href="{{ route('contact.index', ['subject' => 'Request a Quote']) }}" class="ml-2 hidden rounded border-2 border-amber-500 px-4 py-2 text-sm font-bold text-amber-500 transition hover:bg-amber-500 hover:text-white lg:inline-block">
                    Get Quote
                </a>
            </div>
        </div>

        {{-- mobile search --}}
        <form action="{{ route('products.index') }}" method="GET" class="relative pb-3 md:hidden" data-search>
            <div class="flex h-11 overflow-hidden rounded bg-white">
                <input type="search" name="q" value="{{ request('q') }}" autocomplete="off" data-search-input placeholder="Search products..."
                       class="min-w-0 flex-1 px-4 text-sm outline-none">
                <button type="submit" class="grid w-12 place-items-center bg-amber-500 text-white" aria-label="Search">
                    <x-icon name="search" class="h-5 w-5" />
                </button>
            </div>
            <div data-search-results class="absolute left-0 right-0 top-full z-50 hidden max-h-[60vh] overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-2xl"></div>
        </form>
    </div>

    {{-- ============ Category nav + MEGA MENU (desktop) ============ --}}
    @php
        // one icon per department, picked from its name
        $deptIcon = function (string $name) {
            $n = \Illuminate\Support\Str::lower($name);
            foreach (['body' => 'car', 'exterior' => 'car', 'engine' => 'engine', 'drive' => 'engine', 'perform' => 'gauge',
                      'exhaust' => 'gauge', 'suspens' => 'spring', 'tire' => 'wheel', 'wheel' => 'wheel', 'speaker' => 'speaker',
                      'audio' => 'speaker', 'amp' => 'amplifier', 'stereo' => 'radio', 'electr' => 'screen', 'install' => 'frame',
                      'safety' => 'camera', 'power' => 'cable', 'wiring' => 'cable', 'interior' => 'seat', 'care' => 'bottle',
                      'light' => 'electric', 'tool' => 'tools'] as $kw => $icon) {
                if (str_contains($n, $kw)) return $icon;
            }
            return 'box';
        };
        // products sit in leaf categories: a department's count is its whole subtree
        $treeCount = function ($cat) use (&$treeCount) {
            return (int) $cat->products_count + $cat->visibleChildrenRecursive->sum(fn ($c) => $treeCount($c));
        };
        // the menu entries: real departments, or the planned car-audio ones as searches
        $menu = $navCategories->count()
            ? $navCategories->map(fn ($c) => (object) ['name' => $c->name, 'url' => route('products.index', ['category' => $c->slug]), 'icon' => $deptIcon($c->name), 'count' => $treeCount($c), 'cat' => $c])
            : collect(['Car Audio & Electronics', 'Installation & Integration', 'Speakers & Subwoofers', 'Amplifiers', 'Stereo Receivers', 'Safety & Security', 'Power & Wiring', 'Interior & Exterior', 'Car Care'])
                ->map(fn ($n) => (object) ['name' => $n, 'url' => route('products.index', ['q' => $n]), 'icon' => $deptIcon($n), 'count' => null, 'cat' => null]);
    @endphp
    <nav class="relative hidden border-b border-slate-200 bg-white shadow-[0_2px_10px_rgba(0,0,0,0.05)] lg:block">
        <div class="mx-auto flex max-w-7xl items-stretch px-3 sm:px-6">

            {{-- all departments --}}
            <div class="mega-trigger relative mr-2 flex">
                <button type="button" class="flex items-center gap-2 bg-amber-500 px-4 text-[13px] font-bold text-white transition hover:bg-amber-600">
                    <x-icon name="menu" class="h-4 w-4" /> All Categories
                    <x-icon name="chevron" class="mega-caret h-3 w-3 transition-transform duration-200" />
                </button>
                <div class="mega-panel absolute left-0 top-full z-40 w-72 overflow-hidden rounded-b-md border-x border-b border-t-[3px] border-slate-200 border-t-amber-500 bg-white py-2 shadow-2xl shadow-slate-900/15">
                    @foreach ($menu as $item)
                        <a href="{{ $item->url }}" class="group flex items-center gap-3 px-4 py-2 transition hover:bg-amber-50">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded bg-slate-100 text-slate-700 transition group-hover:bg-amber-500 group-hover:text-white">
                                <x-icon name="{{ $item->icon }}" class="h-[18px] w-[18px]" />
                            </span>
                            <span class="flex-1 text-[13px] font-semibold text-slate-800 transition group-hover:text-amber-600">{{ $item->name }}</span>
                            @if ($item->count !== null)<span class="text-[11px] font-medium text-slate-400">{{ $item->count }}</span>@endif
                            <x-icon name="chevron" class="h-3 w-3 -rotate-90 text-slate-300 transition group-hover:text-amber-500" />
                        </a>
                    @endforeach
                    <a href="{{ route('products.index') }}" class="mx-4 mt-1 flex items-center gap-1.5 border-t border-slate-100 pt-2.5 text-[13px] font-bold text-amber-600 hover:text-amber-700">
                        Shop all products <x-icon name="arrow" class="h-3.5 w-3.5" />
                    </a>
                </div>
            </div>

            {{-- department tabs, each with its mega panel --}}
            @foreach ($menu as $item)
                @php $cat = $item->cat; $subs = $cat ? $cat->visibleChildrenRecursive : collect(); @endphp
                <div class="mega-trigger static {{ $loop->index < 3 ? 'flex' : ($loop->index < 5 ? 'hidden xl:flex' : 'hidden 2xl:flex') }}">
                    <a href="{{ $item->url }}"
                       class="mega-tab group flex h-full items-center gap-1.5 whitespace-nowrap px-3 py-3 text-[13px] font-semibold text-slate-900 transition">
                        <x-icon name="{{ $item->icon }}" class="h-4 w-4 text-slate-400 transition group-hover:text-amber-500" />
                        {{ $item->name }}
                        @if ($subs->count())
                            <x-icon name="chevron" class="mega-caret h-3 w-3 text-slate-400 transition-transform duration-200" />
                        @endif
                    </a>

                    @if ($subs->count())
                        @php $shownSubs = $subs->sortByDesc(fn ($sub) => $treeCount($sub))->take(8); @endphp
                        <div class="mega-panel absolute inset-x-0 top-full z-40 max-h-[80vh] overflow-y-auto border-t-[3px] border-amber-500 bg-white shadow-2xl shadow-slate-900/15">
                            <div class="mx-auto grid max-w-7xl gap-6 px-6 py-6 lg:grid-cols-[1fr_260px]">
                                <div>
                                    <div class="mb-4 flex items-baseline justify-between gap-4">
                                        <h3 class="flex items-center gap-2 text-lg font-extrabold text-[#111]">
                                            <x-icon name="{{ $item->icon }}" class="h-5 w-5 text-amber-500" /> {{ $item->name }}
                                        </h3>
                                        <a href="{{ $item->url }}" class="mega-link text-[13px] font-bold text-amber-600 hover:text-amber-700">View all {{ $item->count }} products →</a>
                                    </div>
                                    <div class="grid grid-cols-3 gap-3 xl:grid-cols-4">
                                        @foreach ($shownSubs as $sub)
                                            @php $subImg = $sub->image ?: null; $children = $sub->visibleChildrenRecursive; @endphp
                                            <div class="rounded-md border border-slate-200 p-3 transition hover:border-amber-500 hover:shadow-md">
                                                <a href="{{ route('products.index', ['category' => $sub->slug]) }}" class="group/sub flex items-center gap-3">
                                                    <span class="grid h-14 w-14 shrink-0 place-items-center overflow-hidden rounded bg-white">
                                                        @if ($subImg)
                                                            <img src="{{ media($subImg) }}" alt="" loading="lazy" class="h-full w-full object-contain transition duration-300 group-hover/sub:scale-110">
                                                        @else
                                                            <x-icon name="{{ $deptIcon($sub->name) }}" class="h-7 w-7 text-slate-400" />
                                                        @endif
                                                    </span>
                                                    <span class="min-w-0">
                                                        <span class="line-clamp-2 text-[13px] font-bold leading-snug text-[#111] transition group-hover/sub:text-amber-600">{{ $sub->name }}</span>
                                                        <span class="text-[11px] text-slate-400">{{ $treeCount($sub) }} {{ \Illuminate\Support\Str::plural('product', $treeCount($sub)) }}</span>
                                                    </span>
                                                </a>
                                                @if ($children->count())
                                                    <ul class="mt-2.5 space-y-1 border-t border-slate-100 pt-2">
                                                        @foreach ($children->take(4) as $child)
                                                            <li><a href="{{ route('products.index', ['category' => $child->slug]) }}" class="mega-link text-[12.5px] text-slate-600 hover:text-amber-600">{{ $child->name }}</a></li>
                                                        @endforeach
                                                        @if ($children->count() > 4)
                                                            <li><a href="{{ route('products.index', ['category' => $sub->slug]) }}" class="text-[12px] font-semibold text-amber-600 hover:text-amber-700">+{{ $children->count() - 4 }} more</a></li>
                                                        @endif
                                                    </ul>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                    @if ($subs->count() > $shownSubs->count())
                                        <a href="{{ $item->url }}" class="mt-4 inline-flex items-center gap-1.5 text-[13px] font-bold text-amber-600 hover:text-amber-700">
                                            See all {{ $subs->count() }} categories <x-icon name="arrow" class="h-3.5 w-3.5" />
                                        </a>
                                    @endif
                                </div>

                                {{-- department promo --}}
                                <a href="{{ $item->url }}" class="group/promo relative hidden flex-col overflow-hidden rounded-md bg-[#111] p-5 text-white lg:flex">
                                    <div class="pointer-events-none absolute -right-6 top-0 h-full w-3 -skew-x-[24deg] bg-amber-500/80"></div>
                                    @if ($cat->image)
                                        <span class="block h-32 overflow-hidden rounded bg-white p-2">
                                            <img src="{{ media($cat->image) }}" alt="" loading="lazy" class="h-full w-full object-contain transition duration-500 group-hover/promo:scale-105">
                                        </span>
                                    @endif
                                    <p class="mt-4 text-xs font-bold uppercase tracking-widest text-amber-500">Shop</p>
                                    <p class="text-xl font-black italic leading-tight">{{ $item->name }}</p>
                                    <p class="mt-1 text-xs text-white/70">{{ $item->count }} products · Genuine brands · Fast shipping</p>
                                    <span class="mt-4 inline-flex w-fit items-center gap-1.5 rounded bg-amber-500 px-4 py-2 text-xs font-bold text-white transition group-hover/promo:bg-amber-600">
                                        Shop Now <x-icon name="arrow" class="h-3.5 w-3.5" />
                                    </span>
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach

            <div class="ml-auto flex items-center gap-1 pl-3">
                <a href="{{ route('brands.index') }}" class="mega-tab group flex h-full items-center gap-1.5 px-3 py-3 text-[13px] font-semibold text-slate-900">
                    <x-icon name="badge" class="h-4 w-4 text-slate-400 transition group-hover:text-amber-500" /> Brands
                </a>
                <a href="{{ route('products.index', ['sort' => 'price_asc']) }}" class="ml-1 inline-flex items-center gap-1.5 rounded-full bg-red-600 px-3.5 py-1.5 text-xs font-bold text-white shadow-sm shadow-red-600/30 transition hover:bg-red-700">
                    <x-icon name="tag" class="h-3.5 w-3.5" /> Deals
                </a>
            </div>
        </div>
    </nav>

</header>

{{-- ============ Mobile slide-over menu ============ --}}
<div data-menu-panel class="fixed inset-0 z-50 hidden lg:hidden">
    <div data-menu-overlay class="menu-overlay absolute inset-0 bg-slate-900/60"></div>
    <div class="menu-drawer absolute left-0 top-0 flex h-full w-80 max-w-[85%] flex-col bg-white shadow-2xl">
        <div class="flex items-center justify-between bg-[#111] px-5 py-4">
            @include('layouts.partials.logo-mark', ['size' => 'sm'])
            <button data-menu-close class="grid h-8 w-8 place-items-center rounded-full text-white/70 hover:bg-white/10 hover:text-white" aria-label="Close">
                <span class="text-2xl leading-none">&times;</span>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto">
            {{-- quick links --}}
            <div class="grid grid-cols-3 gap-2 border-b border-slate-200 p-3">
                @foreach ([
                    ['home', 'Home', route('home')],
                    ['grid', 'Shop', route('products.index')],
                    ['badge', 'Brands', route('brands.index')],
                    ['tag', 'Deals', route('products.index', ['sort' => 'price_asc'])],
                    ['edit', 'Blog', route('blog.index')],
                    ['chat', 'Contact', route('contact.index')],
                ] as [$icon, $label, $url])
                    <a href="{{ $url }}" class="flex flex-col items-center gap-1 rounded-md border border-slate-200 py-2.5 text-[11px] font-semibold text-slate-700 transition hover:border-amber-500 hover:text-amber-600">
                        <x-icon name="{{ $icon }}" class="h-5 w-5 {{ $label === 'Deals' ? 'text-red-600' : 'text-amber-500' }}" /> {{ $label }}
                    </a>
                @endforeach
            </div>

            {{-- departments --}}
            <p class="px-4 pb-1 pt-4 text-[11px] font-bold uppercase tracking-widest text-slate-400">Shop by Category</p>
            <div class="px-2 pb-3">
                @foreach ($menu as $item)
                    @php $subs = $item->cat ? $item->cat->visibleChildrenRecursive : collect(); @endphp
                    <div class="border-b border-slate-100 last:border-0">
                        @if ($subs->count())
                            <button data-acc-toggle class="flex w-full items-center gap-3 rounded px-2 py-2.5 text-left text-sm font-semibold text-slate-800">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded bg-slate-100 text-slate-700"><x-icon name="{{ $item->icon }}" class="h-[18px] w-[18px]" /></span>
                                <span class="flex-1">{{ $item->name }}</span>
                                <span class="text-[11px] font-medium text-slate-400">{{ $item->count }}</span>
                                <x-icon name="chevron" data-acc-icon class="h-4 w-4 text-slate-400 transition-transform" />
                            </button>
                            <div class="hidden pb-2 pl-12 pr-2">
                                <a href="{{ $item->url }}" class="block py-1.5 text-[13px] font-bold text-amber-600">View all {{ $item->name }}</a>
                                @include('layouts.partials._mobile_items', ['items' => $subs, 'depth' => 0])
                            </div>
                        @else
                            <a href="{{ $item->url }}" class="flex items-center gap-3 rounded px-2 py-2.5 text-sm font-semibold text-slate-800">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded bg-slate-100 text-slate-700"><x-icon name="{{ $item->icon }}" class="h-[18px] w-[18px]" /></span>
                                <span class="flex-1">{{ $item->name }}</span>
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </nav>

        {{-- contact + quote --}}
        <div class="grid grid-cols-2 gap-2 border-t border-slate-200 p-3">
            <a href="tel:{{ $supportPhoneTel }}" class="flex items-center justify-center gap-1.5 rounded border border-slate-300 px-3 py-2.5 text-sm font-bold text-slate-800 transition hover:border-amber-500">
                <x-icon name="phone" class="h-4 w-4 text-amber-500" /> Call Us
            </a>
            <a href="{{ route('contact.index', ['subject' => 'Request a Quote']) }}" class="flex items-center justify-center rounded bg-amber-500 px-3 py-2.5 text-sm font-bold text-white transition hover:bg-amber-600">
                Get Quote
            </a>
        </div>
    </div>
</div>
