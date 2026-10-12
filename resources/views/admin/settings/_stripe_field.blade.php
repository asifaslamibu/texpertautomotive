{{-- Stripe credential input. Props: $name, $label, $ph, $secret (bool), $val --}}
<div>
    <label class="mb-1.5 block text-xs font-semibold text-slate-300">{{ $label }}</label>
    <div class="relative">
        <input name="{{ $name }}" value="{{ $val }}" placeholder="{{ $ph }}"
               type="{{ $secret ? 'password' : 'text' }}" autocomplete="off" spellcheck="false"
               @if($secret) data-secret-input @endif
               class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 {{ $secret ? 'pr-12' : '' }} font-mono text-xs outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
        @if ($secret)
            <button type="button" data-secret-toggle aria-label="Show / hide"
                    class="absolute right-2 top-1/2 -translate-y-1/2 rounded px-2 py-1 text-[11px] font-bold text-slate-400 hover:text-white">Show</button>
        @endif
    </div>
</div>
