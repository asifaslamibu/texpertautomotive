@extends('admin.layouts.app')

@section('title', 'Discount Codes')
@section('heading', 'Discount Codes')
@section('subheading', 'Codes shoppers can enter in the cart or at checkout')

@section('content')

<div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur shadow-sm">
    <div class="flex flex-col gap-3 border-b border-white/10 p-4 sm:flex-row sm:items-center">
        <p class="text-sm text-slate-400">{{ $coupons->total() }} code(s)</p>
        <a href="{{ route('admin.coupons.create') }}" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-amber-400 sm:ml-auto">New code</a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-white/5 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                <tr>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Discount</th>
                    <th class="px-4 py-3">Minimum order</th>
                    <th class="px-4 py-3">Used</th>
                    <th class="px-4 py-3">Valid</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5 text-slate-300">
                @forelse ($coupons as $coupon)
                    <tr>
                        <td class="px-4 py-3 font-bold tracking-wide text-slate-100">
                            {{ $coupon->code }}
                            @if ($coupon->note)<span class="block text-xs font-normal text-slate-500">{{ $coupon->note }}</span>@endif
                        </td>
                        <td class="px-4 py-3">{{ $coupon->label() }}</td>
                        <td class="px-4 py-3">{{ (float) $coupon->min_subtotal > 0 ? '$'.number_format((float) $coupon->min_subtotal, 2) : '—' }}</td>
                        <td class="px-4 py-3">{{ $coupon->used_count }}{{ $coupon->max_uses ? ' / '.$coupon->max_uses : '' }}</td>
                        <td class="px-4 py-3 text-xs">
                            {{ $coupon->starts_at?->format('d M Y') ?? 'Now' }} – {{ $coupon->expires_at?->format('d M Y') ?? 'No end' }}
                        </td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('admin.coupons.toggle', $coupon) }}">
                                @csrf @method('PATCH')
                                <button class="rounded-full px-2.5 py-1 text-xs font-bold {{ $coupon->active ? 'bg-emerald-500/15 text-emerald-300' : 'bg-slate-500/15 text-slate-400' }}">
                                    {{ $coupon->active ? 'On' : 'Off' }}
                                </button>
                            </form>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('admin.coupons.edit', $coupon) }}" class="text-sm font-semibold text-slate-300 hover:text-white">Edit</a>
                                <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" onsubmit="return confirm('Delete this code?')">
                                    @csrf @method('DELETE')
                                    <button class="text-sm font-semibold text-rose-400 hover:text-rose-300">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">No discount codes yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($coupons->hasPages())
        <div class="border-t border-white/10 p-4">{{ $coupons->links() }}</div>
    @endif
</div>

@endsection
