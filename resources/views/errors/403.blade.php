@extends('layouts.app')

@section('title', 'Access Denied — Texpert Deals')

@section('content')
<section class="mx-auto flex max-w-2xl flex-col items-center px-4 py-24 text-center sm:px-6">
    <p class="text-[100px] font-black leading-none tracking-tight text-amber-400 sm:text-[140px]">403</p>
    <h1 class="mt-2 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">Access denied</h1>
    <p class="mt-3 max-w-md text-slate-500">{{ $exception->getMessage() ?: 'You don’t have permission to view this page.' }}</p>
    <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
        <a href="{{ route('home') }}" class="rounded-full bg-amber-400 px-6 py-3 text-sm font-black uppercase tracking-wide text-slate-900 transition hover:bg-amber-500">Back to Home</a>
        <a href="{{ route('products.index') }}" class="rounded-full border border-slate-300 px-6 py-3 text-sm font-bold text-slate-700 transition hover:border-slate-400">Browse Products</a>
    </div>
</section>
@endsection
