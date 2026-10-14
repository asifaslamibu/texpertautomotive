@props(['product'])

{{-- Storefront product card (shop grid, related products) — same look as the homepage
     best sellers. The list view in the shop grid relies on the image link being the
     first child and the details block the second (see app.css `.is-list`). --}}
@php
    $onSale = $product->compare_at_price && $product->compare_at_price > $product->price;
    $stars = (float) $product->rating;
@endphp
<div class="card-zoom group flex h-full flex-col overflow-hidden rounded-md border border-slate-200 bg-white transition duration-300 hover:border-amber-500 hover:shadow-lg">
    <a href="{{ route('products.show', $product) }}" class="relative block aspect-square overflow-hidden bg-white p-3">
        <img src="{{ media($product->image) }}" alt="{{ $product->name }}" loading="lazy" decoding="async" class="h-full w-full object-contain">

        @if ($onSale)
            <span class="absolute left-2 top-2 rounded bg-red-600 px-2 py-0.5 text-[10px] font-bold uppercase text-white">Save {{ $product->discount_percent }}%</span>
        @elseif (! $product->in_stock)
            <span class="absolute left-2 top-2 rounded bg-slate-800 px-2 py-0.5 text-[10px] font-bold uppercase text-white">Call for price</span>
        @endif
        <span class="absolute right-2.5 top-2.5 text-blue-600" aria-hidden="true"><x-icon name="heart" class="h-4 w-4" /></span>
    </a>

    <div class="flex flex-1 flex-col px-3 pb-3">
        @if ($product->brand)
            <p class="text-[10px] font-bold uppercase tracking-wide text-amber-600">{{ $product->brand }}</p>
        @endif
        <a href="{{ route('products.show', $product) }}" class="mt-0.5 line-clamp-2 min-h-9 text-[12.5px] font-semibold leading-[18px] text-[#111] transition hover:text-amber-600">
            {{ $product->name }}
        </a>

        <div class="mt-1.5 flex flex-wrap items-baseline gap-x-2">
            <span class="text-lg font-extrabold text-[#111]">${{ number_format($product->price, 2) }}</span>
            @if ($onSale)
                <span class="text-xs text-slate-400 line-through">${{ number_format($product->compare_at_price, 2) }}</span>
            @endif
        </div>

        <div class="flex items-center gap-1">
            <span class="flex text-amber-500">
                @for ($s = 1; $s <= 5; $s++)
                    <x-icon name="star" solid class="h-3.5 w-3.5 {{ $s <= round($stars) ? '' : 'opacity-25' }}" />
                @endfor
            </span>
            <span class="text-[11px] text-slate-500">({{ $product->reviews_count }})</span>
        </div>

        <div class="mt-auto pt-3">
            @if ($product->in_stock)
                <form method="POST" action="{{ route('cart.add', $product) }}" data-cart-add>
                    @csrf
                    <button type="submit" class="w-full rounded bg-amber-500 py-2 text-xs font-bold text-white transition hover:bg-amber-600 active:scale-[.99]" aria-label="Add {{ $product->name }} to cart">Add to Cart</button>
                </form>
            @else
                <a href="{{ route('products.show', $product) }}" class="block rounded bg-[#111] py-2 text-center text-xs font-bold text-white transition hover:bg-black">View Product</a>
            @endif
        </div>
    </div>
</div>
