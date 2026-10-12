{{-- Order summary body: the lines a buyer can still change, plus the totals.
     Re-rendered on its own whenever a quantity changes at checkout. --}}
<div class="space-y-4">
    @foreach ($items as $item)
        @php
            $p = $item['product'];
            $max = \App\Support\Cart::availableFor($p);
        @endphp
        <div class="flex items-center gap-4">
            <span class="relative h-16 w-16 shrink-0">
                <span class="block h-full w-full overflow-hidden rounded-lg border border-slate-200 bg-white">
                    <img src="{{ media($p->image) }}" alt="" class="h-full w-full object-contain p-1">
                </span>
                <span class="absolute -right-2 -top-2 grid h-6 w-6 place-items-center rounded-full bg-slate-800 text-xs font-bold text-white ring-2 ring-slate-50">{{ $item['qty'] }}</span>
            </span>

            <span class="min-w-0 flex-1">
                <span class="block text-sm font-semibold leading-snug text-slate-800">{{ \Illuminate\Support\Str::limit($p->name, 70) }}</span>
                @if ($p->sku)
                    <span class="block text-xs text-slate-500">SKU: {{ $p->sku }}</span>
                @endif

                {{-- quantity --}}
                <span class="mt-1.5 flex items-center gap-2">
                    <span class="inline-flex items-center rounded-md border border-slate-300 bg-white" data-co-qty data-product="{{ $p->id }}">
                        <button type="button" data-co-qty-dec aria-label="Decrease quantity"
                                class="px-2.5 py-1 text-slate-500 transition hover:text-amber-600">−</button>
                        <input type="text" inputmode="numeric" value="{{ $item['qty'] }}" data-co-qty-input aria-label="Quantity"
                               @if($max) max="{{ $max }}" @endif
                               class="w-9 border-x border-slate-200 bg-transparent py-1 text-center text-sm font-semibold outline-none">
                        <button type="button" data-co-qty-inc aria-label="Increase quantity"
                                class="px-2.5 py-1 text-slate-500 transition hover:text-amber-600">+</button>
                    </span>
                    <button type="button" data-co-qty-remove data-product="{{ $p->id }}"
                            class="text-xs font-medium text-slate-400 transition hover:text-rose-600">Remove</button>
                </span>
            </span>

            <span class="text-sm font-semibold text-slate-900">${{ number_format($item['line_total'], 2) }}</span>
        </div>
    @endforeach
</div>

@php
    $coupon = \App\Support\Cart::coupon();
    $discount = \App\Support\Cart::discount($subtotal);
@endphp

{{-- discount code --}}
<div class="mt-6 border-t border-slate-200 pt-5" data-coupon-box>
    @if ($coupon)
        <div class="flex items-center justify-between gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2.5">
            <span class="flex min-w-0 items-center gap-2 text-sm">
                <x-icon name="check" class="h-4 w-4 shrink-0 text-emerald-600" />
                <span class="truncate font-semibold text-emerald-800">{{ $coupon->code }}</span>
                <span class="shrink-0 text-emerald-700">{{ $coupon->label() }}</span>
            </span>
            <button type="button" data-coupon-remove class="shrink-0 text-xs font-semibold text-slate-500 transition hover:text-rose-600">Remove</button>
        </div>
    @else
        <div class="flex gap-2">
            <input type="text" data-coupon-input placeholder="Discount code" autocomplete="off" maxlength="40"
                   class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2.5 text-sm uppercase placeholder:normal-case placeholder:text-slate-400 focus:border-amber-400 focus:ring-amber-400">
            <button type="button" data-coupon-apply
                    class="shrink-0 rounded-lg bg-slate-200 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-300">Apply</button>
        </div>
    @endif
    <p data-coupon-message class="mt-2 hidden text-sm"></p>
</div>

<dl class="mt-5 space-y-3 border-t border-slate-200 pt-5 text-sm"
    data-subtotal="{{ $subtotal }}" data-tax="{{ $tax }}" data-shipping="0" data-discount="{{ $discount }}">
    <div class="flex justify-between text-slate-600">
        <dt>Subtotal · {{ $items->sum('qty') }} {{ Str::plural('item', $items->sum('qty')) }}</dt>
        <dd class="font-medium text-slate-900">${{ number_format($subtotal, 2) }}</dd>
    </div>
    @if ($discount > 0)
        <div class="flex justify-between text-emerald-700">
            <dt>Discount{{ $coupon ? ' · '.$coupon->code : '' }}</dt>
            <dd class="font-medium">−${{ number_format($discount, 2) }}</dd>
        </div>
    @endif
    <div class="flex justify-between text-slate-600">
        <dt>Shipping</dt>
        @if($freeShippingCart)
            <dd data-summary-shipping class="font-semibold text-emerald-600">Free</dd>
        @else
            <dd data-summary-shipping class="text-slate-500">Enter shipping address</dd>
        @endif
    </div>
    <div class="flex justify-between text-slate-600">
        <dt>Tax (7%)</dt>
        <dd class="font-medium text-slate-900">${{ number_format($tax, 2) }}</dd>
    </div>
    <div class="flex items-baseline justify-between border-t border-slate-200 pt-4 text-slate-900">
        <dt class="text-base font-bold">Total</dt>
        <dd class="flex items-baseline gap-2">
            <span class="text-xs font-medium text-slate-500">USD</span>
            <span data-summary-total class="text-2xl font-black">${{ number_format($subtotal - $discount + $tax, 2) }}</span>
        </dd>
    </div>
</dl>
