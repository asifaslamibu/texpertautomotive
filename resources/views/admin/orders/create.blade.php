@extends('admin.layouts.app')

@section('title', 'Create Order')
@section('heading', 'Create Order')
@section('subheading', 'Build an order and send the customer a link to pay online')

@section('content')

@php $inp = 'w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30'; @endphp

@unless ($stripeReady)
    <div class="mb-5 rounded-xl border border-amber-400/30 bg-amber-400/10 p-4 text-sm text-amber-200">
        Stripe is not enabled yet, so the customer won't be able to pay online. Turn it on in <a href="{{ route('admin.settings.edit') }}" class="font-bold underline">Settings → Payments</a> first. You can still create the order.
    </div>
@endunless

@if ($errors->any())
    <div class="mb-5 rounded-xl border border-rose-400/30 bg-rose-500/10 p-4 text-sm text-rose-200">
        <ul class="list-disc pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.orders.store') }}" data-order-form class="grid gap-6 lg:grid-cols-3">
    @csrf

    <div class="space-y-6 lg:col-span-2">
        {{-- customer --}}
        <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6">
            <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-slate-300">Customer</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Name *</label>
                    <input name="customer_name" value="{{ old('customer_name') }}" required class="{{ $inp }}">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Email *</label>
                    <input name="customer_email" type="email" value="{{ old('customer_email') }}" required class="{{ $inp }}">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Phone</label>
                    <input name="customer_phone" value="{{ old('customer_phone') }}" class="{{ $inp }}">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Company</label>
                    <input name="customer_company" value="{{ old('customer_company') }}" class="{{ $inp }}">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Address</label>
                    <input name="customer_address" value="{{ old('customer_address') }}" class="{{ $inp }}">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">City</label>
                    <input name="customer_city" value="{{ old('customer_city') }}" class="{{ $inp }}">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-200">State</label>
                        <input name="customer_state" value="{{ old('customer_state') }}" class="{{ $inp }}">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-200">ZIP</label>
                        <input name="customer_zip" value="{{ old('customer_zip') }}" class="{{ $inp }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- products --}}
        <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6">
            <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-slate-300">Products</h3>

            <div class="relative" data-search-wrap>
                <input type="text" data-product-search autocomplete="off" placeholder="Search products by name or SKU…" class="{{ $inp }}">
                <div data-search-results class="absolute left-0 right-0 top-full z-20 mt-1 hidden max-h-72 overflow-y-auto rounded-lg border border-white/10 bg-slate-950 shadow-2xl"></div>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="pb-2">Item</th>
                            <th class="w-28 pb-2 text-right">Price</th>
                            <th class="w-20 pb-2 text-right">Qty</th>
                            <th class="w-28 pb-2 text-right">Total</th>
                            <th class="w-8 pb-2"></th>
                        </tr>
                    </thead>
                    <tbody data-items-body></tbody>
                </table>
                <p data-empty class="py-4 text-center text-sm text-slate-500">No items yet — search above or add a custom line.</p>
            </div>

            <button type="button" data-add-custom class="mt-3 text-sm font-semibold text-slate-300 hover:text-white">+ Add custom item</button>
        </div>
    </div>

    {{-- summary --}}
    <div class="lg:col-span-1">
        <div class="sticky top-6 space-y-4 rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6">
            <h3 class="text-sm font-bold uppercase tracking-wide text-slate-300">Summary</h3>

            <div class="flex items-center justify-between text-sm text-slate-300">
                <span>Subtotal</span><span data-subtotal class="font-semibold text-slate-100">$0.00</span>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Discount %</label>
                <div class="relative">
                    <input name="discount_percent" type="number" step="0.01" min="0" max="100" value="{{ old('discount_percent', '0') }}" data-discount-percent class="{{ $inp }} pr-8">
                    <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">%</span>
                </div>
                <p class="mt-1 text-xs text-slate-400">Taken off the subtotal, before shipping and tax.</p>
            </div>
            <div data-discount-row hidden class="flex items-center justify-between text-sm text-emerald-300">
                <span>Discount (<span data-discount-label>0</span>%)</span><span data-discount-amount class="font-semibold">−$0.00</span>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Shipping</label>
                <input name="shipping" type="number" step="0.01" min="0" value="{{ old('shipping', '0') }}" data-shipping class="{{ $inp }}">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Tax</label>
                <input name="tax" type="number" step="0.01" min="0" value="{{ old('tax', '0') }}" data-tax class="{{ $inp }}">
            </div>
            <div class="flex items-center justify-between border-t border-white/10 pt-3 text-base font-bold text-white">
                <span>Total</span><span data-total>$0.00</span>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Notes <span class="font-normal text-slate-400">(internal)</span></label>
                <textarea name="notes" rows="2" class="{{ $inp }}">{{ old('notes') }}</textarea>
            </div>

            <button type="submit" class="w-full rounded-lg bg-white px-6 py-3 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-200">Create Order &amp; Get Pay Link</button>
            <a href="{{ route('admin.orders.index') }}" class="block text-center text-sm font-semibold text-slate-400 hover:text-slate-200">Cancel</a>
        </div>
    </div>
</form>

