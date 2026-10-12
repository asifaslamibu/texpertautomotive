@extends('layouts.app')

@section('title', 'Track My Order — Texpert Deals')
@section('meta_description', 'Track your Texpert Deals order status using your order number and email.')

@section('content')

@include('pages._hero', [
    'eyebrow' => 'Order status',
    'title' => 'Track My Order',
    'subtitle' => 'Enter your order number and the email used at checkout to see your order status.',
])

<div class="mx-auto max-w-2xl px-4 py-12 sm:px-6">

    {{-- lookup form --}}
    <form method="GET" action="{{ route('track') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700">Order number</label>
                <input type="text" name="order" value="{{ request('order') }}" placeholder="e.g. TD-100245" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700">Email</label>
                <input type="email" name="email" value="{{ request('email') }}" placeholder="you@example.com" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
            </div>
        </div>
        <button type="submit" class="mt-5 w-full rounded-lg bg-slate-900 py-3 text-sm font-black uppercase tracking-wide text-white transition hover:bg-slate-800 sm:w-auto sm:px-10">
            Track Order
        </button>
    </form>

    {{-- result --}}
    @if ($searched)
        @if ($order)
            @php
                $steps = ['pending', 'processing', 'shipped', 'completed'];
                $currentIndex = array_search($order->status, $steps);
                $cancelled = $order->status === 'cancelled';
            @endphp
            <div class="mt-8 overflow-hidden rounded-2xl border border-slate-200 bg-white">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-6 py-4">
                    <div>
                        <p class="text-sm text-slate-500">Order</p>
                        <p class="text-lg font-black text-slate-900">{{ $order->order_number }}</p>
                    </div>
                    <span class="rounded-full bg-{{ $order->status_color }}-50 px-3 py-1 text-xs font-bold uppercase tracking-wide text-{{ $order->status_color }}-700">{{ ucfirst($order->status) }}</span>
                </div>

                <div class="px-6 py-5">
                    {{-- progress --}}
                    @unless ($cancelled)
                        <div class="flex items-center">
                            @foreach ($steps as $i => $step)
                                <div class="flex flex-col items-center {{ $i === 0 ? '' : 'flex-1' }}">
                                    @if ($i > 0)
                                        <div class="-mt-5 mb-2 h-1 w-full {{ $i <= $currentIndex ? 'bg-amber-400' : 'bg-slate-200' }}"></div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <div class="-mt-3 flex justify-between">
                            @foreach ($steps as $i => $step)
                                <div class="flex flex-col items-center text-center" style="width:25%">
                                    <span class="grid h-7 w-7 place-items-center rounded-full text-xs font-bold {{ $i <= $currentIndex ? 'bg-amber-400 text-slate-900' : 'bg-slate-200 text-slate-400' }}">{{ $i + 1 }}</span>
                                    <span class="mt-1.5 text-[11px] font-semibold uppercase tracking-wide {{ $i <= $currentIndex ? 'text-slate-700' : 'text-slate-400' }}">{{ $step }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="rounded-lg bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">This order has been cancelled. Please contact us if you have questions.</p>
                    @endunless

                    {{-- meta --}}
                    <dl class="mt-6 grid gap-3 border-t border-slate-100 pt-5 text-sm sm:grid-cols-2">
                        <div class="flex justify-between sm:block">
                            <dt class="text-slate-500">Placed on</dt>
                            <dd class="font-semibold text-slate-800">{{ optional($order->placed_at ?? $order->created_at)->format('M j, Y') }}</dd>
                        </div>
                        <div class="flex justify-between sm:block sm:text-right">
                            <dt class="text-slate-500">Order total</dt>
                            <dd class="font-semibold text-slate-800">${{ number_format($order->total, 2) }}</dd>
                        </div>
                    </dl>

                    {{-- items --}}
                    @if ($order->items->count())
                        <div class="mt-5 border-t border-slate-100 pt-5">
                            <p class="mb-2 text-sm font-bold text-slate-900">Items ({{ $order->items->sum('quantity') }})</p>
                            <ul class="space-y-2">
                                @foreach ($order->items as $item)
                                    <li class="flex items-center justify-between gap-3 text-sm">
                                        <span class="min-w-0 flex-1 truncate text-slate-700">{{ $item->name }} <span class="text-slate-400">× {{ $item->quantity }}</span></span>
                                        <span class="shrink-0 font-semibold text-slate-800">${{ number_format($item->line_total, 2) }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="mt-8 rounded-2xl border border-dashed border-slate-300 bg-white py-12 text-center">
                <x-icon name="search" class="mx-auto h-10 w-10 text-slate-300" />
                <p class="mt-4 font-semibold text-slate-700">No matching order found</p>
                <p class="mt-1 text-sm text-slate-500">Double-check your order number and email, or <a href="{{ route('contact.index') }}" class="font-semibold text-amber-600 hover:text-amber-700">contact us</a> for help.</p>
            </div>
        @endif
    @endif
</div>

@endsection
