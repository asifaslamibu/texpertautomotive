@props(['column', 'label' => '', 'align' => 'left'])
@php
    $curSort = request('sort');
    $curDir = request('dir', 'asc') === 'desc' ? 'desc' : 'asc';
    $isActive = $curSort === $column;
    $nextDir = ($isActive && $curDir === 'asc') ? 'desc' : 'asc';
    $params = array_merge(request()->query(), ['sort' => $column, 'dir' => $nextDir]);
    $href = url()->current().'?'.http_build_query($params);
@endphp
<th {{ $attributes->merge(['class' => 'px-4 py-3 text-'.$align]) }}>
    <a href="{{ $href }}" class="inline-flex items-center gap-1 transition hover:text-white {{ $isActive ? 'text-white' : '' }}">
        {{ $label ?: $slot }}
        @if ($isActive)
            <span class="text-[9px] leading-none">{{ $curDir === 'asc' ? '▲' : '▼' }}</span>
        @else
            <span class="text-[9px] leading-none opacity-30">▲▼</span>
        @endif
    </a>
</th>
