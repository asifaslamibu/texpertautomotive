@extends('layouts.app')

@section('title', 'Order Confirmed — Texpert Deals')
@section('robots', 'noindex, follow')

@section('content')

<div class="mx-auto max-w-3xl px-4 py-14 sm:px-6">
    <div class="text-center">
        <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-emerald-100 text-emerald-600">
            <x-icon name="check" class="h-9 w-9" />
        </span>
        <h1 class="mt-5 text-3xl font-black tracking-tight text-slate-900">Thank you for your order!</h1>
        <p class="mt-2 text-slate-500">Your order <span class="font-bold text-slate-800">{{ $order->order_number }}</span> has been placed and is now <span class="font-semibold capitalize text-amber-600">{{ $order->status }}</span>.</p>
        <p class="text-sm text-slate-400">A confirmation was sent to {{ $order->customer->email }}</p>
    </div>

    @if ($order->shipping_quote_pending)
        <div class="mt-8 rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900">
            <p class="font-bold">Next step: your shipping quote</p>
            <p class="mt-1 text-amber-800">
                Some items in this order are too large or heavy for standard parcel rates, so nothing has been charged yet.
                Our team will email <b>{{ $order->customer->email }}</b> the exact shipping cost with a secure payment link — usually within one business day.
                Your order ships as soon as it is paid.
            </p>
        </div>
    @endif

    <div class="mt-10 overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-6 py-4">
            <h2 class="font-bold text-slate-900">Order summary</h2>
        </div>
        <div class="divide-y divide-slate-100">
            @foreach ($order->items as $item)
                <div class="flex items-center gap-4 px-6 py-4">
                    <span class="h-14 w-14 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                        <img src="{{ media($item->product->image ?? '') ?: 'https://placehold.co/80' }}" alt="" class="h-full w-full object-cover">
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-semibold text-slate-800">{{ $item->name }}</p>
                        <p class="text-sm text-slate-400">${{ number_format($item->price, 2) }} × {{ $item->quantity }}</p>
                    </div>
                    <p class="font-bold text-slate-900">${{ number_format($item->line_total, 2) }}</p>
                </div>
            @endforeach
        </div>
        <dl class="space-y-2 border-t border-slate-200 bg-slate-50 px-6 py-5 text-sm">
            <div class="flex justify-between text-slate-600"><dt>Subtotal</dt><dd>${{ number_format($order->subtotal, 2) }}</dd></div>
            <div class="flex justify-between text-slate-600"><dt>Shipping</dt><dd>{{ $order->shipping > 0 ? '$'.number_format($order->shipping, 2) : ($order->shipping_quote_pending ? 'To be quoted' : 'Free') }}</dd></div>
            @if ((float) ($order->discount ?? 0) > 0)
                <div class="flex justify-between text-emerald-600"><dt>Discount ({{ rtrim(rtrim(number_format((float) $order->discount_percent, 2), '0'), '.') }}%)</dt><dd>−${{ number_format((float) $order->discount, 2) }}</dd></div>
            @endif
            <div class="flex justify-between text-slate-600"><dt>Tax</dt><dd>${{ number_format($order->tax, 2) }}</dd></div>
            <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-black text-slate-900"><dt>Total</dt><dd>${{ number_format($order->total, 2) }}</dd></div>
        </dl>
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 text-sm">
            <h3 class="font-bold text-slate-900">Shipping to</h3>
            <p class="mt-2 text-slate-600">{{ $order->customer->name }}<br>
                {{ $order->customer->address }}<br>
                {{ $order->customer->city }}, {{ $order->customer->state }} {{ $order->customer->zip }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 text-sm">
            <h3 class="font-bold text-slate-900">Payment</h3>
            <p class="mt-2 flex items-center gap-2 text-slate-600">
                Status:
                @if ($order->payment_status === 'paid')
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-bold text-emerald-700"><x-icon name="check" class="h-3.5 w-3.5" /> Paid</span>
                @else
                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-700">{{ $order->payment_method === 'Net 30' ? 'Net 30 terms' : 'Awaiting payment' }}</span>
                @endif
            </p>
            <p class="mt-2 text-slate-600">Method: {{ $order->payment_method }}<br>
                Placed: {{ $order->placed_at?->format('M d, Y g:i A') }}
                @if ($order->paid_at)<br>Paid: {{ $order->paid_at->format('M d, Y g:i A') }}@endif
            </p>
        </div>
    </div>

    <div class="mt-8 text-center">
        <a href="{{ route('products.index') }}" class="inline-block rounded-lg bg-amber-400 px-8 py-3 text-sm font-black uppercase tracking-wide text-slate-900 transition hover:bg-amber-500">Continue Shopping</a>
    </div>
</div>

@if ($trackPurchase ?? false)
    {{-- Rendered only once per order (orders.analytics_tracked_at) — a refresh won't re-fire it. --}}
    @push('scripts')
    <script>
        window.trackEcom && window.trackEcom.purchase(@json(\App\Support\Ecom::purchase($order)));
    </script>
    @endpush
@endif

@endsection
