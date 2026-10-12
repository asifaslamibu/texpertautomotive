@extends('layouts.app')

@section('title', 'My Account — Texpert Deals')
@section('robots', 'noindex, follow')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-12 sm:px-6">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900">My Account</h1>
            <p class="mt-1 text-sm text-slate-500">Hi {{ $customer->name }} — manage your orders and details.</p>
        </div>
        <form method="POST" action="{{ route('account.logout') }}">
            @csrf
            <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:border-rose-300 hover:text-rose-600">Log out</button>
        </form>
    </div>

    @if (session('status'))
        <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="mt-5 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            <ul class="list-inside list-disc">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="mt-8 grid gap-6 lg:grid-cols-[1.4fr_1fr]">

        {{-- order history --}}
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="font-bold text-slate-900">Order history</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-6 py-3">Order</th>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Status</th>
                            <th class="px-6 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($orders as $order)
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-6 py-3"><a href="{{ route('account.orders.show', $order) }}" class="font-semibold text-amber-600 hover:text-amber-700">{{ $order->order_number }}</a></td>
                                <td class="px-6 py-3 text-slate-500">{{ $order->placed_at?->format('M d, Y') }}</td>
                                <td class="px-6 py-3"><span class="rounded-full bg-{{ $order->status_color }}-50 px-2.5 py-1 text-xs font-semibold capitalize text-{{ $order->status_color }}-700">{{ $order->status }}</span></td>
                                <td class="px-6 py-3 text-right font-bold text-slate-900">${{ number_format($order->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-12 text-center text-slate-400">No orders yet. <a href="{{ route('products.index') }}" class="font-semibold text-amber-600">Start shopping →</a></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($orders->hasPages())
                <div class="border-t border-slate-200 p-4">{{ $orders->links() }}</div>
            @endif
        </section>

        {{-- profile --}}
        <section class="h-fit rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="font-bold text-slate-900">Your details</h2>
            <form method="POST" action="{{ route('account.update') }}" class="mt-4 space-y-3">
                @csrf @method('PUT')
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Name</label>
                    <input name="name" value="{{ old('name', $customer->name) }}" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Phone</label>
                        <input name="phone" value="{{ old('phone', $customer->phone) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-slate-600">Company</label>
                        <input name="company" value="{{ old('company', $customer->company) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
                    </div>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Address</label>
                    <input name="address" value="{{ old('address', $customer->address) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div><label class="mb-1 block text-xs font-semibold text-slate-600">City</label><input name="city" value="{{ old('city', $customer->city) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100"></div>
                    <div><label class="mb-1 block text-xs font-semibold text-slate-600">State</label><input name="state" value="{{ old('state', $customer->state) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100"></div>
                    <div><label class="mb-1 block text-xs font-semibold text-slate-600">ZIP</label><input name="zip" value="{{ old('zip', $customer->zip) }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100"></div>
                </div>
                <div class="border-t border-slate-100 pt-3">
                    <label class="mb-1 block text-xs font-semibold text-slate-600">New password <span class="font-normal text-slate-400">(leave blank to keep)</span></label>
                    <div class="grid grid-cols-2 gap-3">
                        <input type="password" name="password" placeholder="New password" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
                        <input type="password" name="password_confirmation" placeholder="Confirm" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
                    </div>
                </div>
                <button class="w-full rounded-lg bg-slate-900 py-2.5 text-sm font-bold text-white transition hover:bg-slate-800">Save changes</button>
            </form>
        </section>
    </div>
</div>
@endsection
