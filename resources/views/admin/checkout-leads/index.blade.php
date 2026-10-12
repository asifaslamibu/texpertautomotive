@extends('admin.layouts.app')

@section('title', 'Abandoned Checkouts')
@section('heading', 'Abandoned Checkouts')
@section('subheading', 'Details people typed on the checkout page — including orders they never placed')

@section('content')

@php $status = request('status') === 'ordered' ? 'ordered' : 'abandoned'; @endphp

<div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur shadow-sm">
    <div class="flex flex-col gap-3 border-b border-white/10 p-4 lg:flex-row lg:items-center">
        <div class="flex gap-2">
            <a href="{{ route('admin.checkout-leads.index') }}"
               class="rounded-lg px-4 py-2 text-sm font-semibold transition {{ $status === 'abandoned' ? 'bg-white text-slate-900' : 'border border-white/15 text-slate-300 hover:bg-white/10' }}">
                Abandoned ({{ number_format($abandonedCount) }})
            </a>
            <a href="{{ route('admin.checkout-leads.index', ['status' => 'ordered']) }}"
               class="rounded-lg px-4 py-2 text-sm font-semibold transition {{ $status === 'ordered' ? 'bg-white text-slate-900' : 'border border-white/15 text-slate-300 hover:bg-white/10' }}">
                Completed ({{ number_format($orderedCount) }})
            </a>
        </div>

        <form method="GET" class="flex flex-1 gap-2">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name, email, phone…"
                   class="w-full max-w-xs rounded-lg border border-white/10 bg-slate-950/50 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            <button class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-200">Filter</button>
        </form>

        <a href="{{ route('admin.checkout-leads.export', ['status' => $status]) }}"
           class="rounded-lg bg-amber-500 px-4 py-2 text-center text-sm font-bold text-slate-900 shadow-sm transition hover:bg-amber-400 lg:ml-auto">Export CSV</a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-white/5 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                <tr>
                    <th class="px-4 py-3">Buyer</th>
                    <th class="px-4 py-3">Contact</th>
                    <th class="px-4 py-3">Ship to</th>
                    <th class="px-4 py-3">Cart</th>
                    <th class="px-4 py-3">Last activity</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse ($leads as $lead)
                    <tr class="align-top">
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-100">{{ $lead->name ?: '—' }}</p>
                            @if ($lead->company)<p class="text-xs text-slate-400">{{ $lead->company }}</p>@endif
                            @if ($lead->order)
                                <span class="mt-1 inline-block rounded-full bg-emerald-500/15 px-2 py-0.5 text-xs font-semibold text-emerald-300">
                                    Ordered — {{ $lead->order->order_number }}
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($lead->email)
                                <a href="mailto:{{ $lead->email }}" class="block text-slate-200 hover:text-white">{{ $lead->email }}</a>
                            @endif
                            @if ($lead->phone)
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $lead->phone) }}" class="block text-xs text-slate-400 hover:text-slate-200">{{ $lead->phone }}</a>
                            @endif
                            @unless ($lead->email || $lead->phone)
                                <span class="text-slate-500">—</span>
                            @endunless
                        </td>
                        <td class="px-4 py-3 text-slate-400">
                            @if ($lead->address || $lead->city)
                                <p class="text-slate-300">{{ $lead->address ?: '—' }}</p>
                                <p class="text-xs">{{ collect([$lead->city, $lead->state, $lead->zip])->filter()->implode(', ') }}</p>
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-100">${{ number_format((float) $lead->cart_total, 2) }}</p>
                            @if ($items = $lead->items)
                                <details class="mt-1">
                                    <summary class="cursor-pointer text-xs text-slate-400 hover:text-slate-200">{{ count($items) }} item(s)</summary>
                                    <ul class="mt-1 space-y-0.5 text-xs text-slate-400">
                                        @foreach ($items as $i)
                                            <li>{{ $i['qty'] ?? 1 }} × {{ $i['name'] ?? '' }} @if(!empty($i['sku']))<span class="text-slate-500">({{ $i['sku'] }})</span>@endif</li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                            @if ($lead->notes)
                                <p class="mt-1 text-xs italic text-slate-500">“{{ Str::limit($lead->notes, 60) }}”</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-400">
                            <p>{{ $lead->updated_at?->diffForHumans() }}</p>
                            <p class="text-xs text-slate-500">{{ $lead->updated_at?->format('M j, Y g:i A') }}</p>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('admin.checkout-leads.destroy', $lead) }}" onsubmit="return confirm('Remove this checkout?')">
                                @csrf @method('DELETE')
                                <button class="rounded-lg border border-white/10 px-3 py-1.5 text-xs font-semibold text-slate-300 transition hover:border-rose-400/40 hover:bg-rose-500/10 hover:text-rose-300">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-slate-400">
                            {{ $status === 'ordered' ? 'No completed checkouts yet.' : 'No abandoned checkouts — everyone who started, finished.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($leads->hasPages())
        <div class="border-t border-white/10 p-4">{{ $leads->links() }}</div>
    @endif
</div>

@endsection
