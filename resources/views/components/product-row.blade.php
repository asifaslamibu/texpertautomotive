@props(['title', 'products', 'link' => null, 'eyebrow' => null])

@if ($products->count())
    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
        <div data-reveal class="mb-6 flex items-end justify-between gap-4">
            <div>
                @if ($eyebrow)<p class="text-xs font-bold uppercase tracking-widest text-amber-500">{{ $eyebrow }}</p>@endif
                <h2 class="mt-1 text-2xl font-black tracking-tight text-slate-900">{{ $title }}</h2>
            </div>
            <div class="flex items-center gap-2">
                @if ($link)
                    <a href="{{ $link }}" class="hidden items-center gap-1 rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-amber-400 hover:text-amber-600 sm:flex">
                        View all <x-icon name="arrow" class="h-4 w-4" />
                    </a>
                @endif
                <button type="button" data-slider-prev aria-label="Previous" class="grid h-10 w-10 place-items-center rounded-full border border-slate-300 bg-white text-slate-600 transition hover:border-amber-400 hover:text-amber-600 active:scale-95"><x-icon name="arrow" class="h-5 w-5 rotate-180" /></button>
                <button type="button" data-slider-next aria-label="Next" class="grid h-10 w-10 place-items-center rounded-full border border-slate-300 bg-white text-slate-600 transition hover:border-amber-400 hover:text-amber-600 active:scale-95"><x-icon name="arrow" class="h-5 w-5" /></button>
            </div>
        </div>

        <div data-reveal data-slider data-no-autoplay class="relative">
            <div data-slider-track class="no-scrollbar flex snap-x gap-5 overflow-x-auto scroll-smooth pb-2">
                @foreach ($products as $product)
                    <div data-slide class="w-48 shrink-0 snap-start sm:w-56">
                        <x-product-card :product="$product" />
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
