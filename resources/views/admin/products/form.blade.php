@extends('admin.layouts.app')

@section('title', $product->exists ? 'Edit Product' : 'New Product')
@section('heading', $product->exists ? 'Edit Product' : 'New Product')
@section('subheading', $product->exists ? $product->name : 'Add a product to your catalog')

@section('content')

{{-- breadcrumb --}}
<nav class="mb-5 flex items-center gap-2 text-sm text-slate-400">
    <a href="{{ route('admin.products.index') }}" class="hover:text-white">Products</a>
    <span class="text-slate-300">/</span>
    <span class="font-semibold text-slate-200">{{ $product->exists ? 'Edit' : 'New' }}</span>
</nav>

{{-- validation error summary --}}
@if ($errors->any())
    <div class="mb-5 rounded-xl border border-rose-400/30 bg-rose-500/15 p-4">
        <p class="text-sm font-bold text-rose-300">Please fix the following:</p>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-300">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $field = 'w-full rounded-lg border bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none transition focus:ring-2 focus:ring-slate-500/30';
    $ok = 'border-white/10 focus:border-slate-400';
    $bad = 'border-rose-400 focus:border-rose-400 focus:ring-rose-100';
@endphp

<form method="POST" action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" enctype="multipart/form-data" class="mx-auto max-w-5xl">
    @csrf
    @if ($product->exists) @method('PUT') @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- ===== main column ===== --}}
        <div class="space-y-6 lg:col-span-2">

            {{-- details --}}
            <section class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
                <h2 class="font-bold text-white">Product details</h2>
                <p class="mb-4 text-xs text-slate-400">Basic information shown on the storefront.</p>
                <div class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-200">Product name <span class="text-rose-500">*</span></label>
                        <input name="name" value="{{ old('name', $product->name) }}" required
                               class="{{ $field }} {{ $errors->has('name') ? $bad : $ok }}">
                        @error('name')<p class="mt-1 text-xs text-rose-300">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-200">Brand</label>
                            <input name="brand" value="{{ old('brand', $product->brand) }}" list="brand-list"
                                   class="{{ $field }} {{ $errors->has('brand') ? $bad : $ok }}">
                            <datalist id="brand-list">
                                @foreach (\App\Models\Brand::orderBy('name')->pluck('name') as $b)
                                    <option value="{{ $b }}"></option>
                                @endforeach
                            </datalist>
                            @error('brand')<p class="mt-1 text-xs text-rose-300">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-200">SKU</label>
                            <input name="sku" value="{{ old('sku', $product->sku) }}"
                                   class="{{ $field }} {{ $errors->has('sku') ? $bad : $ok }}">
                            @error('sku')<p class="mt-1 text-xs text-rose-300">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    {{-- Badge --}}
                    @php
                        $tagMode  = old('tag_mode', $product->tag_mode ?: 'inherit');
                        $brandTag = \App\Models\Brand::tagFor(old('brand', $product->brand));
                    @endphp
                    <div data-badge-control class="rounded-xl border border-white/10 bg-slate-950/30 p-4">
                        <label class="mb-2 block text-sm font-medium text-slate-200">Badge</label>

                        <div class="space-y-2">
                            <label class="flex items-start gap-2.5 text-sm text-slate-300">
                                <input type="radio" name="tag_mode" value="inherit" @checked($tagMode === 'inherit')
                                       class="mt-0.5 border-white/10 text-slate-200">
                                <span>
                                    Use the brand's badge
                                    <span class="ml-1 text-xs text-slate-500">
                                        @if ($brandTag)(currently “{{ $brandTag }}”)@else(this brand has no badge set)@endif
                                    </span>
                                </span>
                            </label>

                            <label class="flex items-start gap-2.5 text-sm text-slate-300">
                                <input type="radio" name="tag_mode" value="custom" @checked($tagMode === 'custom')
                                       class="mt-0.5 border-white/10 text-slate-200">
                                <span>Custom badge for this product only</span>
                            </label>

                            <div class="pl-7">
                                <input name="tag" value="{{ old('tag', $product->tag) }}" maxlength="60"
                                       placeholder="e.g. Ships from manufacturer"
                                       class="{{ $field }} {{ $errors->has('tag') ? $bad : $ok }}">
                                @error('tag')<p class="mt-1 text-xs text-rose-300">{{ $message }}</p>@enderror
                            </div>

                            <label class="flex items-start gap-2.5 text-sm text-slate-300">
                                <input type="radio" name="tag_mode" value="none" @checked($tagMode === 'none')
                                       class="mt-0.5 border-white/10 text-slate-200">
                                <span>No badge on this product</span>
                            </label>
                        </div>

                        <div class="mt-4 border-t border-white/10 pt-3">
                            <span class="mb-1.5 block text-xs text-slate-500">Preview</span>
                            <span data-badge-preview-wrap>
                                <x-brand-tag :tag="$product->badge ?: ($brandTag ?: 'Ships from manufacturer')" data-badge-preview />
                            </span>
                            <span data-badge-preview-empty class="hidden text-xs italic text-slate-500">No badge shown</span>
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-200">Short description</label>
                        <textarea name="short_description" rows="2" maxlength="500"
                                  class="{{ $field }} {{ $errors->has('short_description') ? $bad : $ok }}">{{ old('short_description', $product->short_description) }}</textarea>
                        <p class="mt-1 text-xs text-slate-400">A one-line summary (max 500 characters).</p>
                        @error('short_description')<p class="mt-1 text-xs text-rose-300">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-200">Full description</label>
                        <textarea name="description" rows="6"
                                  class="{{ $field }} {{ $errors->has('description') ? $bad : $ok }}">{{ old('description', $product->description) }}</textarea>
                        @error('description')<p class="mt-1 text-xs text-rose-300">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            {{-- pricing --}}
            <section class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
                <h2 class="font-bold text-white">Pricing</h2>
                <p class="mb-4 text-xs text-slate-400">Set the selling price and an optional “compare-at” price to show a discount.</p>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-200">Price ($) <span class="text-rose-500">*</span></label>
                        <input name="price" type="number" step="0.01" min="0" value="{{ old('price', $product->price) }}" required
                               class="{{ $field }} {{ $errors->has('price') ? $bad : $ok }}">
                        @error('price')<p class="mt-1 text-xs text-rose-300">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-200">Compare-at price ($)</label>
                        <input name="compare_at_price" type="number" step="0.01" min="0" value="{{ old('compare_at_price', $product->compare_at_price) }}"
                               class="{{ $field }} {{ $errors->has('compare_at_price') ? $bad : $ok }}">
                        <p class="mt-1 text-xs text-slate-400">Original price (shown struck-through). Leave blank if none.</p>
                        @error('compare_at_price')<p class="mt-1 text-xs text-rose-300">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            {{-- media --}}
            <section class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
                <h2 class="font-bold text-white">Media</h2>
                <p class="mb-4 text-xs text-slate-400">Upload a main image or paste a URL, plus optional gallery images.</p>
                <div class="grid gap-5 sm:grid-cols-[160px_1fr]">
                    <div>
                        <img data-image-preview src="{{ $product->image ? media($product->image) : '' }}"
                             class="{{ $product->image ? '' : 'hidden' }} h-36 w-36 rounded-xl border border-white/10 object-cover" alt="">
                        @unless ($product->image)
                            <div class="grid h-36 w-36 place-items-center rounded-xl border border-dashed border-white/10 bg-white/5 text-slate-300" data-image-placeholder>
                                <x-icon name="box" class="h-10 w-10" />
                            </div>
                        @endunless
                    </div>
                    <div class="space-y-3">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-200">Upload image</label>
                            <input type="file" name="image_file" accept="image/*" data-image-input
                                   class="block w-full text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-200 hover:file:bg-white/20">
                            <p class="mt-1 text-xs text-slate-400">JPG, PNG, WEBP or GIF · max 4MB.</p>
                            @error('image_file')<p class="mt-1 text-xs text-rose-300">{{ $message }}</p>@enderror
                        </div>
                        <div class="flex items-center gap-3 text-xs text-slate-400">
                            <span class="h-px flex-1 bg-white/10"></span>OR paste a URL<span class="h-px flex-1 bg-white/10"></span>
                        </div>
                        <input name="image" value="{{ old('image', $product->image) }}" placeholder="https://…"
                               class="{{ $field }} {{ $errors->has('image') ? $bad : $ok }}">
                        <p class="text-xs text-slate-400">Leave both blank to auto-generate a placeholder.</p>
                        @error('image')<p class="text-xs text-rose-300">{{ $message }}</p>@enderror
                    </div>
                </div>
                @php
                    $galleryImages = old('gallery', is_array($product->gallery) ? $product->gallery : []);
                    $tileBase = 'group relative h-24 w-24 overflow-hidden rounded-xl border border-white/10 bg-white/5';
                    $removeBtn = 'absolute right-1 top-1 grid h-6 w-6 place-items-center rounded-full bg-slate-950/80 text-sm font-bold text-white hover:bg-rose-500';
                @endphp
                <div class="mt-6 border-t border-white/10 pt-5" data-gallery>
                    <div class="flex items-baseline justify-between gap-3">
                        <label class="block text-sm font-medium text-slate-200">Gallery images</label>
                        <span class="text-xs text-slate-400" data-gallery-count></span>
                    </div>
                    <p class="mb-3 text-xs text-slate-400">Shown as clickable thumbnails on the product page. Drag to reorder, × to remove.</p>

                    <div class="flex flex-wrap gap-3" data-gallery-list>
                        @foreach ($galleryImages as $img)
                            <div class="{{ $tileBase }} cursor-move" draggable="true" data-gallery-tile>
                                <img src="{{ media($img) }}" alt="" class="h-full w-full object-cover" loading="lazy">
                                <input type="hidden" name="gallery[]" value="{{ $img }}">
                                <button type="button" class="{{ $removeBtn }}" data-gallery-remove aria-label="Remove image">×</button>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-3 flex flex-wrap gap-3" data-gallery-new></div>

                    <label class="mt-3 flex cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-white/15 bg-white/5 px-4 py-6 text-center transition hover:border-slate-400 hover:bg-white/10" data-gallery-drop>
                        <x-icon name="box" class="h-7 w-7 text-slate-300" />
                        <span class="text-sm font-semibold text-slate-200">Click to upload or drop images here</span>
                        <span class="text-xs text-slate-400">Select several at once · JPG, PNG, WEBP or GIF · max 4MB each</span>
                        <input type="file" name="gallery_files[]" accept="image/*" multiple class="sr-only" data-gallery-input>
                    </label>
                    @error('gallery_files')<p class="mt-1 text-xs text-rose-300">{{ $message }}</p>@enderror
                    @foreach ($errors->get('gallery_files.*') as $messages)
                        <p class="mt-1 text-xs text-rose-300">{{ $messages[0] }}</p>
                    @endforeach

                    <details class="mt-3" @if (old('gallery_text')) open @endif>
                        <summary class="cursor-pointer text-xs font-semibold text-slate-300 hover:text-white">Add images by URL instead</summary>
                        <textarea name="gallery_text" rows="3" placeholder="One image URL per line…"
                                  class="mt-2 {{ $field }} {{ $ok }}">{{ old('gallery_text') }}</textarea>
                    </details>
                </div>
            </section>
        </div>

        {{-- ===== sidebar ===== --}}
        <div class="space-y-6">

            {{-- organize --}}
            <section class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
                <h2 class="mb-4 font-bold text-white">Organization</h2>
                <div class="space-y-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-200">Category <span class="text-rose-500">*</span></label>
                        @php
                            $selectedId = old('category_id', $product->category_id);
                            $selectedLabel = optional($categories->firstWhere('id', (int) $selectedId))->label;
                        @endphp
                        <div class="relative" data-combobox>
                            <input type="hidden" name="category_id" value="{{ $selectedId }}" data-combobox-value>
                            <input type="text" autocomplete="off" placeholder="Type to search a category…" data-combobox-input
                                   value="{{ $selectedLabel }}"
                                   class="{{ $field }} pr-9 {{ $errors->has('category_id') ? $bad : $ok }}">
                            <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"><x-icon name="chevron" class="h-4 w-4" /></span>
                            <ul data-combobox-list class="absolute left-0 right-0 top-full z-30 mt-1 hidden max-h-64 overflow-y-auto rounded-lg border border-white/10 bg-slate-900/70 backdrop-blur py-1 shadow-xl shadow-slate-300/40">
                                @foreach ($categories as $c)
                                    <li data-combobox-option data-value="{{ $c->id }}" data-label="{{ $c->label }}"
                                        class="cursor-pointer px-3 py-2 text-sm text-slate-200 hover:bg-white/10 {{ (int) $selectedId === $c->id ? 'bg-white/10 font-semibold text-slate-200' : '' }}">{{ $c->label }}</li>
                                @endforeach
                                <li data-combobox-empty class="hidden px-3 py-3 text-center text-sm text-slate-400">No categories match.</li>
                            </ul>
                        </div>
                        @error('category_id')<p class="mt-1 text-xs text-rose-300">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>

            {{-- status --}}
            <section class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
                <h2 class="mb-4 font-bold text-white">Status</h2>
                <div class="space-y-3">
                    <label class="flex items-center justify-between gap-2 rounded-lg border px-3 py-2.5 {{ old('published', $product->published ?? true) ? 'border-sky-400/30 bg-sky-500/15' : 'border-white/15 bg-white/10' }}">
                        <span class="text-sm font-bold text-slate-100">{{ old('published', $product->published ?? true) ? 'Published' : 'Draft' }} <span class="font-normal text-slate-400">— visible on storefront</span></span>
                        <input type="checkbox" name="published" value="1" @checked(old('published', $product->published ?? true)) class="h-4 w-8 rounded-full border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 focus:ring-slate-400">
                    </label>
                    <label class="flex items-center justify-between gap-2 rounded-lg border border-white/10 px-3 py-2.5">
                        <span class="text-sm font-medium text-slate-200">In stock</span>
                        <input type="checkbox" name="in_stock" value="1" @checked(old('in_stock', $product->in_stock)) class="h-4 w-8 rounded-full border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 focus:ring-slate-400">
                    </label>
                    <div class="rounded-lg border border-white/10 px-3 py-2.5">
                        <label for="stock_qty" class="flex items-center justify-between gap-3">
                            <span class="text-sm font-medium text-slate-200">Stock quantity</span>
                            <input type="number" id="stock_qty" name="stock_qty" min="0" step="1" inputmode="numeric" value="{{ old('stock_qty', $product->stock_qty) }}" placeholder="—"
                                   class="w-28 rounded-lg border border-white/10 bg-slate-950/50 px-3 py-1.5 text-right text-sm text-slate-100 placeholder:text-slate-500 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                        </label>
                        <p class="mt-1.5 text-xs text-slate-400">Above 0 marks the product in stock, 0 marks it out of stock. Leave blank to use the switch above. Stock Update overwrites this for SKUs in the sheet.</p>
                        @error('stock_qty')<p class="mt-1 text-xs text-rose-300">{{ $message }}</p>@enderror
                    </div>
                    <label class="flex items-center justify-between gap-2 rounded-lg border border-white/10 px-3 py-2.5">
                        <span class="text-sm font-medium text-slate-200">Free shipping <span class="font-normal text-slate-400">— whole order ships free when this is in the cart</span></span>
                        <input type="checkbox" name="free_shipping" value="1" @checked(old('free_shipping', $product->free_shipping ?? false)) class="h-4 w-8 rounded-full border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 focus:ring-slate-400">
                    </label>
                    <label class="flex items-center justify-between gap-2 rounded-lg border border-white/10 px-3 py-2.5">
                        <span class="text-sm font-medium text-slate-200">Featured on storefront</span>
                        <input type="checkbox" name="featured" value="1" @checked(old('featured', $product->featured)) class="h-4 w-8 rounded-full border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 focus:ring-slate-400">
                    </label>
                </div>
            </section>

            {{-- ratings --}}
            <section class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
                <h2 class="mb-4 font-bold text-white">Ratings</h2>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-200">Rating</label>
                        <input name="rating" type="number" step="0.1" min="0" max="5" value="{{ old('rating', $product->rating ?? 5) }}"
                               class="{{ $field }} {{ $errors->has('rating') ? $bad : $ok }}">
                        @error('rating')<p class="mt-1 text-xs text-rose-300">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-200"># Reviews</label>
                        <input name="reviews_count" type="number" min="0" value="{{ old('reviews_count', $product->reviews_count ?? 0) }}"
                               class="{{ $field }} {{ $errors->has('reviews_count') ? $bad : $ok }}">
                        @error('reviews_count')<p class="mt-1 text-xs text-rose-300">{{ $message }}</p>@enderror
                    </div>
                </div>
            </section>
        </div>
    </div>

    {{-- sticky action bar --}}
    <div class="sticky bottom-0 mt-6 flex items-center justify-end gap-3 rounded-xl border border-white/10 bg-slate-900/70 backdrop-blur px-5 py-3.5 shadow-lg backdrop-blur">
        <a href="{{ route('admin.products.index') }}" class="rounded-lg border border-white/10 px-5 py-2.5 text-sm font-semibold text-slate-300 hover:bg-white/5">Cancel</a>
        <button class="rounded-lg bg-white px-6 py-2.5 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-200">
            {{ $product->exists ? 'Update Product' : 'Create Product' }}
        </button>
    </div>
