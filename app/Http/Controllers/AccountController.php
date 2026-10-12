<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountController extends Controller
{
    private function customer()
    {
        return Auth::guard('customer')->user();
    }

    public function index()
    {
        $customer = $this->customer();
        $orders = $customer->orders()->latest('placed_at')->latest('id')->paginate(10);

        return view('account.dashboard', compact('customer', 'orders'));
    }

    public function update(Request $request)
    {
        $customer = $this->customer();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:40'],
            'company' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:200'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'zip' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $customer->fill(collect($data)->except('password')->filter(fn ($v) => $v !== null)->all());
        if (! empty($data['password'])) {
            $customer->password = $data['password'];
        }
        $customer->save();

        return back()->with('status', 'Your details have been updated.');
    }

    public function order(Order $order)
    {
        // Only the owner may view their order.
        abort_unless($order->customer_id === $this->customer()->id, 404);

        $order->load('items', 'customer');

        return view('account.order', compact('order'));
    }
}
