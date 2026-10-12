@props(['tag'])

@php
    $tag = trim((string) $tag);

    // The first word is highlighted and the rest reads plain ("SHIPS from manufacturer"),
    // which keeps any admin-entered wording looking like the intended badge.
    [$lead, $rest] = array_pad(explode(' ', $tag, 2), 2, null);
@endphp

@if ($tag !== '')
<span {{ $attributes->merge(['class' => 'brand-tag']) }}>
    <span class="brand-tag-lead">{{ $lead }}</span>@if ($rest)<span>{{ $rest }}</span>@endif
</span>

@once
@push('head')
<style>
    .brand-tag {
        display: inline-flex;
        align-items: center;
        gap: .4em;
        background: #2b2b2b;
        color: #fff;
        font-size: .72rem;
        font-weight: 700;
        letter-spacing: .06em;
        text-transform: uppercase;
        line-height: 1;
        white-space: nowrap;
        /* extra right padding leaves room for the arrow the clip-path carves out */
        padding: .7em 2em .7em 1.2em;
        
        border-radius: 9999px 0 0 9999px;
        clip-path: polygon(0 0, calc(100% - 13px) 0, 100% 50%, calc(100% - 13px) 100%, 0 100%);
    }
    .brand-tag-lead { color: #f2b134; }
</style>
@endpush
@endonce
@endif
