{{-- "Need Support?" strip — shown above the footer on inner pages --}}
@php
    $sPhone = \App\Models\Setting::get('contact_phone', '+1-254-358-0017');
    $sPhoneTel = preg_replace('/[^0-9+]/', '', $sPhone);
    $sTollFree = \App\Models\Setting::get('toll_free', '(945) 427-9519');
    $sEmail = \App\Models\Setting::get('contact_email', 'sales@texpertdeals.us');
@endphp
<section class="border-t border-slate-200 bg-slate-50">
    <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-8 sm:px-6 lg:flex-row lg:items-center lg:gap-8">
        {{-- heading --}}
        <div class="flex items-center gap-4 lg:w-72 lg:shrink-0">
            <span class="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-amber-400 text-slate-900 shadow-sm">
                <x-icon name="headset" class="h-7 w-7" stroke-width="2.2" />
            </span>
            <div>
                <h2 class="text-xl font-black text-slate-900">Need Support?</h2>
                @if ($sTollFree)
                    <p class="text-sm font-semibold text-slate-500">Toll-Free: <a href="tel:{{ preg_replace('/[^0-9+]/', '', $sTollFree) }}" class="text-amber-600 hover:text-amber-700">{{ $sTollFree }}</a></p>
                @else
                    <p class="text-sm font-semibold text-slate-500">Our team is here to help</p>
                @endif
            </div>
        </div>

        {{-- actions --}}
        <div class="grid flex-1 gap-3 sm:grid-cols-3">
            <a href="tel:{{ $sPhoneTel }}"
               class="flex items-center justify-center gap-2.5 rounded-xl border border-slate-200 bg-white px-5 py-4 text-sm font-bold text-slate-800 shadow-sm transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-md">
                <x-icon name="phone" class="h-5 w-5 text-amber-500" /> Call Us
            </a>
            <button type="button"
                    onclick="var t=document.querySelector('[data-chat-toggle]'); if(t){t.click()} else {location.href='{{ route('contact.index') }}'}"
                    class="flex items-center justify-center gap-2.5 rounded-xl border border-slate-200 bg-white px-5 py-4 text-sm font-bold text-slate-800 shadow-sm transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-md">
                <x-icon name="chat" class="h-5 w-5 text-amber-500" /> Live Chat
            </button>
            <a href="mailto:{{ $sEmail }}"
               class="flex items-center justify-center gap-2.5 rounded-xl border border-slate-200 bg-white px-5 py-4 text-sm font-bold text-slate-800 shadow-sm transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-md">
                <x-icon name="mail" class="h-5 w-5 text-amber-500" /> Email Us
            </a>
        </div>
    </div>
</section>
