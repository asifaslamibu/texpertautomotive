@extends('admin.layouts.app')

@section('title', $brand->exists ? 'Edit Brand' : 'New Brand')
@section('heading', $brand->exists ? 'Edit Brand' : 'New Brand')

@section('content')

<form method="POST" action="{{ $brand->exists ? route('admin.brands.update', $brand) : route('admin.brands.store') }}" enctype="multipart/form-data" class="mx-auto max-w-2xl">
    @csrf
    @if ($brand->exists) @method('PUT') @endif

    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-rose-400/30 bg-rose-500/15 p-4">
            <p class="text-sm font-bold text-rose-300">Please fix the following:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-300">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <div class="space-y-5">
            <div> 
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Brand name *</label>
                <input name="name" value="{{ old('name', $brand->name) }}" required
                       class="w-full rounded-lg border bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:ring-2 focus:ring-slate-500/30 {{ $errors->has('name') ? 'border-rose-400 focus:border-rose-400' : 'border-white/10 focus:border-slate-400' }}">
                @error('name')<p class="mt-1 text-xs text-rose-300">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Logo</label>
                <div class="flex items-center gap-4">
                    <span class="grid h-20 w-28 shrink-0 place-items-center overflow-hidden rounded-xl border border-white/10 bg-white/5">
                        <img data-image-preview src="{{ $brand->logo ? media($brand->logo) : '' }}" class="{{ $brand->logo ? '' : 'hidden' }} max-h-full max-w-full object-contain" alt="">
                    </span>
                    <div class="flex-1">
                        <input type="file" name="logo_file" accept="image/*" data-image-input
                               class="block w-full text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-200 hover:file:bg-white/20">
                        <p class="mt-1 text-xs text-slate-400">PNG (transparent preferred), JPG, WEBP or SVG · max 2MB.</p>
                    </div>
                </div>
                <input name="logo" value="{{ old('logo', $brand->logo) }}" placeholder="…or paste a logo URL" class="mt-3 w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Link (optional)</label>
                <input name="link" value="{{ old('link', $brand->link) }}" placeholder="Leave blank to link to a search for this brand" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Product tag (optional)</label>
                <input name="tag" value="{{ old('tag', $brand->tag) }}" maxlength="60"
                       placeholder="e.g. Ships from manufacturer"
                       class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                <p class="mt-2 text-xs text-slate-400">
                    Shown as a badge on <strong class="text-slate-300">every product of this brand</strong>. Leave blank for none.
                </p>
                <div class="mt-3">
                    <span class="mb-1.5 block text-xs text-slate-500">Preview</span>
                    <span data-tag-preview-wrap @class(['hidden' => ! old('tag', $brand->tag)])>
                        {{-- the text is a placeholder only until something is typed; the wrapper stays hidden while empty --}}
                        <x-brand-tag :tag="old('tag', $brand->tag) ?: 'Ships from manufacturer'" data-tag-preview />
                    </span>
                    <span data-tag-preview-empty @class(['text-xs italic text-slate-500', 'hidden' => (bool) old('tag', $brand->tag)])>
                        No badge on this brand's products
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap items-end gap-6">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Sort order</label>
                    <input name="sort" type="number" min="0" value="{{ old('sort', $brand->sort ?? 0) }}" class="w-32 rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400">
                </div>
            </div>

            <div class="space-y-2 rounded-xl border border-white/10 bg-white/5 p-4">
                <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Where to display</p>
                <label class="flex items-center gap-2 text-sm font-medium text-slate-200">
                    <input type="checkbox" name="featured" value="1" @checked(old('featured', $brand->featured)) class="rounded border-white/10 text-slate-200"> Show in <strong>Popular Brands</strong>
                </label>
                <label class="flex items-center gap-2 text-sm font-medium text-slate-200">
                    <input type="checkbox" name="in_marquee" value="1" @checked(old('in_marquee', $brand->in_marquee ?? true)) class="rounded border-white/10 text-slate-200"> Show in <strong>brand slider (marquee)</strong>
                </label>
                <label data-tag-enabled-label class="flex items-center gap-2 text-sm font-medium text-slate-200">
                    <input type="checkbox" name="tag_enabled" value="1" data-tag-enabled
                           @checked(old('tag_enabled', $brand->tag_enabled ?? true)) class="rounded border-white/10 text-slate-200">
                    Show <strong>badge</strong> on this brand's products
                </label>
                <p data-tag-enabled-hint class="pl-6 text-xs text-slate-500">Untick to hide the badge without clearing the wording above.</p>
            </div>
        </div>
    </div>

    <div class="mt-6 flex items-center justify-end gap-3">
        <a href="{{ route('admin.brands.index') }}" class="rounded-lg border border-white/10 px-5 py-2.5 text-sm font-semibold text-slate-300 hover:bg-white/5">Cancel</a>
        <button class="rounded-lg bg-white px-6 py-2.5 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-200">{{ $brand->exists ? 'Update Brand' : 'Create Brand' }}</button>
    </div>
</form>

@push('scripts')
<script>
    // Live-preview the badge, mirroring the component's "first word is highlighted" rule.
    (function () {
   

        const lead = preview.querySelector('.brand-tag-lead');
        const rest = lead?.nextElementSibling;

        const label = document.querySelector('[data-tag-enabled-label]');
        const hint  = document.querySelector('[data-tag-enabled-hint]');

        const render = () => {
            const text = input.value.trim();

            // The switch is meaningless with nothing to show, so make that plain
            // instead of letting it be ticked while no badge ever appears. Typing the
            // first wording switches it on — that is the only reason to type it.
            if (toggle) {
                if (text !== '' && toggle.disabled) toggle.checked = true;
                toggle.disabled = text === '';
                label.classList.toggle('opacity-40', text === '');
                hint.textContent = text === ''
                    ? 'Enter the badge wording above to switch this on.'
                    : 'Untick to hide the badge without clearing the wording above.';
            }

            const on = toggle ? toggle.checked : true;

            // No text, or switched off, means no badge — say so rather than previewing an example.
            const show = text !== '' && on;
            wrap.classList.toggle('hidden', ! show);
            empty.classList.toggle('hidden', show);
            empty.textContent = (text !== '' && ! on)
                ? 'Badge is switched off for this brand'
                : "No badge on this brand's products";
            if (! show) return;

            const space = text.indexOf(' ');
            lead.textContent = space === -1 ? text : text.slice(0, space);
            if (rest) rest.textContent = space === -1 ? '' : text.slice(space + 1);
        };

        input.addEventListener('input', render);
        toggle?.addEventListener('change', render);

        // A disabled checkbox posts nothing, so re-enable it just before submit.
        input.closest('form')?.addEventListener('submit', () => {
            if (toggle) toggle.disabled = false;
        });

        render();
    })();
</script>
@endpush

@endsection
