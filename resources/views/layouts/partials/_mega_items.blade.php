@php
    $depth = $depth ?? 0;
    $limit = $limit ?? null;            // only applied at depth 0 (the direct child categories)
    $viewAllUrl = $viewAllUrl ?? null;
    $shown = ($depth === 0 && $limit) ? $items->take($limit) : $items;
@endphp
<ul class="space-y-1.5 {{ $depth > 0 ? 'mt-1.5 ml-2 border-l border-slate-100 pl-2.5' : '' }}">
    @foreach ($shown as $item)
        {{-- a group and its children stay together when the list flows across columns --}}
        <li class="{{ $depth === 0 ? 'break-inside-avoid' : '' }}">
            <a href="{{ route('products.index', ['category' => $item->slug]) }}"
               class="flex items-baseline gap-1 text-[13px] text-slate-600 transition hover:text-amber-600">
                {{-- underline sits on the name only, not on the product count --}}
                <span class="mega-link truncate">{{ $item->name }}</span>
                <span class="text-xs text-slate-400">({{ $item->products_count }})</span>
            </a>
            @if ($item->visibleChildrenRecursive->count() && $depth < 2)
                @include('layouts.partials._mega_items', ['items' => $item->visibleChildrenRecursive, 'depth' => $depth + 1])
            @endif
        </li>
    @endforeach
</ul>

@if ($depth === 0 && $limit && $viewAllUrl && $items->count() > $limit)
    <a href="{{ $viewAllUrl }}" class="mega-link mt-2 inline-flex items-center gap-1 text-[13px] font-semibold text-amber-500 transition hover:text-amber-600">
        View all →
    </a>
@endif
