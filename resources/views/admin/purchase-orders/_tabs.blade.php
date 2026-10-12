@php
    $tabs = [
        ['admin.purchase-orders.index', 'Purchase Orders', 'admin.purchase-orders.*'],
        ['admin.distributors.index', 'Distributors', 'admin.distributors.*'],
    ];
@endphp
<div class="mb-5 flex flex-wrap gap-2 border-b border-white/10 pb-3">
    @foreach ($tabs as [$route, $label, $pattern])
        <a href="{{ route($route) }}"
           class="rounded-full px-4 py-1.5 text-sm font-semibold transition {{ request()->routeIs($pattern) ? 'bg-white text-slate-900' : 'border border-white/10 text-slate-300 hover:bg-white/10' }}">
            {{ $label }}
        </a>
    @endforeach
</div>
