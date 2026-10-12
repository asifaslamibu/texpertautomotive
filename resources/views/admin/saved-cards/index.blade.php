@extends('admin.layouts.app')

@section('title', 'Saved Cards')
@section('heading', 'Saved Cards')
@section('subheading', 'Cards customers saved at checkout (stored securely by Stripe)')

@section('content')

<div class="mb-5 flex items-start gap-2.5 rounded-xl border border-white/10 bg-white/5 px-4 py-3 text-sm text-slate-300">
    <x-icon name="lock" class="mt-0.5 h-5 w-5 shrink-0 text-emerald-300" />
    <p>Raw card numbers and CVV are <b class="text-white">never</b> stored here. Stripe keeps the actual card; we only show brand, last 4 digits and expiry.</p>
</div>

<div class="mb-4 flex items-center justify-between gap-3">
    <p class="text-sm text-slate-400">{{ $cards->total() }} saved {{ \Illuminate\Support\Str::plural('card', $cards->total()) }}</p>
    @if ($cards->total())
        <a href="{{ route('admin.saved-cards.export') }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-bold text-slate-900 transition hover:bg-slate-200">
            <x-icon name="arrow" class="h-4 w-4 rotate-90" /> Export CSV
        </a>
    @endif
</div>

<div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-white/5 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                <tr>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Card</th>
                    <th class="px-4 py-3">Expiry</th>
                    <th class="px-4 py-3">Saved</th>
                    <th class="px-4 py-3 text-right"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @forelse ($cards as $card)
                    <tr class="transition hover:bg-white/5">
                        <td class="px-4 py-3">
                            @if ($card->customer)
                                <a href="{{ route('admin.customers.show', $card->customer) }}" class="font-semibold text-slate-200 hover:text-white">{{ $card->name ?: $card->customer->name }}</a>
                            @else
                                <span class="font-semibold text-slate-200">{{ $card->name ?: '—' }}</span>
                            @endif
                            <div class="text-xs text-slate-400">{{ $card->email }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-2 rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 font-mono text-sm text-white">
                                <x-icon name="lock" class="h-3.5 w-3.5 text-slate-400" /> {{ $card->label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-300">{{ $card->expiry }}</td>
                        <td class="px-4 py-3 text-slate-400">{{ $card->created_at?->format('M d, Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('admin.saved-cards.destroy', $card) }}" onsubmit="return confirm('Remove this saved card? It will also be detached from Stripe.')">
                                @csrf @method('DELETE')
                                <button class="rounded-lg border border-white/10 px-3 py-1.5 text-xs font-semibold text-slate-300 transition hover:border-rose-400/40 hover:bg-rose-500/10 hover:text-rose-300">Remove</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-12 text-center text-slate-400">No saved cards yet. Cards appear here after customers pay by card at checkout.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-white/10 p-4">{{ $cards->links('pagination.admin') }}</div>
</div>

@endsection
