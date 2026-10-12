@extends('layouts.app')

@section('title', 'About Us — Texpert Deals')
@section('meta_description', 'Texpert Deals is a Texas-based wholesale distributor of automotive, HVAC, tools, marine, water systems and outdoor gear — bulk pricing, fast shipping and expert support nationwide.')

@section('content')

@include('pages._hero', [
    'eyebrow' => 'Our story',
    'title' => 'About Texpert Deals',
    'subtitle' => 'At Texpert Deals, we are dedicated to providing top-quality products and exceptional service to our customers — a wide range of expert-approved solutions to meet diverse business needs, with a focus on reliability, innovation and value.',
])

<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">

    {{-- intro --}}
    <div class="grid gap-10 lg:grid-cols-[1.4fr_1fr] lg:items-start">
        <div>
            <h2 class="text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">Dedicated to finding you the best offers every day</h2>
            <div class="mt-5 space-y-4 leading-relaxed text-slate-600">
                <p>Texpert Deals is a fast-growing distribution platform focused on delivering high-quality, value-driven products to a wide and digitally connected customer base. With strong sourcing capabilities and efficient supply chain management, we connect customers with reliable products through a seamless and convenient shopping experience.</p>
                <p><span class="font-semibold text-slate-900">Founded in 2018</span>, Texpert Deals was built with a vision to simplify access to quality products by combining competitive pricing, dependable service and a streamlined distribution network. Through trusted supplier partnerships and a customer-focused approach, we have established ourselves as a reliable name in the market.</p>
                <p>Today, Texpert Deals generates <span class="font-semibold text-slate-900">$4.5 million in annual revenue</span> and continues to scale rapidly, with a clear goal of reaching $20 million in revenue by 2026–2027.</p>
                <p>Texpert Deals is built for HVAC/R pros who demand more. As a Texas-based wholesale distributor, we bring 1,000+ trusted brands — Honeywell, Fieldpiece, NAVAC, Goodman, Copeland, Trane, Klein, RectorSeal and more — together in one catalog at true wholesale prices. Every product we ship is genuine, OEM-sourced, and warranty-backed, so you can install with confidence.</p>
                <p>What sets us apart is the blend of deep industry experience, a massive in-stock inventory, and real relationships across the manufacturing and distribution sectors. That means fast shipping on 100,000+ in-stock items, volume discounts and Net-30 terms for contractors, and expert support when you need it. Whether you're a contractor, distributor, reseller, or running your own shop, we deliver top-tier products with the speed, service, and reliability the job demands.</p>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            @php
                $stats = [
                    ['2018', 'Year founded'],
                    ['$4.5M', 'Annual revenue'],
                    ['$20M', 'Revenue goal by 2027'],
                    ['8+', 'Product categories'],
                ];
            @endphp
            @foreach ($stats as [$big, $small])
                <div class="rounded-2xl border border-slate-200 bg-white p-6 text-center">
                    <p class="text-3xl font-black text-amber-500">{{ $big }}</p>
                    <p class="mt-1 text-sm font-medium text-slate-600">{{ $small }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- values --}}
    <div class="mt-16">
        <h2 class="text-center text-2xl font-black tracking-tight text-slate-900">Why choose Texpert Deals</h2>
        <div class="mt-8 grid gap-5 sm:grid-cols-3">
            @php
                $values = [
                    ['truck', 'Fast shipping across the US', 'Quick, reliable shipping nationwide on every order — from single items to bulk.'],
                    ['refresh', '30-day returns', 'Changed your mind? Hassle-free returns within 30 days, no stress.'],
                    ['phone', '24/7 dedicated support', 'A real support team on hand around the clock to help you get it right.'],
                    ['box', 'Bulk & wholesale pricing', 'Competitive bulk pricing for distributors, resellers and wholesalers nationwide.'],
                    ['shield', 'Trusted sourcing', 'Top-tier products through trusted supplier, contractor and distributor partnerships.'],
                    ['star', 'Large inventory', 'A wide range across automotive, HVAC, tools, marine, water systems and more.'],
                ];
            @endphp
            @foreach ($values as [$icon, $title, $text])
                <div class="rounded-2xl border border-slate-200 bg-white p-6">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-amber-100 text-amber-600"><x-icon name="{{ $icon }}" class="h-5 w-5" /></span>
                    <h3 class="mt-4 font-bold text-slate-900">{{ $title }}</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-slate-500">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- CTA --}}
    <div class="mt-16 flex flex-col items-center justify-between gap-4 rounded-3xl bg-slate-900 px-8 py-10 text-center text-white sm:flex-row sm:text-left">
        <div>
            <h3 class="text-2xl font-black">Ready to get started?</h3>
            <p class="mt-1 text-slate-300">Browse our full catalog or reach out for custom wholesale pricing.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('products.index') }}" class="rounded-full bg-amber-400 px-6 py-3 text-sm font-bold text-slate-900 transition hover:bg-amber-300">Shop Products</a>
            <a href="{{ route('contact.index') }}" class="rounded-full border border-white/30 px-6 py-3 text-sm font-bold text-white transition hover:bg-white/10">Contact Us</a>
        </div>
    </div>
</div>

@endsection
