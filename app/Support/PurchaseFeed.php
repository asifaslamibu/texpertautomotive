<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * The "John from Texas bought this 5 minutes ago" social-proof feed.
 *
 * Prefers REAL recent orders (first name + customer's state + the product they
 * bought), so the popups become genuine as sales come in. Until then — a fresh
 * store has no orders — it falls back to a feed generated from real published
 * products so the widget still has something to show. The generated items are
 * illustrative, not real sales.
 */
class PurchaseFeed
{
    private const NAMES = [
        'James', 'Michael', 'Robert', 'David', 'John', 'Chris', 'Daniel', 'Matthew',
        'Anthony', 'Mark', 'Steven', 'Kevin', 'Brian', 'Jason', 'Jeff', 'Ryan',
        'Sarah', 'Jessica', 'Ashley', 'Amanda', 'Melissa', 'Nicole', 'Laura', 'Rachel',
        'Carlos', 'Luis', 'Miguel', 'Andre', 'Tyler', 'Derek', 'Wayne', 'Gary',
    ];

    private const PLACES = [
        'Texas', 'California', 'Florida', 'New York', 'Ohio', 'Georgia', 'Arizona',
        'Illinois', 'Michigan', 'Nevada', 'Colorado', 'Washington', 'Virginia',
        'North Carolina', 'Pennsylvania', 'Tennessee', 'Dallas, TX', 'Houston, TX',
        'Phoenix, AZ', 'Atlanta, GA', 'Chicago, IL', 'Denver, CO', 'Miami, FL',
    ];

    public static function enabled(): bool
    {
        return Setting::get('social_proof_enabled', '1') !== '0';
    }

    /** @return array<int, array{name:string, place:string, product:string, url:string, image:string, ago:string}> */
    public static function items(int $limit = 15): array
    {
        if (! self::enabled()) {
            return [];
        }

        // Built on every page load, so cache it briefly (cleared when a product changes).
        return \Illuminate\Support\Facades\Cache::remember('purchase_feed', now()->addMinutes(10), function () use ($limit) {
            $real = self::fromOrders($limit);

            return count($real) >= 5 ? $real : self::generated($limit);
        });
    }

    /** Genuine recent purchases, when there are orders to draw from. */
    private static function fromOrders(int $limit): array
    {
        if (Order::count() === 0) {
            return [];
        }

        $orders = Order::with(['customer', 'items'])
            ->whereHas('items')
            ->latest('placed_at')->latest('id')
            ->take($limit * 2)->get();

        $items = [];
        foreach ($orders as $order) {
            $line = $order->items->first();
            $product = $line ? Product::find($line->product_id) : null;
            $name = Str::of((string) ($order->customer->name ?? ''))->trim()->before(' ')->value();
            $place = $order->customer->state ?: $order->customer->city;

            if (! $product || $name === '' || ! $place) {
                continue;
            }

            $items[] = [
                'name'    => $name,
                'place'   => $place,
                'product' => $product->name,
                'url'     => route('products.show', $product),
                'image'   => media($product->image),
                'ago'     => self::ago(optional($order->placed_at ?? $order->created_at)),
            ];
            if (count($items) >= $limit) {
                break;
            }
        }

        return $items;
    }

    /** Illustrative feed built from real products when there are no orders yet. */
    private static function generated(int $limit): array
    {
        // Pick random IDs then load only those rows — avoids ORDER BY RAND(), which
        // is a full-table sort that gets very slow on a large catalogue.
        $ids = Product::published()->where('image', 'like', '/uploads/%')->pluck('id');
        if ($ids->isEmpty()) {
            $ids = Product::published()->pluck('id');
        }
        $pick = $ids->shuffle()->take($limit);
        $products = Product::whereIn('id', $pick)->get();

        $minutes = 2;   // walk backwards in time so the list feels like a stream
        $items = [];
        foreach ($products as $product) {
            $minutes += mt_rand(3, 40);
            $items[] = [
                'name'    => self::NAMES[array_rand(self::NAMES)],
                'place'   => self::PLACES[array_rand(self::PLACES)],
                'product' => $product->name,
                'url'     => route('products.show', $product),
                'image'   => media($product->image),
                'ago'     => self::agoFromMinutes($minutes),
            ];
        }

        return $items;
    }

    private static function ago($when): string
    {
        if (! $when) {
            return 'recently';
        }

        return self::agoFromMinutes(max(1, (int) $when->diffInMinutes(now())));
    }

    private static function agoFromMinutes(int $minutes): string
    {
        if ($minutes < 2) {
            return 'just now';
        }
        if ($minutes < 60) {
            return $minutes.' minutes ago';
        }
        $hours = intdiv($minutes, 60);

        return $hours === 1 ? 'an hour ago' : $hours.' hours ago';
    }
}
