@php
    $depth = $depth ?? 0;
    $active = $activeCategory ?? null;
    // true if this category — or any descendant — is the active one (used to auto-open the branch)
    $branchActive = function ($cat) use (&$branchActive, $active) {
        if (! $active) return false;
        if ($cat->id === $active->id) return true;
        foreach ($cat->visibleChildrenRecursive as $c) {
            if ($branchActive($c)) return true;
        }
        return false;
    };
    $allUrl = route('products.index');
@endphp
@foreach ($items as $cat)
    @php
        $isActive = optional($active)->id === $cat->id;
        $hasChildren = $cat->visibleChildrenRecursive->count() > 0;
        $url = route('products.index', ['category' => $cat->slug]);
        $textCls = $isActive ? 'font-semibold text-amber-600' : 'text-slate-600';
    @endphp
    <li>
        @if ($hasChildren)
            <details class="cat-acc" @if ($branchActive($cat)) open @endif>
                <summary class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-slate-50">
                    <input type="checkbox" onclick="event.stopPropagation()"
                           onchange="window.location = this.checked ? '{{ $url }}' : '{{ $allUrl }}'" @checked($isActive)
                           class="rounded border-slate-300 text-amber-600 focus:ring-amber-400">
                    <span class="flex-1 truncate {{ $textCls }}">{{ $cat->name }}</span>
                    <x-icon name="chevron" class="cat-caret h-3.5 w-3.5 shrink-0 text-slate-400 transition-transform duration-200" />
                </summary>
                <ul class="ml-3 mt-0.5 border-l border-slate-200 pl-2">
                    @include('products._cat_filter', ['items' => $cat->visibleChildrenRecursive, 'depth' => $depth + 1, 'activeCategory' => $active])
                </ul>
            </details>
        @else
            <label class="flex cursor-pointer items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-slate-50">
                <input type="checkbox"
                       onchange="window.location = this.checked ? '{{ $url }}' : '{{ $allUrl }}'" @checked($isActive)
                       class="rounded border-slate-300 text-amber-600 focus:ring-amber-400">
                <span class="flex-1 truncate {{ $textCls }}">{{ $cat->name }}</span>
            </label>
        @endif
    </li>
@endforeach
