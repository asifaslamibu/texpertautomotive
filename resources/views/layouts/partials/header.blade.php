
{{-- ============ Announcement bar ============ --}}
@if (\App\Models\Setting::get('announcement_enabled', '1') !== '0')
<div class="overflow-hidden bg-gradient-to-r from-black via-slate-900 to-black text-white">
    <div class="mx-auto max-w-7xl px-4 py-2 text-center text-[13px] font-medium tracking-wide sm:px-6">
        {{ \App\Models\Setting::get('announcement', '🔥 Bulk refrigerant pricing • 5% off your first order — use code DEALS5') }}
        @if (\App\Models\Setting::get('free_shipping_note'))
            <span class="mx-3 hidden text-white/40 sm:inline">|</span>
            <span class="hidden sm:inline">{{ \App\Models\Setting::get('free_shipping_note') }}</span>
        @endif
    </div>
</div>
@endif

{{-- ============ Main header ============ --}}
{{-- Pages that define the `plain_header` section (checkout) get a header that
     stays at the top of the page instead of following the scroll. --}}
@php $stickyHeader = ! View::hasSection('plain_header'); @endphp
<header @if($stickyHeader) data-header @endif class="site-header z-30 bg-black/95 backdrop-blur {{ $stickyHeader ? 'sticky top-0' : 'relative' }}">
    <div class="mx-auto max-w-7xl px-2 sm:px-3">
        <div class="header-row flex items-center gap-4 py-3.5">

            <button type="button" data-menu-toggle class="lg:hidden -ml-1 p-2 text-white" aria-label="Open menu">
                <x-icon name="menu" class="h-6 w-6" />
            </button>

            <a href="{{ route('home') }}" class="group flex shrink-0 items-center">
                @php $headerLogo = \App\Models\Setting::get('header_logo'); @endphp
                @if ($headerLogo)
                    <img src="{{ media($headerLogo) }}" alt="{{ \App\Models\Setting::get('site_name', 'Texpert Deals') }}" class="h-10 w-auto">
                @elseif (file_exists(public_path('images/logo.png')))
                    <img src="{{ asset('images/logo.png') }}" alt="Texpert Deals" class="h-10 w-auto">
                @else
                    <span class="relative flex flex-col leading-[0.8]">
                        <span class="text-2xl font-black uppercase tracking-tight text-white">
                            TEXP<span class="relative">E<span class="absolute -right-1 -top-1 italic text-amber-400">/</span></span>RT
                        </span>
                        <span class="-mt-0.5 self-end text-[13px] font-black uppercase tracking-[0.35em] text-amber-400">DEALS</span>
                    </span>
                @endif
            </a>

            <form action="{{ route('products.index') }}" method="GET" class="relative hidden flex-1 md:block" data-search>
                <input type="search" name="q" value="{{ request('q') }}" autocomplete="off" data-search-input
                       placeholder="Search products by name, brand or SKU…"
                       class="w-full rounded-full border border-slate-300 bg-slate-50 py-2.5 pl-5 pr-12 text-sm outline-none transition focus:border-amber-400 focus:bg-white focus:ring-4 focus:ring-amber-100">
                <button type="submit" class="absolute right-1.5 top-1/2 grid h-8 w-8 -translate-y-1/2 place-items-center rounded-full bg-slate-900 text-white transition hover:bg-slate-800 active:scale-95">
                    <x-icon name="search" class="h-4 w-4" />
                </button>
                <div data-search-results class="absolute left-0 right-0 top-full z-50 mt-2 hidden max-h-[70vh] overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-300/40"></div>
            </form>

            <div class="ml-auto flex items-center gap-1 sm:gap-3">
                @php
                    $supportPhone = \App\Models\Setting::get('contact_phone', '+1-254-358-0017');
                    $supportPhoneTel = preg_replace('/[^0-9+]/', '', $supportPhone);
                    $tollFree = \App\Models\Setting::get('toll_free', '(945) 427-9519');
                    $tollFreeTel = preg_replace('/[^0-9+]/', '', $tollFree);
                    $supportOnline = \App\Models\Setting::get('support_online', '1') !== '0';
                    $supportWait = \App\Models\Setting::get('support_wait', '2 min');
                @endphp

                {{-- Contact Us — hover dropdown with live chat + call --}}
                <div class="group/contact relative hidden lg:block">
                    <button type="button" class="flex items-center gap-2 rounded-full px-2 py-1.5 text-left text-white/85 transition hover:text-amber-400">
                        <x-icon name="headset" class="h-6 w-6 shrink-0 text-amber-400" />
                        <span class="flex flex-col leading-tight">
                            <span class="flex items-center gap-1 text-sm font-bold">Contact Us <x-icon name="chevron" class="h-3 w-3 transition-transform duration-200 group-hover/contact:rotate-180" /></span>
                            <span class="flex items-center gap-1 text-[11px] font-medium {{ $supportOnline ? 'text-emerald-400' : 'text-white/50' }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $supportOnline ? 'bg-emerald-400' : 'bg-slate-400' }}"></span>
                                Live Chat: {{ $supportOnline ? 'Online Now' : 'Offline' }}
                            </span>
                        </span>
                    </button>

                    {{-- panel (bridge with pt-2 so hover doesn't drop) --}}
                    <div class="invisible absolute right-0 top-full z-50 w-72 pt-3 opacity-0 transition-all duration-150 group-hover/contact:visible group-hover/contact:opacity-100">
                        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-400/30">
                            <button type="button"
                                    onclick="var t=document.querySelector('[data-chat-toggle]'); if(t){t.click()} else {location.href='{{ route('contact.index') }}'}"
                                    class="flex w-full items-center gap-3 px-4 py-3.5 text-left transition hover:bg-amber-50">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-slate-100 text-slate-700"><x-icon name="chat" class="h-5 w-5" /></span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-bold text-amber-600">Live Chat</span>
                                    <span class="flex items-center gap-1 text-xs text-slate-500"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> EST. wait: {{ $supportWait }}</span>
                                </span>
                            </button>
                            <div class="mx-4 border-t border-slate-100"></div>
                            <a href="tel:{{ $supportPhoneTel }}" class="flex items-center gap-3 px-4 py-3.5 transition hover:bg-amber-50">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-slate-100 text-slate-700"><x-icon name="phone" class="h-5 w-5" /></span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-bold text-slate-900">Call <span class="text-amber-600">{{ $supportPhone }}</span></span>
                                    <span class="flex items-center gap-1 text-xs text-slate-500"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> EST. wait: {{ $supportWait }}</span>
                                </span>
                            </a>
                            @if ($tollFree)
                                <div class="mx-4 border-t border-slate-100"></div>
                                <a href="tel:{{ $tollFreeTel }}" class="flex items-center gap-3 px-4 py-3.5 transition hover:bg-amber-50">
                                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-slate-100 text-slate-700"><x-icon name="phone" class="h-5 w-5" /></span>
                                    <span class="min-w-0">
                                        <span class="block text-sm font-bold text-slate-900">Toll-Free <span class="text-amber-600">{{ $tollFree }}</span></span>
                                        <span class="text-xs text-slate-500">Call us free, anytime</span>
                                    </span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Get Quote --}}
                <a href="{{ route('contact.index', ['subject' => 'Request a Quote']) }}" class="hidden shrink-0 items-center gap-1.5 rounded-full bg-amber-400 px-4 py-2 text-sm font-black uppercase tracking-wide text-slate-900 shadow-sm transition hover:bg-amber-500 lg:inline-flex">
                    Get Quote
                </a>
                <a href="{{ auth('customer')->check() ? route('account.index') : route('account.login') }}"
                   class="group grid h-9 w-9 place-items-center rounded-full text-white/80 transition hover:bg-white/10"
                   aria-label="{{ auth('customer')->check() ? 'My Account' : 'Sign in' }}" title="{{ auth('customer')->check() ? 'My Account' : 'Sign in' }}">
                    <x-icon name="user" class="h-5 w-5 transition group-hover:scale-110" />
                </a>
                <a href="{{ route('cart.index') }}" data-cart-open class="group relative grid h-9 w-9 place-items-center rounded-full text-white/80 transition hover:bg-white/10" aria-label="Cart">
                    <x-icon name="cart" class="h-5 w-5 transition group-hover:scale-110" />
                    <span data-cart-count class="absolute -right-0.5 -top-0.5 grid h-4 min-w-4 place-items-center rounded-full bg-amber-500 px-1 text-[10px] font-bold text-white {{ \App\Support\Cart::count() > 0 ? '' : 'hidden' }}">{{ \App\Support\Cart::count() }}</span>
                </a>
            </div>
        </div>

        {{-- mobile search --}}
        <form action="{{ route('products.index') }}" method="GET" class="relative pb-3 md:hidden" data-search>
            <input type="search" name="q" value="{{ request('q') }}" autocomplete="off" data-search-input placeholder="Search products…"
                   class="w-full rounded-full border border-slate-300 bg-slate-50 py-2.5 pl-5 pr-12 text-sm outline-none focus:border-amber-400 focus:bg-white">
            <button type="submit" class="absolute right-1.5 top-[0.4rem] grid h-8 w-8 place-items-center rounded-full bg-slate-900 text-white">
                <x-icon name="search" class="h-4 w-4" />
            </button>
            <div data-search-results class="absolute left-0 right-0 top-full z-50 hidden max-h-[60vh] overflow-y-auto rounded-2xl border border-slate-200 bg-white shadow-2xl shadow-slate-300/40"></div>
        </form>
    </div>

    {{-- ============ Category nav + MEGA MENU (desktop) ============ --}}
    <nav class="relative hidden bg-white shadow-[0_4px_14px_rgba(0,0,0,0.08)] lg:block">
        <div class="mx-auto flex max-w-7xl items-stretch px-2 sm:px-3">
            @forelse ($navCategories as $cat)
                <div class="mega-trigger static">
                    <a href="{{ route('products.index', ['category' => $cat->slug]) }}"
                       class="mega-tab flex h-full items-center gap-1.5 px-5 py-3.5 text-[13px] font-semibold text-slate-900 transition">
                        {{ $cat->name }}
                        @if ($cat->visibleChildrenRecursive->count())
                            <x-icon name="chevron" class="mega-caret h-3 w-3 transition-transform duration-200" />
                        @endif
                    </a>

                    {{-- mega panel --}}
                    @if ($cat->visibleChildrenRecursive->count())
                        @php
                            // IT & Communication and Computer & Accessories show at most 4 subcategories (and at
                            // most 4 child categories under each, with a "View all" link); every other category
                            // shows up to 12, unlimited children.
                            $catNameLower = \Illuminate\Support\Str::lower($cat->name);
                            $isComm = in_array($cat->slug, ['it-communication', 'computer-accessories'])
                                || \Illuminate\Support\Str::contains($catNameLower, ['communication', 'computer']);
                            $subLimit = $isComm ? 4 : 12;
                            $childLimit = $isComm ? 4 : null;
                        @endphp
                        <div class="mega-panel absolute left-0 right-0 top-full z-40 max-h-[80vh] overflow-y-auto border-t-4 border-amber-400 bg-white shadow-2xl shadow-slate-400/30">
                            <div class="mx-auto max-w-7xl px-6 py-7">
                                {{-- header --}}
                                <div class="mb-6 flex items-center justify-between border-b border-slate-100 pb-4">
                                    <h3 class="text-xl font-bold text-slate-900">{{ $cat->name }}</h3>
                                    <a href="{{ route('products.index', ['category' => $cat->slug]) }}"
                                       class="mega-link text-sm font-bold text-amber-500 transition hover:text-amber-600">
                                        View All{{ $cat->visibleChildrenRecursive->count() > $subLimit ? ' ('.$cat->visibleChildrenRecursive->count().')' : '' }} →
                                    </a>
                                </div>

                                {{-- columns: subcategories with the most child categories first → full nested tree underneath --}}
                                @php
                                    $topSubs = $cat->visibleChildrenRecursive
                                        ->sortByDesc(fn ($s) => $s->visibleChildrenRecursive->count())
                                        ->take($subLimit);

                                    // A category with only two or three groups would stack each one into a
                                    // single very tall column: give those groups the full width instead and
                                    // flow their items across columns, so the panel stays short.
                                    $subCount = $topSubs->count();
                                    $panelCols = match (true) {
                                        $subCount <= 1 => 'grid-cols-1',
                                        $subCount === 2 => 'grid-cols-2',
                                        $subCount === 3 => 'grid-cols-3',
                                        $subCount === 4 => 'grid-cols-4',
                                        default => '[grid-template-columns:repeat(auto-fill,minmax(190px,1fr))]',
                                    };
                                    $itemCols = match (true) {
                                        $subCount <= 1 => 'columns-4',
                                        $subCount === 2 => 'columns-3',
                                        $subCount === 3 => 'columns-2',
                                        $subCount === 4 => 'columns-2',
                                        default => '',
                                    };
                                @endphp
                                <div class="grid gap-x-6 gap-y-8 {{ $panelCols }}">
                                    @foreach ($topSubs as $sub)
                                        {{-- side-by-side groups get a hairline between them so the trees stay apart --}}
                                        <div class="min-w-0 {{ $itemCols && ! $loop->first ? 'border-l border-slate-100 pl-6' : '' }}">
                                            <a href="{{ route('products.index', ['category' => $sub->slug]) }}"
                                               class="mega-link mb-2.5 inline-block text-sm font-bold leading-snug text-amber-500 transition hover:text-amber-600">
                                                {{ $sub->name }}
                                            </a>
                                            @if ($sub->visibleChildrenRecursive->count())
                                                <div class="{{ $itemCols }} gap-x-6">
                                                    @include('layouts.partials._mega_items', ['items' => $sub->visibleChildrenRecursive, 'depth' => 0, 'limit' => $childLimit, 'viewAllUrl' => route('products.index', ['category' => $sub->slug])])
                                                </div>
                                            @else
                                                <a href="{{ route('products.index', ['category' => $sub->slug]) }}" class="mega-link text-[13px] text-slate-500 hover:text-amber-600">{{ $sub->products_count }} products</a>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                @if ($cat->visibleChildrenRecursive->count() > $subLimit)
                                    <div class="mt-7 border-t border-slate-100 pt-4 text-center">
                                        <a href="{{ route('products.index', ['category' => $cat->slug]) }}"
                                           class="inline-flex items-center gap-1.5 rounded-full bg-amber-400 px-5 py-2 text-sm font-bold text-slate-900 transition hover:bg-amber-500">
                                            View all {{ $cat->visibleChildrenRecursive->count() }} categories →
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <span class="px-5 py-3.5 text-sm text-slate-400">No categories yet — import products to populate the menu.</span>
            @endforelse

        </div>
    </nav> 


</header>

{{-- ============ Mobile slide-over menu ============ --}}
<div data-menu-panel class="fixed inset-0 z-50 hidden lg:hidden">
    <div data-menu-overlay class="menu-overlay absolute inset-0 bg-slate-900/50"></div>
    <div class="menu-drawer absolute left-0 top-0 flex h-full w-80 max-w-[85%] flex-col bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-slate-200 p-5">
            <span class="text-lg font-black text-slate-900">texper<span class="text-amber-500">deals</span></span>
            <button data-menu-close class="grid h-8 w-8 place-items-center rounded-full text-slate-500 hover:bg-slate-100" aria-label="Close">
                <span class="text-2xl leading-none">&times;</span>
            </button>
        </div>
        {{-- quick contact + quote (mobile) --}}
        <div class="border-b border-slate-200 p-3">
            <div class="grid grid-cols-2 gap-2">
                <a href="{{ route('contact.index', ['subject' => 'Request a Quote']) }}" class="flex items-center justify-center gap-1.5 rounded-lg bg-amber-400 px-3 py-2.5 text-sm font-black uppercase tracking-wide text-slate-900 transition hover:bg-amber-500">
                    Get Quote
                </a>
                <a href="tel:{{ $supportPhoneTel }}" class="flex items-center justify-center gap-1.5 rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-bold text-slate-800 transition hover:border-amber-300 hover:bg-amber-50">
                    <x-icon name="phone" class="h-4 w-4 text-amber-500" /> Call
                </a>
            </div>
            @if ($tollFree)
                <a href="tel:{{ $tollFreeTel }}" class="mt-2 flex items-center justify-center gap-1.5 rounded-lg bg-slate-900 px-3 py-2.5 text-sm font-bold text-white transition hover:bg-slate-800">
                    <x-icon name="phone" class="h-4 w-4 text-amber-400" /> Toll-Free {{ $tollFree }}
                </a>
            @endif
        </div>
        <nav class="flex-1 overflow-y-auto p-3">
            @forelse ($navCategories as $cat)
                <div class="border-b border-slate-100">
                    <button data-acc-toggle class="flex w-full items-center justify-between px-3 py-3 text-sm font-semibold text-slate-800">
                        <span>{{ $cat->name }}</span>
                        <x-icon name="chevron" data-acc-icon class="h-4 w-4 text-slate-400 transition-transform" />
                    </button>
                    <div class="hidden pb-3 pl-4 pr-3">
                        <a href="{{ route('products.index', ['category' => $cat->slug]) }}" class="block rounded-lg px-2 py-1.5 text-sm font-bold text-amber-600">View all {{ $cat->name }}</a>
                        @include('layouts.partials._mobile_items', ['items' => $cat->visibleChildrenRecursive, 'depth' => 0])
                    </div>
                </div>
            @empty
                <p class="px-3 py-4 text-sm text-slate-400">No categories yet.</p>
            @endforelse
        </nav>
    </div>
</div>
