@extends('layouts.app')

@section('title', $model['label'].' Ribbons, Labels & Parts — Texpert Deals')
@section('meta_description', 'Every ribbon, label, wristband, battery and spare part compatible with the '.$model['label'].' printer. '.$totalCount.' genuine Zebra products in stock.')

@section('content')

{{-- ===== Header ===== --}}
<section class="border-b border-slate-200 bg-slate-50">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
        <nav class="flex flex-wrap items-center gap-2 text-sm text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-amber-500">Home</a>
            <span>/</span>
            <a href="{{ route('printer-finder.index') }}" class="hover:text-amber-500">Printer Supplies</a>
            <span>/</span>
            <span class="font-semibold text-slate-700">{{ $model['label'] }}</span>
        </nav>

        <div class="mt-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black tracking-tight text-slate-900 sm:text-4xl">
                    Supplies for <span class="text-amber-600">{{ $model['label'] }}</span>
                </h1>
                <p class="mt-2 text-slate-500">
                    <span class="font-semibold text-slate-700">{{ $totalCount }}</span> compatible
                    {{ Str::plural('product', $totalCount) }} — verified against the manufacturer's compatibility list.
                </p>
            </div>
            <a href="{{ route('printer-finder.index') }}"
               class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-amber-400 hover:text-amber-600">
                <x-icon name="refresh" class="h-4 w-4" />
                Change printer
            </a>
        </div>
    </div>
</section>

<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">

    {{-- ===== Type filter chips ===== --}}
    @if ($buckets->count() > 1)
        <div class="mb-8 flex flex-wrap items-center gap-2">
            <a href="{{ route('printer-finder.show', $model['slug']) }}"
               @class([
                   'rounded-full px-4 py-2 text-sm font-bold transition',
                   'bg-slate-900 text-white'                                            => ! $activeType,
                   'bg-white text-slate-600 ring-1 ring-slate-200 hover:ring-amber-400' => (bool) $activeType,
               ])>
                All <span class="ml-1 opacity-60">{{ $buckets->sum() }}</span>
            </a>

            @foreach ($buckets as $bucket => $count)
                <a href="{{ route('printer-finder.show', ['model' => $model['slug'], 'type' => $bucket]) }}"
                   @class([
                       'rounded-full px-4 py-2 text-sm font-bold transition',
                       'bg-slate-900 text-white'                                            => $activeType === $bucket,
                       'bg-white text-slate-600 ring-1 ring-slate-200 hover:ring-amber-400' => $activeType !== $bucket,
                   ])>
                    {{ $bucket }} <span class="ml-1 opacity-60">{{ $count }}</span>
                </a>
            @endforeach
        </div>
    @endif

    {{-- ===== Products ===== --}}
    @if ($products->count())
        <div class="grid grid-cols-2 gap-5 md:grid-cols-3 lg:grid-cols-4">
            @foreach ($products as $product)
                <div data-reveal style="--reveal-delay:{{ ($loop->index % 4) * 60 }}ms">
                    <x-product-card :product="$product" />
                </div>
            @endforeach
        </div>
    @else
        <div class="rounded-xl border border-dashed border-slate-300 bg-white py-20 text-center">
            <x-icon name="box" class="mx-auto h-10 w-10 text-slate-300" />
            <p class="mt-4 font-semibold text-slate-700">Nothing here yet</p>
            <p class="mt-1 text-sm text-slate-500">We don't stock this category for the {{ $model['label'] }} right now.</p>
            <a href="{{ route('printer-finder.show', $model['slug']) }}" class="mt-5 inline-block rounded-full bg-slate-900 px-5 py-2 text-sm font-semibold text-white">See all {{ $model['label'] }} supplies</a>
        </div>
    @endif

    {{-- ===== Help ===== --}}
    <div class="mt-12 rounded-2xl bg-slate-900 px-6 py-8 sm:px-10">
        <div class="flex flex-col items-start justify-between gap-6 sm:flex-row sm:items-center">
            <div>
                <h3 class="text-xl font-black text-white">Can't see the part you need for your {{ $model['label'] }}?</h3>
                <p class="mt-1 text-slate-300">Tell us the part number — we source genuine, warranty-backed Zebra stock.</p>
            </div>
            @php $helpPhone = \App\Models\Setting::get('contact_phone', '+1-254-358-0017'); $helpToll = \App\Models\Setting::get('toll_free', '(945) 427-9519'); @endphp
            <div class="flex shrink-0 flex-wrap gap-3">
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $helpPhone) }}"
                   class="rounded-full bg-amber-500 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-amber-600">Call {{ $helpPhone }}</a>
                @if ($helpToll)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $helpToll) }}" class="rounded-full bg-white/10 px-5 py-2.5 text-sm font-bold text-white ring-1 ring-white/20 transition hover:bg-white/20">Toll-Free {{ $helpToll }}</a>@endif
                <a href="{{ route('part-request') }}"
                   class="rounded-full bg-white/10 px-5 py-2.5 text-sm font-bold text-white ring-1 ring-white/20 transition hover:bg-white/20">Request a part</a>
            </div>
        </div>
    </div>
</div>

@endsection
