@extends('admin.layouts.app')

@section('title', 'Purchase Orders')
@section('heading', 'Purchase Orders')
@section('subheading', 'Create purchase orders and email them to distributors')

@section('content')

@include('admin.purchase-orders._tabs')

<div class="mb-5 flex flex-wrap items-center gap-3">
    <form method="GET" class="flex flex-1 gap-2">
        <input name="q" value="{{ request('q') }}" placeholder="Search PO # or customer…"
               class="w-full max-w-xs rounded-xl border border-white/10 bg-slate-900/70 px-4 py-2 text-sm text-white placeholder-slate-500 focus:border-white/30 focus:outline-none">
        <select name="status" onchange="this.form.submit()"
                class="rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white focus:outline-none">
            <option value="">All statuses</option>
            <option value="draft" @selected(request('status') === 'draft')>Pending</option>
            <option value="sent" @selected(request('status') === 'sent')>Approved / Sent</option>
        </select>
    </form>
    <a href="{{ route('admin.purchase-orders.export', request()->only('q', 'status')) }}"
       class="rounded-xl border border-white/10 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:bg-white/5">
        ⬇ Export to Excel
    </a>
    <a href="{{ route('admin.purchase-orders.create') }}"
       class="rounded-xl bg-white px-4 py-2 text-sm font-bold text-slate-900 transition hover:bg-slate-200">
        + New Purchase Order
    </a>
</div>

<div data-bulk class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur shadow-sm">
    <div class="px-4 pt-4"><x-bulk-bar :action="route('admin.purchase-orders.bulk-destroy')" /></div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-white/5 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                <tr>
                    <th class="px-4 py-3 w-8"><input type="checkbox" data-bulk-all class="rounded border-white/20 bg-transparent"></th>
                    <x-th-sort column="po_number" label="PO #" />
                    <x-th-sort column="distributor_name" label="Customer" />
                    <th class="px-4 py-3">Distributor</th>
                    <x-th-sort column="po_date" label="Date" />
                    <th class="px-4 py-3 text-right">Total</th>
                    <x-th-sort column="status" label="Status" />
                    <th class="px-4 py-3 text-right"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @php $hasDistributor = \App\Models\Distributor::where('active', true)->exists(); @endphp
                @forelse ($orders as $po)
                    <tr class="transition hover:bg-white/5">
                        <td class="px-4 py-3"><input type="checkbox" name="ids[]" value="{{ $po->id }}" data-bulk-item class="rounded border-white/20 bg-transparent"></td>
                        <td class="px-4 py-3 font-semibold text-white">
                            <a href="{{ route('admin.purchase-orders.show', $po) }}" class="hover:underline">{{ $po->po_number }}</a>
                            @if ($po->order_id)<div class="text-xs font-normal text-slate-500">from order</div>@endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-200">{{ $po->distributor_name }}</div>
                            <div class="text-xs text-slate-400">{{ $po->distributor_email ?: '—' }}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-300">{{ $po->distributor?->name ?: '—' }}</td>
                        <td class="px-4 py-3 text-slate-400">{{ optional($po->po_date)->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-white">${{ number_format($po->total, 2) }}</td>
                        <td class="px-4 py-3">
                            @if ($po->approved_at)
                                <span class="rounded-full bg-emerald-500/15 px-2.5 py-1 text-xs font-semibold text-emerald-300">Approved</span>
                            @elseif ($po->status === 'sent')
                                <span class="rounded-full bg-emerald-500/15 px-2.5 py-1 text-xs font-semibold text-emerald-300">Sent</span>
                            @else
                                <span class="rounded-full bg-amber-500/15 px-2.5 py-1 text-xs font-semibold text-amber-300">Pending</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-3">
                                @if (! $po->approved_at && $hasDistributor)
                                    <form method="POST" action="{{ route('admin.purchase-orders.approve', $po) }}"
                                          onsubmit="return confirm('Approve {{ $po->po_number }} and email it to the distributor?');">
                                        @csrf
                                        <button class="rounded-lg bg-emerald-500 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-emerald-600">Approve</button>
                                    </form>
                                @endif
                                <a href="{{ route('admin.purchase-orders.edit', $po) }}" class="text-xs font-semibold text-slate-300 hover:text-white">Edit</a>
                                <form method="POST" action="{{ route('admin.purchase-orders.destroy', $po) }}"
                                      onsubmit="return confirm('Delete {{ $po->po_number }}? This cannot be undone.');">
                                    @csrf @method('DELETE')
                                    <button class="text-xs font-semibold text-rose-300 hover:text-rose-200">Delete</button>
                                </form>
                                <a href="{{ route('admin.purchase-orders.show', $po) }}" class="text-xs font-semibold text-slate-300 hover:text-white">Open →</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-12 text-center text-slate-400">No purchase orders yet. Create your first one.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-white/10 p-4">{{ $orders->links('pagination.admin') }}</div>
</div>

@endsection
