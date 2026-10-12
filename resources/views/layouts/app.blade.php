<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="@yield('robots', 'index, follow, max-image-preview:large')">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="google-site-verification" content="iiSyybLx3EHb8-07RSEwpLVSPjcUjQJqBLqrwHN1_nc" />

    <title>@yield('title', 'HVAC, Plumbing & Printer Supplies Online — Texpert Deals')</title>
    <meta name="description" content="@yield('meta_description', 'Texpert Deals stocks HVAC, plumbing, electrical and Zebra printer supplies for US contractors — genuine, warranty-backed parts from 1,000+ brands, shipped fast.')">

    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    @php $fontHref = 'https://fonts.bunny.net/css?family=assistant:400,500,600,700,800|rubik:400,500,600,700,800,900&display=swap'; @endphp
    <link rel="preload" as="style" href="{{ $fontHref }}">
    <link href="{{ $fontHref }}" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="{{ $fontHref }}" rel="stylesheet"></noscript>

    <meta name="search-suggest-url" content="{{ route('search.suggest') }}">

    @php $favicon = \App\Models\Setting::get('favicon'); @endphp
    @if ($favicon)
        <link rel="icon" href="{{ media($favicon) }}">
        <link rel="shortcut icon" href="{{ media($favicon) }}">
        <link rel="apple-touch-icon" href="{{ media($favicon) }}">
    @endif

    {{-- Open Graph / Twitter (link previews for ads & social) --}}
    @php
        $ogImage = \App\Models\Setting::get('og_image') ?: \App\Models\Setting::get('header_logo');
        $ogSite = \App\Models\Setting::get('site_name', 'Texpert Deals');
    @endphp
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $ogSite }}">
    <meta property="og:title" content="@yield('title', $ogSite)">
    <meta property="og:description" content="@yield('meta_description', 'Shop HVAC, tools, electrical and more — bulk pricing, fast shipping.')">
    <meta property="og:url" content="{{ url()->current() }}">
    @if ($ogImage)<meta property="og:image" content="{{ media($ogImage) }}">@endif
    <meta name="twitter:card" content="summary_large_image">

    {{-- SEO: Organization + WebSite structured data (JSON-LD) --}}
    @include('layouts.partials.schema')

    {{-- page-specific structured data (Product, Breadcrumbs, …) --}}
    @stack('head')

    {{-- Marketing / ads tracking --}}
    @include('layouts.partials.tracking')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root{font-family:'Assistant',ui-sans-serif,system-ui,sans-serif; --font-weight-black:700}
        h1,h2,h3,h4,h5,h6{font-family:'Rubik',sans-serif}
    </style>
    {!! \App\Support\Theme::styleTag() !!}
</head>
<body class="min-h-screen bg-white text-slate-800 antialiased">
    {{-- Admin-managed custom <body> code (GTM noscript, chat widgets, etc.) --}}
    @php $bodyCode = \App\Models\Setting::get('body_code'); @endphp
    @if ($bodyCode)
        {!! $bodyCode !!}
    @endif

    @php $gtmId = \App\Models\Setting::get('gtm_id'); @endphp
    @if ($gtmId)
        <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    @endif
    @include('layouts.partials.admin_bar')
    @include('layouts.partials.header')

    @if (session('status'))
        <div data-flash class="fixed left-1/2 top-20 z-50 flex -translate-x-1/2 items-center gap-2 rounded-full bg-slate-900 px-5 py-3 text-sm font-semibold text-white shadow-xl">
            <x-icon name="check" class="h-5 w-5 text-amber-400" /> {{ session('status') }}
        </div>
    @endif

    <main>
        @yield('content')
    </main>

    @unless (request()->routeIs('home'))
        @include('layouts.partials.support-strip')
    @endunless

    @include('layouts.partials.footer')

    @include('cart.partials.drawer')

    @include('layouts.partials.chatbot')

    {{-- nothing should distract someone who is paying --}}
    @unless (request()->routeIs('checkout.*', 'pay.*'))
        @include('layouts.partials.purchase-notify')
    @endunless

    @stack('scripts')
</body>
</html>
