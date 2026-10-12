@php $depth = $depth ?? 0; @endphp
@foreach ($items as $item)
    <a href="{{ route('products.index', ['category' => $item->slug]) }}"
       class="block rounded-lg py-1.5 pr-2 text-[13px] {{ $depth === 0 ? 'font-semibold text-slate-700' : 'text-slate-500' }} hover:text-amber-600"
       style="padding-left: {{ 0.5 + $depth * 0.85 }}rem">
        {{ $item->name }} <span class="text-slate-400">({{ $item->products_count }})</span>
    </a>
    @if ($item->visibleChildrenRecursive->count())
        @include('layouts.partials._mobile_items', ['items' => $item->visibleChildrenRecursive, 'depth' => $depth + 1])
    @endif
@endforeach
