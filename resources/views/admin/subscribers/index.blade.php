@extends('admin.layouts.app')

@section('title', 'Subscribers')
@section('heading', 'Subscribers')
@section('subheading', 'Newsletter sign-ups from the storefront')

@section('content')

<div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur shadow-sm">
    <div class="flex flex-col gap-3 border-b border-white/10 p-4 sm:flex-row sm:items-center">
        <form method="GET" class="flex flex-1 gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Search email…"
                   class="w-full max-w-xs rounded-lg border border-white/10 bg-slate-950/50 px-3 py-2 text-sm text-slate-100 placeholder:text-slate-500 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            <button class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-200">Filter</button>
        </form>
        <div class="flex items-center gap-3 sm:ml-auto">
            <p class="text-sm text-slate-400">{{ $subscribers->total() }} subscriber(s)</p>
            <a href="{{ route('admin.subscribers.export') }}" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-amber-400">Export CSV</a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-white/5 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                <tr>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Source</th>
                    <th class="px-4 py-3">Subscribed</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                @forelse ($subscribers as $s)
                    <tr>
                        <td class="px-4 py-3 font-semibold text-slate-100">{{ $s->email }}</td>
                        <td class="px-4 py-3 text-slate-400">{{ $s->source ?: '—' }}</td>
                        <td class="px-4 py-3 text-slate-400">{{ $s->created_at?->format('M j, Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST" action="{{ route('admin.subscribers.destroy', $s) }}" onsubmit="return confirm('Remove this subscriber?')">
                                @csrf @method('DELETE')
                                <button class="rounded-lg border border-white/10 px-2.5 py-1.5 text-xs font-semibold text-rose-300 hover:border-rose-400/30 hover:bg-rose-500/15" aria-label="Remove">&times;</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-sm text-slate-400">No subscribers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="p-4">{{ $subscribers->links() }}</div>
</div>

@endsection