</form>

@push('scripts')
<script>
    // Gallery manager: reorder / remove saved images, preview + remove new uploads.
    (function () {
        const root = document.querySelector('[data-gallery]');
        if (!root) return;

        const list   = root.querySelector('[data-gallery-list]');
        const fresh  = root.querySelector('[data-gallery-new]');
        const input  = root.querySelector('[data-gallery-input]');
        const drop   = root.querySelector('[data-gallery-drop]');
        const count  = root.querySelector('[data-gallery-count]');
        let files = []; // File objects queued for upload

        const syncInput = () => {
            const dt = new DataTransfer();
            files.forEach((f) => dt.items.add(f));
            input.files = dt.files;
        };

        const updateCount = () => {
            const saved = list.children.length;
            const parts = [];
            if (saved) parts.push(saved + ' saved');
            if (files.length) parts.push(files.length + ' to upload');
            count.textContent = parts.join(' · ');
        };

        const renderNew = () => {
            fresh.innerHTML = '';
            files.forEach((file, i) => {
                const tile = document.createElement('div');
                tile.className = @json($tileBase) + ' ring-2 ring-amber-400/60';
                const img = document.createElement('img');
                img.className = 'h-full w-full object-cover';
                img.src = URL.createObjectURL(file);
                img.onload = () => URL.revokeObjectURL(img.src);
                const badge = document.createElement('span');
                badge.className = 'absolute bottom-1 left-1 rounded bg-amber-400 px-1.5 text-[10px] font-bold text-slate-900';
                badge.textContent = 'NEW';
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = @json($removeBtn);
                btn.textContent = '×';
                btn.setAttribute('aria-label', 'Remove image');
                btn.addEventListener('click', () => { files.splice(i, 1); syncInput(); renderNew(); });
                tile.append(img, badge, btn);
                fresh.appendChild(tile);
            });
            updateCount();
        };

        const addFiles = (list) => {
            Array.from(list).filter((f) => f.type.startsWith('image/')).forEach((f) => files.push(f));
            syncInput();
            renderNew();
        };

        input.addEventListener('change', () => addFiles(input.files));

        ['dragover', 'dragenter'].forEach((ev) => drop.addEventListener(ev, (e) => {
            if (!e.dataTransfer.types.includes('Files')) return;
            e.preventDefault();
            drop.classList.add('border-amber-400');
        }));
        ['dragleave', 'drop'].forEach((ev) => drop.addEventListener(ev, () => drop.classList.remove('border-amber-400')));
        drop.addEventListener('drop', (e) => {
            if (!e.dataTransfer.files.length) return;
            e.preventDefault();
            addFiles(e.dataTransfer.files);
        });

        // remove a saved image
        list.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-gallery-remove]');
            if (!btn) return;
            btn.closest('[data-gallery-tile]').remove();
            updateCount();
        });

        // drag to reorder saved images (hidden inputs follow the DOM order)
        let dragging = null;
        list.addEventListener('dragstart', (e) => {
            dragging = e.target.closest('[data-gallery-tile]');
            dragging?.classList.add('opacity-40');
        });
        list.addEventListener('dragend', () => { dragging?.classList.remove('opacity-40'); dragging = null; });
        list.addEventListener('dragover', (e) => {
            if (!dragging) return;
            e.preventDefault();
            const over = e.target.closest('[data-gallery-tile]');
            if (!over || over === dragging) return;
            const r = over.getBoundingClientRect();
            list.insertBefore(dragging, e.clientX > r.left + r.width / 2 ? over.nextSibling : over);
        });

        updateCount();
    })();

    // Keep the badge preview honest about what the storefront will actually render.
    (function () {
        const scope = document.querySelector('[data-badge-control]');
        if (!scope) return;

        const preview  = scope.querySelector('[data-badge-preview]');
        const wrap     = scope.querySelector('[data-badge-preview-wrap]');
        const empty    = scope.querySelector('[data-badge-preview-empty]');
        const tagInput = scope.querySelector('input[name="tag"]');
        const brandTag = @json($brandTag);

        const lead = preview?.querySelector('.brand-tag-lead');
        const rest = lead?.nextElementSibling;

        const render = () => {
            const mode = scope.querySelector('input[name="tag_mode"]:checked')?.value || 'inherit';
            const text = mode === 'none'   ? ''
                       : mode === 'custom' ? tagInput.value.trim()
                       : (brandTag || '');

            tagInput.disabled = mode !== 'custom';
            tagInput.classList.toggle('opacity-40', mode !== 'custom');

            if (!text) {
                wrap.classList.add('hidden');
                empty.classList.remove('hidden');
                return;
            }
            wrap.classList.remove('hidden');
            empty.classList.add('hidden');

            const space = text.indexOf(' ');
            lead.textContent = space === -1 ? text : text.slice(0, space);
            if (rest) rest.textContent = space === -1 ? '' : text.slice(space + 1);
        };

        scope.addEventListener('change', (e) => { if (e.target.name === 'tag_mode') render(); });
        tagInput.addEventListener('input', render);

        // A disabled input submits nothing, so re-enable it just before the form posts.
        scope.closest('form')?.addEventListener('submit', () => { tagInput.disabled = false; });

        render();
    })();
</script>
@endpush

@endsection
