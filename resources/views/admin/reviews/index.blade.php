@extends('admin.layouts.app')

@section('title', 'Reviews')
@section('heading', 'Reviews')
@section('subheading', 'Moderate customer testimonials')

@section('content')

<div class="mb-5 flex flex-wrap items-center gap-3">
    <div class="flex flex-wrap gap-2">
        @foreach (['' => 'All', 'published' => 'Published', 'unpublished' => 'Hidden', 'featured' => 'Featured'] as $key => $label)
            <a href="{{ route('admin.reviews.index', array_filter([
                    'filter' => $key ?: null,
                    'rating' => request('rating'),
                    'q' => request('q'),
                ])) }}"
               class="rounded-full px-4 py-1.5 text-sm font-semibold transition {{ request('filter', '') === $key ? 'bg-white text-slate-900' : 'bg-slate-900/70 backdrop-blur border border-white/10 text-slate-300 ring-1 ring-white/10 hover:bg-white/5' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- by star rating; "1–2★" is every complaint in one go --}}
    <div class="flex flex-wrap gap-2">
        @foreach (['' => 'Any stars', 'low' => '1–2★ (bad)', '1' => '1★', '2' => '2★', '3' => '3★', '4' => '4★', '5' => '5★'] as $key => $label)
            <a href="{{ route('admin.reviews.index', array_filter([
                    'rating' => $key ?: null,
                    'filter' => request('filter'),
                    'q' => request('q'),
                ])) }}"
               class="rounded-full px-3 py-1.5 text-xs font-semibold transition {{ (string) request('rating', '') === $key ? 'bg-amber-400 text-slate-900' : 'bg-slate-900/70 backdrop-blur border border-white/10 text-slate-300 ring-1 ring-white/10 hover:bg-white/5' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <form method="GET" class="relative flex-1 sm:max-w-xs">
        @if (request('filter'))<input type="hidden" name="filter" value="{{ request('filter') }}">@endif
        @if (request('rating'))<input type="hidden" name="rating" value="{{ request('rating') }}">@endif
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search reviews…"
               class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 py-2 pl-9 pr-3 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
        <span class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400"><x-icon name="search" class="h-4 w-4" /></span>
    </form>
    @if (request('q') || request('rating'))
        <a href="{{ route('admin.reviews.index', request('filter') ? ['filter' => request('filter')] : []) }}" class="text-sm font-semibold text-slate-400 hover:text-white">Reset</a>
    @endif
    <a href="{{ route('admin.reviews.create') }}" class="ml-auto rounded-lg bg-white px-4 py-2 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-200">+ Add Review</a>
</div>

@if (request('q') || request('rating'))
    @php
        $what = collect([
            request('rating') ? (request('rating') === 'low' ? 'rated 1–2★' : 'rated '.request('rating').'★') : null,
            request('q') ? 'matching “'.request('q').'”' : null,
        ])->filter()->implode(' and ');
    @endphp
    <div class="mb-3 flex flex-wrap items-center gap-3">
        <p class="text-sm text-slate-400">{{ number_format($reviews->total()) }} review(s) {!! $what !!}</p>

        @if ($reviews->total() > 0 && \Illuminate\Support\Facades\Route::has('admin.reviews.destroy-matching'))
            <form method="POST" action="{{ route('admin.reviews.destroy-matching') }}"
                  onsubmit="return confirm('Delete all {{ number_format($reviews->total()) }} review(s) {{ strip_tags($what) }}? This cannot be undone.')">
                @csrf @method('DELETE')
                <input type="hidden" name="q" value="{{ request('q') }}">
                <input type="hidden" name="rating" value="{{ request('rating') }}">
                <input type="hidden" name="filter" value="{{ request('filter') }}">
                <button class="rounded-lg border border-rose-400/40 bg-rose-500/10 px-3 py-1.5 text-xs font-semibold text-rose-300 transition hover:bg-rose-500/20">
                    Delete all {{ number_format($reviews->total()) }} of these
                </button>
            </form>
        @endif
    </div>
@endif

<div data-bulk class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur shadow-sm">
    <div class="px-4 pt-4"><x-bulk-bar :action="route('admin.reviews.bulk-destroy')" /></div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-white/10 bg-white/5 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                <tr>
                    <th class="px-4 py-3 w-8"><input type="checkbox" data-bulk-all class="rounded border-white/20 bg-transparent"></th>
                    <x-th-sort column="author_name" label="Reviewer" />
                    <th class="px-4 py-3">Review</th>
                    <x-th-sort column="rating" label="Rating" />
                    <x-th-sort column="is_published" label="Status" />
                    <x-th-sort column="created_at" label="Date" />
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @forelse ($reviews as $review)
                    <tr class="hover:bg-white/5">
                        <td class="px-4 py-3"><input type="checkbox" name="ids[]" value="{{ $review->id }}" data-bulk-item class="rounded border-white/20 bg-transparent"></td>
                        <td class="px-4 py-3 align-top">
                            <p class="font-semibold text-slate-100">{{ $review->author_name }}
                                @if ($review->featured)<span class="ml-1 rounded bg-white/10 px-1.5 py-0.5 text-[10px] font-bold text-slate-200">FEATURED</span>@endif
                            </p>
                            <p class="text-xs text-slate-400">{{ $review->author_role }}{{ $review->product ? ' · '.\Illuminate\Support\Str::limit($review->product->name, 30) : ' · General' }}</p>
                        </td>
                        <td class="px-4 py-3 align-top max-w-md">
                            <p class="text-slate-300">“{{ \Illuminate\Support\Str::limit($review->body, 140) }}”</p>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <div class="flex text-slate-200">
                                @for ($i = 0; $i < $review->rating; $i++)<x-icon name="star" class="h-4 w-4 fill-slate-200" />@endfor
                            </div>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $review->is_published ? 'bg-emerald-500/15 text-emerald-300' : 'bg-white/5 text-slate-400' }}">
                                {{ $review->is_published ? 'Published' : 'Hidden' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 align-top text-xs text-slate-400 whitespace-nowrap">{{ $review->created_at?->format('M j, Y') }}</td>
                        <td class="px-4 py-3 align-top">
                            <div class="flex items-center justify-end gap-2">
                                <form method="POST" action="{{ route('admin.reviews.toggle', $review) }}">
                                    @csrf @method('PATCH')
                                    <button class="rounded-lg border border-white/10 px-3 py-1.5 text-xs font-semibold text-slate-300 hover:border-white/15 hover:text-white">{{ $review->is_published ? 'Hide' : 'Publish' }}</button>
                                </form>
                                <a href="{{ route('admin.reviews.edit', $review) }}" class="rounded-lg border border-white/10 px-3 py-1.5 text-xs font-semibold text-slate-300 hover:border-white/15 hover:text-white">Edit</a>
                                <form method="POST" action="{{ route('admin.reviews.destroy', $review) }}" onsubmit="return confirm('Delete this review?')">
                                    @csrf @method('DELETE')
                                    <button class="rounded-lg border border-white/10 px-2.5 py-1.5 text-xs font-semibold text-rose-300 hover:border-rose-400/30 hover:bg-rose-500/15" aria-label="Delete">&times;</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-slate-400">No reviews found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6">{{ $reviews->links('pagination.admin') }}</div>

@endsection
