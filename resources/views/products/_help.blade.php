@php
    $helpPhone = \App\Models\Setting::get('contact_phone', '+1-254-358-0017');
    $helpToll = \App\Models\Setting::get('toll_free', '(945) 427-9519');
@endphp
<div class="rounded-xl bg-slate-900 p-5 text-white">
    <x-icon name="phone" class="h-7 w-7 text-amber-200" />
    <h3 class="mt-3 font-bold">Need help choosing?</h3>
    <p class="mt-1 text-sm text-amber-100">Talk to an expert before you buy.</p>
    <div class="mt-4 flex flex-wrap gap-2">
        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $helpPhone) }}" class="inline-block rounded-full bg-white px-4 py-2 text-sm font-bold text-amber-600">Call {{ $helpPhone }}</a>
        @if ($helpToll)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $helpToll) }}" class="inline-block rounded-full bg-white/10 px-4 py-2 text-sm font-bold text-white ring-1 ring-white/20 transition hover:bg-white/20">Toll-Free {{ $helpToll }}</a>@endif
    </div>
</div>
