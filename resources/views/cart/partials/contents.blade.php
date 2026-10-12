@php
    use App\Support\Cart;
    $items = Cart::items();
    $subtotal = Cart::subtotal();
    $shipping = Cart::shipping();
    $tax = Cart::tax();
    $total = Cart::total();
    $freeShipping = Cart::hasFreeShipping();
@endphp

@if ($items->isEmpty())
    <div class="flex flex-1 flex-col items-center justify-center px-6 py-16 text-center">
        <x-icon name="cart" class="h-12 w-12 text-slate-300" />
        <p class="mt-4 text-base font-semibold text-slate-700">Your cart is empty</p>
        <p class="mt-1 text-sm text-slate-500">Add some products to get started.</p>
        <a href="{{ route('products.index') }}" class="mt-6 rounded-lg bg-amber-400 px-6 py-2.5 text-sm font-bold text-slate-900 transition hover:bg-amber-500">Shop Products</a>
    </div>
@else
    {{-- scrollable item list --}}
    <div class="flex-1 overflow-y-auto">
        @foreach ($items as $item)
            @php $p = $item['product']; @endphp
            <div class="flex gap-3 border-b border-slate-100 p-4">
                <a href="{{ route('products.show', $p) }}" class="h-16 w-16 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                    <img src="{{ media($p->image) }}" alt="{{ $p->name }}" class="h-full w-full object-cover">
                </a>
                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-2">
                        <a href="{{ route('products.show', $p) }}" class="line-clamp-2 text-sm font-semibold leading-snug text-slate-800 hover:text-amber-600">{{ $p->name }}</a>
                        <form method="POST" action="{{ route('cart.remove', $p) }}" data-cart-remove>
                            @csrf @method('DELETE')
                            <button type="submit" class="-mt-1 shrink-0 text-slate-300 transition hover:text-rose-600" aria-label="Remove">
                                <span class="text-xl leading-none">&times;</span>
                            </button>
                        </form>
                    </div>
                    <div class="mt-2 flex items-center justify-between">
                        {{-- qty stepper (AJAX-updates on change) --}}
                        <form method="POST" action="{{ route('cart.update', $p) }}" class="flex items-center rounded-lg border border-slate-300" data-cart-update data-qty>
                            @csrf @method('PATCH')
                            <button type="button" data-qty-dec class="px-2.5 py-1.5 text-slate-500 hover:text-amber-600" aria-label="Decrease">−</button>
                            <input name="qty" value="{{ $item['qty'] }}" data-qty-input @if($max = \App\Support\Cart::availableFor($p)) max="{{ $max }}" @endif class="w-9 border-x border-slate-200 bg-transparent py-1 text-center text-sm font-semibold outline-none" aria-label="Quantity" inputmode="numeric">
                            <button type="button" data-qty-inc class="px-2.5 py-1.5 text-slate-500 hover:text-amber-600" aria-label="Increase">+</button>
                        </form>
                        <p class="text-sm font-bold text-slate-900">${{ number_format($item['line_total'], 2) }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- footer summary --}}
    <div class="shrink-0 border-t border-slate-200 bg-white p-4">
        <dl class="space-y-1.5 text-sm">
            <div class="flex justify-between text-slate-600"><dt>Subtotal</dt><dd>${{ number_format($subtotal, 2) }}</dd></div>
            <div class="flex justify-between text-slate-600"><dt>Shipping</dt>
                @if($freeShipping)
                    <dd class="font-semibold text-emerald-600">Free</dd>
                @else
                    <dd class="text-slate-400">Enter shipping address</dd>
                @endif
            </div>
            <div class="flex justify-between text-slate-600"><dt>Tax (7%)</dt><dd>${{ number_format($tax, 2) }}</dd></div>
            <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-black text-slate-900"><dt>Total</dt><dd>${{ number_format($subtotal + $tax, 2) }}</dd></div>
        </dl>
        <p class="mt-2 text-xs text-slate-400">{{ $freeShipping ? 'This order ships free.' : 'Shipping calculated at checkout.' }}</p>

        <a href="{{ route('checkout.index') }}" class="mt-4 block rounded-lg bg-amber-400 py-3 text-center text-sm font-black uppercase tracking-wide text-slate-900 transition hover:bg-amber-500">
            Checkout
        </a>
        <a href="{{ route('cart.index') }}" class="mt-2 block text-center text-sm font-semibold text-slate-500 hover:text-amber-600">View full cart</a>
    </div>
@endif
