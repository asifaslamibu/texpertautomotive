@extends('layouts.app')

@section('title', 'Order '.$order->order_number.' — Texpert Deals')
@section('robots', 'noindex, follow')

@section('content')
<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
    <a href="{{ route('account.index') }}" class="text-sm font-semibold text-slate-500 hover:text-amber-600">← Back to my account</a>

    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-black tracking-tight text-slate-900">Order {{ $order->order_number }}</h1>
        <span class="rounded-full bg-{{ $order->status_color }}-50 px-3 py-1 text-xs font-semibold capitalize text-{{ $order->status_color }}-700">{{ $order->status }}</span>
    </div>
    <p class="mt-1 text-sm text-slate-500">Placed {{ $order->placed_at?->format('M d, Y g:i A') }}
        @if ($order->payment_status === 'paid')<span class="ml-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-700">Paid</span>@endif
    </p>

    {{-- shipment tracking --}}
    @if ($order->tracking_number)
        @php $track = \App\Support\Ups::track($order->tracking_number); @endphp
        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h3 class="font-bold text-slate-900">Shipment tracking</h3>
                    <p class="text-sm text-slate-600">{{ $order->shipping_service ?: 'UPS' }} · <span class="font-mono">{{ $order->tracking_number }}</span></p>
                </div>
                <a href="https://www.ups.com/track?tracknum={{ $order->tracking_number }}" target="_blank" rel="noopener" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white transition hover:bg-slate-800">Track on UPS →</a>
            </div>
            @if ($track)
                <p class="mt-3 inline-block rounded-full px-3 py-1 text-sm font-bold {{ $track['delivered'] ? 'bg-emerald-100 text-emerald-700' : 'bg-sky-100 text-sky-700' }}">{{ $track['status'] }}</p>
                @if (! empty($track['activity']))
                    <ul class="mt-3 space-y-1.5 text-sm text-slate-500">
                        @foreach (array_slice($track['activity'], 0, 4) as $a)
                            <li class="flex gap-2"><span class="text-slate-300">•</span> {{ $a['status'] }}@if($a['location']) — {{ $a['location'] }}@endif</li>
                        @endforeach
                    </ul>
                @endif
            @endif
        </div>
    @endif

    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white">
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

    <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 text-sm">
        <h3 class="font-bold text-slate-900">Shipping to</h3>
        <p class="mt-2 text-slate-600">{{ $order->customer->name }}<br>
            {{ $order->customer->address }}<br>
            {{ $order->customer->city }}, {{ $order->customer->state }} {{ $order->customer->zip }}</p>
    </div>
</div>
@endsection
