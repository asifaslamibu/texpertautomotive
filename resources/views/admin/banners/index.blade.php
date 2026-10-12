@extends('admin.layouts.app')

@section('title', 'Hero Slider')
@section('heading', 'Hero Slider')
@section('subheading', 'Slides shown in the big homepage hero slider — heading, button & background image')

@section('content')

<div class="mb-5 flex flex-wrap items-center gap-3">
    <p class="text-sm text-slate-400">{{ $banners->count() }} slide(s)</p>
    <a href="{{ route('home') }}" target="_blank" class="text-sm font-semibold text-slate-200 hover:text-white">View on storefront →</a>
    <a href="{{ route('admin.banners.create') }}" class="ml-auto rounded-lg bg-white px-4 py-2 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-200">+ Add Slide</a>
</div>

<div class="space-y-3">
    @forelse ($banners as $i => $banner)
        <div class="flex items-center gap-4 rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-3 shadow-sm {{ $banner->active ? '' : 'opacity-60' }}">
            <div class="flex flex-col">
                <form method="POST" action="{{ route('admin.banners.move', [$banner, 'up']) }}">
                    @csrf @method('PATCH')
                    <button @disabled($i === 0) class="grid h-6 w-6 place-items-center rounded text-slate-400 hover:bg-white/5 hover:text-slate-200 disabled:opacity-30" aria-label="Move up"><x-icon name="chevron" class="h-4 w-4 rotate-180" /></button>
                </form>
                <form method="POST" action="{{ route('admin.banners.move', [$banner, 'down']) }}">
                    @csrf @method('PATCH')
                    <button @disabled($loop->last) class="grid h-6 w-6 place-items-center rounded text-slate-400 hover:bg-white/5 hover:text-slate-200 disabled:opacity-30" aria-label="Move down"><x-icon name="chevron" class="h-4 w-4" /></button>
                </form>
            </div>

            {{-- preview --}}
            <div class="relative hidden h-20 w-40 shrink-0 overflow-hidden rounded-xl bg-gradient-to-r from-black via-slate-900 to-slate-800 sm:block">
                @if ($banner->image)
                    <img src="{{ media($banner->image) }}" alt="" class="absolute inset-0 h-full w-full object-cover">
                    <div class="absolute inset-0 bg-slate-950/50"></div>
                @endif
                <div class="relative flex h-full flex-col justify-center px-3">
                    <span class="truncate text-[9px] font-bold uppercase text-white/80">{{ $banner->eyebrow }}</span>
                    <span class="truncate text-xs font-black uppercase text-slate-200">{{ $banner->title }}</span>
                    <span class="truncate text-[10px] font-bold uppercase text-white">{{ $banner->subtitle }}</span>
                </div>
            </div>

            <div class="min-w-0 flex-1">
                <p class="truncate font-bold text-slate-100">{{ $banner->title }}</p>
                <p class="truncate text-xs text-slate-400">{{ $banner->eyebrow }} · {{ $banner->subtitle }}</p>
                @if ($banner->cta_label)<p class="mt-0.5 text-xs text-slate-400">Button: <span class="font-semibold text-slate-300">{{ $banner->cta_label }}</span></p>@endif
            </div>

            <form method="POST" action="{{ route('admin.banners.toggle', $banner) }}">
                @csrf @method('PATCH')
                <button class="rounded-full border px-3 py-1 text-[11px] font-bold {{ $banner->active ? 'border-emerald-400/30 bg-emerald-500/15 text-emerald-300' : 'border-white/10 bg-white/5 text-slate-400' }}">{{ $banner->active ? 'Active' : 'Hidden' }}</button>
            </form>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.banners.edit', $banner) }}" class="rounded-lg border border-white/10 px-3 py-1.5 text-xs font-semibold text-slate-300 hover:border-white/15 hover:text-white">Edit</a>
                 <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" onsubmit="return confirm('Delete this slide?')">
                    @csrf @method('DELETE')
                    <button class="rounded-lg border border-white/10 px-2.5 py-1.5 text-xs font-semibold text-rose-300 hover:border-rose-400/30 hover:bg-rose-500/15" aria-label="Delete">&times;</button>
                </form>
            </div>
        </div>
    @empty
        <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-10 text-center text-sm text-slate-400">
            No slides yet. <a href="{{ route('admin.banners.create') }}" class="font-semibold text-slate-200">Add your first slide</a>.
        </div>
    @endforelse
</div>

@endsection
