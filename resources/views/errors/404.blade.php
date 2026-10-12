@extends('layouts.app')

@section('title', 'Page Not Found — Texpert Deals')
@section('meta_description', 'The page you are looking for could not be found.')

@section('content')
<section class="mx-auto flex max-w-2xl flex-col items-center px-4 py-24 text-center sm:px-6">
    <p class="text-[100px] font-black leading-none tracking-tight text-amber-400 sm:text-[140px]">404</p>
    <h1 class="mt-2 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">Page not found</h1>
    <p class="mt-3 max-w-md text-slate-500">The page you’re looking for doesn’t exist or may have moved. Let’s get you back on track.</p>

    <form action="{{ route('products.index') }}" method="GET" class="relative mt-7 w-full max-w-md">
        <input type="search" name="q" placeholder="Search products by name, brand or SKU…"
               class="w-full rounded-full border border-slate-300 bg-white py-3 pl-5 pr-12 text-sm outline-none transition focus:border-amber-400 focus:ring-4 focus:ring-amber-100">
        <button type="submit" class="absolute right-1.5 top-1/2 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-full bg-slate-900 text-white transition hover:bg-slate-800">
            <x-icon name="search" class="h-4 w-4" />
        </button>
    </form>

    <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
        <a href="{{ route('home') }}" class="rounded-full bg-amber-400 px-6 py-3 text-sm font-black uppercase tracking-wide text-slate-900 transition hover:bg-amber-500">Back to Home</a>
        <a href="{{ route('products.index') }}" class="rounded-full border border-slate-300 px-6 py-3 text-sm font-bold text-slate-700 transition hover:border-slate-400">Browse Products</a>
        <a href="{{ route('contact.index') }}" class="rounded-full border border-slate-300 px-6 py-3 text-sm font-bold text-slate-700 transition hover:border-slate-400">Contact Us</a>
    </div>
</section>
@endsection
