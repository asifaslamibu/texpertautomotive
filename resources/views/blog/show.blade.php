@extends('layouts.app')

@section('title', ($blog->meta_title ?: $blog->title).' · '.\App\Models\Setting::get('site_name', 'Texpert Deals'))
@section('meta_description', $blog->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($blog->excerpt ?: $blog->body), 155))

@push('head')
@php
    $articleLd = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $blog->title,
        'image' => $blog->image ? media($blog->image) : null,
        'datePublished' => optional($blog->date)->toIso8601String(),
        'dateModified' => optional($blog->updated_at)->toIso8601String(),
        'author' => ['@type' => 'Organization', 'name' => $blog->author ?: \App\Models\Setting::get('site_name', 'Texpert Deals')],
        'publisher' => ['@type' => 'Organization', 'name' => \App\Models\Setting::get('site_name', 'Texpert Deals')],
        'mainEntityOfPage' => url()->current(),
        'description' => $blog->excerpt ? strip_tags($blog->excerpt) : null,
    ], fn ($v) => $v !== null && $v !== '');
@endphp
<script type="application/ld+json">{!! json_encode($articleLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')

<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Breadcrumb --}}
    <nav class="mb-4 flex flex-wrap items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('home') }}" class="hover:text-amber-600 transition">Home</a>
        <span class="text-slate-300">&gt;</span>
        <a href="{{ route('blog.index') }}" class="hover:text-amber-600 transition">News</a>
        <span class="text-slate-300">&gt;</span>
        <span class="font-medium text-slate-700">{{ \Illuminate\Support\Str::limit($blog->title, 55) }}</span>
    </nav>

    {{-- Top Page Title (News) --}}
    <div class="mb-8 border-b border-slate-200 pb-4">
        <h1 class="text-3xl font-black tracking-tight text-slate-900 sm:text-4xl">News</h1>
    </div>

    {{-- Main 2-Column Layout (Matching Screenshot) --}}
    <div class="flex flex-col gap-10 lg:flex-row lg:items-start">

        {{-- Left Sidebar: Recent Posts + Featured Products --}}
        <aside class="w-full lg:w-72 xl:w-80 shrink-0 space-y-8">

            {{-- Recent Posts Widget --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="border-b border-slate-100 pb-3 text-xs font-black uppercase tracking-wider text-slate-900">
                    Recent Post
                </h3>
                <div class="mt-4 divide-y divide-slate-100">
                    @forelse ($recentPosts as $post)
                        <article class="py-3.5 first:pt-0 last:pb-0">
                            <a href="{{ route('blog.show', $post) }}" class="group block">
                                <h4 class="text-xs font-bold leading-snug text-slate-800 transition group-hover:text-amber-600">
                                    {{ $post->title }}
                                </h4>
                                <p class="mt-1 text-[11px] font-medium text-slate-400">
                                    {{ optional($post->date)->format('d F Y') }}
                                </p>
                            </a>
                        </article>
                    @empty
                        <p class="text-xs text-slate-400">No other recent posts.</p>
                    @endforelse
                </div>
            </div>

            {{-- Featured Products Widget --}}
            @if ($featuredProducts->isNotEmpty())
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="border-b border-slate-100 pb-3 text-xs font-black uppercase tracking-wider text-slate-900">
                        Featured Products
                    </h3>
                    <div class="mt-4 space-y-5">
                        @foreach ($featuredProducts as $item)
                            <div class="group relative rounded-xl border border-slate-100 bg-slate-50/50 p-3.5 transition hover:border-amber-200 hover:bg-white hover:shadow-md">
                                
                                {{-- Brand Tag / Badge --}}
                                @if ($item->brand)
                                    <div class="mb-2 inline-flex items-center rounded bg-red-600 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-white">
                                        {{ $item->brand }}
                                    </div>
                                @endif

                                {{-- Product Image Thumbnail --}}
                                <a href="{{ route('products.show', $item) }}" class="block aspect-square w-full overflow-hidden rounded-lg bg-white p-2">
                                    <img src="{{ media($item->image) }}" alt="{{ $item->name }}" loading="lazy" class="h-full w-full object-contain transition duration-300 group-hover:scale-105">
                                </a>

                                {{-- Details --}}
                                <div class="mt-3">
                                    @if ($item->brand)
                                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $item->brand }}</p>
                                    @endif
                                    
                                    <a href="{{ route('products.show', $item) }}" class="mt-0.5 line-clamp-2 text-xs font-bold leading-snug text-blue-700 transition hover:text-amber-600 hover:underline">
                                        {{ $item->name }}
                                    </a>

                                    @if ($item->sku)
                                        <p class="mt-1 text-[11px] text-slate-400">SKU: <span class="font-mono text-slate-600">{{ $item->sku }}</span></p>
                                    @endif

                                    <p class="mt-1.5 text-sm font-black text-slate-900">
                                        ${{ number_format($item->price, 2) }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Contractor Store CTA Box --}}
            <div class="rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-500 to-amber-600 p-6 text-white shadow-md">
                <span class="inline-flex rounded-full bg-white/20 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wider text-white">Pro HVAC Supplies</span>
                <h4 class="mt-3 text-lg font-black leading-snug">Need Tools or OEM Replacement Parts?</h4>
                <p class="mt-1.5 text-xs text-amber-100 leading-relaxed">Genuine Appion, Fieldpiece, Yellow Jacket &amp; Goodman units in stock with fast shipping.</p>
                <a href="{{ route('products.index') }}" class="mt-4 inline-flex items-center gap-1.5 rounded-xl bg-slate-900 px-4 py-2.5 text-xs font-bold text-white shadow transition hover:bg-black">
                    Browse All Products <x-icon name="arrow" class="h-3.5 w-3.5" />
                </a>
            </div>

        </aside>

        {{-- Right Column: Full Blog Article Content --}}
        <main class="flex-1 min-w-0">

            <article class="rounded-3xl border border-slate-200 bg-white p-6 sm:p-9 shadow-sm">

                {{-- Post Header --}}
                <header class="border-b border-slate-100 pb-6">
                    <h1 class="text-2xl font-black leading-tight tracking-tight text-slate-900 sm:text-3xl lg:text-4xl">
                        {{ $blog->title }}
                    </h1>
                    <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                        <span>By <strong class="font-bold text-slate-700">{{ $blog->author ?: 'Texpert Deals Editorial' }}</strong></span>
                        <span class="text-slate-300">•</span>
                        <time datetime="{{ optional($blog->date)->toIso8601String() }}" class="font-medium text-slate-600">
                            {{ optional($blog->date)->format('d F Y') }}
                        </time>
                        <span class="text-slate-300">•</span>
                        <span class="rounded-full bg-amber-50 px-2.5 py-0.5 font-bold text-amber-700">{{ $blog->readMinutes() }} min read</span>
                    </div>
                </header>

                {{-- Hero Image --}}
                @if ($blog->image)
                    <div class="mt-6 overflow-hidden rounded-2xl bg-slate-100 shadow-sm border border-slate-100">
                        <img src="{{ media($blog->image) }}" alt="{{ $blog->title }}" class="aspect-[16/9] w-full object-cover" fetchpriority="high">
                    </div>
                @endif

                {{-- Article Rich Body --}}
                @php $body = trim((string) $blog->body); @endphp
                <div class="blog-body mt-8">
                    @if (\Illuminate\Support\Str::contains($body, ['<p', '<div', '<br', '<ul', '<ol', '<h1', '<h2', '<h3', '<img', '<a ', '<blockquote', '<table']))
                        {!! $body !!}
                    @else
                        {!! nl2br(e($body)) !!}
                    @endif
                </div>

                {{-- Article Footer / Navigation --}}
                <div class="mt-12 flex flex-wrap items-center justify-between gap-4 border-t border-slate-200 pt-6">
                    <a href="{{ route('blog.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-bold text-slate-700 transition hover:border-amber-300 hover:bg-amber-50 hover:text-amber-700">
                        <x-icon name="arrow" class="h-3.5 w-3.5 rotate-180" /> All News &amp; Articles
                    </a>
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <span class="font-bold">Share:</span>
                        <a href="https://twitter.com/intent/tweet?text={{ urlencode($blog->title) }}&url={{ urlencode(url()->current()) }}" target="_blank" rel="noopener" class="rounded-lg border border-slate-200 px-2.5 py-1 font-semibold text-slate-600 hover:border-amber-300 hover:text-amber-600">Twitter / X</a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(url()->current()) }}" target="_blank" rel="noopener" class="rounded-lg border border-slate-200 px-2.5 py-1 font-semibold text-slate-600 hover:border-amber-300 hover:text-amber-600">LinkedIn</a>
                    </div>
                </div>

            </article>

            {{-- Keep Reading / Related Posts Grid --}}
            @if ($related->count())
                <section class="mt-12">
                    <h3 class="text-xl font-black text-slate-900">Keep Reading</h3>
                    <div class="mt-5 grid gap-5 sm:grid-cols-3">
                        @foreach ($related as $post)
                            <a href="{{ route('blog.show', $post) }}" class="group block rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm transition duration-300 hover:-translate-y-1 hover:border-amber-200 hover:shadow-lg">
                                <span class="block aspect-[16/10] overflow-hidden rounded-xl bg-slate-100">
                                    @if ($post->image)
                                        <img src="{{ media($post->image) }}" alt="{{ $post->title }}" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                    @else
                                        <span class="grid h-full w-full place-items-center text-slate-300"><x-icon name="edit" class="h-8 w-8" /></span>
                                    @endif
                                </span>
                                <span class="mt-3 block text-xs font-bold leading-snug text-slate-800 transition group-hover:text-amber-600">{{ $post->title }}</span>
                                <span class="mt-1 block text-[11px] text-slate-400">{{ optional($post->date)->format('M j, Y') }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

        </main>

    </div>

</div>

@push('head')
<style>
    .blog-body { color: #334155; font-size: 1.05rem; line-height: 1.85; }
    .blog-body > * + * { margin-top: 1.25rem; }
    .blog-body h2 { font-family: inherit; font-size: 1.45rem; font-weight: 800; color: #0f172a; margin-top: 2.2rem; margin-bottom: 0.75rem; line-height: 1.3; }
    .blog-body h3 { font-family: inherit; font-size: 1.2rem; font-weight: 700; color: #0f172a; margin-top: 1.8rem; margin-bottom: 0.5rem; }
    .blog-body p { margin-top: 1.15rem; color: #334155; }
    .blog-body a { color: #d97706; font-weight: 600; text-decoration: underline; text-underline-offset: 3px; }
    .blog-body a:hover { color: #b45309; }
    .blog-body ul, .blog-body ol { margin-top: 1.15rem; padding-left: 1.5rem; }
    .blog-body ul { list-style: disc; }
    .blog-body ol { list-style: decimal; }
    .blog-body li { margin-top: 0.5rem; }
    .blog-body img { border-radius: 1rem; margin-top: 1.75rem; margin-bottom: 1rem; max-width: 100%; height: auto; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08); border: 1px solid #f1f5f9; }
    .blog-body blockquote { border-left: 4px solid #f59e0b; background: #fffbeb; padding: 1rem 1.4rem; border-radius: 0.6rem; color: #475569; font-style: italic; margin-top: 1.5rem; margin-bottom: 1.5rem; }
    .blog-body strong { color: #0f172a; font-weight: 700; }
    .blog-body table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; margin-bottom: 1.5rem; font-size: 0.95rem; }
    .blog-body th { background: #f8fafc; color: #0f172a; font-weight: 700; text-align: left; padding: 0.75rem 1rem; border: 1px solid #e2e8f0; }
    .blog-body td { padding: 0.75rem 1rem; border: 1px solid #e2e8f0; color: #475569; }
    .blog-body tr:nth-child(even) { background: #f8fafc; }
    .blog-body .product-callout { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 1rem; padding: 1.25rem; margin-top: 1.75rem; margin-bottom: 1.75rem; }
</style>
@endpush

@endsection
