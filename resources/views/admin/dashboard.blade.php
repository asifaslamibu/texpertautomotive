@extends('admin.layouts.app')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')
@section('subheading', 'Store performance at a glance')

@section('content')

{{-- ===== KPI cards ===== --}}
<div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
    @php
        $kpis = [
            ['Total Revenue', '$'.number_format($totalRevenue, 2), 'truck', 'from-slate-800 to-slate-950'],
            ['Orders', number_format($ordersCount), 'cart', 'from-emerald-500 to-green-600'],
            ['Customers', number_format($customersCount), 'user', 'from-sky-500 to-indigo-600'],
            ['Avg. Order Value', '$'.number_format($avgOrder, 2), 'star', 'from-violet-500 to-purple-700'],
        ];
    @endphp
    @foreach ($kpis as [$label, $value, $icon, $grad])
        <div class="group relative overflow-hidden rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-sm font-medium text-slate-400">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-black text-white">{{ $value }}</p>
                </div>
                <span class="grid h-11 w-11 place-items-center rounded-xl bg-gradient-to-br {{ $grad }} text-white shadow-lg">
                    <x-icon name="{{ $icon }}" class="h-6 w-6" />
                </span>
            </div>
        </div>
    @endforeach
</div>

{{-- ===== Stripe account summary ===== --}}
@if ($stripe)
    <div class="mt-6 rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <div class="mb-5 flex flex-wrap items-center gap-3">
            <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white shadow-lg">
                <x-icon name="lock" class="h-5 w-5" />
            </span>
            <h2 class="font-bold text-white">Stripe — Payments</h2>
            <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $stripe['mode'] === 'live' ? 'bg-emerald-500/15 text-emerald-300' : 'bg-white/10 text-slate-200' }}">{{ strtoupper($stripe['mode']) }} MODE</span>
            <a href="https://dashboard.stripe.com/{{ $stripe['mode'] === 'test' ? 'test/' : '' }}payments" target="_blank" rel="noopener" class="ml-auto text-sm font-semibold text-slate-200 hover:text-white">Open Stripe →</a>
        </div>

        @if ($stripe['error'])
            <div class="rounded-xl border border-rose-400/30 bg-rose-500/15 px-4 py-3 text-sm text-rose-200">
                Could not load Stripe data: {{ $stripe['error'] }}
            </div>
        @else
            {{-- balance + account cards --}}
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Available balance</p>
                    @forelse ($stripe['available'] as $b)
                        <p class="mt-1 text-xl font-black text-white">{{ number_format($b['amount'], 2) }} <span class="text-sm font-semibold text-slate-400">{{ $b['currency'] }}</span></p>
                    @empty
                        <p class="mt-1 text-xl font-black text-white">0.00</p>
                    @endforelse
                </div>
                <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Pending balance</p>
                    @forelse ($stripe['pending'] as $b)
                        <p class="mt-1 text-xl font-black text-white">{{ number_format($b['amount'], 2) }} <span class="text-sm font-semibold text-slate-400">{{ $b['currency'] }}</span></p>
                    @empty
                        <p class="mt-1 text-xl font-black text-white">0.00</p>
                    @endforelse
                </div>
                <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Recent payments (gross)</p>
                    <p class="mt-1 text-xl font-black text-emerald-300">{{ number_format($stripe['recent_gross'], 2) }} <span class="text-sm font-semibold text-slate-400">{{ $stripe['account']['currency'] }}</span></p>
                </div>
                <div class="rounded-xl border border-white/10 bg-white/5 p-4">
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Account</p>
                    <p class="mt-1 truncate text-sm font-bold text-white">{{ $stripe['account']['name'] ?? $stripe['account']['id'] }}</p>
                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $stripe['account']['charges_enabled'] ? 'bg-emerald-500/15 text-emerald-300' : 'bg-amber-500/15 text-amber-300' }}">{{ $stripe['account']['charges_enabled'] ? 'Charges on' : 'Charges off' }}</span>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $stripe['account']['payouts_enabled'] ? 'bg-emerald-500/15 text-emerald-300' : 'bg-amber-500/15 text-amber-300' }}">{{ $stripe['account']['payouts_enabled'] ? 'Payouts on' : 'Payouts off' }}</span>
                        <span class="rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-bold text-slate-300">{{ $stripe['account']['country'] }}</span>
                    </div>
                </div>
            </div>

            {{-- recent payments --}}
            <div class="mt-5">
                <h3 class="mb-2 text-sm font-bold text-white">Recent payments</h3>
                <div class="overflow-x-auto rounded-xl border border-white/10">
                    <table class="w-full text-sm">
                        <thead class="bg-white/5 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                            <tr>
                                <th class="px-4 py-2.5">Customer</th>
                                <th class="px-4 py-2.5">Description</th>
                                <th class="px-4 py-2.5">Date</th>
                                <th class="px-4 py-2.5">Status</th>
                                <th class="px-4 py-2.5 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/10">
                            @forelse ($stripe['payments'] as $pmt)
                                <tr class="hover:bg-white/5">
                                    <td class="px-4 py-2.5 text-slate-200">{{ $pmt['email'] }}</td>
                                    <td class="px-4 py-2.5 text-slate-400">{{ \Illuminate\Support\Str::limit($pmt['desc'], 40) }}</td>
                                    <td class="px-4 py-2.5 text-slate-400">{{ \Illuminate\Support\Carbon::createFromTimestamp($pmt['date'])->format('M d, Y g:i A') }}</td>
                                    <td class="px-4 py-2.5">
                                        @php $ok = $pmt['paid'] && ! $pmt['refunded']; @endphp
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $pmt['refunded'] ? 'bg-amber-500/15 text-amber-300' : ($ok ? 'bg-emerald-500/15 text-emerald-300' : 'bg-rose-500/15 text-rose-300') }}">
                                            {{ $pmt['refunded'] ? 'Refunded' : ($ok ? 'Succeeded' : ucfirst($pmt['status'])) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-right font-bold text-white">{{ number_format($pmt['amount'], 2) }} <span class="text-xs font-semibold text-slate-400">{{ $pmt['currency'] }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">No payments yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endif

{{-- ===== Charts row ===== --}}
<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm lg:col-span-2">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="font-bold text-white">Revenue — last 12 months</h2>
            <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-slate-200">Paid orders</span>
        </div>
        <div class="h-72"><canvas id="salesChart"></canvas></div>
    </div>

    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <h2 class="mb-4 font-bold text-white">Orders by Status</h2>
        <div class="mx-auto h-56 max-w-xs"><canvas id="statusChart"></canvas></div>
        <div class="mt-4 space-y-1.5">
            @foreach ($statusCounts as $status => $count)
                <div class="flex items-center justify-between text-sm">
                    <span class="capitalize text-slate-300">{{ $status }}</span>
                    <span class="font-semibold text-white">{{ $count }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ===== Top categories + top products ===== --}}
<div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <h2 class="mb-4 font-bold text-white">Top Categories by Revenue</h2>
        <div class="h-64"><canvas id="categoryChart"></canvas></div>
    </div>

    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <h2 class="mb-4 font-bold text-white">Best-Selling Products</h2>
        <div class="space-y-3">
            @forelse ($topProducts as $i => $p)
                <div class="flex items-center gap-3">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-white/5 text-sm font-bold text-slate-400">{{ $i + 1 }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-slate-100">{{ $p->name }}</p>
                        <p class="text-xs text-slate-400">{{ $p->units }} units sold</p>
                    </div>
                    <span class="shrink-0 text-sm font-bold text-emerald-300">${{ number_format($p->revenue, 0) }}</span>
                </div>
            @empty
                <p class="text-sm text-slate-400">No sales yet.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- ===== Recent orders ===== --}}
