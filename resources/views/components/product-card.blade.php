@props(['product'])

<div class="card-zoom group flex h-full flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition duration-300 hover:-translate-y-1.5 hover:border-amber-200 hover:shadow-xl hover:shadow-slate-200/70">
    <a href="{{ route('products.show', $product) }}" class="relative block aspect-square overflow-hidden bg-white p-3">
        <img src="{{ media($product->image) }}" alt="{{ $product->name }}" loading="lazy" decoding="async" class="h-full w-full object-contain">

        {{-- stock badge, top-left: green in stock → amber running low → red last one --}}
        @php
            $stock = $product->stock_badge;
            $stockClass = [
                'green' => 'bg-emerald-600 text-white',
                'amber' => 'bg-amber-500 text-white',
                'red' => 'bg-rose-600 text-white',
                'slate' => 'bg-slate-800 text-white',
            ][$stock['tone']];
        @endphp
        <span class="absolute left-3 top-3 rounded-md px-2.5 py-1 text-[11px] font-bold shadow-sm {{ $stockClass }}">{{ $stock['label'] }}</span>

        {{-- savings badge, top-right --}}
        @if ($product->discount_percent)
            <span class="absolute right-3 top-3 grid h-12 w-12 place-items-center rounded-full bg-rose-600 text-center text-[11px] font-bold leading-tight text-white shadow-md">
                Save<br>{{ $product->discount_percent }}%
            </span>
        @endif

        {{-- quick add — slides up on hover --}}
        <span class="absolute inset-x-3 bottom-3 translate-y-[140%] rounded-full bg-slate-900 py-2.5 text-center text-xs font-bold text-white opacity-0 shadow-lg transition-all duration-300 group-hover:translate-y-0 group-hover:opacity-100">
            View Product
        </span>
    </a>

    <div class="flex flex-1 flex-col p-4">
        <p class="text-[11px] font-bold uppercase tracking-wide text-amber-500">{{ $product->brand }}</p>
        <a href="{{ route('products.show', $product) }}" class="mt-1 line-clamp-2 text-base font-bold leading-snug text-slate-800 transition group-hover:text-amber-500">
            {{ $product->name }}
        </a>

        @if ($product->sku)
            <p class="mt-1 text-sm font-semibold text-slate-500">SKU: <span class="font-bold text-slate-700">{{ $product->sku }}</span></p>
        @endif

        @if ($product->badge)
            <x-brand-tag :tag="$product->badge" class="mt-2 self-start !text-[9px] !px-3 !py-1.5" />
        @endif

        <div class="mt-2 flex items-center gap-1">
            <x-icon name="star" class="h-3.5 w-3.5 fill-amber-400 text-amber-400" />
            <span class="text-xs font-semibold text-slate-600">{{ $product->rating }}</span>
            <span class="text-xs text-slate-400">({{ $product->reviews_count }})</span>
        </div>

        <div class="mt-auto flex items-end justify-between gap-2 pt-3">
            <div class="flex items-baseline gap-2">
                <span class="text-lg font-extrabold text-slate-900">${{ number_format($product->price, 2) }}</span>
                @if ($product->compare_at_price && $product->compare_at_price > $product->price)
                    <span class="text-sm text-slate-400 line-through">${{ number_format($product->compare_at_price, 2) }}</span>
                @endif
            </div>

            @if ($product->in_stock)
                <form method="POST" action="{{ route('cart.add', $product) }}" data-cart-add class="shrink-0">
                    @csrf
                    <button type="submit" class="grid h-9 w-9 place-items-center rounded-lg bg-amber-400 text-slate-900 shadow-sm transition hover:bg-amber-500 active:scale-95" aria-label="Add {{ $product->name }} to cart" title="Add to cart">
                        <x-icon name="cart" class="h-4 w-4" />
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
