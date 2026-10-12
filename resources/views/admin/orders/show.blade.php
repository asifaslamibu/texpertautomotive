@extends('admin.layouts.app')

@section('title', 'Order '.$order->order_number)
@section('heading', 'Order '.$order->order_number)
@section('subheading', 'Placed '.$order->placed_at?->format('M d, Y \a\t g:i A'))

@section('content')

<div class="mb-5">
    <a href="{{ route('admin.orders.index') }}" class="text-sm font-semibold text-slate-400 hover:text-white">← Back to orders</a>
</div>

@if ($order->shipping_quote_pending)
    <div class="mb-6 rounded-2xl border border-rose-400/30 bg-rose-500/10 p-5">
        <h2 class="font-bold text-white">Shipping quote needed</h2>
        <p class="mt-1 text-sm text-slate-300">
            No carrier could rate this order at checkout (too heavy or oversized), so the customer placed it <b>without shipping and without paying</b>.
            Get a freight / carrier quote, enter it under <b>Charges → Shipping $</b> (and the carrier under <b>Shipping service</b>), save,
            then send the customer the payment link below.
        </p>
    </div>
@endif

@if ($order->pay_token && $order->payment_status !== 'paid')
    <div class="mb-6 rounded-2xl border border-amber-400/25 bg-amber-400/5 p-5">
        <div class="flex items-center gap-2">
            <x-icon name="lock" class="h-5 w-5 text-amber-400" />
            <h2 class="font-bold text-white">Payment link</h2>
        </div>
        <p class="mt-1 text-sm text-slate-400">Send this link to the customer — they can pay <span class="font-semibold text-slate-200">${{ number_format($order->total, 2) }}</span> online. The order is marked paid automatically once payment completes.</p>
        <div class="mt-3 flex flex-col gap-2 sm:flex-row">
            <input type="text" readonly value="{{ $order->payUrl() }}" data-pay-link
                   class="w-full rounded-lg border border-white/10 bg-slate-950/60 px-3 py-2.5 font-mono text-xs text-slate-100 outline-none">
            <div class="flex shrink-0 gap-2">
                <button type="button" data-copy-pay class="rounded-lg bg-white px-4 py-2.5 text-sm font-bold text-slate-900 hover:bg-slate-200">Copy</button>
                <a href="{{ $order->payUrl() }}" target="_blank" class="rounded-lg border border-white/10 px-4 py-2.5 text-sm font-semibold text-slate-200 hover:bg-white/5">Open</a>
            </div>
        </div>
    </div>
    @push('scripts')
    <script>
        document.querySelector('[data-copy-pay]')?.addEventListener('click', function () {
            var i = document.querySelector('[data-pay-link]');
            i.select(); i.setSelectionRange(0, 99999);
            navigator.clipboard && navigator.clipboard.writeText(i.value);
            var t = this.textContent; this.textContent = 'Copied!';
            setTimeout(() => { this.textContent = t; }, 1500);
        });
    </script>
    @endpush
@endif

