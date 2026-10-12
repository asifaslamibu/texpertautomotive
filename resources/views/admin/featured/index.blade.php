@extends('admin.layouts.app')

@section('title', 'Featured Slider')
@section('heading', 'Featured Slider')
@section('subheading', 'Pick the products shown in the homepage "Featured Deals" slider and set their order')

@section('content')

<div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">

    {{-- ===== current featured products (ordered) ===== --}}
    <div>
        <div class="mb-3 flex items-center justify-between">
            <h2 class="font-bold text-white">In the slider <span class="text-sm font-normal text-slate-400">({{ $featured->count() }})</span></h2>
            <a href="{{ route('home') }}" target="_blank" class="text-sm font-semibold text-slate-200 hover:text-white">View on storefront →</a>
        </div>

        <div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur shadow-sm">
            <ul class="divide-y divide-white/10">
                @forelse ($featured as $i => $product)
                    <li class="flex items-center gap-3 p-3">
                        <div class="flex flex-col">
                            <form method="POST" action="{{ route('admin.featured.move', [$product, 'up']) }}">
                                @csrf @method('PATCH')
                                <button @disabled($i === 0) class="grid h-6 w-6 place-items-center rounded text-slate-400 hover:bg-white/5 hover:text-slate-200 disabled:opacity-30" aria-label="Move up"><x-icon name="chevron" class="h-4 w-4 rotate-180" /></button>
                            </form>
                            <form method="POST" action="{{ route('admin.featured.move', [$product, 'down']) }}">
                                @csrf @method('PATCH')
                                <button @disabled($loop->last) class="grid h-6 w-6 place-items-center rounded text-slate-400 hover:bg-white/5 hover:text-slate-200 disabled:opacity-30" aria-label="Move down"><x-icon name="chevron" class="h-4 w-4" /></button>
                            </form>
                        </div>

                        <span class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-lg border border-white/10 bg-white/5">
                            @if ($product->image)
                                <img src="{{ media($product->image) }}" alt="" class="max-h-full max-w-full object-contain">
                            @else
                                <x-icon name="box" class="h-5 w-5 text-slate-300" />
                            @endif
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-slate-100">{{ $product->name }}</p>
                            <p class="text-xs text-slate-400">{{ $product->brand ?: $product->category?->name }} · ${{ number_format((float) $product->price, 2) }}</p>
                        </div>

                        <span class="hidden rounded-full bg-white/5 px-2 py-0.5 text-[11px] font-bold text-slate-400 sm:inline">#{{ $i + 1 }}</span>

                        <form method="POST" action="{{ route('admin.featured.remove', $product) }}" onsubmit="return confirm('Remove from the featured slider?')">
                            @csrf @method('DELETE')
                            <button class="rounded-lg border border-white/10 px-2.5 py-1.5 text-xs font-semibold text-rose-300 hover:border-rose-400/30 hover:bg-rose-500/15" aria-label="Remove">&times;</button>
                        </form>
                    </li>
                @empty
                    <li class="p-10 text-center text-sm text-slate-400">No featured products yet. Search on the right to add some.</li>
                @endforelse
            </ul>
        </div>
        <p class="mt-2 text-xs text-slate-400">Top item shows first. The slider displays up to 50 products.</p>
    </div>

    {{-- ===== add products ===== --}}
    <div>
        <h2 class="mb-3 font-bold text-white">Add products</h2>
        <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-4 shadow-sm">
            <form method="GET" class="relative">
                <input type="search" name="q" value="{{ $search }}" autofocus placeholder="Search by name, SKU or brand…"
                       class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 py-2.5 pl-9 pr-3 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                <span class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400"><x-icon name="search" class="h-4 w-4" /></span>
            </form>

            @if ($results !== null)
                <div class="mt-4 space-y-2">
                    @forelse ($results as $product)
                        <div class="flex items-center gap-3 rounded-xl border border-white/10 p-2">
                            <span class="grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-lg border border-white/10 bg-white/5">
                                @if ($product->image)
                                    <img src="{{ media($product->image) }}" alt="" class="max-h-full max-w-full object-contain">
                                @else
                                    <x-icon name="box" class="h-4 w-4 text-slate-300" />
                                @endif
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-slate-100">{{ $product->name }}</p>
                                <p class="text-xs text-slate-400">{{ $product->brand ?: $product->sku }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.featured.add', $product) }}">
                                @csrf
                                <button class="shrink-0 rounded-lg bg-white px-3 py-1.5 text-xs font-bold text-slate-900 hover:bg-slate-200">+ Add</button>
                            </form>
                        </div>
                    @empty
                        <p class="py-6 text-center text-sm text-slate-400">No matching products (already-featured items are hidden).</p>
                    @endforelse
                </div>
            @else
                <p class="mt-4 text-sm text-slate-400">Type a product name, SKU or brand above to find products to feature.</p>
            @endif
        </div>
    </div>
</div>

@endsection
