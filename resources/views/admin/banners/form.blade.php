@extends('admin.layouts.app')

@section('title', $banner->exists ? 'Edit Slide' : 'New Slide')
@section('heading', $banner->exists ? 'Edit Slide' : 'New Slide')

@section('content')

<form method="POST" action="{{ $banner->exists ? route('admin.banners.update', $banner) : route('admin.banners.store') }}" enctype="multipart/form-data" class="mx-auto max-w-2xl">
    @csrf
    @if ($banner->exists) @method('PUT') @endif

    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <div class="space-y-5">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Eyebrow <span class="font-normal text-slate-400">(small line above title)</span></label>
                <input name="eyebrow" value="{{ old('eyebrow', $banner->eyebrow) }}" placeholder="e.g. Built for"
                       class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Title <span class="font-normal text-slate-400">(optional — leave empty for an image-only slide)</span></label>
                <input name="title" value="{{ old('title', $banner->title) }}" placeholder="e.g. Wholesale Prices"
                       class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Subtitle <span class="font-normal text-slate-400">(white line below title)</span></label>
                <input name="subtitle" value="{{ old('subtitle', $banner->subtitle) }}" placeholder="e.g. On Thousands of Products"
                       class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Button label</label>
                    <input name="cta_label" value="{{ old('cta_label', $banner->cta_label) }}" placeholder="e.g. Shop Now"
                           class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Button link</label>
                    <input name="cta_link" value="{{ old('cta_link', $banner->cta_link) }}" placeholder="URL, /path, or a search term"
                           class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                    <p class="mt-1 text-xs text-slate-400">Leave blank to link to all products. A plain word becomes a product search.</p>
                </div>
            </div>

            <div data-image-field>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Background image <span class="font-normal text-slate-400">(optional)</span></label>
                <div class="flex items-center gap-4">
                    <span class="grid h-20 w-36 shrink-0 place-items-center overflow-hidden rounded-xl border border-white/10 bg-slate-950">
                        <img data-image-preview src="{{ $banner->image ? media($banner->image) : '' }}" class="{{ $banner->image ? '' : 'hidden' }} max-h-full max-w-full object-cover" alt="">
                    </span>
                    <input type="file" name="image_file" accept="image/*" data-image-input
                           class="block w-full text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-200 hover:file:bg-white/20">
                </div>
                <input name="image" value="{{ old('image', $banner->image) }}" placeholder="…or paste an image URL" class="mt-3 w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                <p class="mt-1 text-xs text-slate-400">A dark overlay is added automatically so the text stays readable. JPG/PNG/WEBP · max 4MB.</p>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Background video <span class="font-normal text-slate-400">(optional — plays muted &amp; looped behind the text)</span></label>
                <div class="flex items-center gap-4">
                    <span class="grid h-20 w-36 shrink-0 place-items-center overflow-hidden rounded-xl border border-white/10 bg-slate-950 text-slate-500">
                        @if ($banner->video)
                            <video src="{{ media($banner->video) }}" class="max-h-full max-w-full object-cover" muted playsinline></video>
                        @else
                            <x-icon name="truck" class="h-6 w-6 opacity-40" />
                        @endif
                    </span>
                    <input type="file" name="video_file" accept="video/mp4,video/webm,video/ogg"
                           class="block w-full text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-200 hover:file:bg-white/20">
                </div>
                <input name="video" value="{{ old('video', $banner->video) }}" placeholder="…or paste a video URL (.mp4/.webm)" class="mt-3 w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                <p class="mt-1 text-xs text-slate-400">If a video is set it plays as the slide background (image is used as its poster). MP4/WEBM · max 30MB. Keep it short &amp; compressed.</p>

                @php $spd = old('video_speed', ($banner->video_speed === null || $banner->video_speed === '') ? '1.00' : number_format((float) $banner->video_speed, 2)); @endphp
                <div class="mt-3">
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Video speed <span class="font-normal text-slate-400">(playback rate)</span></label>
                    <div class="flex items-center gap-3">
                        <input type="range" name="video_speed" min="0.25" max="4" step="0.05" value="{{ $spd }}"
                               oninput="this.nextElementSibling.textContent = parseFloat(this.value).toFixed(2) + '×'"
                               class="h-2 w-full cursor-pointer accent-amber-500">
                        <span class="w-14 shrink-0 text-right text-sm font-semibold text-slate-200">{{ number_format((float) $spd, 2) }}×</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-400">1.00× = normal. Below 1 = slow motion, above 1 = faster. Only applies when a video is set.</p>
                </div>

                @php $fit = old('video_fit', $banner->video_fit ?: 'cover'); @endphp
                <div class="mt-3">
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Video fit</label>
                    <select name="video_fit" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                        <option value="cover" @selected($fit === 'cover')>Cover — fill the whole banner (may crop edges)</option>
                        <option value="contain" @selected($fit === 'contain')>Contain — show the full video (may show side bars)</option>
                    </select>
                    <p class="mt-1 text-xs text-slate-400">Cover looks best for wide/landscape videos. Use Contain if the video gets cut off.</p>
                </div>
            </div>

            <div class="flex flex-wrap items-end gap-6">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Sort order</label>
                    <input name="sort" type="number" min="0" value="{{ old('sort', $banner->sort ?? 0) }}" class="w-32 rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400">
                </div>
                <label class="flex items-center gap-2 text-sm font-medium text-slate-200">
                    <input type="checkbox" name="active" value="1" @checked(old('active', $banner->active ?? true)) class="rounded border-white/10 text-slate-200"> Active (visible on storefront)
                </label>
            </div>
        </div>
    </div>

    <div class="mt-6 flex items-center justify-end gap-3">
        <a href="{{ route('admin.banners.index') }}" class="rounded-lg border border-white/10 px-5 py-2.5 text-sm font-semibold text-slate-300 hover:bg-white/5">Cancel</a>
        <button class="rounded-lg bg-white px-6 py-2.5 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-200">{{ $banner->exists ? 'Update Slide' : 'Create Slide' }}</button>
    </div>
</form>

@endsection
