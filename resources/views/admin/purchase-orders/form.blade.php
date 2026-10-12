@extends('admin.layouts.app')

@php $editing = $po->exists; @endphp

@section('title', $editing ? 'Edit '.$po->po_number : 'New Purchase Order')
@section('heading', $editing ? 'Edit '.$po->po_number : 'New Purchase Order')
@section('subheading', 'Fill in the details — you can preview, download the PDF, and email it to the distributor')

@section('content')

@php
    $items = old('items', $po->items ?: []);
    if (empty($items)) { $items = [['description' => '', 'qty' => 1, 'price' => '', 'discount' => '']]; }
    $label = 'mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-400';
    $input = 'w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-white/30 focus:outline-none';
@endphp

<form method="POST"
      action="{{ $editing ? route('admin.purchase-orders.update', $po) : route('admin.purchase-orders.store') }}"
      class="space-y-6">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- ---- PO meta ---- --}}
        <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-5 backdrop-blur">
            <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-white">Order</h3>
            <div class="mb-4">
                <label class="{{ $label }}">Order No.</label>
                <input value="{{ $po->po_number }}" disabled class="{{ $input }} opacity-60">
            </div>
            <div>
                <label class="{{ $label }}">Date</label>
                <input type="date" name="po_date"
                       value="{{ old('po_date', optional($po->po_date)->format('Y-m-d') ?: now()->format('Y-m-d')) }}"
                       class="{{ $input }}">
            </div>
        </div>

        {{-- ---- Distributor info ---- --}}
        <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-5 backdrop-blur lg:col-span-2">
            <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-white">Distributor / Customer Information</h3>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="{{ $label }}">Name</label>
                    <input name="distributor_name" value="{{ old('distributor_name', $po->distributor_name) }}" class="{{ $input }}" placeholder="Distributor name">
                </div>
                <div>
                    <label class="{{ $label }}">Phone No</label>
                    <input name="distributor_phone" value="{{ old('distributor_phone', $po->distributor_phone) }}" class="{{ $input }}" placeholder="(214)-000-0000">
                </div>
                <div class="sm:col-span-2">
                    <label class="{{ $label }}">Email</label>
                    <input type="email" name="distributor_email" value="{{ old('distributor_email', $po->distributor_email) }}" class="{{ $input }}" placeholder="distributor@example.com">
                </div>
                <div>
                    <label class="{{ $label }}">Billing Address</label>
                    <textarea name="billing_address" rows="2" class="{{ $input }}" placeholder="Billing address">{{ old('billing_address', $po->billing_address) }}</textarea>
                </div>
                <div>
                    <label class="{{ $label }}">Shipping Address</label>
                    <textarea name="shipping_address" rows="2" class="{{ $input }}" placeholder="Shipping address">{{ old('shipping_address', $po->shipping_address) }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- ---- Line items ---- --}}
    <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-5 backdrop-blur" data-items data-search-url="{{ route('admin.purchase-orders.product-search') }}">
        <div class="mb-1 flex items-center justify-between">
            <h3 class="text-sm font-bold uppercase tracking-wide text-white">Order Details</h3>
            <button type="button" data-add-row class="rounded-lg border border-white/15 px-3 py-1.5 text-xs font-semibold text-slate-200 transition hover:bg-white/10">+ Add item</button>
        </div>
        <p class="mb-4 text-xs text-slate-400">Start typing in <span class="font-semibold text-slate-300">Item Description</span> to search your products (fills price automatically), or just type any text manually.</p>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                    <tr>
                        <th class="pb-2 pr-2 w-8">#</th>
                        <th class="pb-2 pr-2">Item Description</th>
                        <th class="pb-2 pr-2 w-20">Qty</th>
                        <th class="pb-2 pr-2 w-28">Price</th>
                        <th class="pb-2 pr-2 w-28">Discount</th>
                        <th class="pb-2 pr-2 w-28 text-right">Total</th>
                        <th class="pb-2 w-8"></th>
                    </tr>
                </thead>
                <tbody data-rows>
                    @foreach ($items as $i => $row)
                        <tr data-row class="align-top">
                            <td class="py-1 pr-2 text-slate-500" data-index>{{ $i + 1 }}</td>
                            <td class="py-1 pr-2"><input name="items[{{ $i }}][description]" value="{{ $row['description'] ?? '' }}" class="{{ $input }}" placeholder="Description"></td>
                            <td class="py-1 pr-2"><input name="items[{{ $i }}][qty]" value="{{ $row['qty'] ?? '' }}" data-qty type="number" step="any" min="0" class="{{ $input }} text-right"></td>
                            <td class="py-1 pr-2"><input name="items[{{ $i }}][price]" value="{{ $row['price'] ?? '' }}" data-price type="number" step="any" min="0" class="{{ $input }} text-right"></td>
                            <td class="py-1 pr-2"><input name="items[{{ $i }}][discount]" value="{{ $row['discount'] ?? '' }}" data-discount type="number" step="any" min="0" class="{{ $input }} text-right"></td>
                            <td class="py-1 pr-2 text-right font-semibold text-white" data-line>$0.00</td>
                            <td class="py-1 text-right"><button type="button" data-remove class="text-slate-500 transition hover:text-rose-400" title="Remove">&times;</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- totals --}}
        <div class="mt-5 flex justify-end">
            <div class="w-full max-w-xs space-y-2 text-sm">
                <div class="flex justify-between text-slate-300"><span>Subtotal</span><span data-subtotal class="font-semibold text-white">$0.00</span></div>
                <div class="flex items-center justify-between text-slate-300">
                    <span>Tax</span>
                    <input name="tax" value="{{ old('tax', $po->tax ?: '') }}" data-tax type="number" step="any" min="0" placeholder="0.00" class="{{ $input }} w-28 text-right">
                </div>
                <div class="flex items-center justify-between text-slate-300">
                    <span>Shipping</span>
                    <input name="shipping" value="{{ old('shipping', $po->shipping ?: '') }}" data-shipping type="number" step="any" min="0" placeholder="0.00" class="{{ $input }} w-28 text-right">
                </div>
                <div class="flex justify-between border-t border-white/10 pt-2 text-base font-bold text-white"><span>Total</span><span data-total>$0.00</span></div>
            </div>
        </div>
    </div>

    {{-- ---- Notes ---- --}}
    <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-5 backdrop-blur">
        <label class="{{ $label }}">Notes</label>
        <textarea name="notes" rows="3" class="{{ $input }}" placeholder="Any notes for the distributor…">{{ old('notes', $po->notes) }}</textarea>
    </div>

    <div class="flex items-center gap-3">
        <button class="rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-slate-900 transition hover:bg-slate-200">
            {{ $editing ? 'Save changes' : 'Create purchase order' }}
        </button>
        <a href="{{ $editing ? route('admin.purchase-orders.show', $po) : route('admin.purchase-orders.index') }}"
           class="rounded-xl border border-white/10 px-5 py-2.5 text-sm font-semibold text-slate-300 transition hover:bg-white/5">Cancel</a>
    </div>
