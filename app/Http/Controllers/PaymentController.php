<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\StripeConfig;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class PaymentController extends Controller
{
    /** Customer-facing payment page for an admin-created order. */
    public function show(string $token)
    {
        $order = $this->resolve($token);
        $order->load('items', 'customer');

        return view('pay.show', [
            'order' => $order,
            'stripeReady' => StripeConfig::enabled(),
        ]);
    }

    /** Start a Stripe Checkout session for the order and send the customer to Stripe. */
    public function pay(string $token)
    {
        $order = $this->resolve($token);

        if ($order->payment_status === 'paid') {
            return redirect()->route('pay.show', $token);
        }
        if (! StripeConfig::enabled()) {
            return back()->with('status', 'Online payment is not available right now. Please contact us to pay.');
        }

        $order->loadMissing('items', 'customer');
        $currency = StripeConfig::currency();

        $lineItems = [];
        foreach ($order->items as $item) {
            $lineItems[] = [
                'quantity' => (int) $item->quantity,
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => (int) round((float) $item->price * 100),
                    'product_data' => ['name' => $item->name],
                ],
            ];
        }
        if ((float) $order->shipping > 0) {
            $lineItems[] = ['quantity' => 1, 'price_data' => ['currency' => $currency, 'unit_amount' => (int) round((float) $order->shipping * 100), 'product_data' => ['name' => 'Shipping']]];
        }
        if ((float) $order->tax > 0) {
            $lineItems[] = ['quantity' => 1, 'price_data' => ['currency' => $currency, 'unit_amount' => (int) round((float) $order->tax * 100), 'product_data' => ['name' => 'Sales tax']]];
        }

        // A discounted order is charged as one line for the agreed total — Stripe
        // line items cannot be negative, and the order total is what was quoted.
        if ((float) ($order->discount ?? 0) > 0) {
            $percent = rtrim(rtrim(number_format((float) $order->discount_percent, 2), '0'), '.');
            $lineItems = [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => (int) round((float) $order->total * 100),
                    'product_data' => ['name' => 'Order '.$order->order_number.' ('.$percent.'% discount applied)'],
                ],
            ]];
        }

        try {
            $stripe = new StripeClient(StripeConfig::secret());
            $session = $stripe->checkout->sessions->create([
                'mode' => 'payment',
                // card only: no Link sign-up block on the payment page
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'customer_email' => $order->customer?->email,
                'client_reference_id' => (string) $order->id,
                'metadata' => ['order_id' => (string) $order->id, 'order_number' => $order->order_number],
                'success_url' => route('checkout.success', $order).'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('pay.show', $token),
            ]);
        } catch (\Throwable $e) {
            Log::error('Pay-link Stripe session failed: '.$e->getMessage());

            return back()->with('status', 'We could not start the payment. Please try again in a moment.');
        }

        $order->update(['stripe_session_id' => $session->id]);

        return redirect()->away($session->url, 303);
    }

    /** Look up an order by its pay token (404 if unknown). */
    private function resolve(string $token): Order
    {
        return Order::where('pay_token', $token)->firstOrFail();
    }
}
