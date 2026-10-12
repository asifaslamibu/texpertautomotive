@extends('admin.layouts.app')

@section('title', $customer->name)
@section('heading', $customer->name)
@section('subheading', 'Customer profile')

@section('content')

<div class="mb-5">
    <a href="{{ route('admin.customers.index') }}" class="text-sm font-semibold text-slate-400 hover:text-white">← Back to customers</a>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    {{-- profile --}}
    <div class="space-y-6">
        <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 text-center shadow-sm">
            <span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-gradient-to-br from-slate-800 to-slate-950 text-2xl font-bold text-white">{{ substr($customer->name, 0, 1) }}</span>
            <h2 class="mt-4 text-lg font-bold text-white">{{ $customer->name }}</h2>
            @if ($customer->company)<p class="text-sm text-slate-400">{{ $customer->company }}</p>@endif
            <div class="mt-4 grid grid-cols-2 gap-3 border-t border-white/10 pt-4 text-center">
                <div><p class="text-xl font-black text-white">{{ $customer->orders->count() }}</p><p class="text-xs text-slate-400">Orders</p></div>
                <div><p class="text-xl font-black text-emerald-300">${{ number_format($customer->total_spent, 0) }}</p><p class="text-xs text-slate-400">Spent</p></div>
            </div>
        </div>
        <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
            <h3 class="mb-3 font-bold text-white">Contact</h3>
            <div class="space-y-1.5 text-sm text-slate-300">
                <p>{{ $customer->email }}</p>
                <p>{{ $customer->phone }}</p>
                <p class="pt-2 text-slate-400">{{ $customer->address }}<br>{{ $customer->city }}, {{ $customer->state }} {{ $customer->zip }}</p>
            </div>
        </div>
    </div>

    {{-- orders --}}
    <div class="lg:col-span-2">
        <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur shadow-sm">
            <h2 class="border-b border-white/10 p-5 font-bold text-white">Order History</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-white/5 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                        <tr><th class="px-5 py-3">Order #</th><th class="px-5 py-3">Date</th><th class="px-5 py-3">Status</th><th class="px-5 py-3 text-right">Total</th></tr>
                    </thead>
                    <tbody class="divide-y divide-white/10">
                        @forelse ($customer->orders as $order)
                            <tr class="transition hover:bg-white/5">
                                <td class="px-5 py-3"><a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-slate-200">{{ $order->order_number }}</a></td>
                                <td class="px-5 py-3 text-slate-400">{{ $order->placed_at?->format('M d, Y') }}</td>
                                <td class="px-5 py-3"><span class="rounded-full bg-{{ $order->status_color }}-500/15 px-2.5 py-1 text-xs font-semibold capitalize text-{{ $order->status_color }}-300">{{ $order->status }}</span></td>
                                <td class="px-5 py-3 text-right font-bold text-white">${{ number_format($order->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-10 text-center text-slate-400">No orders yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
