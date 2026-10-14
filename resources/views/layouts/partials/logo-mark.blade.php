{{-- Brand logo for dark backgrounds (header, footer, mobile menu, homepage banner).
     An uploaded logo in Admin → Settings still wins where the caller checks for it.
     $size: 'sm' | 'lg'; $dark: true when it sits on a light background (text mark only). --}}
@php
    $lg = ($size ?? 'lg') === 'lg';
    $ink = ($dark ?? false) ? 'text-[#111]' : 'text-white';
    $logoFile = 'images/texpert-automotive-logo.png';
@endphp
@if (! ($dark ?? false) && file_exists(public_path($logoFile)))
    <img src="{{ asset($logoFile) }}?v={{ filemtime(public_path($logoFile)) }}" alt="{{ \App\Models\Setting::get('site_name', 'Texpert Automotive') }}"
         width="640" height="170" class="{{ $lg ? 'h-12 sm:h-14' : 'h-10' }} w-auto">
@else
    <span class="flex flex-col items-center leading-none">
        <span class="font-black italic tracking-tight {{ $ink }} {{ $lg ? 'text-[34px]' : 'text-2xl' }}" style="font-family:'Rubik',sans-serif">TE<span class="text-amber-500">X</span>PERT</span>
        <span class="mt-0.5 flex w-full items-center gap-1.5 font-bold uppercase text-amber-500 {{ $lg ? 'text-[11px] tracking-[0.32em]' : 'text-[8px] tracking-[0.3em]' }}">
            <span class="h-px flex-1 bg-amber-500"></span>Automotive<span class="h-px flex-1 bg-amber-500"></span>
        </span>
    </span>
@endif