@php $rowTpl = 'ROW_INDEX'; @endphp
<template data-row-template>
    <tr class="border-t border-white/10" data-item-row>
        <td class="py-2 pr-3">
            <input type="hidden" data-f="product_id">
            <input type="text" data-f="name" required placeholder="Item name" class="w-full rounded border border-white/10 bg-slate-950/50 px-2 py-1.5 text-sm text-slate-100 outline-none focus:border-slate-400">
        </td>
        <td class="py-2 text-right">
            <input type="number" step="0.01" min="0" data-f="price" required class="w-24 rounded border border-white/10 bg-slate-950/50 px-2 py-1.5 text-right text-sm text-slate-100 outline-none focus:border-slate-400">
        </td>
        <td class="py-2 text-right">
            <input type="number" min="1" step="1" value="1" data-f="quantity" required class="w-16 rounded border border-white/10 bg-slate-950/50 px-2 py-1.5 text-right text-sm text-slate-100 outline-none focus:border-slate-400">
        </td>
        <td class="py-2 text-right font-semibold text-slate-100" data-line-total>$0.00</td>
        <td class="py-2 text-right">
            <button type="button" data-remove class="grid h-7 w-7 place-items-center rounded text-rose-300 hover:bg-rose-500/15">&times;</button>
        </td>
    </tr>
</template>

@push('scripts')
<script>
(function () {
    const form = document.querySelector('[data-order-form]');
    if (!form) return;
    const body = form.querySelector('[data-items-body]');
    const tpl = document.querySelector('[data-row-template]');
    const empty = form.querySelector('[data-empty]');
    const searchInput = form.querySelector('[data-product-search]');
    const results = form.querySelector('[data-search-results]');
    const searchUrl = @json(route('admin.orders.product-search'));
    let idx = 0;

    const money = (n) => '$' + (Math.round(n * 100) / 100).toFixed(2);

    function renumber() {
        body.querySelectorAll('[data-item-row]').forEach((row, i) => {
            row.querySelectorAll('[data-f]').forEach((el) => {
                el.name = 'items[' + i + '][' + el.dataset.f + ']';
            });
        });
        empty.classList.toggle('hidden', body.children.length > 0);
    }

    function recalc() {
        let sub = 0;
        body.querySelectorAll('[data-item-row]').forEach((row) => {
            const price = parseFloat(row.querySelector('[data-f="price"]').value) || 0;
            const qty = parseInt(row.querySelector('[data-f="quantity"]').value) || 0;
            const lt = price * qty;
            row.querySelector('[data-line-total]').textContent = money(lt);
            sub += lt;
        });
        const shipping = parseFloat(form.querySelector('[data-shipping]').value) || 0;
        const tax = parseFloat(form.querySelector('[data-tax]').value) || 0;

        // percentage off the subtotal only — shipping and tax are added after it
        const pct = Math.min(100, Math.max(0, parseFloat(form.querySelector('[data-discount-percent]').value) || 0));
        const discount = Math.round(sub * pct) / 100;
        const row = form.querySelector('[data-discount-row]');
        row.hidden = discount <= 0;
        form.querySelector('[data-discount-label]').textContent = pct;
        form.querySelector('[data-discount-amount]').textContent = '−' + money(discount);

        form.querySelector('[data-subtotal]').textContent = money(sub);
        form.querySelector('[data-total]').textContent = money(Math.max(0, sub - discount) + shipping + tax);
    }

    function addRow(data) {
        const node = tpl.content.firstElementChild.cloneNode(true);
        node.querySelector('[data-f="product_id"]').value = data && data.id ? data.id : '';
        node.querySelector('[data-f="name"]').value = data && data.name ? data.name : '';
        node.querySelector('[data-f="price"]').value = data && (data.price || data.price === 0) ? data.price : '';
        body.appendChild(node);
        idx++;
        renumber();
        recalc();
        if (!data) node.querySelector('[data-f="name"]').focus();
    }

    body.addEventListener('input', recalc);
    body.addEventListener('click', (e) => {
        if (e.target.closest('[data-remove]')) {
            e.target.closest('[data-item-row]').remove();
            renumber(); recalc();
        }
    });
    form.querySelector('[data-discount-percent]').addEventListener('input', recalc);
    form.querySelector('[data-shipping]').addEventListener('input', recalc);
    form.querySelector('[data-tax]').addEventListener('input', recalc);
    form.querySelector('[data-add-custom]').addEventListener('click', () => addRow(null));

    // product search
    let t = null;
    searchInput.addEventListener('input', () => {
        clearTimeout(t);
        const q = searchInput.value.trim();
        if (q.length < 2) { results.classList.add('hidden'); results.innerHTML = ''; return; }
        t = setTimeout(async () => {
            try {
                const res = await fetch(searchUrl + '?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const list = await res.json();
                results.innerHTML = list.length
                    ? list.map((p) => `<button type="button" class="flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm text-slate-200 hover:bg-white/5" data-pick='${JSON.stringify(p).replace(/'/g, "&#39;")}'><span class="min-w-0 truncate">${p.name}<span class="ml-2 text-xs text-slate-500">${p.sku || ''}</span></span><span class="shrink-0 font-semibold text-slate-100">${money(p.price)}</span></button>`).join('')
                    : '<p class="px-3 py-2 text-sm text-slate-500">No matches</p>';
                results.classList.remove('hidden');
            } catch (e) { /* ignore */ }
        }, 220);
    });
    results.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-pick]');
        if (!btn) return;
        addRow(JSON.parse(btn.dataset.pick));
        searchInput.value = '';
        results.classList.add('hidden'); results.innerHTML = '';
        searchInput.focus();
    });
    document.addEventListener('click', (e) => {
        if (!e.target.closest('[data-search-wrap]')) { results.classList.add('hidden'); }
    });

    form.addEventListener('submit', (e) => {
        if (body.children.length === 0) { e.preventDefault(); alert('Add at least one item.'); }
    });

    recalc();
})();
</script>
@endpush

@endsection
