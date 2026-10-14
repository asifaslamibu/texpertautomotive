@props(['name' => 'box', 'class' => 'w-6 h-6', 'solid' => false])

@php
    $icons = [
        // --- category icons ---
        'ac'        => '<rect x="2" y="4" width="20" height="9" rx="2"/><path d="M5 7.5h9M5 10h6"/><path d="M5 17c1.3 0 1.3 1.4 2.6 1.4M11 17c1.3 0 1.3 1.4 2.6 1.4M17 17c1.3 0 1.3 1.4 2.6 1.4"/>',
        'parts'     => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M5 5l2 2M17 17l2 2M19 5l-2 2M7 17l-2 2"/>',
        'minisplit' => '<rect x="2" y="5" width="20" height="7" rx="2"/><path d="M5 16c1.5 0 1.5 2 3 2M11 16c1.5 0 1.5 2 3 2M17 16c1.5 0 1.5 2 3 2"/>',
        'air'       => '<path d="M2 8h13a3 3 0 1 0-3-3M2 12h17a3 3 0 1 1-3 3M2 16h9a2.5 2.5 0 1 1-2.5 2.5"/>',
        'plumbing'  => '<path d="M4 7h6v4H4zM10 9h4M14 5h2a4 4 0 0 1 4 4v2M18 13v6M15 19h6"/>',
        'electric'  => '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
        'tools'     => '<path d="M14 7a4 4 0 0 0-5 5l-7 7 2 2 7-7a4 4 0 0 0 5-5l-2 2-2-2 2-2z"/>',
        'pool'      => '<path d="M3 18c1.5 0 1.5 1.5 3 1.5S7.5 18 9 18s1.5 1.5 3 1.5 1.5-1.5 3-1.5 1.5 1.5 3 1.5 1.5-1.5 3-1.5M7 14V5a2 2 0 0 1 4 0M7 9h4M13 14V5a2 2 0 0 1 4 0M13 9h4"/>',
        // --- ui icons ---
        'search'    => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        'cart'      => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/>',
        'user'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'phone'     => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3.1-8.7A2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
        'chat'      => '<path d="M21 11.5a8.4 8.4 0 0 1-9 8.4 9 9 0 0 1-3.6-.7L3 21l1.9-5.4A8.4 8.4 0 0 1 12 3a8.4 8.4 0 0 1 9 8.5z"/>',
        'truck'     => '<rect x="1" y="6" width="13" height="10" rx="1"/><path d="M14 9h4l3 3v4h-7zM5.5 16a2 2 0 1 0 4 0M16 16a2 2 0 1 0 4 0"/>',
        'shield'    => '<path d="M12 2 4 5v6c0 5 3.5 8.5 8 11 4.5-2.5 8-6 8-11V5z"/>',
        'star'      => '<path d="m12 2 3 6.5 7 .9-5 4.8 1.3 7L12 18l-6.3 3.2L7 14.2 2 9.4l7-.9z"/>',
        'refresh'   => '<path d="M21 2v6h-6M3 12a9 9 0 0 1 15-6.7L21 8M3 22v-6h6M21 12a9 9 0 0 1-15 6.7L3 16"/>',
        'check'     => '<path d="m20 6-11 11-5-5"/>',
        'arrow'     => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'chevron'   => '<path d="m6 9 6 6 6-6"/>',
        'menu'      => '<path d="M3 12h18M3 6h18M3 18h18"/>',
        'edit'      => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'gauge'     => '<path d="M12 14a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/><path d="M12 2a10 10 0 1 0 10 10"/><path d="M14 12l5-5"/>',
        'facebook'  => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
        'instagram' => '<rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1"/>',
        'linkedin'  => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>',
        'whatsapp'  => '<path d="M3 21l1.65-4.8a8 8 0 1 1 3.4 3.32z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5.5 0 1-.5 1-1l-1.5-1-1 .8c-1-.5-2-1.5-2.5-2.5l.8-1-1-1.5c-.5 0-1 .5-1 1z"/>',
        'box'       => '<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="m3 8 9 5 9-5"/>',
        'lock'      => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'computer'  => '<rect x="3" y="4" width="18" height="11" rx="2"/><path d="M3 15h18M9 19h6M10 15l-.4 4M14 15l.4 4"/>',
        'mail'      => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 6 10-6"/>',
        'headset'   => '<path d="M4 14v-2a8 8 0 0 1 16 0v2"/><rect x="2" y="14" width="4" height="6" rx="1.5"/><rect x="18" y="14" width="4" height="6" rx="1.5"/><path d="M20 18v1a3 3 0 0 1-3 3h-3"/>',
        'tag'       => '<path d="M20.6 13.4 12 22l-9-9V4a1 1 0 0 1 1-1h8.9z"/><circle cx="8" cy="8" r="1.4"/>',
        'shield-check' => '<path d="M12 2 4 5v6c0 5 3.5 8.5 8 11 4.5-2.5 8-6 8-11V5z"/><path d="m9 11.5 2 2 4-4"/>',
        'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/>',
        // --- car audio / automotive icons ---
        'car'       => '<path d="M5 17H3v-5l2-5h14l2 5v5h-2"/><path d="M3 12h18"/><circle cx="7.5" cy="17" r="2"/><circle cx="16.5" cy="17" r="2"/><path d="M9.5 17h5"/>',
        'speaker'   => '<circle cx="12" cy="12" r="9.5"/><circle cx="12" cy="12" r="5.5"/><circle cx="12" cy="12" r="1.8"/>',
        'amplifier' => '<rect x="2" y="7" width="20" height="10" rx="1.5"/><path d="M5 7V5M9 7V5M13 7V5M17 7V5M6 12h4M15 11v2M14 12h2"/>',
        'radio'     => '<rect x="2" y="6" width="20" height="12" rx="1.5"/><rect x="5" y="9" width="9" height="6" rx=".5"/><circle cx="18" cy="12" r="1.8"/>',
        'screen'    => '<rect x="2" y="4" width="20" height="13" rx="1.5"/><path d="M6 21h12M5 8h4M5 11h4M12 8h6v6h-6z"/>',
        'frame'     => '<rect x="2" y="5" width="20" height="14" rx="1.5"/><rect x="6" y="9" width="12" height="6" rx=".5"/><path d="M2 9h2M2 15h2M20 9h2M20 15h2"/>',
        'camera'    => '<rect x="3" y="7" width="13" height="10" rx="1.5"/><path d="m16 10 5-3v10l-5-3"/><circle cx="9.5" cy="12" r="2.2"/>',
        'cable'     => '<path d="M4 20c4 0 4-6 8-6s4 6 8 6"/><path d="M7 4v4M10 4v4M5.5 8h6v3a3 3 0 0 1-6 0z"/>',
        'seat'      => '<path d="M7 3h4l1 9h6a2 2 0 0 1 2 2v2H8a2 2 0 0 1-2-2L5 5a2 2 0 0 1 2-2z"/><path d="M9 16v5M17 16v5"/>',
        'bottle'    => '<path d="M9 2h6v3l2 3v12a2 2 0 0 1-2 2H9a2 2 0 0 1-2-2V8l2-3z"/><path d="M7 12h10M7 17h10"/>',
        'intake'    => '<path d="M3 9h7v6H3z"/><path d="M10 10h3l2-3h5v10h-5l-2-3h-3"/><path d="M17 7v10"/>',
        'heart'     => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21.2l8.8-8.8a5.5 5.5 0 0 0 0-7.8z"/>',
        'orders'    => '<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="m3 8 9 5 9-5M12 13v8M7.5 5.5l9 5"/>',
        'return'    => '<path d="M9 14 4 9l5-5"/><path d="M4 9h11a5 5 0 0 1 0 10h-3"/>',
        'badge'     => '<path d="M12 2 4 5v6c0 5 3.5 8.5 8 11 4.5-2.5 8-6 8-11V5z"/><path d="m8.5 11.5 2.5 2.5 4.5-5"/>',
        'quote'     => '<path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/>',
        'youtube'   => '<rect x="2" y="5" width="20" height="14" rx="4"/><path d="m10 9 5 3-5 3z"/>',
        'tiktok'    => '<path d="M14 3v11.5a3.5 3.5 0 1 1-3.5-3.5"/><path d="M14 3c.5 2.5 2.5 4.5 5 4.5"/>',
        'engine'    => '<path d="M7 8h6l2 2h3v3h2v-2h1v6h-1v-2h-2v3h-3l-2 2H8l-2-2H4v-3H2v-2h2v-3h3z"/><path d="M9 5h4M11 5v3"/>',
        'spring'    => '<path d="M8 3h8M8 21h8M8 5.5l8 2.5-8 2.5 8 2.5-8 2.5 8 2.5"/>',
        'wheel'     => '<circle cx="12" cy="12" r="9.5"/><circle cx="12" cy="12" r="5.5"/><circle cx="12" cy="12" r="1.5"/><path d="M12 6.5v4M12 13.5v4M6.5 12h4M13.5 12h4"/>',
        'home'      => '<path d="m3 11 9-8 9 8"/><path d="M5 9.5V21h5v-6h4v6h5V9.5"/>',
        'grid'      => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>',
    ];

    /**
     * Filled versions, for places that want a solid glyph rather than an outline.
     * Holes (the tick in the shield, the eyelet in the tag) are cut with
     * fill-rule="evenodd" so whatever is behind shows through.
     */
    $solidIcons = [
        'shield-check' => '<path d="M12 1.7 3.4 4.9v6.2c0 5.4 3.8 9.2 8.6 11.8 4.8-2.6 8.6-6.4 8.6-11.8V4.9zM10.9 15.9 7 12l1.6-1.6 2.3 2.3 4.5-4.5L17 9.8z"/>',
        'shield'       => '<path d="M12 1.7 3.4 4.9v6.2c0 5.4 3.8 9.2 8.6 11.8 4.8-2.6 8.6-6.4 8.6-11.8V4.9z"/>',
        'tag'          => '<path d="M13.3 2.4H4.2a1.8 1.8 0 0 0-1.8 1.8v9.1c0 .5.2.9.5 1.3l7.4 7.4a1.8 1.8 0 0 0 2.6 0l8.1-8.1a1.8 1.8 0 0 0 0-2.6l-7.4-7.4a1.8 1.8 0 0 0-1.3-.5zM7.9 6.3a1.7 1.7 0 1 1 0 3.4 1.7 1.7 0 0 1 0-3.4z"/>',
        'box'          => '<path d="M12 1.6 2.3 7.2v9.6L12 22.4l9.7-5.6V7.2zM12 4l6.6 3.8L12 11.6 5.4 7.8z"/>',
        'truck'        => '<path d="M2.9 4.8A1.7 1.7 0 0 0 1.2 6.5v8.8c0 .9.8 1.7 1.7 1.7h.7a3.2 3.2 0 0 1 6.2 0h3.6a3.2 3.2 0 0 1 6.2 0h1.2c.7 0 1.2-.6 1.2-1.3v-3.3c0-.4-.1-.8-.4-1.1l-2.7-2.9a1.7 1.7 0 0 0-1.2-.5h-3.2V6.5a1.7 1.7 0 0 0-1.7-1.7zm13.6 4.8h2.7l2.1 2.3h-4.8z"/><circle cx="6.7" cy="18.1" r="2"/><circle cx="17.5" cy="18.1" r="2"/>',
        'headset'      => '<path d="M12 1.7A9.6 9.6 0 0 0 2.4 11.3v7.1c0 1 .8 1.8 1.8 1.8h1.1c1.2 0 2.2-1 2.2-2.2v-3.4c0-1.2-1-2.2-2.2-2.2H4.2v-1.1A7.8 7.8 0 0 1 12 3.5a7.8 7.8 0 0 1 7.8 7.8v1.1h-1.1c-1.2 0-2.2 1-2.2 2.2V18c0 1.2 1 2.2 2.2 2.2h.2a2.6 2.6 0 0 1-2.4 1.6h-2.4a1.3 1.3 0 1 0 0 1.8h2.4a4.4 4.4 0 0 0 4.4-4.4v-7.9A9.6 9.6 0 0 0 12 1.7z"/>',
        'star'         => '<path d="m12 1.8 3.1 6.7 7.3.9-5.4 5 1.4 7.2L12 18.2 5.6 21.6 7 14.4 1.6 9.4l7.3-.9z"/>',
        'clock'        => '<path d="M12 2.1a9.9 9.9 0 1 0 0 19.8 9.9 9.9 0 0 0 0-19.8zm1.1 4.6v5l3.3 1.9-1.1 1.9-4.3-2.5v-6.3z"/>',
        'check'        => '<path d="M9.2 18.6 2.7 12l2.2-2.2 4.3 4.3 9.9-9.9L21.3 6z"/>',
        'lock'         => '<path d="M12 1.8a5.2 5.2 0 0 0-5.2 5.2v2.8H5.4A1.8 1.8 0 0 0 3.6 11.6v9A1.8 1.8 0 0 0 5.4 22.4h13.2a1.8 1.8 0 0 0 1.8-1.8v-9a1.8 1.8 0 0 0-1.8-1.8h-1.4V7A5.2 5.2 0 0 0 12 1.8zm0 2.4A2.8 2.8 0 0 1 14.8 7v2.8H9.2V7A2.8 2.8 0 0 1 12 4.2z"/>',
    ];

    $solid = filter_var($solid ?? false, FILTER_VALIDATE_BOOLEAN);
    $path = ($solid ? ($solidIcons[$name] ?? null) : null) ?? $icons[$name] ?? $icons['box'];
    $isSolid = $solid && isset($solidIcons[$name]);
@endphp

<svg {{ $attributes->merge(['class' => $class]) }} viewBox="0 0 24 24"
     fill="{{ $isSolid ? 'currentColor' : 'none' }}"
     @if ($isSolid) fill-rule="evenodd" clip-rule="evenodd" stroke="none" @else stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" @endif
     aria-hidden="true">
    {!! $path !!}
</svg>
