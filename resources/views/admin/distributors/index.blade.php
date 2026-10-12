@extends('admin.layouts.app')

@section('title', 'Distributors')
@section('heading', 'Distributors')
@section('subheading', 'Suppliers your purchase orders are sent to')

@section('content')

@include('admin.purchase-orders._tabs')

<div class="mb-5 flex flex-wrap items-center gap-3">
    <form method="GET" class="flex-1">
        <input name="q" value="{{ request('q') }}" placeholder="Search name, company or email…"
               class="w-full max-w-xs rounded-xl border border-white/10 bg-slate-900/70 px-4 py-2 text-sm text-white placeholder-slate-500 focus:border-white/30 focus:outline-none">
    </form>
    <a href="{{ route('admin.distributors.create') }}"
       class="rounded-xl bg-white px-4 py-2 text-sm font-bold text-slate-900 transition hover:bg-slate-200">+ Add Distributor</a>
</div>

<div data-bulk class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur shadow-sm">
    <div class="px-4 pt-4"><x-bulk-bar :action="route('admin.distributors.bulk-destroy')" /></div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-white/5 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                <tr>
                    <th class="px-4 py-3 w-8"><input type="checkbox" data-bulk-all class="rounded border-white/20 bg-transparent"></th>
                    <x-th-sort column="name" label="Distributor" />
                    <x-th-sort column="email" label="Email" />
                    <th class="px-4 py-3">Phone</th>
                    <x-th-sort column="purchase_orders_count" label="POs" align="center" />
                    <x-th-sort column="active" label="Status" />
                    <th class="px-4 py-3 text-right"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @forelse ($distributors as $d)
                    <tr class="transition hover:bg-white/5">
                        <td class="px-4 py-3"><input type="checkbox" name="ids[]" value="{{ $d->id }}" data-bulk-item class="rounded border-white/20 bg-transparent"></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2 font-semibold text-white">
                                {{ $d->name }}
                                @if ($d->is_default)<span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-bold uppercase text-amber-300">Default</span>@endif
                            </div>
                            @if ($d->company)<div class="text-xs text-slate-400">{{ $d->company }}</div>@endif
                        </td>
                        <td class="px-4 py-3 text-slate-300">{{ $d->email }}</td>
                        <td class="px-4 py-3 text-slate-400">{{ $d->phone ?: '—' }}</td>
                        <td class="px-4 py-3 text-center text-slate-300">{{ $d->purchase_orders_count }}</td>
                        <td class="px-4 py-3">
                            @if ($d->active)
                                <span class="rounded-full bg-emerald-500/15 px-2.5 py-1 text-xs font-semibold text-emerald-300">Active</span>
                            @else
                                <span class="rounded-full bg-slate-500/20 px-2.5 py-1 text-xs font-semibold text-slate-300">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-3">
                                @unless ($d->is_default)
                                    <form method="POST" action="{{ route('admin.distributors.default', $d) }}">
                                        @csrf @method('PATCH')
                                        <button class="text-xs font-semibold text-amber-300 hover:text-amber-200">Make default</button>
                                    </form>
                                @endunless
                                <a href="{{ route('admin.distributors.edit', $d) }}" class="text-xs font-semibold text-slate-300 hover:text-white">Edit</a>
                                <form method="POST" action="{{ route('admin.distributors.destroy', $d) }}" onsubmit="return confirm('Delete {{ $d->name }}?');">
                                    @csrf @method('DELETE')
                                    <button class="text-xs font-semibold text-rose-300 hover:text-rose-200">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-slate-400">No distributors yet. Add your first supplier.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-white/10 p-4">{{ $distributors->links('pagination.admin') }}</div>
</div>

@endsection
