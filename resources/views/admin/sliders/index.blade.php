@extends('admin.layouts.app')

@section('title', 'Sliders')
@section('heading', 'Sliders')
@section('subheading', 'Manage every slider shown on the storefront from one place')

@section('content')

@php
    $user = auth()->user();
    $cards = [
        [
            'perm' => 'settings',
            'route' => 'admin.banners.index',
            'icon' => 'air',
            'title' => 'Hero Slider',
            'desc' => 'The big rotating slider at the top of the homepage. Add slides with a heading, button and a background image.',
            'count' => \App\Models\Banner::count().' slide(s)',
        ],
        [
            'perm' => 'products',
            'route' => 'admin.featured.index',
            'icon' => 'star',
            'title' => 'Featured Products',
            'desc' => 'The "Featured Deals" product slider on the homepage. Pick which products appear and set their order.',
            'count' => \App\Models\Product::where('featured', true)->count().' product(s)',
        ],
        [
            'perm' => 'brands',
            'route' => 'admin.brands.index',
            'icon' => 'box',
            'title' => 'Brand Slider',
            'desc' => 'The scrolling brand logo strip (marquee) below the hero. Toggle which brands appear in the slider.',
            'count' => \App\Models\Brand::where('in_marquee', true)->count().' brand(s)',
        ],
        [
            'perm' => 'settings',
            'route' => 'admin.sliders.wholesale.edit',
            'icon' => 'truck',
            'title' => 'Wholesale Banner',
            'desc' => 'The full-width "Apply for Wholesale" banner near the bottom of the homepage. Set its background image, heading, text and button.',
            'count' => \App\Models\Setting::get('home_wallpaper') ? 'Custom image set' : 'Default image',
        ],
    ];
    $cards = array_filter($cards, fn ($c) => $user?->hasPermission($c['perm']));
@endphp

@if (count($cards))
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($cards as $card)
            <a href="{{ route($card['route']) }}" class="group flex flex-col rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-white/15 hover:shadow-md">
                <span class="grid h-12 w-12 place-items-center rounded-xl bg-white/10 text-slate-200 transition group-hover:bg-white group-hover:text-slate-900">
                    <x-icon name="{{ $card['icon'] }}" class="h-6 w-6" />
                </span>
                <h3 class="mt-4 flex items-center gap-2 font-bold text-white">{{ $card['title'] }} <x-icon name="arrow" class="h-4 w-4 text-slate-300 transition group-hover:translate-x-1 group-hover:text-white" /></h3>
                <p class="mt-1.5 flex-1 text-sm leading-relaxed text-slate-400">{{ $card['desc'] }}</p>
                <span class="mt-4 inline-flex w-fit rounded-full bg-white/5 px-3 py-1 text-xs font-bold text-slate-400">{{ $card['count'] }}</span>
            </a>
        @endforeach
    </div>
@else
    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-10 text-center text-sm text-slate-400">
        Your role doesn't have access to any sliders.
    </div>
@endif

@endsection