</form>

{{-- row template --}}
<template data-row-template>
    <tr data-row class="align-top">
        <td class="py-1 pr-2 text-slate-500" data-index></td>
        <td class="py-1 pr-2"><input name="items[__i__][description]" class="{{ $input }}" placeholder="Description"></td>
        <td class="py-1 pr-2"><input name="items[__i__][qty]" value="1" data-qty type="number" step="any" min="0" class="{{ $input }} text-right"></td>
        <td class="py-1 pr-2"><input name="items[__i__][price]" data-price type="number" step="any" min="0" class="{{ $input }} text-right"></td>
        <td class="py-1 pr-2"><input name="items[__i__][discount]" data-discount type="number" step="any" min="0" class="{{ $input }} text-right"></td>
        <td class="py-1 pr-2 text-right font-semibold text-white" data-line>$0.00</td>
        <td class="py-1 text-right"><button type="button" data-remove class="text-slate-500 transition hover:text-rose-400" title="Remove">&times;</button></td>
    </tr>
</template>

@push('scripts')
<script>
(function () {
    const wrap = document.querySelector('[data-items]');
    if (!wrap) return;
    const rows = wrap.querySelector('[data-rows]');
    const tpl = document.querySelector('[data-row-template]');
    const money = n => '$' + (n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const num = el => parseFloat(el && el.value) || 0;

    function renumber() {
        rows.querySelectorAll('[data-row]').forEach((tr, i) => {
            tr.querySelector('[data-index]').textContent = i + 1;
            tr.querySelectorAll('input').forEach(inp => {
                if (inp.name) inp.name = inp.name.replace(/items\[[^\]]*\]/, 'items[' + i + ']');
            });
        });
    }

    function recalc() {
        let subtotal = 0;
        rows.querySelectorAll('[data-row]').forEach(tr => {
            const line = Math.max(0, num(tr.querySelector('[data-qty]')) * num(tr.querySelector('[data-price]')) - num(tr.querySelector('[data-discount]')));
            tr.querySelector('[data-line]').textContent = money(line);
            subtotal += line;
        });
        wrap.querySelector('[data-subtotal]').textContent = money(subtotal);
        const total = subtotal + num(wrap.querySelector('[data-tax]')) + num(wrap.querySelector('[data-shipping]'));
        wrap.querySelector('[data-total]').textContent = money(total);
    }

    wrap.addEventListener('input', recalc);
    wrap.querySelector('[data-add-row]').addEventListener('click', function () {
        const i = rows.querySelectorAll('[data-row]').length;
        const html = tpl.innerHTML.replace(/__i__/g, i);
        rows.insertAdjacentHTML('beforeend', html);
        renumber(); recalc();
    });
    rows.addEventListener('click', function (e) {
        if (!e.target.closest('[data-remove]')) return;
        const tr = e.target.closest('[data-row]');
        if (rows.querySelectorAll('[data-row]').length > 1) tr.remove();
        else tr.querySelectorAll('input').forEach(i => i.value = '');
        renumber(); recalc();
    });

    recalc();
})();

