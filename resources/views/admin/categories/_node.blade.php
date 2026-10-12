@php $depth = $depth ?? 0; $isFirst = $isFirst ?? false; $isLast = $isLast ?? false; @endphp
<div class="flex items-center gap-3 py-3 pr-5 {{ $depth > 0 ? 'border-t border-white/10' : '' }}" style="padding-left: {{ 1.25 + $depth * 1.5 }}rem">
    @if ($depth > 0)<span class="text-slate-300">↳</span>@endif

    {{-- reorder (controls menu order for top-level) --}}
    <div class="flex flex-col gap-0.5">
        <form method="POST" action="{{ route('admin.categories.move', [$node, 'up']) }}">
            @csrf @method('PATCH')
            <button @disabled($isFirst) title="Move up" class="grid h-5 w-5 place-items-center rounded text-slate-400 hover:bg-white/5 hover:text-white disabled:opacity-25 disabled:hover:bg-transparent">↑</button>
        </form>
        <form method="POST" action="{{ route('admin.categories.move', [$node, 'down']) }}">
            @csrf @method('PATCH')
            <button @disabled($isLast) title="Move down" class="grid h-5 w-5 place-items-center rounded text-slate-400 hover:bg-white/5 hover:text-white disabled:opacity-25 disabled:hover:bg-transparent">↓</button>
        </form>
    </div>

    <span class="grid {{ $depth === 0 ? 'h-11 w-11 bg-gradient-to-br from-slate-800 to-slate-950 text-white' : 'h-8 w-8 bg-white/5 text-slate-400' }} shrink-0 place-items-center rounded-lg">
        <x-icon name="{{ $node->icon }}" class="{{ $depth === 0 ? 'h-5 w-5' : 'h-4 w-4' }}" />
    </span>
    <div class="min-w-0 flex-1">
        <p class="{{ $depth === 0 ? 'font-bold text-white' : 'text-sm font-semibold text-slate-200' }}">
            {{ $node->name }}
            @if ($node->hidden)
                <span class="ml-1.5 inline-flex items-center rounded-full bg-amber-400/15 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-amber-300 ring-1 ring-amber-400/30">Hidden</span>
            @endif
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

@foreach ($node->childrenRecursive as $child)
    @include('admin.categories._node', ['node' => $child, 'depth' => $depth + 1, 'isFirst' => $loop->first, 'isLast' => $loop->last])
@endforeach
