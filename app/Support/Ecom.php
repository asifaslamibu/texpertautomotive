<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Product;

/**
 * Builds GA4-format ecommerce payloads (item_id = SKU, falling back to the product id)
 * so every tracking call site sends the same shape.
 */
class Ecom
{
    public static function item(?Product $product, string $name, float $price, int $qty = 1, ?int $productId = null): array
    {
        return [
            'item_id' => $product?->sku ?: (string) ($product?->id ?? $productId),
            'item_name' => $name,
            'price' => round($price, 2),
            'quantity' => $qty,
        ];
    }

    public static function addToCart(Product $product, int $qty): array
    {
        $qty = max(1, $qty);

        return [
            'currency' => 'USD',
            'value' => round((float) $product->price * $qty, 2),
            'items' => [self::item($product, $product->name, (float) $product->price, $qty)],
        ];
    }

    public static function purchase(Order $order): array
    {
        return [
            'transaction_id' => $order->order_number,
            'value' => round((float) $order->total, 2),
            'tax' => round((float) $order->tax, 2),
            'shipping' => round((float) $order->shipping, 2),
            'currency' => 'USD',
            'items' => $order->items
                ->map(fn ($i) => self::item($i->product, $i->name, (float) $i->price, (int) $i->quantity, $i->product_id))
                ->values()
                ->all(),
        ];
    }
}
