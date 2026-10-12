@extends('admin.layouts.app')

@section('title', 'Wholesale Banner')
@section('heading', 'Wholesale Banner')
@section('subheading', 'The full-width "Apply for Wholesale" banner on the homepage')

@section('content')

<div class="mb-5">
    <a href="{{ route('admin.sliders.index') }}" class="text-sm font-semibold text-slate-300 hover:text-white">← Back to Sliders</a>
</div>

@if (session('status'))
    <div class="mx-auto mb-6 max-w-3xl rounded-xl border border-emerald-400/30 bg-emerald-500/15 px-4 py-3 text-sm font-semibold text-emerald-300">
        {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="mx-auto mb-6 max-w-3xl rounded-xl border border-rose-400/30 bg-rose-500/15 p-4">
        <p class="text-sm font-bold text-rose-300">Please fix the following:</p>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-300">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.sliders.wholesale.update') }}" enctype="multipart/form-data" class="mx-auto max-w-3xl space-y-6">
    @csrf @method('PUT')

    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <h2 class="mb-4 font-bold text-white">Background image</h2>

        <div data-image-field>
            <label class="mb-1.5 block text-sm font-medium text-slate-200">Wallpaper</label>
            <div class="flex items-center gap-4">
                <span class="grid h-20 w-36 shrink-0 place-items-center overflow-hidden rounded-xl border border-white/10 bg-white/5">
                    <img data-image-preview src="{{ $settings['home_wallpaper'] ? media($settings['home_wallpaper']) : '' }}" class="{{ $settings['home_wallpaper'] ? '' : 'hidden' }} h-full w-full object-cover" alt="">
                </span>
                <input type="file" name="home_wallpaper_file" accept="image/*" data-image-input
                       class="block w-full text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-200 hover:file:bg-white/20">
            </div>
            <input name="home_wallpaper" value="{{ old('home_wallpaper', $settings['home_wallpaper']) }}" placeholder="…or paste an image URL" class="mt-3 w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            <p class="mt-1 text-xs text-slate-400">Wide landscape image works best (≈1920×800px). Leave blank to use the default warehouse image.</p>
        </div>

        {{-- darkness overlay (left side, behind the text) --}}
        <div class="mt-5">
            @php $ov = old('home_wallpaper_overlay', ($settings['home_wallpaper_overlay'] === null || $settings['home_wallpaper_overlay'] === '') ? 75 : $settings['home_wallpaper_overlay']); @endphp
            <label class="mb-1.5 block text-sm font-medium text-slate-200">Image darkness <span class="font-normal text-slate-400">(overlay on the left, so the text stays readable)</span></label>
            <div class="flex items-center gap-3">
                <input type="range" name="home_wallpaper_overlay" min="0" max="90" step="5" value="{{ $ov }}"
                       oninput="this.nextElementSibling.textContent = this.value + '%'"
                       class="h-2 w-full cursor-pointer accent-amber-500">
                <span class="w-12 shrink-0 text-right text-sm font-semibold text-slate-200">{{ $ov }}%</span>
            </div>
            <p class="mt-1 text-xs text-slate-400">0% = bright image (no darkening); higher = darker on the left where the heading sits. The right side always stays lighter.</p>
        </div>
    </div>

    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <h2 class="mb-4 font-bold text-white">Text & button</h2>
        <div class="space-y-4">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Heading <span class="font-normal text-slate-400">(use <code>|</code> to split — text before is white, after is amber on a new line)</span></label>
                <input name="home_wallpaper_heading" value="{{ old('home_wallpaper_heading', $settings['home_wallpaper_heading']) }}" placeholder="Apply for|Wholesale" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Subtitle</label>
                <textarea name="home_wallpaper_subtitle" rows="2" placeholder="Get exclusive wholesale pricing on premium HVAC tools, parts, and maintenance products." class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">{{ old('home_wallpaper_subtitle', $settings['home_wallpaper_subtitle']) }}</textarea>
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Button text</label>
                    <input name="home_wallpaper_cta" value="{{ old('home_wallpaper_cta', $settings['home_wallpaper_cta']) }}" placeholder="Apply for Wholesale" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Button link <span class="font-normal text-slate-400">(URL)</span></label>
                    <input name="home_wallpaper_link" value="{{ old('home_wallpaper_link', $settings['home_wallpaper_link']) }}" placeholder="{{ route('bulk-order') }}" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                </div>
            </div>
            <p class="text-xs text-slate-400">Leave any field blank to use its default. The "Contact Sales" button and the three feature items are fixed.</p>
        </div>
    </div>

    <div class="mx-auto flex max-w-3xl justify-end">
        <button class="rounded-lg bg-white px-6 py-2.5 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-200">Save Banner</button>
    </div>
</form>

@endsection
