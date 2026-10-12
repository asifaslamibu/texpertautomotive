<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\StripeReport;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $paidStatuses = ['pending', 'processing', 'shipped', 'completed'];

        // ---- KPIs ----
        $totalRevenue = Order::whereIn('status', $paidStatuses)->sum('total');
        $ordersCount = Order::count();
        $customersCount = Customer::count();
        $productsCount = Product::count();
        $avgOrder = $ordersCount ? $totalRevenue / Order::whereIn('status', $paidStatuses)->count() : 0;

        // ---- Sales last 12 months (grouped in PHP for DB portability) ----
        $start = Carbon::now()->startOfMonth()->subMonths(11);
        $months = collect(range(0, 11))->mapWithKeys(function ($i) use ($start) {
            $m = $start->copy()->addMonths($i);
            return [$m->format('Y-m') => ['label' => $m->format('M'), 'revenue' => 0, 'orders' => 0]];
        });

        Order::whereIn('status', $paidStatuses)
            ->where('placed_at', '>=', $start)
            ->get(['total', 'placed_at'])
            ->each(function ($o) use (&$months) {
                $key = $o->placed_at->format('Y-m');
                if (isset($months[$key])) {
                    $bucket = $months[$key];
                    $bucket['revenue'] += (float) $o->total;
                    $bucket['orders'] += 1;
                    $months[$key] = $bucket;
                }
            });

        $salesChart = [
            'labels'  => $months->pluck('label')->values(),
            'revenue' => $months->pluck('revenue')->map(fn ($v) => round($v, 2))->values(),
            'orders'  => $months->pluck('orders')->values(),
        ];

        // ---- Orders by status ----
        $statusCounts = collect(Order::STATUSES)->mapWithKeys(fn ($s) => [
            $s => Order::where('status', $s)->count(),
        ]);

        // ---- Top categories by revenue ----
        $topCategories = OrderItem::query()
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->selectRaw('categories.name as name, SUM(order_items.line_total) as revenue')
            ->groupBy('categories.name')
            ->orderByDesc('revenue')
            ->limit(6)
            ->get();

        // ---- Recent orders & low stock ----
        $recentOrders = Order::with('customer')->latest('placed_at')->take(8)->get();
        $topProducts = OrderItem::query()
            ->selectRaw('name, SUM(quantity) as units, SUM(line_total) as revenue')
            ->groupBy('name')
            ->orderByDesc('units')
            ->limit(5)
            ->get();
        $pendingCount = Order::where('status', 'pending')->count();

        // ---- Stripe account summary — master (secret) admin only ----
        $stripe = auth()->user()?->isMaster() ? StripeReport::summary() : null;

        return view('admin.dashboard', compact(
            'totalRevenue', 'ordersCount', 'customersCount', 'productsCount', 'avgOrder',
            'salesChart', 'statusCounts', 'topCategories', 'recentOrders', 'topProducts', 'pendingCount',
            'stripe'
        ));
    }
}
