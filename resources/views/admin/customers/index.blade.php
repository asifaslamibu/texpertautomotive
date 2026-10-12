@extends('admin.layouts.app')

@section('title', 'Customers')
@section('heading', 'Customers')
@section('subheading', 'Your customer base')

@section('content')

<div data-bulk class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur shadow-sm">
    <div class="border-b border-white/10 p-4">
        <form method="GET" class="flex flex-wrap gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name, email, company…"
                   class="w-full max-w-xs rounded-lg border border-white/10 bg-slate-950/50 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            <button class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-200">Search</button>
        </form>
    </div>

    <div class="px-4 pt-4"><x-bulk-bar :action="route('admin.customers.bulk-destroy')" /></div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-white/5 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                <tr>
                    <th class="px-4 py-3 w-8"><input type="checkbox" data-bulk-all class="rounded border-white/20 bg-transparent"></th>
                    <x-th-sort column="name" label="Customer" />
                    <th class="px-4 py-3">Location</th>
                    <th class="px-4 py-3 text-center">Orders</th>
                    <th class="px-4 py-3 text-right">Total Spent</th>
                    <x-th-sort column="created_at" label="Joined" />
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @forelse ($customers as $customer)
                    <tr class="transition hover:bg-white/5">
                        <td class="px-4 py-3"><input type="checkbox" name="ids[]" value="{{ $customer->id }}" data-bulk-item class="rounded border-white/20 bg-transparent"></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-gradient-to-br from-slate-800 to-slate-950 text-sm font-bold text-white">{{ substr($customer->name, 0, 1) }}</span>
                                <div>
                                    <p class="font-semibold text-slate-100">{{ $customer->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $customer->email }}{{ $customer->company ? ' · '.$customer->company : '' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-300">{{ $customer->city }}, {{ $customer->state }}</td>
                        <td class="px-4 py-3 text-center font-semibold text-white">{{ $customer->orders_count }}</td>
                        <td class="px-4 py-3 text-right font-bold text-emerald-300">${{ number_format($customer->orders_total ?? 0, 2) }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ optional($customer->created_at)->format('M j, Y') }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.customers.show', $customer) }}" class="text-xs font-semibold text-slate-400 hover:text-white">View →</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-slate-400">No customers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-white/10 p-4">{{ $customers->links('pagination.admin') }}</div>
</div>

@endsection
