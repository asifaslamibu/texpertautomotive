@extends('admin.layouts.app')

@section('title', 'Categories')
@section('heading', 'Categories')
@section('subheading', 'Organize categories & subcategories')

@section('content')

<div class="mb-5 flex flex-wrap items-center gap-3">
    <form method="GET" class="relative flex-1 sm:max-w-xs">
        <input type="search" name="q" value="{{ $search }}" placeholder="Search categories…"
               class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 py-2 pl-9 pr-3 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
        <span class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400"><x-icon name="search" class="h-4 w-4" /></span>
    </form>
    @if ($search !== '')
        <a href="{{ route('admin.categories.index') }}" class="text-sm font-semibold text-slate-400 hover:text-white">Reset</a>
    @endif
    <a href="{{ route('admin.categories.create') }}" class="ml-auto rounded-lg bg-white px-4 py-2 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-200">+ Add Category</a>
</div>

@if ($search !== '')
    {{-- ===== search results (flat) ===== --}}
    <p class="mb-3 text-sm text-slate-400">{{ $results->count() }} result(s) for "<span class="font-semibold text-slate-200">{{ $search }}</span>"</p>
    <div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur shadow-sm">
        @forelse ($results as $node)
            <div class="flex items-center gap-3 border-b border-white/10 py-3 px-5 last:border-0">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-white/5 text-slate-400">
                    <x-icon name="{{ $node->icon }}" class="h-4 w-4" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-slate-100">
                        @if ($node->parent)<span class="text-slate-400">{{ $node->parent->name }} ›</span> @endif{{ $node->name }}
                    </p>
                    <p class="truncate text-xs text-slate-400">/{{ $node->slug }}</p>
                </div>
                <span class="hidden rounded-full bg-white/5 px-2.5 py-1 text-xs font-semibold text-slate-300 sm:inline">{{ $node->products_count }} products</span>
                <div class="flex gap-2">
                    <a href="{{ route('admin.categories.edit', $node) }}" class="rounded-lg border border-white/10 px-3 py-1.5 text-xs font-semibold text-slate-300 hover:border-white/15 hover:text-white">Edit</a>
                    <form method="POST" action="{{ route('admin.categories.destroy', $node) }}" onsubmit="return confirm('Delete this category? Its products move to the parent category, and any subcategories move up one level — nothing is lost.')">
                        @csrf @method('DELETE')
                        <button class="rounded-lg border border-white/10 px-3 py-1.5 text-xs font-semibold text-rose-300 hover:border-rose-400/30 hover:bg-rose-500/15">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="py-12 text-center text-slate-400">No categories match "{{ $search }}".</p>
        @endforelse
    </div>
@else
    {{-- ===== full tree ===== --}}
    <p class="mb-4 flex items-center gap-2 rounded-lg bg-white/10 px-4 py-2.5 text-xs text-slate-200">
        <x-icon name="menu" class="h-4 w-4" />
        Use the <span class="font-bold">↑ ↓</span> arrows to reorder. Top-level categories appear in the storefront navigation menu in this order.
    </p>
    <div class="space-y-4">
        @forelse ($parents as $cat)
            <div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur shadow-sm">
                @include('admin.categories._node', ['node' => $cat, 'depth' => 0, 'isFirst' => $loop->first, 'isLast' => $loop->last])
            </div>
        @empty
            <p class="rounded-2xl border border-dashed border-white/10 bg-slate-900/70 backdrop-blur py-12 text-center text-slate-400">No categories yet.</p>
        @endforelse
    </div>
@endif

@endsection
