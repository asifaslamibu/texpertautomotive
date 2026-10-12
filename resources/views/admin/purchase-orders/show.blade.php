@extends('admin.layouts.app')

@section('title', $po->po_number)
@section('heading', $po->po_number)
@section('subheading', 'Preview, download the PDF, or email it to the distributor')

@section('content')

@include('admin.purchase-orders._tabs')

<div class="mb-5 flex flex-wrap items-center gap-3">
    <a href="{{ route('admin.purchase-orders.index') }}" class="text-sm font-semibold text-slate-400 hover:text-white">← Back to purchase orders</a>
    @if ($po->approved_at)
        <span class="rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-bold text-emerald-300">Approved &amp; sent {{ optional($po->sent_at)->format('M d, Y g:i A') }}</span>
    @elseif ($po->status === 'sent')
        <span class="rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-bold text-emerald-300">Sent {{ optional($po->sent_at)->format('M d, Y g:i A') }}</span>
    @else
        <span class="rounded-full bg-amber-500/15 px-3 py-1 text-xs font-bold text-amber-300">Pending approval</span>
    @endif
    @if ($po->order)
        <a href="{{ route('admin.orders.show', $po->order) }}" class="rounded-full border border-white/10 px-3 py-1 text-xs font-semibold text-slate-300 hover:bg-white/5">From order {{ $po->order->order_number }} →</a>
    @endif

    <div class="ml-auto flex flex-wrap items-center gap-2">
        <a href="{{ route('admin.purchase-orders.pdf', $po) }}" target="_blank"
           class="rounded-xl border border-white/10 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:bg-white/5">Preview PDF</a>
        <a href="{{ route('admin.purchase-orders.pdf', ['purchaseOrder' => $po, 'download' => 1]) }}"
           class="rounded-xl border border-white/10 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:bg-white/5">Download</a>
        <a href="{{ route('admin.purchase-orders.edit', $po) }}"
           class="rounded-xl border border-white/10 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:bg-white/5">Edit</a>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    {{-- ---- Summary ---- --}}
    <div class="space-y-6 lg:col-span-2">
        <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-6 backdrop-blur">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Customer Information</div>
                    <div class="mt-1 font-semibold text-white">{{ $po->distributor_name }}</div>
                    <div class="text-sm text-slate-300">{{ $po->distributor_email ?: '—' }}</div>
                    <div class="text-sm text-slate-300">{{ $po->distributor_phone ?: '' }}</div>
                </div>
                <div class="sm:text-right">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Date</div>
                    <div class="mt-1 font-semibold text-white">{{ optional($po->po_date)->format('j F Y') }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Billing Address</div>
                    <div class="mt-1 whitespace-pre-line text-sm text-slate-300">{{ $po->billing_address ?: '—' }}</div>
                </div>
                <div>
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Shipping Address</div>
                    <div class="mt-1 whitespace-pre-line text-sm text-slate-300">{{ $po->shipping_address ?: '—' }}</div>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur">
            <table class="w-full text-sm">
                <thead class="bg-white/5 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Item</th>
                        <th class="px-4 py-3 text-right">Qty</th>
                        <th class="px-4 py-3 text-right">Price</th>
                        <th class="px-4 py-3 text-right">Discount</th>
                        <th class="px-4 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    @forelse ($po->lineItems() as $it)
                        <tr>
                            <td class="px-4 py-3 text-slate-200">{{ $it['description'] }}</td>
                            <td class="px-4 py-3 text-right text-slate-300">{{ rtrim(rtrim(number_format($it['qty'], 2), '0'), '.') }}</td>
                            <td class="px-4 py-3 text-right text-slate-300">${{ number_format($it['price'], 2) }}</td>
                            <td class="px-4 py-3 text-right text-slate-300">{{ $it['discount'] ? '$'.number_format($it['discount'], 2) : '—' }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-white">${{ number_format($it['total'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">No items.</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="border-t border-white/10 text-sm">
                    <tr><td colspan="4" class="px-4 py-2 text-right text-slate-400">Subtotal</td><td class="px-4 py-2 text-right text-slate-200">${{ number_format($po->subtotal, 2) }}</td></tr>
                    <tr><td colspan="4" class="px-4 py-2 text-right text-slate-400">Tax</td><td class="px-4 py-2 text-right text-slate-200">${{ number_format($po->tax, 2) }}</td></tr>
                    <tr><td colspan="4" class="px-4 py-2 text-right text-slate-400">Shipping</td><td class="px-4 py-2 text-right text-slate-200">${{ number_format($po->shipping, 2) }}</td></tr>
                    <tr><td colspan="4" class="px-4 py-3 text-right font-bold text-white">Total</td><td class="px-4 py-3 text-right text-lg font-bold text-white">${{ number_format($po->total, 2) }}</td></tr>
                </tfoot>
            </table>
        </div>

        @if ($po->notes)
            <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-6 backdrop-blur">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">Notes</div>
                <div class="mt-2 whitespace-pre-line text-sm text-slate-300">{{ $po->notes }}</div>
            </div>
        @endif
    </div>

    {{-- ---- Send + danger ---- --}}
    <div class="space-y-6">
        @php $selectedDist = $po->distributor_id ?: optional($distributors->firstWhere('is_default', true))->id ?: optional($distributors->first())->id; @endphp
        <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-6 backdrop-blur">
            <h3 class="text-sm font-bold uppercase tracking-wide text-white">Approve &amp; send</h3>
            @if ($distributors->count())
                <p class="mt-1 text-xs text-slate-400">Choose the distributor and click once — the PO (PDF) is emailed to them.</p>
                <form method="POST" action="{{ route('admin.purchase-orders.approve', $po) }}" class="mt-4 space-y-3"
                      onsubmit="return confirm('Approve {{ $po->po_number }} and email it to the selected distributor?');">
                    @csrf
                    <select name="distributor_id" class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white focus:border-white/30 focus:outline-none">
                        @foreach ($distributors as $d)
                            <option value="{{ $d->id }}" @selected($selectedDist == $d->id)>{{ $d->name }}{{ $d->company ? ' — '.$d->company : '' }} ({{ $d->email }})</option>
                        @endforeach
                    </select>
                    <button class="w-full rounded-xl bg-emerald-500 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-600">
                        {{ $po->approved_at ? 'Re-approve & resend' : 'Approve & send to distributor' }}
                    </button>
                </form>
            @else
                <p class="mt-1 text-xs text-amber-300">Add a distributor first — <a href="{{ route('admin.distributors.create') }}" class="underline">Purchase Orders → Distributors</a> — to enable one-click approve.</p>
            @endif
        </div>

        <div class="rounded-2xl border border-white/10 bg-slate-900/70 p-6 backdrop-blur">
            <h3 class="text-sm font-bold uppercase tracking-wide text-white">Send to another email</h3>
            <p class="mt-1 text-xs text-slate-400">Optional — email the PDF to a specific address.</p>
            <form method="POST" action="{{ route('admin.purchase-orders.send', $po) }}" class="mt-4 space-y-3">
                @csrf
                <input type="email" name="email" required value="{{ optional($po->distributor)->email ?: $po->distributor_email }}"
                       class="w-full rounded-xl border border-white/10 bg-slate-900/70 px-3 py-2 text-sm text-white placeholder-slate-500 focus:border-white/30 focus:outline-none"
                       placeholder="name@example.com">
                <button class="w-full rounded-xl border border-white/10 px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:bg-white/5">
                    Send PDF
                </button>
            </form>
        </div>

        <div class="rounded-2xl border border-rose-400/20 bg-rose-500/5 p-6">
            <h3 class="text-sm font-bold uppercase tracking-wide text-rose-300">Delete</h3>
            <p class="mt-1 text-xs text-slate-400">Permanently remove this purchase order.</p>
            <form method="POST" action="{{ route('admin.purchase-orders.destroy', $po) }}" class="mt-3"
                  onsubmit="return confirm('Delete {{ $po->po_number }}? This cannot be undone.');">
                @csrf @method('DELETE')
                <button class="rounded-xl border border-rose-400/40 px-4 py-2 text-sm font-semibold text-rose-300 transition hover:bg-rose-500/10">Delete purchase order</button>
            </form>
        </div>
    </div>
</div>

@endsection