<div class="mt-6 rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur shadow-sm">
    <div class="flex items-center justify-between border-b border-white/10 p-6">
        <h2 class="font-bold text-white">Recent Orders</h2>
        <a href="{{ route('admin.orders.index') }}" class="text-sm font-semibold text-slate-200 hover:text-white">View all →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-white/5 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                <tr>
                    <th class="px-6 py-3">Order</th>
                    <th class="px-6 py-3">Customer</th>
                    <th class="px-6 py-3">Date</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3 text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @foreach ($recentOrders as $order)
                    <tr class="transition hover:bg-white/5">
                        <td class="px-6 py-3"><a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-slate-200">{{ $order->order_number }}</a></td>
                        <td class="px-6 py-3 text-slate-200">{{ $order->customer->name ?? '—' }}</td>
                        <td class="px-6 py-3 text-slate-400">{{ $order->placed_at?->format('M d, Y') }}</td>
                        <td class="px-6 py-3">
                            <span class="rounded-full bg-{{ $order->status_color }}-500/15 px-2.5 py-1 text-xs font-semibold capitalize text-{{ $order->status_color }}-300">{{ $order->status }}</span>
                        </td>
                        <td class="px-6 py-3 text-right font-bold text-white">${{ number_format($order->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    const sales = @json($salesChart);
    const statusData = @json($statusCounts);
    const catData = @json($topCategories);

    Chart.defaults.font.family = 'Inter, sans-serif';
    Chart.defaults.color = '#94a3b8';

    // Revenue line
    new Chart(document.getElementById('salesChart'), {
        type: 'line',
        data: {
            labels: sales.labels,
            datasets: [{
                label: 'Revenue ($)', data: sales.revenue,
                borderColor: '#818cf8', backgroundColor: 'rgba(129,140,248,.15)',
                fill: true, tension: .4, pointRadius: 3, pointBackgroundColor: '#818cf8', borderWidth: 2,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.1)' } }, x: { grid: { display: false } } }
        }
    });

    // Status doughnut
    const statusColors = { pending:'#f59e0b', processing:'#3b82f6', shipped:'#6366f1', completed:'#10b981', cancelled:'#f43f5e' };
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: Object.keys(statusData),
            datasets: [{ data: Object.values(statusData), backgroundColor: Object.keys(statusData).map(s => statusColors[s]), borderWidth: 0 }]
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { display: false } } }
    });

    // Category bar
    new Chart(document.getElementById('categoryChart'), {
        type: 'bar',
        data: {
            labels: catData.map(c => c.name),
            datasets: [{ label: 'Revenue ($)', data: catData.map(c => Math.round(c.revenue)), backgroundColor: '#818cf8', borderRadius: 6 }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.1)' } }, y: { grid: { display: false } } }
        }
    });
</script>
@endpush

@endsection
