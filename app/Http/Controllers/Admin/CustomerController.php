<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    use \App\Http\Controllers\Concerns\SortsRecords;

    public function index(Request $request)
    {
        $query = Customer::withCount('orders')->withSum('orders as orders_total', 'total');

        if ($request->filled('q')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$request->q}%")
                ->orWhere('email', 'like', "%{$request->q}%")
                ->orWhere('company', 'like', "%{$request->q}%"));
        }

        $this->applySort($query, $request, ['name', 'email', 'created_at'], 'created_at', 'desc');

        $customers = $query->paginate(15)->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function bulkDestroy(\Illuminate\Http\Request $request)
    {
        $ids = (array) $request->input('ids', []);
        $n = \App\Models\Customer::whereIn('id', $ids)->delete();

        return back()->with('status', $n.' customer(s) deleted.');
    }

    public function show(Customer $customer)
    {
        $customer->load(['orders' => fn ($q) => $q->latest('placed_at')]);

        return view('admin.customers.show', compact('customer'));
    }
}
