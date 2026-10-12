@extends('layouts.app')

@section('title', 'Pay for Order '.$order->order_number.' · '.\App\Models\Setting::get('site_name', 'Texpert Deals'))
@section('robots', 'noindex, nofollow')

@section('content')

<div class="mx-auto max-w-2xl px-4 py-12 sm:px-6">
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        {{-- header --}}
        <div class="flex items-center justify-between gap-3 border-b border-slate-100 bg-slate-50 px-6 py-5">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-amber-500">Order {{ $order->order_number }}</p>
                <h1 class="mt-1 text-xl font-black text-slate-900">Complete your payment</h1>
            </div>
            @if ($order->payment_status === 'paid')
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">Paid</span>
            @else
                <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">Payment due</span>
            @endif
        </div>

        <div class="p-6">
            @if (session('status'))
                <div class="mb-5 rounded-lg bg-slate-900 px-4 py-3 text-sm font-semibold text-white">{{ session('status') }}</div>
            @endif

            {{-- items --}}
            <ul class="divide-y divide-slate-100">
                @foreach ($order->items as $item)
                    <li class="flex items-start justify-between gap-4 py-3">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-800">{{ $item->name }}</p>
                            <p class="text-sm text-slate-500">{{ $item->quantity }} × ${{ number_format((float) $item->price, 2) }}</p>
                        </div>
                        <span class="shrink-0 font-bold text-slate-900">${{ number_format((float) $item->line_total, 2) }}</span>
                    </li>
                @endforeach
            </ul>

            {{-- totals --}}
            <div class="mt-4 space-y-2 border-t border-slate-200 pt-4 text-sm">
                <div class="flex justify-between text-slate-600"><span>Subtotal</span><span>${{ number_format((float) $order->subtotal, 2) }}</span></div>
                @if ((float) ($order->discount ?? 0) > 0)
                    <div class="flex justify-between font-semibold text-emerald-600"><span>Discount ({{ rtrim(rtrim(number_format((float) $order->discount_percent, 2), '0'), '.') }}%)</span><span>−${{ number_format((float) $order->discount, 2) }}</span></div>
                @endif
                @if ((float) $order->shipping > 0)
                    <div class="flex justify-between text-slate-600"><span>Shipping</span><span>${{ number_format((float) $order->shipping, 2) }}</span></div>
                @endif
                @if ((float) $order->tax > 0)
                    <div class="flex justify-between text-slate-600"><span>Tax</span><span>${{ number_format((float) $order->tax, 2) }}</span></div>
                @endif
                <div class="flex justify-between border-t border-slate-200 pt-2 text-lg font-black text-slate-900"><span>Total</span><span>${{ number_format((float) $order->total, 2) }}</span></div>
            </div>

            {{-- action --}}
            <div class="mt-6">
                @if ($order->payment_status === 'paid')
                    <div class="rounded-xl bg-emerald-50 p-5 text-center">
                        <x-icon name="check" class="mx-auto h-8 w-8 text-emerald-600" />
                        <p class="mt-2 font-bold text-slate-800">This order is already paid.</p>
                        <p class="text-sm text-slate-500">Thank you! A receipt has been sent to your email.</p>
                    </div>
                @elseif ($stripeReady)
                    <form method="POST" action="{{ route('pay.start', $order->pay_token) }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg bg-slate-900 px-6 py-4 text-sm font-black uppercase tracking-wide text-white shadow-sm transition hover:bg-slate-800 active:scale-[.99]">
                            <x-icon name="lock" class="h-5 w-5" /> Pay ${{ number_format((float) $order->total, 2) }} securely
                        </button>
                    </form>
                    <x-payment-icons secure class="mt-4 flex-wrap justify-center" />
                    <p class="mt-3 text-center text-xs text-slate-400">You'll be redirected to our secure Stripe checkout.</p>
                @else
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-5 text-center text-sm text-slate-600">
                        Online payment is currently unavailable. Please contact us at
                        <a href="mailto:{{ \App\Models\Setting::get('contact_email', 'sales@texpertdeals.us') }}" class="font-semibold text-amber-600">{{ \App\Models\Setting::get('contact_email', 'sales@texpertdeals.us') }}</a> to complete your order.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <p class="mt-4 text-center text-xs text-slate-400">Questions about this order? Contact {{ \App\Models\Setting::get('site_name', 'Texpert Deals') }}.</p>
</div>

@endsection
