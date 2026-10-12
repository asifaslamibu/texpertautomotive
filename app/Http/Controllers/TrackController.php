<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class TrackController extends Controller
{
    public function index(Request $request)
    {
        $order = null;
        $searched = false;

        if ($request->filled('order') && $request->filled('email')) {
            $searched = true;
            $order = Order::with(['items', 'customer'])
                ->where('order_number', trim($request->order))
                ->whereHas('customer', fn ($q) => $q->where('email', trim($request->email)))
                ->first();
        }

        return view('pages.track', compact('order', 'searched'));
    }
}
