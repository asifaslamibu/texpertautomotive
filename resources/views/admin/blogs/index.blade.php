@extends('admin.layouts.app')

@section('title', 'Blog')
@section('heading', 'Blog')
@section('subheading', 'Write and manage articles shown on the public /blog page')

@section('content')

<div class="mb-5 flex flex-wrap items-center gap-3">
    <p class="text-sm text-slate-400">{{ $blogs->total() }} post(s)</p>
    <a href="{{ route('blog.index') }}" target="_blank" class="text-sm font-semibold text-slate-200 hover:text-white">View blog →</a>
    <a href="{{ route('admin.blogs.create') }}" class="ml-auto rounded-lg bg-white px-4 py-2 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-200">+ New Post</a>
</div>

<div class="space-y-3">
    @forelse ($blogs as $blog)
        <div class="flex items-center gap-4 rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-3 shadow-sm {{ $blog->published ? '' : 'opacity-60' }}">
            <div class="hidden h-16 w-24 shrink-0 overflow-hidden rounded-xl bg-slate-950 sm:block">
                @if ($blog->image)
                    <img src="{{ media($blog->image) }}" alt="" class="h-full w-full object-cover">
                @else
                    <div class="grid h-full w-full place-items-center text-slate-600"><x-icon name="edit" class="h-6 w-6" /></div>
                @endif
            </div>

            <div class="min-w-0 flex-1">
                <p class="truncate font-bold text-slate-100">{{ $blog->title }}</p>
                <p class="truncate text-xs text-slate-400">
                    {{ optional($blog->date)->format('M j, Y') }}
                    @if ($blog->author) · {{ $blog->author }} @endif
                    · /{{ $blog->slug }}
                </p>
            </div>

            <form method="POST" action="{{ route('admin.blogs.toggle', $blog) }}">
                @csrf @method('PATCH')
                <button class="rounded-full border px-3 py-1 text-[11px] font-bold {{ $blog->published ? 'border-emerald-400/30 bg-emerald-500/15 text-emerald-300' : 'border-white/10 bg-white/5 text-slate-400' }}">{{ $blog->published ? 'Published' : 'Draft' }}</button>
            </form>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.blogs.edit', $blog) }}" class="rounded-lg border border-white/10 px-3 py-1.5 text-xs font-semibold text-slate-300 hover:border-white/15 hover:text-white">Edit</a>
                <form method="POST" action="{{ route('admin.blogs.destroy', $blog) }}" onsubmit="return confirm('Delete this post?')">
                    @csrf @method('DELETE')
                    <button class="rounded-lg border border-white/10 px-2.5 py-1.5 text-xs font-semibold text-rose-300 hover:border-rose-400/30 hover:bg-rose-500/15" aria-label="Delete">&times;</button>
                </form>
            </div>
        </div>
    @empty
        <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-10 text-center text-sm text-slate-400">
            No posts yet. <a href="{{ route('admin.blogs.create') }}" class="font-semibold text-slate-200">Write your first post</a>.
        </div>
    @endforelse
</div>

<div class="mt-6">{{ $blogs->links() }}</div>

@endsection
