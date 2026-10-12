<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') — TexperDeals Admin</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900" rel="stylesheet">
    @php $favicon = \App\Models\Setting::get('favicon'); @endphp
    @if ($favicon)
        <link rel="icon" href="{{ media($favicon) }}">
        <link rel="shortcut icon" href="{{ media($favicon) }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>:root{--font-weight-black:700}body{font-family:'Inter',sans-serif}</style>
    {!! \App\Support\Theme::styleTag() !!}
    @stack('head')
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">

{{-- ===== Ambient background (matches the login screen) ===== --}}
<div class="pointer-events-none fixed inset-0 z-0 overflow-hidden">
    <div class="absolute -left-24 -top-24 h-96 w-96 animate-blob rounded-full bg-slate-500/15 blur-3xl"></div>
    <div class="absolute -bottom-24 -right-24 h-96 w-96 animate-blob rounded-full bg-indigo-500/10 blur-3xl" style="animation-delay:-5s"></div>
    <div class="absolute inset-0 opacity-[0.06] [background-image:linear-gradient(#fde68a_1px,transparent_1px),linear-gradient(90deg,#fde68a_1px,transparent_1px)] [background-size:42px_42px]"></div>
</div>

@php
    // [route, label, icon, active-pattern, permission-key (null = always visible)]
    $nav = [
        ['admin.dashboard', 'Dashboard', 'gauge', 'admin.dashboard', null],

        // --- Sales ---
        ['admin.orders.index', 'Orders', 'cart', 'admin.orders.*', 'orders'],
        ['admin.purchase-orders.index', 'Purchase Orders', 'truck', 'admin.purchase-orders.*|admin.distributors.*', 'purchase_orders'],
        ['admin.checkout-leads.index', 'Abandoned Checkouts', 'cart', 'admin.checkout-leads.*', 'orders'],
        ['admin.enquiries.index', 'Enquiries', 'chat', 'admin.enquiries.*', 'enquiries'],
        ['admin.customers.index', 'Customers', 'user', 'admin.customers.*', 'customers'],

        // --- Catalog ---
        ['admin.products.index', 'Products', 'parts', 'admin.products.*', 'products'],
        ['admin.categories.index', 'Categories', 'air', 'admin.categories.*', 'categories'],
        ['admin.brands.index', 'Brands', 'star', 'admin.brands.*', 'brands'],
        ['admin.reviews.index', 'Reviews', 'star', 'admin.reviews.*', 'reviews'],
        ['admin.import.index', 'Import (CSV)', 'box', 'admin.import.*', 'import'],
        ['admin.stock.index', 'Stock Update', 'refresh', 'admin.stock.*', 'stock'],
        ['admin.sku.index', 'SKU Update', 'edit', 'admin.sku.*', 'import'],
        ['admin.blogs.index', 'Blog', 'edit', 'admin.blogs.*', 'blogs'],
        ['admin.subscribers.index', 'Subscribers', 'mail', 'admin.subscribers.*', 'subscribers'],
        ['admin.coupons.index', 'Discount Codes', 'star', 'admin.coupons.*', 'coupons'],

        // --- Content & config ---
        ['admin.sliders.index', 'Sliders', 'star', 'admin.sliders.*|admin.banners.*|admin.featured.*', ['products', 'brands', 'settings']],
        ['admin.emails.edit', 'Emails', 'mail', 'admin.emails.*', 'settings'],
        ['admin.settings.edit', 'Settings', 'tools', 'admin.settings.*', 'settings'],
    ];

    // --- Tools (master / super) ---
    $nav[] = ['admin.image-issues.index', 'Image Issues', 'search', 'admin.image-issues.*', 'super'];
    if (config('masteradmin.save_cards')) {
        $nav[] = ['admin.saved-cards.index', 'Saved Cards', 'lock', 'admin.saved-cards.*', 'master'];
    }

    // --- Team ---
    $nav[] = ['admin.users.index', 'Staff', 'user', 'admin.users.*', 'staff'];
    $nav[] = ['admin.roles.index', 'Roles', 'shield', 'admin.roles.*', 'staff'];
    $authUser = auth()->user();
    // perm can be: null (always), a string, or an array (show if the user has ANY of them)
    $nav = array_filter($nav, function ($item) use ($authUser) {
        // skip links whose route isn't registered yet (live route cache not rebuilt after a deploy)
        if (! \Illuminate\Support\Facades\Route::has($item[0])) return false;
        $perm = $item[4];
        if ($perm === null) return true;
        foreach ((array) $perm as $p) {
            if ($p === 'master') { if ($authUser?->isMaster()) return true; continue; }
            if ($p === 'super') { if ($authUser?->isSuperAdmin()) return true; continue; }
            if ($authUser?->hasPermission($p)) return true;
        }
        return false;
    });
