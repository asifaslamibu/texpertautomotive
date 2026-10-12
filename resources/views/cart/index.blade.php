@extends('layouts.app')

@section('title', 'Your Cart — Texpert Deals')
@section('robots', 'noindex, follow')

@section('content')

<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
    <h1 class="text-2xl font-black tracking-tight text-slate-900">Your Cart</h1>

    @if (! empty($stockNotice))
        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-900">{{ $stockNotice }}</div>
    @endif

    @if ($items->isEmpty())
        <div class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white py-20 text-center">
            <x-icon name="cart" class="mx-auto h-12 w-12 text-slate-300" />
            <p class="mt-4 text-lg font-semibold text-slate-700">Your cart is empty</p>
            <p class="mt-1 text-sm text-slate-500">Browse our catalog and add some products.</p>
            <a href="{{ route('products.index') }}" class="mt-6 inline-block rounded-lg bg-amber-400 px-6 py-3 text-sm font-bold text-slate-900 transition hover:bg-amber-500">Shop Products</a>
        </div>
    @else
        <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_360px]">

            {{-- items --}}
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-3">
                    <p class="text-sm font-semibold text-slate-500">{{ $items->count() }} item(s)</p>
                    <form method="POST" action="{{ route('cart.clear') }}">
                        @csrf @method('DELETE')
                        <button class="text-sm font-semibold text-slate-400 hover:text-rose-600">Clear cart</button>
                    </form>
                </div>

                @foreach ($items as $item)
                    @php $p = $item['product']; @endphp
                    <div class="flex flex-wrap items-center gap-4 border-b border-slate-100 p-5 last:border-0">
                        <a href="{{ route('products.show', $p) }}" class="h-20 w-20 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="{{ media($p->image) }}" alt="{{ $p->name }}" class="h-full w-full object-cover">
                        </a>
                        <div class="min-w-0 flex-1 basis-40">
                            <p class="text-[11px] font-bold uppercase tracking-wide text-amber-600">{{ $p->brand }}</p>
                            <a href="{{ route('products.show', $p) }}" class="block text-sm font-semibold leading-snug text-slate-800 hover:text-amber-600 sm:text-base">{{ $p->name }}</a>
                            <p class="mt-0.5 text-sm text-slate-500">${{ number_format($p->price, 2) }} each</p>
                            @php $stock = \App\Support\Cart::availableFor($p); @endphp
                            @if ($p->shows_stock_count && $item['qty'] >= $stock)
                                <p class="mt-0.5 text-xs font-semibold text-amber-600">Only {{ $stock }} in stock — that's the most you can order.</p>
                            @endif
                        </div>

                        {{-- qty + line total + remove — drops to its own row on mobile --}}
                        <div class="flex w-full items-center justify-between gap-4 sm:w-auto sm:justify-end">
                            <form method="POST" action="{{ route('cart.update', $p) }}" class="flex items-center rounded-lg border border-slate-300" data-qty>
                                @csrf @method('PATCH')
                                <button type="button" data-qty-dec class="px-3 py-2 text-slate-500 hover:text-amber-600" aria-label="Decrease">−</button>
                                <input name="qty" value="{{ $item['qty'] }}" data-qty-input onchange="this.form.submit()" @if($max = \App\Support\Cart::availableFor($p)) max="{{ $max }}" @endif class="w-10 border-x border-slate-200 bg-transparent py-1.5 text-center text-sm font-semibold outline-none" aria-label="Quantity">
                                <button type="button" data-qty-inc class="px-3 py-2 text-slate-500 hover:text-amber-600" aria-label="Increase">+</button>
                            </form>

                            <p class="text-right font-bold text-slate-900 sm:w-24">${{ number_format($item['line_total'], 2) }}</p>

                            <form method="POST" action="{{ route('cart.remove', $p) }}">
                                @csrf @method('DELETE')
                                <button class="text-slate-300 transition hover:text-rose-600" aria-label="Remove">
                                    <span class="text-xl leading-none">&times;</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- summary --}}
            <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-6">
                <h2 class="font-bold text-slate-900">Order Summary</h2>

                @php
                    $coupon = \App\Support\Cart::coupon();
                    $discount = \App\Support\Cart::discount($subtotal);
                @endphp

                {{-- discount code --}}
                <div class="mt-4">
                    @if ($coupon)
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2.5">
                            <span class="flex min-w-0 items-center gap-2 text-sm">
                                <span class="truncate font-semibold text-emerald-800">{{ $coupon->code }}</span>
                                <span class="shrink-0 text-emerald-700">{{ $coupon->label() }}</span>
                            </span>
                            <form method="POST" action="{{ route('cart.coupon.remove') }}">
                                @csrf @method('DELETE')
                                <button class="text-xs font-semibold text-slate-500 transition hover:text-rose-600">Remove</button>
                            </form>
                        </div>
                    @else
                        <form method="POST" action="{{ route('cart.coupon.apply') }}" class="flex gap-2">
                            @csrf
                            <input name="code" value="{{ old('code') }}" maxlength="40" placeholder="Discount code" autocomplete="off"
                                   class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm uppercase placeholder:normal-case placeholder:text-slate-400 focus:border-amber-400 focus:ring-amber-400">
                            <button class="shrink-0 rounded-lg bg-slate-200 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-300">Apply</button>
                        </form>
                    @endif
                    @error('code')<p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>@enderror
                </div>

                <dl class="mt-4 space-y-2.5 text-sm">
                    <div class="flex justify-between text-slate-600"><dt>Subtotal</dt><dd>${{ number_format($subtotal, 2) }}</dd></div>
                    @if ($discount > 0)
                        <div class="flex justify-between text-emerald-700"><dt>Discount · {{ $coupon->code }}</dt><dd>−${{ number_format($discount, 2) }}</dd></div>
                    @endif
                    @php $freeShipping = ! \App\Support\Cart::shippingEnabled() || \App\Support\Cart::hasFreeShipping(); @endphp
                    <div class="flex justify-between text-slate-600">
                        <dt class="flex items-center gap-1">Shipping
                            @unless($freeShipping)
                                <span class="grid h-4 w-4 cursor-help place-items-center rounded-full border border-slate-300 text-[10px] font-bold text-slate-400" title="Calculated at checkout once you enter your delivery address">?</span>
                            @endunless
                        </dt>
                        @if($freeShipping)
                            <dd class="font-semibold text-emerald-600">Free</dd>
                        @else
                            <dd class="text-slate-400">Calculated at checkout</dd>
                        @endif
                    </div>
                    <div class="flex justify-between text-slate-600"><dt>Tax (7%)</dt><dd>${{ number_format($tax, 2) }}</dd></div>
                    <div class="flex justify-between border-t border-slate-200 pt-3 text-base font-black text-slate-900"><dt>Total</dt><dd>${{ number_format($subtotal - $discount + $tax, 2) }}</dd></div>
                </dl>
                <p class="mt-2 text-xs text-slate-400">{{ $freeShipping ? 'This order ships free.' : 'Shipping is calculated at checkout from your delivery address.' }}</p>

                <a href="{{ route('checkout.index') }}" class="mt-5 block rounded-lg bg-amber-400 py-3 text-center text-sm font-black uppercase tracking-wide text-slate-900 transition hover:bg-amber-500">
                    Proceed to Checkout
                </a>
                <a href="{{ route('products.index') }}" class="mt-3 block text-center text-sm font-semibold text-slate-500 hover:text-amber-600">← Continue shopping</a>
                <x-payment-icons secure class="mt-5 justify-center border-t border-slate-200 pt-4" />
            </aside>
        </div>
    @endif
</div>

@endsection
