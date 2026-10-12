<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Support\Cart;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /** Put a discount code on the cart, or say plainly why it cannot be used. */
    public function store(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'max:40'],
        ], ['code.required' => 'Please enter a discount code.']);

        $subtotal = Cart::subtotal();
        $coupon = Coupon::findByCode($request->input('code'));

        if (! $coupon) {
            return $this->respond($request, false, 'That discount code was not recognised.');
        }

        if ($reason = $coupon->reasonUnusable($subtotal)) {
            return $this->respond($request, false, $reason);
        }

        Cart::applyCoupon($coupon);

        return $this->respond($request, true, 'Discount applied — '.$coupon->label().'.');
    }

    public function destroy(Request $request)
    {
        Cart::forgetCoupon();

        return $this->respond($request, true, 'Discount code removed.');
    }

    /** The checkout page swaps its summary in place; the cart page just reloads. */
    private function respond(Request $request, bool $ok, string $message)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => $ok,
                'message' => $message,
                'summary' => view('checkout.partials.summary', $this->summaryData())->render(),
                'totalCents' => (int) round(Cart::total() * 100),
            ], $ok ? 200 : 422);
        }

        return $ok
            ? back()->with('status', $message)
            : back()->withErrors(['code' => $message]);
    }

    private function summaryData(): array
    {
        $subtotal = Cart::subtotal();

        return [
            'items' => Cart::items(),
            'subtotal' => $subtotal,
            'tax' => Cart::tax($subtotal),
            'freeShippingCart' => ! Cart::shippingEnabled() || Cart::hasFreeShipping(),
        ];
    }
}