/* ---- Product autocomplete on the Item Description fields (pick to fetch, or type freely) ---- */
(function () {
    const wrap = document.querySelector('[data-items]');
    if (!wrap || !wrap.dataset.searchUrl) return;
    const url = wrap.dataset.searchUrl;
    let box = null, active = null, timer = null, justChose = false;

    const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));
    function close() { if (box) { box.remove(); box = null; } active = null; }

    function render(list, input) {
        close();
        if (!list.length) return;
        active = input;
        box = document.createElement('div');
        box.style.cssText = 'position:absolute;z-index:60;max-height:260px;overflow:auto;background:#0f172a;border:1px solid rgba(255,255,255,.15);border-radius:10px;box-shadow:0 12px 32px rgba(0,0,0,.55);';
        const r = input.getBoundingClientRect();
        box.style.left = (r.left + scrollX) + 'px';
        box.style.top = (r.bottom + scrollY + 4) + 'px';
        box.style.width = Math.max(260, r.width) + 'px';
        list.forEach(p => {
            const o = document.createElement('div');
            o.style.cssText = 'padding:8px 10px;cursor:pointer;color:#e2e8f0;border-bottom:1px solid rgba(255,255,255,.06);font-size:13px;';
            o.innerHTML = '<div style="font-weight:600">' + esc(p.name) + '</div><div style="color:#94a3b8;font-size:11px">' + (p.sku ? esc(p.sku) + ' · ' : '') + '$' + Number(p.price).toFixed(2) + '</div>';
            o.onmousedown = e => { e.preventDefault(); choose(p, input); };
            o.onmouseenter = () => o.style.background = 'rgba(255,255,255,.08)';
            o.onmouseleave = () => o.style.background = '';
            box.appendChild(o);
        });
        document.body.appendChild(box);
    }

    function choose(p, input) {
        input.value = p.name;
        const row = input.closest('[data-row]');
        const price = row && row.querySelector('[data-price]');
        if (price && p.price) price.value = Number(p.price).toFixed(2);
        const qty = row && row.querySelector('[data-qty]');
        if (qty && !qty.value) qty.value = 1;   // default to 1 so the line total isn't zero
        close();
        justChose = true;
        input.dispatchEvent(new Event('input', { bubbles: true }));  // recalc totals
    }

    wrap.addEventListener('input', e => {
        const input = e.target.closest('input[name$="[description]"]');
        if (!input) return;
        if (justChose) { justChose = false; return; }
        const q = input.value.trim();
        clearTimeout(timer);
        if (q.length < 2) { close(); return; }
        timer = setTimeout(() => {
            fetch(url + '?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(list => { if (document.activeElement === input) render(list, input); })
                .catch(() => {});
        }, 220);
    });

    document.addEventListener('click', e => { if (box && !box.contains(e.target) && e.target !== active) close(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
    window.addEventListener('scroll', close, true);
})();
</script>
@endpush

@endsection
