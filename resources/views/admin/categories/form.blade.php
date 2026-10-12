@extends('admin.layouts.app')

@section('title', $category->exists ? 'Edit Category' : 'New Category')
@section('heading', $category->exists ? 'Edit Category' : 'New Category')

@section('content')

<form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" enctype="multipart/form-data" class="mx-auto max-w-2xl">
    @csrf
    @if ($category->exists) @method('PUT') @endif

    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <div class="space-y-4">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Name *</label>
                <input name="name" value="{{ old('name', $category->name) }}" required class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Tagline</label>
                <input name="tagline" value="{{ old('tagline', $category->tagline) }}" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Parent category</label>
                <select name="parent_id" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400">
                    <option value="">— None (top-level category) —</option>
                    @foreach ($parents as $p)
                        <option value="{{ $p->id }}" @selected(old('parent_id', $category->parent_id)==$p->id)>{{ $p->label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-400">Choose any category as the parent — nest as deep as you like (Main › Sub › Sub-sub …).</p>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-200">Icon *</label>
                <div class="grid grid-cols-5 gap-2 sm:grid-cols-9">
                    @foreach ($icons as $ic)
                        <label class="cursor-pointer">
                            <input type="radio" name="icon" value="{{ $ic }}" class="peer sr-only" @checked(old('icon', $category->icon)==$ic)>
                            <span class="grid aspect-square place-items-center rounded-xl border border-white/10 text-slate-400 transition peer-checked:border-slate-400 peer-checked:bg-white/10 peer-checked:text-slate-200 hover:bg-white/5">
                                <x-icon name="{{ $ic }}" class="h-6 w-6" />
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- department image (shown in homepage "Shop by Department") --}}
            <div data-image-field>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Image <span class="font-normal text-slate-400">(used in “Shop by Department”; falls back to the icon)</span></label>
                <div class="flex items-center gap-4">
                    <span class="grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-xl border border-white/10 bg-white p-1">
                        <img data-image-preview src="{{ $category->image ? media($category->image) : '' }}" class="{{ $category->image ? '' : 'hidden' }} max-h-full max-w-full object-contain" alt="">
                    </span>
                    <input type="file" name="image_file" accept="image/*" data-image-input
                           class="block w-full text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-200 hover:file:bg-white/20">
                </div>
                <input name="image" value="{{ old('image', $category->image) }}" placeholder="…or paste an image URL" class="mt-3 w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                <p class="mt-1 text-xs text-slate-400">Transparent PNG recommended (like your product-style icons).</p>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Sort order</label>
                <input name="sort" type="number" min="0" value="{{ old('sort', $category->sort) }}" class="w-40 rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400">
            </div>

            <label class="flex items-center gap-2 text-sm font-medium text-slate-200">
                <input type="checkbox" name="featured" value="1" @checked(old('featured', $category->featured)) class="rounded border-white/10 text-slate-200"> Show in storefront navigation
            </label>

            <label class="mt-3 flex items-center gap-2 text-sm font-medium text-amber-300">
                <input type="checkbox" name="hidden" value="1" @checked(old('hidden', $category->hidden)) class="rounded border-white/10 text-amber-400"> Hide from storefront
            </label>
            <p class="mt-1 pl-6 text-xs text-slate-400">Hides this category (and everything under it) from the menu, filters &amp; departments — and its products stop showing in category browsing.</p>
        </div>
    </div>

    <div class="mt-6 flex items-center justify-end gap-3">
        <a href="{{ route('admin.categories.index') }}" class="rounded-lg border border-white/10 px-5 py-2.5 text-sm font-semibold text-slate-300 hover:bg-white/5">Cancel</a>
        <button class="rounded-lg bg-white px-6 py-2.5 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-200">
            {{ $category->exists ? 'Update Category' : 'Create Category' }}
        </button>
    </div>
</form>

@endsection
