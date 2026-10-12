@props(['action', 'label' => 'Delete selected'])
{{-- Sits inside a [data-bulk] wrapper. Bare CSRF input is read by the bulk JS. --}}
<div data-bulk-bar class="hidden mb-3 flex flex-wrap items-center gap-3 rounded-xl border border-white/10 bg-slate-800/60 px-4 py-2.5 text-sm">
    @csrf
    <span class="text-slate-300"><span data-bulk-count class="font-bold text-white">0</span> selected</span>

    {{-- optional list-specific actions --}}
    {{ $slot }}

    <button type="button" data-bulk-submit data-bulk-action="{{ $action }}"
            class="ml-auto rounded-lg bg-rose-500 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-rose-600">
        {{ $label }}
    </button>
</div>
