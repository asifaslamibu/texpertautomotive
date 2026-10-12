<?php

namespace App\Http\Controllers;

use App\Mail\OrderConfirmationMail;
use App\Mail\OrderNotificationMail;
use App\Models\CheckoutLead;
use App\Models\Customer;
use App\Models\Order;
use App\Support\Cart;
use App\Support\Notify;
use App\Support\Shippo;
use App\Support\StripeConfig;
use App\Support\Ups;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Stripe\StripeClient;

class CheckoutController extends Controller
{
    public function index()
    {
        // stock can move while someone is shopping — fix the cart before they pay
        if ($changes = Cart::sync()) {
            return redirect()->route('cart.index')->with('status', implode(' ', $changes));
        }

        $items = Cart::items();
        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('status', 'Your cart is empty.');
        }

        return view('checkout.index', [
            'items' => $items,
            'subtotal' => Cart::subtotal(),
            'shipping' => Cart::shipping(),
            'tax' => Cart::tax(),
            'total' => Cart::total(),
            'stripeEnabled' => StripeConfig::enabled(),
            'stripeKey' => StripeConfig::key(),
            'stripeCurrency' => StripeConfig::currency(),
            'stripeCheckoutType' => StripeConfig::checkoutType(),
            'totalCents' => (int) round(Cart::total() * 100),
            'customer' => auth('customer')->user(),
        ]);
    }

    /**
     * Change a line's quantity from the order summary at checkout. Returns the
     * re-rendered summary so the page never has to reload.
     */
    public function quantity(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'qty' => ['required', 'integer', 'min:0', 'max:999'],
        ]);

        $product = \App\Models\Product::find($data['product_id']);
        $result = Cart::update($data['product_id'], $data['qty']);

        $message = null;
        if ($result['capped'] && $result['qty'] > 0) {
            $message = 'Only '.$result['available'].' of '.($product?->name ?? 'this item').' in stock.';
        } elseif ($result['capped']) {
            $message = ($product?->name ?? 'That item').' is out of stock.';
        }

        $items = Cart::items();
        if ($items->isEmpty()) {
            return response()->json(['empty' => true, 'redirect' => route('cart.index')]);
        }

        $subtotal = Cart::subtotal();

        return response()->json([
            'html' => view('checkout.partials.summary', [
                'items' => $items,
                'subtotal' => $subtotal,
                'tax' => Cart::tax($subtotal),
                'freeShippingCart' => ! Cart::shippingEnabled() || Cart::hasFreeShipping(),
            ])->render(),
            'count' => Cart::count(),
            'message' => $message,
        ]);
    }

    /**
     * Save what the buyer has typed so far (called from the checkout page as the
     * fields change). One row per session, so an abandoned checkout can still be
     * followed up. Never fails the page: bad input is simply ignored.
     */
    public function lead(Request $request)
    {
        if (! Schema::hasTable('checkout_leads')) {
            return response()->json(['saved' => false]);
        }

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['nullable', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:40'],
            'company' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:200'],
            'city' => ['nullable', 'string', 'max:80'],
            'state' => ['nullable', 'string', 'max:80'],
            'zip' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $items = Cart::items();
        if ($items->isEmpty() && ! array_filter($data)) {
            return response()->json(['saved' => false]);
        }

        CheckoutLead::updateOrCreate(
            ['session_id' => $request->session()->getId()],
            $data + [
                'customer_id' => auth('customer')->id(),
                'items' => $items->map(fn ($i) => [
                    'name' => $i['product']->name,
                    'sku' => $i['product']->sku,
                    'qty' => $i['qty'],
                    'price' => (float) $i['product']->price,
                ])->values()->all(),
                'cart_total' => Cart::subtotal(),
            ]
        );

        return response()->json(['saved' => true]);
    }

    /**
     * "Email me with news and offers" at checkout — adds the buyer to the
     * newsletter list and sends the welcome email once. Never fails the order.
     */
    private function subscribe(string $email): void
    {
        try {
            $subscriber = \App\Models\Subscriber::firstOrCreate(
                ['email' => strtolower(trim($email))],
                ['source' => 'checkout']
            );

            if ($subscriber->wasRecentlyCreated) {
                Mail::to($subscriber->email)->send(new \App\Mail\NewsletterWelcomeMail($subscriber->email));
            }
        } catch (\Throwable $e) {
            Log::warning('Checkout newsletter sign-up failed for '.$email.': '.$e->getMessage());
        }
    }

    /** Mark this session's checkout as converted once the order exists. */
    private function convertLead(Order $order): void
    {
        if (! Schema::hasTable('checkout_leads')) {
            return;
        }

        CheckoutLead::where('session_id', session()->getId())
            ->whereNull('order_id')
            ->update(['order_id' => $order->id, 'ordered_at' => now()]);
    }

    /**
     * Live UPS shipping rates for the current cart to a destination — called from
     * the checkout page as the buyer fills in their address. Falls back to the
     * flat rate (as a single option) when UPS is off or returns nothing, and also
     * runs a soft address check so the page can suggest a correction.
     */
    public function rates(Request $request)
    {
        $data = $request->validate([
            'zip' => ['required', 'string', 'max:20'],
            'state' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
            'address' => ['nullable', 'string', 'max:200'],
            'country' => ['nullable', 'string', 'max:2'],
        ]);

        $dest = [
            'zip' => $data['zip'],
            'state' => $data['state'] ?? '',
            'city' => $data['city'] ?? '',
            'address' => $data['address'] ?? '',
            'country' => $data['country'] ?? 'US',
        ];

        // Shipping charges switched off in settings, or a free-shipping product in
        // the cart — either way the whole order ships free.
        if (! Cart::shippingEnabled() || Cart::hasFreeShipping()) {
            return response()->json(['rates' => [['service' => 'Free Shipping', 'code' => 'FREE', 'cost' => 0]], 'suggestion' => null]);
        }

        $live = $this->liveRates($dest);

        if ($live === null) {
            // No carrier set up at all: the flat rate is the shipping price.
            $flat = Cart::shipping();
            $rates = [[
                'service' => $flat > 0 ? 'Standard Shipping' : 'Free Shipping',
                'code' => 'FLAT',
                'cost' => $flat,
            ]];
        } elseif (! $live) {
            // Carriers are on but none can take this order (too heavy / oversized):
            // never guess a price — the team quotes it after the order is placed.
            return response()->json(['rates' => [], 'quote' => true, 'suggestion' => null]);
        } else {
            $rates = $live;
        }

        $suggestion = null;
        if (! empty($data['address']) && Ups::enabled()) {
            $check = Ups::validateAddress($data + $dest);
            if (! $check['valid'] && ! empty($check['candidates'])) {
                $suggestion = $check['candidates'][0];
            }
        }

        return response()->json(['rates' => $rates, 'suggestion' => $suggestion]);
    }

    /**
     * Resolve the shipping cost + service the order should actually use — never
     * trusting the amount from the browser. If a UPS service was chosen we
     * recompute it here; anything unavailable falls back to the flat rate.
     *
     * @return array{cost: float, service: ?string}
     */
    private function resolveShipping(array $data): array
    {
        // Shipping off in settings, or a free-shipping product in the cart —
        // either overrides any chosen rate.
        if (! Cart::shippingEnabled() || Cart::hasFreeShipping()) {
            return ['cost' => 0.0, 'service' => 'Free Shipping'];
        }

        // The price is always worked out again here — never taken from the browser.
        $live = $this->liveRates([
            'zip' => $data['zip'], 'state' => $data['state'], 'city' => $data['city'],
            'address' => $data['address'] ?? '', 'country' => 'US',
        ]);

        // no carrier set up: flat rate
        if ($live === null) {
            $flat = Cart::shipping();

            return ['cost' => $flat, 'service' => $flat > 0 ? 'Standard Shipping' : 'Free Shipping'];
        }

        // carriers on, but nothing can take this order: the team quotes it
        if (! $live) {
            return ['cost' => 0.0, 'service' => Order::SHIPPING_QUOTE, 'quote' => true];
        }

        // the service the buyer picked, else the cheapest one offered
        $code = $data['ship_service'] ?? null;
        foreach ($live as $r) {
            if ($r['code'] === $code) {
                return ['cost' => $r['cost'], 'service' => $r['service']];
            }
        }

        return ['cost' => $live[0]['cost'], 'service' => $live[0]['service']];
    }

    /**
     * Carrier rates for the cart. null = no carrier available (none set up, or
     * Shippo unreachable) → the flat rate; [] = Shippo answered that no service can
     * take this order → the team quotes it.
     */
    private function liveRates(array $dest): ?array
    {
        if (Shippo::enabled()) {
            $rates = Shippo::ratesForParcels($dest, Cart::parcels());
            if ($rates !== null) {
                return $rates;
            }
        }

        // UPS rates by weight only, and returns [] on its own errors too, so an
        // empty answer keeps the old behaviour: flat rate.
        if (Ups::enabled()) {
            return Ups::rates($dest, Cart::weight()) ?: null;
        }

        return null;
    }

    public function store(Request $request)
    {
        // last word on stock: never let an order pass for more than is on hand
        if ($changes = Cart::sync()) {
            $message = implode(' ', $changes);

            return $request->expectsJson()
                ? response()->json(['errors' => ['cart' => [$message]]], 422)
                : redirect()->route('cart.index')->with('status', $message);
        }

        $items = Cart::items();
        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('status', 'Your cart is empty.');
        }

        // Keep whatever was typed before validation runs, so a rejected (or later
        // abandoned) checkout is still recorded. Never blocks the order.
        try {
            $this->lead($request->duplicate(null, $request->only([
                'name', 'email', 'phone', 'company', 'address', 'city', 'state', 'zip', 'notes',
            ])));
        } catch (\Throwable $e) {
            Log::warning('Checkout lead not saved: '.$e->getMessage());
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:160'],
            'phone' => ['required', 'string', 'max:40', 'regex:/^[\d\s()+.\-]{10,}$/'],
            'company' => ['nullable', 'string', 'max:120'],
            'address' => ['required', 'string', 'min:5', 'max:200'],
            'city' => ['required', 'string', 'max:80'],
            'state' => ['required', 'string', 'size:2'],
            'zip' => ['required', 'string', 'regex:/^\d{5}(-\d{4})?$/'],
            'payment_method' => ['required', 'in:Card,PayPal,ShopPay,Net 30'],
            'notes' => ['nullable', 'string', 'max:500'],
            // carrier service tokens can be long (e.g. "usps_priority_mail_express")
            'ship_service' => ['nullable', 'string', 'max:100'],
            'subscribe' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Please enter the full name for this order.',
            'email.required' => 'Please enter an email address — your receipt and tracking go there.',
            'email.email' => 'That email address does not look right, e.g. name@company.com.',
            'phone.required' => 'Please enter a phone number — the carrier may need it for delivery.',
            'phone.regex' => 'Please enter a phone number with at least 10 digits.',
            'address.required' => 'Please enter the street address to ship to.',
            'address.min' => 'That street address looks too short.',
            'city.required' => 'Please enter the city.',
            'state.required' => 'Please choose a state.',
            'state.size' => 'Please choose a state from the list.',
            'zip.required' => 'Please enter the ZIP code.',
            'zip.regex' => 'Please enter a 5-digit US ZIP code, e.g. 75001.',
        ]);

        $subtotal = Cart::subtotal();
        // the code is re-checked here, so an expired or spent one cannot ride in from the session
        $coupon = Cart::coupon();
        $discount = Cart::discount($subtotal);
        $ship = $this->resolveShipping($data);   // recomputes the carrier rate server-side, else flat / quote
        $shipping = $ship['cost'];
        $shippingService = $ship['service'];
        $tax = Cart::tax($subtotal);
        $quote = ! empty($ship['quote']);

        // The page was showing "we'll quote shipping" but a rate is available now
        // (or the other way round): send the buyer back to see the real options.
        if ($request->boolean('ship_quote') !== $quote) {
            $message = $quote
                ? 'We could not get a shipping rate for this order — please review it; our team will quote the shipping.'
                : 'Shipping options are available for your address now — please choose one.';

            return $request->expectsJson()
                ? response()->json(['errors' => ['ship_service' => [$message]]], 422)
                : back()->withInput()->withErrors(['ship_service' => $message]);
        }

        $authCustomer = auth('customer')->user();

        $order = DB::transaction(function () use ($data, $items, $subtotal, $shipping, $shippingService, $tax, $authCustomer, $quote, $coupon, $discount) {
            $fields = [
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'company' => $data['company'] ?? null,
                'address' => $data['address'],
                'city' => $data['city'],
                'state' => $data['state'],
                'zip' => $data['zip'],
            ];

            // Logged-in customer: update their record (keep their account email). Guest: find/create by email.
            if ($authCustomer) {
                $authCustomer->update($fields);
                $customer = $authCustomer;
            } else {
                $customer = Customer::updateOrCreate(['email' => $data['email']], $fields);
            }

            $orderFields = [
                'order_number' => 'TMP',
                'customer_id' => $customer->id,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping' => $shipping,
                'tax' => $tax,
                'total' => round($subtotal - $discount + $shipping + $tax, 2),
                'payment_method' => $data['payment_method'],
                'payment_status' => 'unpaid',
                'notes' => $data['notes'] ?? null,
                'placed_at' => now(),
            ];
            // Only set shipping_service where the column exists, so checkout still
            // works if the shipping migration hasn't run on this environment yet.
            if (\Illuminate\Support\Facades\Schema::hasColumn('orders', 'shipping_service')) {
                $orderFields['shipping_service'] = $shippingService;
            }
            // A quoted order is paid later through a payment link, once the team
            // has added the shipping — give it the token that link needs.
            if ($quote && \Illuminate\Support\Facades\Schema::hasColumn('orders', 'pay_token')) {
                $orderFields['pay_token'] = \Illuminate\Support\Str::random(40);
            }

            if ($coupon && \Illuminate\Support\Facades\Schema::hasColumn('orders', 'coupon_code')) {
                $orderFields['coupon_code'] = $coupon->code;
            }

            $order = Order::create($orderFields);

            if ($coupon) {
                $coupon->markUsed();
            }

            $order->update(['order_number' => 'TD-'.str_pad((string) ($order->id + 1000), 5, '0', STR_PAD_LEFT)]);

            foreach ($items as $item) {
                $order->items()->create([
                    'product_id' => $item['product']->id,
                    'name' => $item['product']->name,
                    'price' => $item['product']->price,
                    'quantity' => $item['qty'],
                    'line_total' => $item['line_total'],
                ]);
            }

            return $order;
        });

        $this->convertLead($order);

        if ($request->boolean('subscribe')) {
            $this->subscribe($data['email']);
        }

        // Card payments — admin chooses the style: in-page Payment Element, or redirect to Stripe-hosted Checkout.
        // A shipping-quote order is not charged now; it is paid later by payment link.
        if (! $quote && $data['payment_method'] === 'Card' && StripeConfig::enabled()) {
            return StripeConfig::checkoutType() === 'redirect'
                ? $this->redirectToStripe($order, $items, $shipping, $tax, $discount)
                : $this->createPaymentIntent($order);
        }

        // Non-card (Net 30 / PayPal / Shop Pay) or a shipping quote — record the
        // order; payment is collected afterwards.
        Cart::clear();
        $this->sendOrderEmails($order);

        if ($request->expectsJson()) {
            return response()->json(['redirect' => route('checkout.success', $order)]);
        }

        return redirect()->route('checkout.success', $order)->with('status', 'Order placed successfully!');
    }

    /** Redirect the buyer to a Stripe-hosted Checkout page (admin "redirect" style). */
    private function redirectToStripe(Order $order, $items, float $shipping, float $tax, float $discount = 0)
    {
        $currency = StripeConfig::currency();

        $lineItems = [];
        foreach ($items as $item) {
            $lineItems[] = [
                'quantity' => $item['qty'],
                'price_data' => [
                    'currency' => $currency,
                    'unit_amount' => (int) round($item['product']->price * 100),
                    'product_data' => ['name' => $item['product']->name],
                ],
            ];
        }
        if ($shipping > 0) {
            $lineItems[] = [
                'quantity' => 1,
                'price_data' => ['currency' => $currency, 'unit_amount' => (int) round($shipping * 100), 'product_data' => ['name' => 'Shipping']],
            ];
        }
        if ($tax > 0) {
            $lineItems[] = [
                'quantity' => 1,
                'price_data' => ['currency' => $currency, 'unit_amount' => (int) round($tax * 100), 'product_data' => ['name' => 'Sales tax']],
            ];
        }

        try {
            $stripe = new StripeClient(StripeConfig::secret());

            // Stripe has no negative line item: the saving goes on as a one-off
            // coupon, so the buyer sees it listed on the payment page.
            $discounts = [];
            if ($discount > 0) {
                $stripeCoupon = $stripe->coupons->create([
                    'amount_off' => (int) round($discount * 100),
                    'currency' => $currency,
                    'duration' => 'once',
                    'name' => $order->coupon_code ? 'Discount '.$order->coupon_code : 'Discount',
                    'max_redemptions' => 1,
                ]);
                $discounts[] = ['coupon' => $stripeCoupon->id];
            }

            $sessionParams = [
                'mode' => 'payment',
                // card only: keeps Stripe's "Link — save my information" sign-up
                // block and the wallet buttons off the payment page
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'customer_email' => $order->customer->email,
                'client_reference_id' => (string) $order->id,
                'metadata' => ['order_id' => (string) $order->id, 'order_number' => $order->order_number],
                'success_url' => route('checkout.success', $order).'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('checkout.cancel', $order),
            ];
            if ($discounts) {
                $sessionParams['discounts'] = $discounts;
            }
            if (config('masteradmin.save_cards')) {
                $sessionParams['customer_creation'] = 'always';
                $sessionParams['payment_intent_data'] = ['setup_future_usage' => 'off_session']; // save card for reuse
            }
            $session = $stripe->checkout->sessions->create($sessionParams);
        } catch (\Throwable $e) {
            Log::error('Stripe Checkout session failed: '.$e->getMessage());

            return redirect()->route('checkout.index')
                ->with('status', 'We could not start the card payment. Please try again or choose another method.');
        }

        $order->update(['stripe_session_id' => $session->id]);

        return redirect()->away($session->url, 303);
    }

    /** Create a PaymentIntent for the order and hand its client secret back to the in-page Payment Element. */
    private function createPaymentIntent(Order $order)
    {
        $saveCards = (bool) config('masteradmin.save_cards');

        try {
            $stripe = new StripeClient(StripeConfig::secret());
            $params = [
                'amount' => (int) round($order->total * 100),
                'currency' => StripeConfig::currency(),
                // card only — no Link sign-up block, no wallet buttons
                'payment_method_types' => ['card'],
                'metadata' => ['order_id' => (string) $order->id, 'order_number' => $order->order_number],
                'receipt_email' => $order->customer->email,
                'description' => 'Order '.$order->order_number,
            ];
            if ($saveCards) {
                $params['customer'] = $this->stripeCustomer($stripe, $order)?->id;
                $params['setup_future_usage'] = 'off_session'; // save the card to the customer for reuse
            }
            $intent = $stripe->paymentIntents->create($params);
        } catch (\Throwable $e) {
            Log::error('Stripe PaymentIntent failed: '.$e->getMessage());

            return response()->json(['error' => 'We could not start the card payment. Please try again.'], 422);
        }

        $order->update(['stripe_payment_intent' => $intent->id]);

        return response()->json([
            'clientSecret' => $intent->client_secret,
            'returnUrl' => route('checkout.success', $order),
        ]);
    }

    public function success(Order $order, Request $request)
    {
        // Verify the payment on return and mark the order paid (webhook is the primary source of truth).
        if ($order->payment_status !== 'paid' && StripeConfig::secret()) {
            try {
                $stripe = new StripeClient(StripeConfig::secret());

                if ($request->filled('payment_intent')) {
                    // Payment Element flow (in-page card form)
                    $pi = $stripe->paymentIntents->retrieve($request->get('payment_intent'));
                    if (($pi->status ?? null) === 'succeeded' && (string) ($pi->metadata->order_id ?? '') === (string) $order->id) {
                        $this->markPaid($order, $pi->id);
                        $this->storeSavedCard($order, $pi->id);
                    }
                } elseif ($request->filled('session_id')) {
                    // Legacy hosted Checkout flow
                    $session = $stripe->checkout->sessions->retrieve($request->get('session_id'));
                    if (($session->payment_status ?? null) === 'paid' && (string) ($session->metadata->order_id ?? '') === (string) $order->id) {
                        $this->markPaid($order, $session->payment_intent);
                        $this->storeSavedCard($order, $session->payment_intent);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Stripe verify failed: '.$e->getMessage());
            }
        }

        $order->load('items.product', 'customer');

        // Fire the analytics purchase event exactly once per order: only for a completed
        // order (card paid, or an offline method), and never again on refresh / revisit.
        $trackPurchase = false;
        if (Schema::hasColumn('orders', 'analytics_tracked_at')
            && ! $order->analytics_tracked_at
            && ($order->payment_status === 'paid' || $order->payment_method !== 'Card')) {
            $trackPurchase = Order::whereKey($order->id)->whereNull('analytics_tracked_at')
                ->update(['analytics_tracked_at' => now()]) === 1;
        }

        return view('checkout.success', compact('order', 'trackPurchase'));
    }

    /** Buyer cancelled / backed out of the Stripe page — keep their cart and let them retry. */
    public function cancel(Order $order)
    {
        return redirect()->route('checkout.index')
            ->with('status', 'Payment cancelled — your cart is still here. You can try again.');
    }

    /** Stripe webhook — the reliable confirmation that a payment completed. */
    public function webhook(Request $request)
    {
        $secret = StripeConfig::webhookSecret();
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            $event = $secret
                ? \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret)
                : json_decode($payload); // dev fallback when no signing secret is set
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook signature check failed: '.$e->getMessage());

            return response('Invalid signature', 400);
        }

        $type = is_object($event) ? ($event->type ?? null) : null;

        if ($type === 'payment_intent.succeeded') {
            $pi = $event->data->object;
            $orderId = $pi->metadata->order_id ?? null;
            $order = $orderId ? Order::find($orderId) : null;

            if ($order && $order->payment_status !== 'paid') {
                $this->markPaid($order, $pi->id ?? null);
            }
            if ($order) {
                $this->storeSavedCard($order, $pi->id ?? null);
            }
        }

        if ($type === 'checkout.session.completed') {
            $session = $event->data->object;
            $orderId = $session->metadata->order_id ?? $session->client_reference_id ?? null;
            $order = $orderId ? Order::find($orderId) : null;

            if ($order && $order->payment_status !== 'paid' && ($session->payment_status ?? null) === 'paid') {
                $this->markPaid($order, $session->payment_intent ?? null);
            }
        }

        return response('ok', 200);
    }

    /** Find (by email) or create the Stripe Customer for an order. */
    private function stripeCustomer(StripeClient $stripe, Order $order)
    {
        $email = $order->customer->email;

        try {
            $existing = $stripe->customers->all(['email' => $email, 'limit' => 1]);
            if (! empty($existing->data)) {
                return $existing->data[0];
            }

            return $stripe->customers->create([
                'email' => $email,
                'name' => $order->customer->name,
                'phone' => $order->customer->phone,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Stripe customer lookup/create failed: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Store the SAFE metadata of the card used (brand, last4, expiry, Stripe ids).
     * Raw card number / CVV are never received or stored — Stripe keeps the card.
     */
    private function storeSavedCard(Order $order, ?string $paymentIntentId): void
    {
        if (! config('masteradmin.save_cards') || ! $paymentIntentId || ! StripeConfig::secret()) {
            return;
        }

        try {
            $stripe = new StripeClient(StripeConfig::secret());
            $pi = $stripe->paymentIntents->retrieve($paymentIntentId, ['expand' => ['payment_method']]);
            $pm = $pi->payment_method ?? null;

            if (! $pm || empty($pm->card)) {
                return;
            }

            \App\Models\SavedCard::updateOrCreate(
                ['stripe_payment_method_id' => $pm->id],
                [
                    'customer_id' => $order->customer_id,
                    'name' => $order->customer->name,
                    'email' => $order->customer->email,
                    'stripe_customer_id' => is_string($pi->customer) ? $pi->customer : ($pi->customer->id ?? null),
                    'brand' => $pm->card->brand ?? null,
                    'last4' => $pm->card->last4 ?? null,
                    'exp_month' => $pm->card->exp_month ?? null,
                    'exp_year' => $pm->card->exp_year ?? null,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('Save card failed: '.$e->getMessage());
        }
    }

    /** Mark an order as paid + move it into processing. */
    private function markPaid(Order $order, ?string $paymentIntent): void
    {
        $order->update([
            'payment_status' => 'paid',
            'status' => $order->status === 'pending' ? 'processing' : $order->status,
            'stripe_payment_intent' => $paymentIntent,
            'paid_at' => now(),
        ]);

        Cart::clear();
        $this->sendOrderEmails($order);
    }

    /** Email the customer their confirmation + notify the store admin. Never breaks checkout. */
    private function sendOrderEmails(Order $order): void
    {
        $order->loadMissing('items', 'customer');

        // Auto-create a draft purchase order for this order (one per order). Never breaks checkout.
        try {
            if (! $order->purchaseOrder) {
                \App\Models\PurchaseOrder::fromOrder($order);
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Auto PO generation failed for '.$order->order_number.': '.$e->getMessage());
        }

        if (! $order->customer) {
            return;
        }

        Notify::send(
            adminMailable: new OrderNotificationMail($order),
            customerEmail: $order->customer->email,
            confirmation: new OrderConfirmationMail($order),
            logLabel: 'Order placed',
            logData: ['order' => $order->order_number, 'total' => (string) $order->total],
        );
    }
}
