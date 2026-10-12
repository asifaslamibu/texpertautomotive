@extends('admin.layouts.app')

@section('title', $review->exists ? 'Edit Review' : 'New Review')
@section('heading', $review->exists ? 'Edit Review' : 'New Review')

@section('content')

<form method="POST" action="{{ $review->exists ? route('admin.reviews.update', $review) : route('admin.reviews.store') }}" class="mx-auto max-w-2xl">
    @csrf
    @if ($review->exists) @method('PUT') @endif

    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <div class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Author name *</label>
                    <input name="author_name" value="{{ old('author_name', $review->author_name) }}" required class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Author role</label>
                    <input name="author_role" value="{{ old('author_role', $review->author_role) }}" placeholder="Contractor, Homeowner…" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Rating *</label>
                    <select name="rating" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400">
                        @for ($i = 5; $i >= 1; $i--)
                            <option value="{{ $i }}" @selected(old('rating', $review->rating)==$i)>{{ $i }} star{{ $i > 1 ? 's' : '' }}</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Related product</label>
                    <select name="product_id" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400">
                        <option value="">General testimonial</option>
                        @foreach ($products as $p)
                            <option value="{{ $p->id }}" @selected(old('product_id', $review->product_id)==$p->id)>{{ \Illuminate\Support\Str::limit($p->name, 50) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Review body *</label>
                <textarea name="body" rows="4" required class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">{{ old('body', $review->body) }}</textarea>
            </div>

            <div class="flex flex-wrap gap-6">
                <label class="flex items-center gap-2 text-sm font-medium text-slate-200">
                    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $review->is_published)) class="rounded border-white/10 text-slate-200 checked:bg-white checked:border-white"> Published
                </label>
                <label class="flex items-center gap-2 text-sm font-medium text-slate-200">
                    <input type="checkbox" name="featured" value="1" @checked(old('featured', $review->featured)) class="rounded border-white/10 text-slate-200 checked:bg-white checked:border-white"> Feature on homepage
                </label>
            </div>
        </div>
    </div>

    <div class="mt-6 flex items-center justify-end gap-3">
        <a href="{{ route('admin.reviews.index') }}" class="rounded-lg border border-white/10 px-5 py-2.5 text-sm font-semibold text-slate-300 hover:bg-white/5">Cancel</a>
        <button class="rounded-lg bg-white px-6 py-2.5 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-200">{{ $review->exists ? 'Update Review' : 'Create Review' }}</button>
    </div>
</form>

@endsection
