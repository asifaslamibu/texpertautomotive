@extends('admin.layouts.app')

@section('title', 'Duplicate Products')
@section('heading', 'Duplicate Products')
@section('subheading', 'Products that share the same SKU or name — review and remove the extras')

@section('content')

<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <a href="{{ route('admin.products.index') }}" class="text-sm font-semibold text-slate-300 hover:text-white">← Back to Products</a>

    {{-- toggle: group by SKU / by name --}}
    <div class="flex gap-2">
        <a href="{{ route('admin.products.duplicates', ['by' => 'sku']) }}"
           class="rounded-full px-4 py-1.5 text-sm font-semibold transition {{ $by === 'sku' ? 'bg-white text-slate-900' : 'border border-white/10 text-slate-300 hover:bg-white/10' }}">Same SKU</a>
        <a href="{{ route('admin.products.duplicates', ['by' => 'name']) }}"
           class="rounded-full px-4 py-1.5 text-sm font-semibold transition {{ $by === 'name' ? 'bg-white text-slate-900' : 'border border-white/10 text-slate-300 hover:bg-white/10' }}">Same name</a>
    </div>
</div>

@if ($groups->count())
    <p class="mb-4 text-sm text-slate-400">
        <span class="font-bold text-white">{{ $totalGroups }}</span> group{{ $totalGroups === 1 ? '' : 's' }} of duplicates ·
        <span class="font-bold text-amber-400">{{ $totalExtra }}</span> extra product{{ $totalExtra === 1 ? '' : 's' }} could be removed.
        Tick the ones to delete (keep at least one per group), then Delete.
    </p>

    <form method="POST" action="{{ route('admin.products.bulk-destroy') }}" data-bulk onsubmit="return confirm('Delete the selected product(s)? This cannot be undone.');">
        @csrf @method('DELETE')

        {{-- sticky delete bar --}}
        <div data-bulk-bar class="sticky top-2 z-10 mb-4 hidden items-center gap-3 rounded-xl border border-rose-400/30 bg-rose-500/15 px-4 py-2.5 text-sm backdrop-blur">
            <span class="text-rose-200"><span data-bulk-count class="font-bold text-white">0</span> selected</span>
            <button type="submit" class="ml-auto rounded-lg bg-rose-500 px-4 py-1.5 text-xs font-bold text-white transition hover:bg-rose-600">Delete selected</button>
        </div>

        <div class="space-y-5">
            @foreach ($groups as $key => $items)
                <div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur shadow-sm">
                    <div class="flex items-center justify-between border-b border-white/10 px-4 py-3">
                        <h3 class="truncate pr-4 font-bold text-white" title="{{ $key }}">{{ $key }}</h3>
                        <span class="shrink-0 rounded-full bg-white/5 px-3 py-1 text-xs font-bold text-slate-300">{{ $items->count() }} products</span>
                    </div>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-white/10 text-slate-300">
                            @foreach ($items as $i => $p)
                                <tr class="hover:bg-white/5">
                                    <td class="w-10 px-4 py-3">
                                        {{-- first item in each group is left unticked as the "keep" default --}}
                                        <input type="checkbox" name="ids[]" value="{{ $p->id }}" data-bulk-item
                                               class="rounded border-white/20 bg-transparent text-rose-500 focus:ring-rose-400">
                                    </td>
                                    <td class="w-14 py-2">
                                        <span class="grid h-10 w-10 place-items-center overflow-hidden rounded-lg bg-white/5">
                                            <img src="{{ media($p->image) }}" alt="" class="h-full w-full object-contain">
                                        </span>
                                    </td>
                                    <td class="py-3">
                                        <span class="block font-medium text-slate-100">{{ $p->name }}</span>
                                        <span class="text-xs text-slate-500">
                                            SKU {{ $p->sku ?: '—' }} · {{ $p->category->name ?? 'Uncategorized' }}
                                            @if ($i === 0)<span class="ml-1 rounded bg-emerald-500/15 px-1.5 py-0.5 text-[10px] font-bold text-emerald-400">oldest — keep</span>@endif
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums text-slate-200">${{ number_format($p->price, 2) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">
                                        <a href="{{ route('admin.products.edit', $p) }}" class="rounded-lg border border-white/10 px-3 py-1.5 text-xs font-semibold text-slate-300 hover:border-white/15 hover:text-white">Edit</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endforeach
        </div>
    </form>
@else
    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-16 text-center">
        <x-icon name="check" class="mx-auto h-10 w-10 text-emerald-400" />
        <p class="mt-4 font-semibold text-white">No duplicates found</p>
        <p class="mt-1 text-sm text-slate-400">No two products share the same {{ $by === 'name' ? 'name' : 'SKU' }}.</p>
    </div>
@endif

@endsection