<div class="grid gap-6 lg:grid-cols-3">
    {{-- items --}}
    <div class="space-y-6 lg:col-span-2">
        <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur shadow-sm">
            <div class="flex items-center justify-between border-b border-white/10 p-5">
                <h2 class="font-bold text-white">Items ({{ $order->items->count() }})</h2>
                <div class="flex items-center gap-2">
                    @if ($order->payment_status === 'paid')
                        <span class="rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-bold text-emerald-300">Paid · {{ $order->payment_method }}</span>
                    @else
                        <span class="rounded-full bg-white/5 px-3 py-1 text-xs font-bold text-slate-400">Unpaid · {{ $order->payment_method }}</span>
                    @endif
                    <span class="rounded-full bg-{{ $order->status_color }}-500/15 px-3 py-1 text-xs font-semibold capitalize text-{{ $order->status_color }}-300">{{ $order->status }}</span>
                </div>
            </div>
            <div class="divide-y divide-white/10">
                @foreach ($order->items as $item)
                    <div class="flex items-center gap-4 p-5">
                        <img src="{{ media($item->product->image ?? '') ?: 'https://placehold.co/80' }}" class="h-14 w-14 shrink-0 rounded-lg border border-white/10 object-cover" alt="">
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-semibold text-slate-100">{{ $item->name }}</p>
                            <p class="text-sm text-slate-400">${{ number_format($item->price, 2) }} × {{ $item->quantity }}</p>
                        </div>
                        <p class="font-bold text-white">${{ number_format($item->line_total, 2) }}</p>
                    </div>
                @endforeach
            </div>
            <div class="space-y-2 border-t border-white/10 bg-white/5 p-5 text-sm">
                <div class="flex justify-between text-slate-300"><span>Subtotal</span><span>${{ number_format($order->subtotal, 2) }}</span></div>
                <div class="flex justify-between text-slate-300"><span>Shipping</span><span>{{ $order->shipping > 0 ? '$'.number_format($order->shipping, 2) : ($order->shipping_quote_pending ? 'To be quoted' : 'Free') }}</span></div>
                @if ((float) ($order->discount ?? 0) > 0)
                    <div class="flex justify-between text-emerald-300"><span>Discount ({{ (float) ($order->discount_percent ?? 0) > 0 ? rtrim(rtrim(number_format((float) $order->discount_percent, 2), '0'), '.').'%' : ($order->coupon_code ?? 'code') }})</span><span>−${{ number_format((float) $order->discount, 2) }}</span></div>
                @endif
                <div class="flex justify-between text-slate-300"><span>Tax</span><span>${{ number_format($order->tax, 2) }}</span></div>
                <div class="flex justify-between border-t border-white/10 pt-2 text-base font-bold text-white"><span>Total</span><span>${{ number_format($order->total, 2) }}</span></div>
            </div>
        </div>
    </div>

    {{-- sidebar --}}
    <div class="space-y-6">
        {{-- update status --}}
        <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-5 shadow-sm">
            <h2 class="mb-4 font-bold text-white">Update order</h2>
            <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Status</label>
                    <select name="status" class="w-full rounded-lg border border-white/10 bg-slate-950/50 px-3 py-2.5 text-sm text-slate-100 capitalize outline-none focus:border-slate-400">
                        @foreach (\App\Models\Order::STATUSES as $s)
                            <option value="{{ $s }}" @selected($order->status==$s)>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-200">Tracking number</label>
                        <input name="tracking_number" value="{{ $order->tracking_number }}" placeholder="1Z…" class="w-full rounded-lg border border-white/10 bg-slate-950/50 px-3 py-2.5 text-sm text-slate-100 placeholder:text-slate-500 outline-none focus:border-slate-400">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-slate-200">Shipping service</label>
                        <input name="shipping_service" value="{{ $order->shipping_service }}" placeholder="UPS Ground" class="w-full rounded-lg border border-white/10 bg-slate-950/50 px-3 py-2.5 text-sm text-slate-100 placeholder:text-slate-500 outline-none focus:border-slate-400">
                    </div>
                </div>
                @php
                    $fld = 'w-full rounded-lg border border-white/10 bg-slate-950/50 px-3 py-2.5 text-sm text-slate-100 placeholder:text-slate-500 outline-none focus:border-slate-400';
                    $c = $order->customer;
                @endphp

                {{-- charges — total is recalculated on save --}}
                <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                    <p class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-400">Charges</p>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-200">Shipping $</label>
                            <input name="shipping" type="number" step="0.01" min="0" value="{{ old('shipping', number_format((float) $order->shipping, 2, '.', '')) }}" class="{{ $fld }}">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-200">Tax $</label>
                            <input name="tax" type="number" step="0.01" min="0" value="{{ old('tax', number_format((float) $order->tax, 2, '.', '')) }}" class="{{ $fld }}">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-200">Discount %</label>
                            <input name="discount_percent" type="number" step="0.01" min="0" max="100" value="{{ old('discount_percent', number_format((float) ($order->discount_percent ?? 0), 2, '.', '')) }}" class="{{ $fld }}">
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-slate-400">Subtotal stays ${{ number_format((float) $order->subtotal, 2) }} — the total is worked out again when you save.</p>
                </div>

                {{-- ship-to address (saved on the customer record) --}}
                <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                    <p class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-400">Ship to</p>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-200">Name</label>
                            <input name="ship_name" value="{{ old('ship_name', $c?->name) }}" class="{{ $fld }}">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-200">Phone</label>
                            <input name="ship_phone" value="{{ old('ship_phone', $c?->phone) }}" class="{{ $fld }}">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-sm font-medium text-slate-200">Company</label>
                            <input name="ship_company" value="{{ old('ship_company', $c?->company) }}" class="{{ $fld }}">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1.5 block text-sm font-medium text-slate-200">Street address</label>
                            <input name="ship_address" value="{{ old('ship_address', $c?->address) }}" placeholder="123 Main St, Suite 200" class="{{ $fld }}">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-200">City</label>
                            <input name="ship_city" value="{{ old('ship_city', $c?->city) }}" class="{{ $fld }}">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-200">State</label>
                                <input name="ship_state" value="{{ old('ship_state', $c?->state) }}" placeholder="TX" class="{{ $fld }}">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-200">ZIP</label>
                                <input name="ship_zip" value="{{ old('ship_zip', $c?->zip) }}" placeholder="75001" class="{{ $fld }}">
                            </div>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-slate-400">This is the customer's address on file — used for the UPS label.</p>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Internal notes</label>
                    <textarea name="notes" rows="3" class="w-full rounded-lg border border-white/10 bg-slate-950/50 px-3 py-2.5 text-sm text-slate-100 placeholder:text-slate-500 outline-none focus:border-slate-400">{{ $order->notes }}</textarea>
                </div>
                <button class="w-full rounded-lg bg-white py-2.5 text-sm font-bold text-slate-900 transition hover:bg-slate-200">Save changes</button>
            </form>

            {{-- UPS label --}}
            @if (\App\Support\Ups::enabled())
                <div class="mt-4 border-t border-white/10 pt-4">
                    @error('label')<p class="mb-2 text-xs text-rose-400">{{ $message }}</p>@enderror
                    <div class="flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('admin.orders.label', $order) }}">
                            @csrf
                            <select name="service_code" class="rounded-lg border border-white/10 bg-slate-950/50 px-2.5 py-2 text-xs text-slate-100 outline-none">
                                <option value="03">UPS Ground</option>
                                <option value="02">2nd Day Air</option>
                                <option value="01">Next Day Air</option>
                                <option value="12">3 Day Select</option>
                            </select>
                            <button class="rounded-lg bg-amber-400 px-3 py-2 text-xs font-bold text-slate-900 transition hover:bg-amber-300">
                                {{ $order->label_path ? 'Re-create UPS label' : 'Generate UPS label' }}
                            </button>
                        </form>
                        @if ($order->label_path)
                            <a href="{{ route('admin.orders.label.download', $order) }}" class="rounded-lg border border-white/15 px-3 py-2 text-xs font-semibold text-slate-100 transition hover:bg-white/10">↓ Download label</a>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- live UPS tracking --}}
        @if ($order->tracking_number)
            @php $track = \App\Support\Ups::track($order->tracking_number); @endphp
            <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-5 shadow-sm">
                <h2 class="mb-3 font-bold text-white">Tracking</h2>
                <p class="text-sm text-slate-300">{{ $order->shipping_service ?: 'UPS' }} · <span class="font-mono">{{ $order->tracking_number }}</span></p>
                <a href="https://www.ups.com/track?tracknum={{ $order->tracking_number }}" target="_blank" rel="noopener" class="text-xs font-semibold text-amber-400 hover:text-amber-300">Open on UPS.com →</a>
                @if ($track)
                    <p class="mt-3 inline-block rounded-full px-3 py-1 text-xs font-bold {{ $track['delivered'] ? 'bg-emerald-500/15 text-emerald-400' : 'bg-sky-500/15 text-sky-300' }}">{{ $track['status'] }}</p>
                    @if (! empty($track['activity']))
                        <ul class="mt-3 space-y-1.5 text-xs text-slate-400">
                            @foreach (array_slice($track['activity'], 0, 5) as $a)
                                <li>{{ $a['status'] }} @if($a['location'])— {{ $a['location'] }}@endif</li>
                            @endforeach
                        </ul>
                    @endif
                @endif
            </div>
        @endif

        {{-- purchase order --}}
        @php $canPo = auth()->user()?->isSuperAdmin() || auth()->user()?->hasPermission('purchase_orders'); @endphp
        @if ($canPo)
            <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-5 shadow-sm">
                <h2 class="mb-3 font-bold text-white">Purchase order</h2>
                @if ($order->purchaseOrder)
                    <p class="text-sm text-slate-400">
                        {{ $order->purchaseOrder->po_number }} —
                        @if ($order->purchaseOrder->approved_at)
                            <span class="font-semibold text-emerald-300">Approved &amp; sent</span>
                        @else
                            <span class="font-semibold text-amber-300">Pending approval</span>
                        @endif
                    </p>
                    <a href="{{ route('admin.purchase-orders.show', $order->purchaseOrder) }}"
                       class="mt-3 block w-full rounded-lg border border-white/10 py-2.5 text-center text-sm font-semibold text-slate-200 transition hover:bg-white/5">Open purchase order →</a>
                @else
                    <p class="text-sm text-slate-400">No purchase order yet for this order.</p>
                    <form method="POST" action="{{ route('admin.purchase-orders.from-order', $order) }}" class="mt-3">
                        @csrf
                        <button class="w-full rounded-lg bg-white py-2.5 text-sm font-bold text-slate-900 transition hover:bg-slate-200">Generate purchase order</button>
                    </form>
                @endif
            </div>
        @endif

        {{-- customer --}}
        <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-5 shadow-sm">
            <h2 class="mb-3 font-bold text-white">Customer</h2>
            @if ($order->customer)
                <a href="{{ route('admin.customers.show', $order->customer) }}" class="font-semibold text-slate-200">{{ $order->customer->name }}</a>
                <div class="mt-2 space-y-1 text-sm text-slate-400">
                    <p>{{ $order->customer->email }}</p>
                    <p>{{ $order->customer->phone }}</p>
                    @if ($order->customer->company)<p>{{ $order->customer->company }}</p>@endif
                    <p class="pt-1 text-slate-300">{{ $order->customer->address }}<br>{{ $order->customer->city }}, {{ $order->customer->state }} {{ $order->customer->zip }}</p>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