@endphp

<div class="relative z-10 flex min-h-screen">

    {{-- ===== Sidebar ===== --}}
    <aside data-sidebar class="fixed inset-y-0 left-0 z-40 flex w-64 max-w-[85vw] -translate-x-full transform flex-col border-r border-white/10 bg-slate-950/95 text-slate-300 backdrop-blur-xl transition-transform duration-300 lg:translate-x-0 lg:bg-slate-950/80">
        <div class="flex h-16 shrink-0 items-center gap-2 border-b border-white/10 pl-6 pr-3">
            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl border border-white/10 bg-white/10 text-white">
                <x-icon name="box" class="h-5 w-5" />
            </span>
            <span class="truncate text-base font-black uppercase lg:text-lg tracking-tight text-white">TEXP<span class="text-slate-400">E/</span>RT <span class="text-slate-400">DEALS</span></span>
            <button type="button" data-sidebar-close class="ml-auto rounded-lg p-2 text-slate-400 transition hover:bg-white/10 hover:text-white lg:hidden" aria-label="Close menu">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>

        <nav class="flex min-h-0 flex-1 flex-col gap-1 overflow-y-auto overscroll-contain p-4">
            @foreach ($nav as [$route, $label, $icon, $pattern, $perm])
                @php $active = request()->routeIs(...explode('|', $pattern)); @endphp
                <a href="{{ route($route) }}"
                   class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ $active ? 'bg-white text-slate-900 shadow-lg shadow-black/30' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}">
                    <x-icon name="{{ $icon }}" class="h-5 w-5 shrink-0" /><span class="truncate">{{ $label }}</span>
                </a>
            @endforeach
        </nav>

        <div class="shrink-0 border-t border-white/10 p-4">
            <a href="{{ route('home') }}" target="_blank" class="flex items-center gap-3 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-400 transition hover:bg-white/5 hover:text-white">
                <x-icon name="arrow" class="h-5 w-5" /> View Store
            </a>
        </div>
    </aside>

    {{-- sidebar overlay (mobile) --}}
    <div data-sidebar-overlay class="fixed inset-0 z-30 hidden bg-slate-900/50 lg:hidden"></div>

    {{-- ===== Main ===== --}}
    <div class="flex min-w-0 flex-1 flex-col lg:ml-64">

        {{-- Topbar --}}
        <header class="sticky top-0 z-20 flex h-16 items-center gap-2 border-b border-white/10 bg-slate-950/60 px-4 backdrop-blur-xl sm:gap-4 sm:px-6">
            <button type="button" data-sidebar-toggle class="lg:hidden -ml-1 shrink-0 p-2 text-slate-300" aria-label="Menu">
                <x-icon name="menu" class="h-6 w-6" />
            </button>
            <div class="min-w-0">
                <h1 class="truncate text-base font-bold text-white sm:text-lg">@yield('heading', 'Dashboard')</h1>
                <p class="hidden truncate text-xs text-slate-400 sm:block">@yield('subheading', 'Welcome back, manage your store')</p>
            </div>

            <div class="ml-auto flex shrink-0 items-center gap-2 sm:gap-3">
                <span class="hidden text-right sm:block">
                    <span class="block text-sm font-semibold text-white">{{ auth()->user()->name ?? 'Admin' }}</span>
                    <span class="block text-xs text-slate-400">{{ auth()->user()->role->name ?? auth()->user()->email ?? '' }}</span>
                </span>
                <span class="grid h-9 w-9 place-items-center rounded-full bg-gradient-to-br from-slate-700 to-slate-800 text-sm font-bold text-white ring-1 ring-white/10">
                    {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                </span>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="rounded-lg border border-white/10 px-3 py-1.5 text-sm font-semibold text-slate-300 transition hover:border-rose-400/40 hover:bg-rose-500/10 hover:text-rose-300">
                        Logout
                    </button>
                </form>
            </div>
        </header>

        {{-- Flash --}}
        @if (session('status'))
            <div data-flash class="mx-4 mt-4 flex items-center gap-2 rounded-xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-200 sm:mx-6">
                <x-icon name="check" class="h-5 w-5" /> {{ session('status') }}
            </div>
        @endif
        @if (session('error'))
            <div data-flash class="mx-4 mt-4 rounded-xl border border-rose-400/30 bg-rose-500/10 px-4 py-3 text-sm font-medium text-rose-200 sm:mx-6">
                {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mx-4 mt-4 rounded-xl border border-rose-400/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200 sm:mx-6">
                <p class="font-semibold">Please fix the following:</p>
                <ul class="mt-1 list-inside list-disc">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <main class="flex-1 p-4 sm:p-6">
            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>
