<?php

namespace App\Support;

use App\Models\Product;
use App\Models\Setting;

/**
 * Page titles and meta descriptions.
 *
 * Kept here rather than in the Blade files so every page derives them the same
 * way — and because most of the catalogue has no hand-written marketing copy,
 * so the fallback chain matters more than the happy path.
 */
class Seo
{
    /** Google renders roughly this much of a description. */
    public const DESCRIPTION_MAX = 158;

    /** …and roughly this much of a title. */
    public const TITLE_MAX = 60;

    public static function siteName(): string
    {
        return Setting::get('site_name', 'Texpert Deals');
    }

    /** Flatten markup/whitespace and cut to $max on a word boundary. */
    public static function clamp(?string $text, int $max = self::DESCRIPTION_MAX): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags(html_entity_decode((string) $text))));

        if ($text === '' || mb_strlen($text) <= $max) {
            return $text;
        }

        $cut = mb_substr($text, 0, $max - 1);
        $space = mb_strrpos($cut, ' ');

        return rtrim($space ? mb_substr($cut, 0, $space) : $cut, " \t,.;:-").'…';
    }

    /** "<name> — <Site>", trimming the name so the whole title still fits. */
    public static function title(string $subject, ?string $suffix = null): string
    {
        $suffix = $suffix ?? self::siteName();
        $room = self::TITLE_MAX - mb_strlen($suffix) - 3;   // 3 = the " — " separator

        return self::clamp($subject, max(20, $room)).' — '.$suffix;
    }

    /**
     * A product's meta description: its own copy when there is any worth using,
     * otherwise a line built from the facts we do hold.
     */
    public static function productDescription(Product $product): string
    {
        foreach ([$product->short_description, $product->description] as $copy) {
            $text = self::clamp($copy);
            // Ignore stubs — a 20-character description is worse than a generated one.
            if (mb_strlen($text) >= 60) {
                return $text;
            }
        }

        // Long product names would otherwise eat the whole budget and the closing
        // trust line — the part buyers actually respond to — would be cut off.
        $sentence = collect([
            'Buy the '.self::clamp($product->name, 58),
            $product->sku ? '(SKU '.$product->sku.')' : null,
            'at '.self::siteName().'.',
            '$'.number_format((float) $product->price, 2).' —',
            $product->in_stock ? 'in stock, ships fast.' : 'available to order.',
            'Genuine, warranty-backed.',
        ])->filter()->implode(' ');

        return self::clamp($sentence);
    }

    /** Product page title — the brand is usually already in the name, so it is not repeated. */
    public static function productTitle(Product $product): string
    {
        $name = $product->name;
        $brand = trim((string) $product->brand);

        if ($brand !== '' && ! str_starts_with(mb_strtolower($name), mb_strtolower($brand))) {
            $name = $brand.' '.$name;
        }

        return self::title($name);
    }
}
