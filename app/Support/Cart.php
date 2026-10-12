<?php

namespace App\Support;

use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Support\Collection;

class Cart
{
    const KEY = 'cart';
    const COUPON_KEY = 'cart_coupon';
    const FLAT_SHIPPING = 29;
    const TAX_RATE = 0.07;

    /** raw [product_id => qty] map from session */
    public static function raw(): array
    {
        return session()->get(self::KEY, []);
    }

    /**
     * How many units of this product may be in the cart.
     * null = no limit (stock quantity was never synced), 0 = cannot be bought.
     */
    public static function availableFor(?Product $product): ?int
    {
        if (! $product || ! $product->published || ! $product->in_stock) {
            return 0;
        }

        return $product->stock_qty === null ? null : max(0, (int) $product->stock_qty);
    }

    /**
     * Put `$qty` more in the cart, never past what is in stock.
     * @return array{qty: int, capped: bool, available: ?int}
     */
    public static function add(int $productId, int $qty = 1): array
    {
        $cart = self::raw();

        return self::put($cart, $productId, ($cart[$productId] ?? 0) + max(1, $qty));
    }

    /**
     * Set the quantity of one line, never past what is in stock.
     * @return array{qty: int, capped: bool, available: ?int}
     */
    public static function update(int $productId, int $qty): array
    {
        return self::put(self::raw(), $productId, $qty);
    }

    /** Write one line back to the session, clipped to the stock on hand. */
    private static function put(array $cart, int $productId, int $wanted): array
    {
        $available = self::availableFor(Product::find($productId));
        $qty = $available === null ? $wanted : min($wanted, $available);

        if ($qty <= 0) {
            unset($cart[$productId]);
            $qty = 0;
        } else {
            $cart[$productId] = $qty;
        }
        session()->put(self::KEY, $cart);

        return ['qty' => $qty, 'capped' => $qty < $wanted, 'available' => $available];
    }

    /**
     * Drop items that went out of stock and clip lines that are now over it —
     * stock can change between adding to the cart and checking out.
     *
     * @return array<int, string> what changed, for a message to the buyer
     */
    public static function sync(): array
    {
        $cart = self::raw();
        if (! $cart) {
            return [];
        }

        $products = Product::whereIn('id', array_keys($cart))->get()->keyBy('id');
        $changes = [];

        foreach ($cart as $id => $qty) {
            $product = $products->get($id);
            $available = self::availableFor($product);
            $name = $product?->name ?? 'An item';

            if ($available === 0) {
                unset($cart[$id]);
                $changes[] = "{$name} is no longer available and was removed from your cart.";
            } elseif ($available !== null && $qty > $available) {
                $cart[$id] = $available;
                $changes[] = "Only {$available} of {$name} left — your cart was updated.";
            }
        }

        if ($changes) {
            session()->put(self::KEY, $cart);
        }

        return $changes;
    }

    public static function remove(int $productId): void
    {
        $cart = self::raw();
        unset($cart[$productId]);
        session()->put(self::KEY, $cart);
    }

    public static function clear(): void
    {
        session()->forget(self::KEY);
        session()->forget(self::COUPON_KEY);
    }

    /** total number of units in the cart */
    public static function count(): int
    {
        return array_sum(self::raw());
    }

    /** Collection of ['product' => Product, 'qty' => int, 'line_total' => float] */
    public static function items(): Collection
    {
        $cart = self::raw();
        if (empty($cart)) {
            return collect();
        }

        $products = Product::with('category')->whereIn('id', array_keys($cart))->get()->keyBy('id');

        return collect($cart)
            ->map(function ($qty, $id) use ($products) {
                $product = $products->get($id);
                if (! $product) {
                    return null;
                }

                return [
                    'product' => $product,
                    'qty' => $qty,
                    'line_total' => (float) $product->price * $qty,
                ];
            })
            ->filter()
            ->values();
    }

    /** Weight (lbs) assumed for a product with no weight recorded. */
    const DEFAULT_ITEM_WEIGHT = 2.0;

    public static function subtotal(): float
    {
        return (float) self::items()->sum('line_total');
    }

    /** Total shipping weight of the cart in lbs (parses "2.3 lbs" etc.; falls back per item). */
    public static function weight(): float
    {
        $total = 0.0;
        foreach (self::items() as $line) {
            $raw = (string) ($line['product']->weight_lbs ?? '');
            preg_match('/([\d.]+)/', $raw, $m);
            $each = isset($m[1]) && (float) $m[1] > 0 ? (float) $m[1] : self::DEFAULT_ITEM_WEIGHT;
            $total += $each * (int) $line['qty'];
        }

        return round(max(0, $total), 1);
    }

    /** The boxes this cart would ship in — see Parcels. */
    public static function parcels(): array
    {
        return Parcels::forLines(self::items());
    }

    /** True when any item in the cart is flagged for free shipping — the whole order then ships free. */
    public static function hasFreeShipping(): bool
    {
        return self::items()->contains(fn ($line) => (bool) $line['product']->free_shipping);
    }

    /** Shipping charges can be switched off entirely in Admin → Settings. */
    public static function shippingEnabled(): bool
    {
        return \App\Models\Setting::get('shipping_enabled', '1') !== '0';
    }

    public static function shipping(?float $subtotal = null): float
    {
        if (! self::shippingEnabled() || self::hasFreeShipping()) {
            return 0.0;
        }

        $subtotal ??= self::subtotal();

        // Flat rate on every order — there is no "free over $X" threshold.
        return $subtotal > 0 ? self::FLAT_SHIPPING : 0.0;
    }

    /**
     * The discount code held for this cart, or null. A code that has since been
     * switched off, expired or fallen below its minimum quietly stops applying —
     * it is only dropped from the session when the shopper asks for it again.
     */
    public static function coupon(): ?Coupon
    {
        $coupon = Coupon::findByCode(session()->get(self::COUPON_KEY));

        return $coupon && $coupon->isUsable(self::subtotal()) ? $coupon : null;
    }

    public static function applyCoupon(Coupon $coupon): void
    {
        session()->put(self::COUPON_KEY, $coupon->code);
    }

    public static function forgetCoupon(): void
    {
        session()->forget(self::COUPON_KEY);
    }

    public static function discount(?float $subtotal = null): float
    {
        $subtotal ??= self::subtotal();

        return self::coupon()?->discountFor($subtotal) ?? 0.0;
    }

    /** What the order is taxed and charged on: the subtotal after any discount. */
    public static function discountedSubtotal(?float $subtotal = null): float
    {
        $subtotal ??= self::subtotal();

        return round(max(0, $subtotal - self::discount($subtotal)), 2);
    }

    public static function tax(?float $subtotal = null): float
    {
        $subtotal ??= self::subtotal();

        return round(self::discountedSubtotal($subtotal) * self::TAX_RATE, 2);
    }

    public static function total(): float
    {
        $subtotal = self::subtotal();

        return round(self::discountedSubtotal($subtotal) + self::shipping($subtotal) + self::tax($subtotal), 2);
    }
}
