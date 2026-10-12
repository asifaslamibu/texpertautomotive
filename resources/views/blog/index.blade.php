@extends('layouts.app')

@section('title', 'HVAC News, Tips & Pro Contractor Guides · '.\App\Models\Setting::get('site_name', 'Texpert Deals'))
@section('meta_description', 'Professional HVAC guides, tool comparisons, troubleshooting tips and equipment manuals for Goodman, Fieldpiece, Appion, Yellow Jacket and more from '.\App\Models\Setting::get('site_name', 'Texpert Deals').'.')

@section('content')

<div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">

    {{-- Breadcrumb --}}
    <nav class="mb-6 flex flex-wrap items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('home') }}" class="hover:text-amber-600 transition">Home</a>
        <span class="text-slate-300">&gt;</span>
        <span class="font-medium text-slate-700">News &amp; Technical Guides</span>
    </nav>

    {{-- Header Banner --}}
    <div class="mb-12 rounded-3xl bg-gradient-to-br from-slate-900 via-slate-800 to-amber-950 p-8 sm:p-12 text-white shadow-xl">
        <div class="max-w-3xl">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-500/20 px-3 py-1 text-xs font-bold uppercase tracking-wider text-amber-400 border border-amber-400/30">
                Contractor Field Insights
            </span>
            <h1 class="mt-4 text-3xl font-black tracking-tight sm:text-5xl leading-tight">
                HVAC Tech Guides, Tool Reviews &amp; Industry News
            </h1>
            <p class="mt-4 text-base text-slate-300 leading-relaxed">
                Field-tested diagnostics, A2L refrigerant compliance, vacuum procedures, and in-depth equipment comparisons for Goodman, Fieldpiece, Appion, Yellow Jacket, and top HVAC brands.
            </p>
        </div>
    </div>

    {{-- Post Cards Grid --}}
    @if ($posts->count())
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($posts as $post)
                @php
                    $detectedBrand = null;
                    $brandKeywords = ['Goodman', 'Fieldpiece', 'Appion', 'Yellow Jacket', 'NAVAC', 'Nu-Calgon', 'Copeland', 'SUPCO', 'ICM', 'Honeywell', 'Zebra'];
                    foreach ($brandKeywords as $bk) {
                        if (stripos($post->title, $bk) !== false) {
                            $detectedBrand = $bk;
                            break;
                        }
                    }
                @endphp
                <article class="group flex flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white transition-all duration-300 hover:-translate-y-1.5 hover:border-amber-300 hover:shadow-2xl hover:shadow-slate-200">
                    <a href="{{ route('blog.show', $post) }}" class="relative block aspect-[16/9] overflow-hidden bg-slate-100">
                        @if ($post->image)
                            <img src="{{ media($post->image) }}" alt="{{ $post->title }}" loading="lazy" decoding="async" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                        @else
                            <span class="grid h-full w-full place-items-center text-slate-300"><x-icon name="edit" class="h-10 w-10" /></span>
                        @endif

                        @if ($detectedBrand)
                            <span class="absolute top-3 left-3 rounded-md bg-slate-900/85 backdrop-blur-sm px-2.5 py-1 text-[11px] font-black uppercase tracking-wider text-amber-400 border border-white/10 shadow-sm">
                                {{ $detectedBrand }}
                            </span>
                        @endif
                    </a>

                    <div class="flex flex-1 flex-col p-6">
                        <div class="flex items-center justify-between gap-2 text-xs font-semibold text-slate-400">
                            <time datetime="{{ optional($post->date)->toIso8601String() }}">
                                {{ optional($post->date)->format('M d, Y') }}
                            </time>
                            <span class="rounded-full bg-amber-50 px-2.5 py-0.5 text-[11px] font-bold text-amber-700">
                                {{ $post->readMinutes() }} min read
                            </span>
                        </div>

                        <a href="{{ route('blog.show', $post) }}" class="mt-3 line-clamp-2 text-lg font-bold leading-snug text-slate-900 transition group-hover:text-amber-600">
                            {{ $post->title }}
                        </a>

                        @if ($post->excerpt)
                            <p class="mt-2.5 line-clamp-3 text-xs leading-relaxed text-slate-500">
                                {{ $post->excerpt }}
                            </p>
                        @endif

                        <div class="mt-auto pt-6 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-[11px] font-bold text-slate-400">By {{ $post->author ?: 'Texpert Deals' }}</span>
                            <a href="{{ route('blog.show', $post) }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-amber-600 transition group-hover:text-amber-700">
                                Read Guide <x-icon name="arrow" class="h-3.5 w-3.5" />
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-12">{{ $posts->links() }}</div>
    @else
        <div class="rounded-3xl border border-slate-200 bg-white p-16 text-center">
            <x-icon name="edit" class="mx-auto h-12 w-12 text-slate-300" />
            <h3 class="mt-4 text-base font-bold text-slate-800">No articles published yet</h3>
            <p class="mt-1 text-xs text-slate-500">Please check back soon for our latest HVAC field guides and equipment reviews.</p>
        </div>
    @endif

</div>

@endsection
