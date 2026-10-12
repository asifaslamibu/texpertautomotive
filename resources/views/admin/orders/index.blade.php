@extends('admin.layouts.app')

@section('title', 'Orders')
@section('heading', 'Orders')
@section('subheading', 'Track and fulfill orders')

@section('content')

<div data-bulk class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur shadow-sm">
    <div class="flex flex-col gap-3 border-b border-white/10 p-4 sm:flex-row sm:items-center">
        <form method="GET" class="flex flex-1 flex-wrap gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Order # or customer…"
                   class="w-full max-w-xs rounded-lg border border-white/10 bg-slate-950/50 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            <select name="status" onchange="this.form.submit()" class="rounded-lg border border-white/10 bg-slate-950/50 px-3 py-2 text-sm text-slate-100 capitalize outline-none focus:border-slate-400">
                <option value="">All statuses</option>
                @foreach ($statuses as $s)
                    <option value="{{ $s }}" @selected(request('status')==$s)>{{ $s }}</option>
                @endforeach
            </select>
            <button class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-200">Filter</button>
        </form>
        <a href="{{ route('admin.orders.create') }}" class="shrink-0 rounded-lg bg-amber-500 px-4 py-2 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-amber-400 sm:ml-auto">+ Create Order</a>
    </div>

    <div class="px-4 pt-4"><x-bulk-bar :action="route('admin.orders.bulk-destroy')" /></div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-white/5 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                <tr>
                    <th class="px-4 py-3 w-8"><input type="checkbox" data-bulk-all class="rounded border-white/20 bg-transparent"></th>
                    <x-th-sort column="order_number" label="Order #" />
                    <th class="px-4 py-3">Customer</th>
                    <x-th-sort column="placed_at" label="Date" />
                    <th class="px-4 py-3">Payment</th>
                    <x-th-sort column="status" label="Status" />
                    <x-th-sort column="total" label="Total" align="right" />
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @forelse ($orders as $order)
                    <tr class="transition hover:bg-white/5">
                        <td class="px-4 py-3"><input type="checkbox" name="ids[]" value="{{ $order->id }}" data-bulk-item class="rounded border-white/20 bg-transparent"></td>
                        <td class="px-4 py-3"><a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-slate-200">{{ $order->order_number }}</a></td>
                        <td class="px-4 py-3 text-slate-200">{{ $order->customer->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-400">{{ $order->placed_at?->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-slate-400">
                            {{ $order->payment_method }}
                            @if ($order->payment_status === 'paid')
                                <span class="ml-1 rounded-full bg-emerald-500/15 px-2 py-0.5 text-[11px] font-bold text-emerald-300">Paid</span>
                            @else
                                <span class="ml-1 rounded-full bg-white/5 px-2 py-0.5 text-[11px] font-bold text-slate-400">Unpaid</span>
                            @endif
                        </td>
                        <td class="px-4 py-3"><span class="rounded-full bg-{{ $order->status_color }}-500/15 px-2.5 py-1 text-xs font-semibold capitalize text-{{ $order->status_color }}-300">{{ $order->status }}</span></td>
                        <td class="px-4 py-3 text-right font-bold text-white">${{ number_format($order->total, 2) }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.orders.show', $order) }}" class="text-xs font-semibold text-slate-400 hover:text-white">View →</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-12 text-center text-slate-400">No orders found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-white/10 p-4">{{ $orders->links('pagination.admin') }}</div>
</div>

@endsection
