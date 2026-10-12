{{-- Shared page header: $eyebrow, $title, $subtitle --}}
<section class="border-b border-slate-200 bg-slate-50">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
        <nav class="flex items-center gap-2 text-sm text-slate-500">
            <a href="{{ route('home') }}" class="hover:text-amber-500">Home</a>
            <span class="text-slate-300">/</span>
            <span class="font-semibold text-slate-700">{{ $title }}</span>
        </nav>
        @isset($eyebrow)
            <p class="mt-4 text-xs font-bold uppercase tracking-widest text-amber-500">{{ $eyebrow }}</p>
        @endisset
        <h1 class="mt-1 text-3xl font-black tracking-tight text-slate-900 sm:text-4xl">{{ $title }}</h1>
        @isset($subtitle)
            <p class="mt-3 max-w-2xl text-slate-500">{{ $subtitle }}</p>
        @endisset
    </div>
</section>
