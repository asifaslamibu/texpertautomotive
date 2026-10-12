<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $customers = Customer::pluck('id')->all();
        $products = Product::all();

        if (empty($customers) || $products->isEmpty()) {
            return;
        }

        // status weighted distribution
        $statusPool = array_merge(
            array_fill(0, 5, 'completed'),
            array_fill(0, 3, 'shipped'),
            array_fill(0, 3, 'processing'),
            array_fill(0, 2, 'pending'),
            array_fill(0, 1, 'cancelled'),
        );

        for ($n = 1; $n <= 75; $n++) {
            $placedAt = Carbon::now()->subDays(rand(0, 360))->setTime(rand(8, 20), rand(0, 59));

            $order = Order::create([
                'order_number'   => 'TD-'.str_pad((string) (1000 + $n), 5, '0', STR_PAD_LEFT),
                'customer_id'    => $customers[array_rand($customers)],
                'status'         => $statusPool[array_rand($statusPool)],
                'payment_method' => ['Card', 'Card', 'PayPal', 'ShopPay', 'Net 30'][array_rand([0, 1, 2, 3, 4])],
                'placed_at'      => $placedAt,
                'created_at'     => $placedAt,
                'updated_at'     => $placedAt,
            ]);

            $lineCount = rand(1, 4);
            $picked = $products->random(min($lineCount, $products->count()));
            if (! $picked instanceof \Illuminate\Support\Collection) {
                $picked = collect([$picked]);
            }

            $subtotal = 0;
            foreach ($picked as $product) {
                $qty = rand(1, 3);
                $lineTotal = (float) $product->price * $qty;
                $subtotal += $lineTotal;

                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $product->id,
                    'name'       => $product->name,
                    'price'      => $product->price,
                    'quantity'   => $qty,
                    'line_total' => $lineTotal,
                    'created_at' => $placedAt,
                    'updated_at' => $placedAt,
                ]);
            }

            $shipping = $subtotal >= 499 ? 0 : 29;
            $tax = round($subtotal * 0.07, 2);

            $order->update([
                'subtotal' => $subtotal,
                'shipping' => $shipping,
                'tax'      => $tax,
                'total'    => $subtotal + $shipping + $tax,
            ]);
        }
    }
}
