@extends('layouts.app')

@section('title', 'Find Ribbons & Labels by Printer Model — Texpert Deals')
@section('meta_description', 'Select your Zebra printer model and instantly see every compatible ribbon, label, wristband, battery and spare part we stock.')

@section('content')

{{-- ===== Hero ===== --}}
<section class="border-b border-slate-200 bg-slate-900">
    <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6">
        <nav class="flex items-center gap-2 text-sm text-slate-400">
            <a href="{{ route('home') }}" class="hover:text-amber-400">Home</a>
            <span>/</span>
            <span class="font-semibold text-slate-200">Printer Supplies Finder</span>
        </nav>

        <div class="mt-4 max-w-3xl">
            <span class="inline-flex items-center gap-2 rounded-full bg-amber-500/10 px-3 py-1 text-xs font-bold uppercase tracking-wide text-amber-400 ring-1 ring-amber-500/30">
                <x-icon name="search" class="h-3.5 w-3.5" />
                Compatibility tool
            </span>
            <h1 class="mt-4 text-4xl font-black tracking-tight text-white sm:text-5xl">
                Find supplies for <span class="text-amber-500">your printer</span>
            </h1>
            <p class="mt-4 text-lg leading-relaxed text-slate-300">
                Pick your printer model and we'll show every ribbon, label, wristband, battery and
                spare part that fits it — no part-number guesswork, no wrong orders.
            </p>
        </div>

        {{-- live filter over the model list below --}}
        <div class="mt-8 max-w-xl">
            <label class="relative block">
                <span class="sr-only">Search printer models</span>
                <span class="pointer-events-none absolute inset-y-0 left-0 grid w-12 place-items-center">
                    <x-icon name="search" class="h-5 w-5 text-slate-400" />
                </span>
                <input type="text" data-printer-search autocomplete="off"
                       placeholder="Type your model — e.g. ZT411, ZD621, QLn420…"
                       class="w-full rounded-full border-0 bg-white py-4 pl-12 pr-5 text-base font-medium text-slate-900 shadow-lg outline-none ring-2 ring-transparent placeholder:text-slate-400 focus:ring-amber-400">
            </label>
            <p class="mt-3 text-sm text-slate-400">
                <span class="font-semibold text-slate-200">{{ $modelCount }}</span> printer models supported
            </p>
        </div>
    </div>
</section>

{{-- ===== Model picker ===== --}}
<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">

    {{-- shown by JS when the search matches nothing --}}
    <div data-printer-empty hidden class="rounded-xl border border-dashed border-slate-300 bg-white py-16 text-center">
        <x-icon name="search" class="mx-auto h-10 w-10 text-slate-300" />
        <p class="mt-4 font-semibold text-slate-700">No printer matches that model</p>
        <p class="mt-1 text-sm text-slate-500">Can't find yours? Our team will track down the right supplies for you.</p>
        <a href="{{ route('part-request') }}" class="mt-5 inline-block rounded-full bg-slate-900 px-5 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">Request a part</a>
    </div>

    @foreach ($families as $family => $models)
        <section data-family class="mb-12">
            <div class="mb-5 flex items-baseline justify-between border-b border-slate-200 pb-3">
                <h2 class="text-xl font-black tracking-tight text-slate-900">{{ $family }}</h2>
                <span data-family-count class="text-sm text-slate-500">{{ $models->count() }} models</span>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                @foreach ($models as $model)
                    <a href="{{ route('printer-finder.show', $model['slug']) }}"
                       data-printer-item data-name="{{ Str::lower($model['label']) }}"
                       class="group flex items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 transition hover:-translate-y-0.5 hover:border-amber-400 hover:shadow-md">
                        <span class="truncate text-sm font-bold text-slate-800 group-hover:text-amber-600">{{ $model['label'] }}</span>
                        <span class="shrink-0 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-500 group-hover:bg-amber-50 group-hover:text-amber-700">{{ $model['count'] }}</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endforeach

    {{-- help card --}}
    <div class="rounded-2xl bg-slate-900 px-6 py-8 sm:px-10">
        <div class="flex flex-col items-start justify-between gap-6 sm:flex-row sm:items-center">
            <div>
                <h3 class="text-xl font-black text-white">Not sure which supply you need?</h3>
                <p class="mt-1 text-slate-300">Send us your printer model and we'll confirm the exact part before you buy.</p>
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

@push('scripts')
<script>
    // Filter the model cards as you type; hide any family section left with no matches.
    (function () {
        const input = document.querySelector('[data-printer-search]');
        if (!input) return;

        const items    = [...document.querySelectorAll('[data-printer-item]')];
        const families = [...document.querySelectorAll('[data-family]')];
        const empty    = document.querySelector('[data-printer-empty]');

        input.addEventListener('input', () => {
            const q = input.value.trim().toLowerCase();
            let shown = 0;

            items.forEach((el) => {
                const hit = !q || el.dataset.name.includes(q);
                el.hidden = !hit;
                if (hit) shown++;
            });

            families.forEach((section) => {
                const visible = [...section.querySelectorAll('[data-printer-item]')].filter((el) => !el.hidden).length;
                section.hidden = visible === 0;
                const counter = section.querySelector('[data-family-count]');
                if (counter) counter.textContent = visible + ' model' + (visible === 1 ? '' : 's');
            });

            if (empty) empty.hidden = shown > 0;
        });
    })();
</script>
@endpush

@endsection
