<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OrderStatusMail;
use App\Models\Order;
use App\Support\Ups;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    use \App\Http\Controllers\Concerns\SortsRecords;

    public function index(Request $request)
    {
        $query = Order::with('customer');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('q')) {
            $query->where('order_number', 'like', "%{$request->q}%")
                ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$request->q}%"));
        }

        $this->applySort($query, $request, ['order_number', 'status', 'total', 'placed_at'], 'placed_at', 'desc');

        $orders = $query->paginate(15)->withQueryString();
        $statuses = Order::STATUSES;

        return view('admin.orders.index', compact('orders', 'statuses'));
    }

    public function show(Order $order)
    {
        $order->load('items.product', 'customer');

        return view('admin.orders.show', compact('order'));
    }

    /** Form to create an order manually and send the customer a pay-by-link. */
    public function create()
    {
        return view('admin.orders.create', [
            'stripeReady' => \App\Support\StripeConfig::enabled(),
        ]);
    }

    /** JSON product lookup for the create-order picker (id + name + raw price). */
    public function productSearch(Request $request)
    {
        $term = trim((string) $request->input('q'));
        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $products = \App\Models\Product::query()
            ->where(fn ($w) => $w->where('name', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%")
                ->orWhere('brand', 'like', "%{$term}%"))
            ->orderByDesc('in_stock')->limit(10)
            ->get(['id', 'name', 'sku', 'price']);

        return response()->json($products->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku,
            'price' => (float) $p->price,
        ]));
    }

    /** Build the order + line items, generate a pay token, and hand back a payment link. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_email' => ['required', 'email', 'max:160'],
            'customer_phone' => ['nullable', 'string', 'max:60'],
            'customer_company' => ['nullable', 'string', 'max:160'],
            'customer_address' => ['nullable', 'string', 'max:200'],
            'customer_city' => ['nullable', 'string', 'max:120'],
            'customer_state' => ['nullable', 'string', 'max:60'],
            'customer_zip' => ['nullable', 'string', 'max:20'],
            'shipping' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.product_id' => ['nullable', 'integer'],
        ], [], ['items.*.name' => 'product name']);

        $order = \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            // Find or create the customer by email (a random password so the record is valid).
            $customer = \App\Models\Customer::firstOrCreate(
                ['email' => strtolower(trim($data['customer_email']))],
                [
                    'name' => $data['customer_name'],
                    'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(24)),
                ]
            );
            // Keep the latest contact details on file.
            $customer->fill(array_filter([
                'name' => $data['customer_name'],
                'phone' => $data['customer_phone'] ?? null,
                'company' => $data['customer_company'] ?? null,
                'address' => $data['customer_address'] ?? null,
                'city' => $data['customer_city'] ?? null,
                'state' => $data['customer_state'] ?? null,
                'zip' => $data['customer_zip'] ?? null,
            ]))->save();

            $subtotal = 0.0;
            foreach ($data['items'] as $it) {
                $subtotal += (float) $it['price'] * (int) $it['quantity'];
            }
            $shipping = (float) ($data['shipping'] ?? 0);
            $tax = (float) ($data['tax'] ?? 0);

            // percentage off the subtotal; shipping and tax are added afterwards
            $discountPercent = min(100, max(0, (float) ($data['discount_percent'] ?? 0)));
            $discount = round($subtotal * $discountPercent / 100, 2);

            $fields = [
                'order_number' => 'TMP',
                'pay_token' => \Illuminate\Support\Str::random(40),
                'customer_id' => $customer->id,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'shipping' => $shipping,
                'tax' => $tax,
                'total' => max(0, $subtotal - $discount) + $shipping + $tax,
                'payment_method' => 'Card',
                'payment_status' => 'unpaid',
                'notes' => $data['notes'] ?? null,
                'placed_at' => now(),
            ];

            // Discount columns exist only after their migration — guard so creating
            // an order still works where it hasn't run yet.
            if (\Illuminate\Support\Facades\Schema::hasColumn('orders', 'discount')) {
                $fields['discount_percent'] = $discountPercent;
                $fields['discount'] = $discount;
            }

            $order = Order::create($fields);
            $order->update(['order_number' => 'TD-'.str_pad((string) ($order->id + 1000), 5, '0', STR_PAD_LEFT)]);

            foreach ($data['items'] as $it) {
                $order->items()->create([
                    'product_id' => $it['product_id'] ?? null,
                    'name' => $it['name'],
                    'price' => (float) $it['price'],
                    'quantity' => (int) $it['quantity'],
                    'line_total' => (float) $it['price'] * (int) $it['quantity'],
                ]);
            }

            return $order;
        });

        return redirect()->route('admin.orders.show', $order)
            ->with('status', 'Order '.$order->order_number.' created. Share the payment link below with the customer.')
            ->with('pay_link', $order->payUrl());
    }

    public function update(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', Order::STATUSES)],
            'tracking_number' => ['nullable', 'string', 'max:60'],
            'shipping_service' => ['nullable', 'string', 'max:80'],
            'shipping' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'ship_name' => ['nullable', 'string', 'max:120'],
            'ship_phone' => ['nullable', 'string', 'max:60'],
            'ship_company' => ['nullable', 'string', 'max:160'],
            'ship_address' => ['nullable', 'string', 'max:200'],
            'ship_city' => ['nullable', 'string', 'max:120'],
            'ship_state' => ['nullable', 'string', 'max:60'],
            'ship_zip' => ['nullable', 'string', 'max:20'],
        ]);

        $oldStatus = $order->status;
        $fields = ['status' => $data['status'], 'notes' => $request->input('notes')];

        // Charges — shipping/tax/discount can all be corrected after the order was
        // created; the subtotal comes from the line items, so the total is redone.
        $subtotal = (float) $order->subtotal;
        $shipping = $request->filled('shipping') ? (float) $data['shipping'] : (float) $order->shipping;
        $tax = $request->filled('tax') ? (float) $data['tax'] : (float) $order->tax;

        $hasDiscount = \Illuminate\Support\Facades\Schema::hasColumn('orders', 'discount');
        $percent = $hasDiscount
            ? ($request->filled('discount_percent') ? min(100, max(0, (float) $data['discount_percent'])) : (float) $order->discount_percent)
            : 0.0;
        $discount = round($subtotal * $percent / 100, 2);

        $fields['shipping'] = $shipping;
        $fields['tax'] = $tax;
        $fields['total'] = max(0, $subtotal - $discount) + $shipping + $tax;
        if ($hasDiscount) {
            $fields['discount_percent'] = $percent;
            $fields['discount'] = $discount;
        }

        // Ship-to details live on the customer record (that is what the label uses).
        $address = array_filter([
            'name' => $data['ship_name'] ?? null,
            'phone' => $data['ship_phone'] ?? null,
            'company' => $data['ship_company'] ?? null,
            'address' => $data['ship_address'] ?? null,
            'city' => $data['ship_city'] ?? null,
            'state' => $data['ship_state'] ?? null,
            'zip' => $data['ship_zip'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');

        if ($address && $order->customer) {
            $order->customer->fill($address)->save();
        }
        // Tracking columns exist only after the shipping migration — guard so the
        // update works even where it hasn't run yet.
        if (\Illuminate\Support\Facades\Schema::hasColumn('orders', 'tracking_number')) {
            $fields['tracking_number'] = trim((string) ($data['tracking_number'] ?? '')) ?: null;
            $fields['shipping_service'] = trim((string) ($data['shipping_service'] ?? '')) ?: $order->shipping_service;
        }
        $order->update($fields);

        // Notify the customer when the status actually changes.
        $emailed = false;
        if ($order->status !== $oldStatus) {
            $order->loadMissing('items', 'customer');
            if ($order->customer?->email) {
                try {
                    Mail::to($order->customer->email)->send(new OrderStatusMail($order));
                    $emailed = true;
                } catch (\Throwable $e) {
                    Log::warning('Order status email failed for '.$order->order_number.': '.$e->getMessage());
                }
            }
        }

        return back()->with('status', 'Order updated.'.($emailed ? ' Customer notified by email.' : ''));
    }

    /** Buy a shipping label (Shippo preferred, else UPS) for the order and record tracking. */
    public function label(Request $request, Order $order)
    {
        $useShippo = \App\Support\Shippo::enabled();
        if (! $useShippo && ! Ups::enabled()) {
            return back()->withErrors(['label' => 'No shipping provider is enabled. Configure Shippo or UPS in Settings → Shipping first.']);
        }

        $order->loadMissing('customer', 'items');
        $c = $order->customer;
        if (! $c) {
            return back()->withErrors(['label' => 'This order has no customer address.']);
        }

        // total weight from the ordered items (falls back per item)
        $weight = 0.0;
        foreach ($order->items as $it) {
            $p = \App\Models\Product::find($it->product_id);
            preg_match('/([\d.]+)/', (string) ($p->weight_lbs ?? ''), $m);
            $each = isset($m[1]) && (float) $m[1] > 0 ? (float) $m[1] : \App\Support\Cart::DEFAULT_ITEM_WEIGHT;
            $weight += $each * (int) $it->quantity;
        }

        $dest = [
            'name' => $c->name, 'phone' => $c->phone, 'email' => $c->email,
            'address' => $c->address, 'city' => $c->city, 'state' => $c->state, 'zip' => $c->zip, 'country' => 'US',
        ];

        if ($useShippo) {
            $result = \App\Support\Shippo::createLabel($dest, max(1, $weight), $order->shipping_service ?: null);
            if (! $result || empty($result['tracking'])) {
                return back()->withErrors(['label' => 'Shippo could not create the label. Check the ship-from address and the customer address.']);
            }
            $order->update([
                'tracking_number' => $result['tracking'],
                'tracking_carrier' => $result['carrier'] ?? null,
                'shipping_service' => $result['service'] ?: $order->shipping_service,
                'label_path' => $result['label_url'] ?: null, // Shippo returns a hosted PDF URL
                'status' => $order->status === 'pending' ? 'processing' : $order->status,
            ]);

            return back()->with('status', 'Label created. Tracking: '.$result['tracking']);
        }

        // ---- UPS ----
        $result = Ups::createLabel($dest, max(1, $weight), $request->input('service_code', '03'));

        if (! $result || empty($result['tracking'])) {
            return back()->withErrors(['label' => 'UPS could not create the label. Check the ship-from address, account number and the customer address.']);
        }

        // store the label image (base64 GIF) under the private disk
        $path = null;
        if (! empty($result['label_base64'])) {
            $path = 'labels/'.$order->order_number.'_'.Str::random(6).'.gif';
            Storage::put($path, base64_decode($result['label_base64']));
        }

        $order->update([
            'tracking_number' => $result['tracking'],
            'shipping_service' => $result['service'],
            'label_path' => $path,
            'status' => $order->status === 'pending' ? 'processing' : $order->status,
        ]);

        return back()->with('status', 'UPS label created. Tracking: '.$result['tracking']);
    }

    /** Download / open a previously-generated label (a stored file or a Shippo hosted URL). */
    public function labelDownload(Order $order)
    {
        $path = $order->label_path;
        abort_unless($path, 404);

        if (\Illuminate\Support\Str::startsWith($path, ['http://', 'https://'])) {
            return redirect()->away($path);
        }

        abort_unless(Storage::exists($path), 404);

        return Storage::download($path, $order->order_number.'-label');
    }

    public function bulkDestroy(\Illuminate\Http\Request $request)
    {
        $ids = (array) $request->input('ids', []);
        $n = \App\Models\Order::whereIn('id', $ids)->delete();

        return back()->with('status', $n.' order(s) deleted.');
    }
}
