@extends('admin.layouts.app')

@section('title', 'Products')
@section('heading', 'Products')
@section('subheading', 'Manage your catalog')

@section('content')

<div data-bulk class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur shadow-sm">

    {{-- ===== filters ===== --}}
    <form method="GET" class="flex flex-wrap items-center gap-2 border-b border-white/10 p-4">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name, brand, SKU…"
               class="w-full max-w-xs rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">

        <select name="category" class="rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2 text-sm outline-none focus:border-slate-400">
            <option value="">All categories</option>
            @foreach ($categories as $c)
                <option value="{{ $c->id }}" @selected(request('category')==$c->id)>{{ $c->name }}</option>
            @endforeach
        </select>

        <select name="brand" class="rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2 text-sm outline-none focus:border-slate-400">
            <option value="">All brands</option>
            @foreach ($brands as $b)
                <option value="{{ $b }}" @selected(request('brand')===$b)>{{ $b }}</option>
            @endforeach
        </select>

        <select name="stock" class="rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2 text-sm outline-none focus:border-slate-400">
            <option value="">Any stock</option>
            <option value="in" @selected(request('stock')==='in')>In stock</option>
            <option value="out" @selected(request('stock')==='out')>Out of stock</option>
        </select>

        <select name="weight" title="Wrong or missing weights make shipping quotes wrong" class="rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2 text-sm outline-none focus:border-slate-400">
            <option value="">Any weight</option>
            <option value="missing" @selected(request('weight')==='missing')>Weight missing</option>
            <option value="suspect" @selected(request('weight')==='suspect')>Weight looks wrong</option>
        </select>

        <select name="featured" class="rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2 text-sm outline-none focus:border-slate-400">
            <option value="">All</option>
            <option value="yes" @selected(request('featured')==='yes')>Featured</option>
            <option value="no" @selected(request('featured')==='no')>Not featured</option>
        </select>

        <select name="status" class="rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2 text-sm outline-none focus:border-slate-400">
            <option value="">Any status</option>
            <option value="published" @selected(request('status')==='published')>Published</option>
            <option value="draft" @selected(request('status')==='draft')>Draft</option>
        </select>

        <select name="per_page" onchange="this.form.submit()" class="rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2 text-sm outline-none focus:border-slate-400">
            @foreach ($perPageOptions as $opt)
                <option value="{{ $opt }}" @selected(! $showAll && (int) $perPageRaw === $opt)>Show {{ $opt }}</option>
            @endforeach
            <option value="all" @selected($showAll)>Show all</option>
        </select>

        <button class="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-200">Filter</button>
        @if (request()->hasAny(['q','category','brand','stock','featured','status','sort','dir','per_page']))
            <a href="{{ route('admin.products.index') }}" class="rounded-lg px-3 py-2 text-sm font-semibold text-slate-400 hover:text-white">Reset</a>
        @endif
        <span class="ml-auto flex items-center gap-2">
            <a href="{{ route('admin.products.duplicates') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-white/15 px-4 py-2 text-sm font-semibold text-slate-100 transition hover:bg-white/10">
                <x-icon name="search" class="h-4 w-4" /> Duplicates
            </a>
            <a href="{{ route('admin.products.export') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-white/15 px-4 py-2 text-sm font-semibold text-slate-100 transition hover:bg-white/10">
                <x-icon name="box" class="h-4 w-4" /> Export CSV
            </a>
            <a href="{{ route('admin.products.create') }}" class="rounded-lg bg-white px-4 py-2 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-200">+ Add Product</a>
        </span>
    </form>

    {{-- ===== bulk action bar ===== --}}
    <div class="border-b border-white/10 bg-white/5 px-4 py-2.5 text-sm">
        <span class="text-slate-400">{{ $products->total() }} products</span>
    </div>

    {{-- ===== table ===== --}}
    <div class="px-4 pt-4">
        <x-bulk-bar :action="route('admin.products.bulk-destroy')">
            {{-- badge actions for the checked products --}}
            <span class="flex flex-wrap items-center gap-2 border-l border-white/10 pl-3">
                <select data-bulk-tag-mode data-bulk-name="tag_mode"
                        class="rounded-lg border border-white/10 bg-slate-950/60 px-2.5 py-1.5 text-xs font-semibold text-slate-100 outline-none focus:border-slate-400">
                    <option value="custom">Set badge</option>
                    <option value="none">Remove badge</option>
                    <option value="inherit">Use brand badge</option>
                </select>
                <input data-bulk-tag data-bulk-name="tag" maxlength="60" placeholder="Badge text…"
                       class="w-48 rounded-lg border border-white/10 bg-slate-950/60 px-2.5 py-1.5 text-xs text-slate-100 placeholder:text-slate-500 outline-none focus:border-slate-400">
                <button type="button" data-bulk-submit
                        data-bulk-action="{{ route('admin.products.bulk-tag') }}"
                        data-bulk-method="PATCH"
                        data-bulk-fields="[data-bulk-tag-mode], [data-bulk-tag]"
                        data-bulk-confirm="Apply this badge change to %n product(s)?"
                        class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-900 transition hover:bg-white">
                    Apply
                </button>
            </span>
        </x-bulk-bar>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-white/5 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                <tr>
                    <th class="px-4 py-3 w-8"><input type="checkbox" data-bulk-all class="rounded border-white/20 bg-transparent"></th>
                    <x-th-sort column="name" label="Product" />
                    <th class="px-4 py-3">Category</th>
                    <x-th-sort column="price" label="Price" align="right" />
                    <th class="px-4 py-3">Stock</th>
                    <th class="px-4 py-3">Featured</th>
                    <x-th-sort column="published" label="Status" />
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @forelse ($products as $product)
                    <tr class="transition hover:bg-white/5">
                        <td class="px-4 py-3">
                            <input type="checkbox" name="ids[]" value="{{ $product->id }}" data-bulk-item class="rounded border-white/20 bg-transparent" aria-label="Select {{ $product->name }}">
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <img src="{{ media($product->image) }}" alt="" class="h-11 w-11 shrink-0 rounded-lg border border-white/10 object-cover">
                                <div class="min-w-0">
                                    <p class="truncate font-semibold text-slate-100">{{ $product->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $product->brand }} · {{ $product->sku }}
                                        @if (request('weight'))
                                            · <span class="text-amber-300">{{ $product->weight_lbs ?: 'no weight' }}{{ $product->dimensions ? ' · '.$product->dimensions : '' }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-300">{{ $product->category->name ?? '—' }}</td>
                        <td class="px-4 py-3 font-semibold text-white">${{ number_format($product->price, 2) }}</td>
                        <td class="px-4 py-3">
                            @if ($product->in_stock)
                                <span class="rounded-full bg-emerald-500/15 px-2.5 py-1 text-xs font-semibold text-emerald-300">In stock{{ $product->stock_qty ? ' · '.$product->stock_qty : '' }}</span>
                            @else
                                <span class="rounded-full bg-rose-500/15 px-2.5 py-1 text-xs font-semibold text-rose-300">Out</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{!! $product->featured ? '<span class="text-slate-200">★</span>' : '<span class="text-slate-300">—</span>' !!}</td>
                        <td class="px-4 py-3">
                            @if ($product->published)
                                <span class="rounded-full bg-sky-500/15 px-2.5 py-1 text-xs font-semibold text-sky-300">Published</span>
                            @else
                                <span class="rounded-full bg-white/10 px-2.5 py-1 text-xs font-semibold text-slate-200">Draft</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <button type="button" data-quick-toggle="quick-{{ $product->id }}" class="rounded-lg border border-white/10 px-3 py-1.5 text-xs font-semibold text-slate-300 hover:border-white/15 hover:text-white">Quick Edit</button>
                                <a href="{{ route('admin.products.edit', $product) }}" class="rounded-lg border border-white/10 px-3 py-1.5 text-xs font-semibold text-slate-300 hover:border-white/15 hover:text-white">Edit</a>
                                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Delete this product?')">
                                    @csrf @method('DELETE')
                                    <button class="rounded-lg border border-white/10 px-3 py-1.5 text-xs font-semibold text-rose-300 hover:border-rose-400/30 hover:bg-rose-500/15">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    {{-- WordPress-style quick edit row --}}
                    <tr id="quick-{{ $product->id }}" data-quick-row class="hidden">
                        <td colspan="8" class="border-l-2 border-white/15 bg-white/5 px-4 py-4">
                            <form method="POST" action="{{ route('admin.products.quick-update', $product) }}" class="flex flex-wrap items-end gap-4">
                                @csrf @method('PATCH')
                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-slate-400">Category</label>
                                    <div class="relative w-64" data-combobox>
                                        <input type="hidden" name="category_id" value="{{ $product->category_id }}" data-combobox-value>
                                        <input type="text" autocomplete="off" placeholder="Search category…" data-combobox-input
                                               value="{{ $product->category->name ?? '' }}"
                                               class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2 pr-8 text-sm outline-none focus:border-slate-400">
                                        <span class="pointer-events-none absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400"><x-icon name="chevron" class="h-4 w-4" /></span>
                                        <ul data-combobox-list class="absolute left-0 right-0 top-full z-30 mt-1 hidden max-h-56 overflow-y-auto rounded-lg border border-white/10 bg-slate-900/70 backdrop-blur py-1 shadow-xl shadow-slate-300/40">
                                            @foreach ($categories as $c)
                                                <li data-combobox-option data-value="{{ $c->id }}" data-label="{{ $c->name }}"
                                                    class="cursor-pointer px-3 py-2 text-sm text-slate-200 hover:bg-white/10 {{ $product->category_id == $c->id ? 'bg-white/10 font-semibold text-slate-200' : '' }}">{{ $c->name }}</li>
                                            @endforeach
                                            <li data-combobox-empty class="hidden px-3 py-3 text-center text-sm text-slate-400">No categories match.</li>
                                        </ul>
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-slate-400">Price ($)</label>
                                    <input name="price" type="number" step="0.01" min="0" value="{{ $product->price }}" class="w-28 rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2 text-sm outline-none focus:border-slate-400">
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-slate-400">Stock</label>
                                    <select name="in_stock" class="rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2 text-sm outline-none focus:border-slate-400">
                                        <option value="1" @selected($product->in_stock)>In stock</option>
                                        <option value="0" @selected(! $product->in_stock)>Out of stock</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-slate-400">Status</label>
                                    <select name="published" class="rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2 text-sm outline-none focus:border-slate-400">
                                        <option value="1" @selected($product->published)>Published</option>
                                        <option value="0" @selected(! $product->published)>Draft</option>
                                    </select>
                                </div>
                                <label class="flex items-center gap-2 pb-2.5 text-sm font-medium text-slate-200">
                                    <input type="checkbox" name="featured" value="1" @checked($product->featured) class="rounded border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500"> Featured
                                </label>
                                <div class="ml-auto flex items-center gap-2">
                                    <button type="button" data-quick-cancel="quick-{{ $product->id }}" class="rounded-lg border border-white/10 px-4 py-2 text-sm font-semibold text-slate-300 hover:bg-white/5">Cancel</button>
                                    <button class="rounded-lg bg-white px-5 py-2 text-sm font-bold text-slate-900 hover:bg-slate-200">Save</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-12 text-center text-slate-400">No products found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="border-t border-white/10 p-4">{{ $products->links('pagination.admin') }}</div>
</div>

@push('scripts')
<script>
// The badge text only applies to "Set badge" — grey it out for the other two modes.
(function () {
    const mode = document.querySelector('[data-bulk-tag-mode]');
    const text = document.querySelector('[data-bulk-tag]');
    if (!mode || !text) return;

    const sync = () => {
        const custom = mode.value === 'custom';
        text.disabled = ! custom;
        text.classList.toggle('opacity-40', ! custom);
    };
    mode.addEventListener('change', sync);
    sync();
})();

document.addEventListener('click', (e) => {
    const open = e.target.closest('[data-quick-toggle]');
    if (open) {
        const row = document.getElementById(open.dataset.quickToggle);
        if (row) {
            // close any other open quick-edit rows first
            document.querySelectorAll('[data-quick-row]:not(.hidden)').forEach((r) => { if (r !== row) r.classList.add('hidden'); });
            row.classList.toggle('hidden');
        }
        return;
    }
    const cancel = e.target.closest('[data-quick-cancel]');
    if (cancel) {
        document.getElementById(cancel.dataset.quickCancel)?.classList.add('hidden');
    }
});
</script>
@endpush

@endsection
