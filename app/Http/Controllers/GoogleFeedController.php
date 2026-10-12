<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Google Merchant Center product feed (RSS 2.0 + g: namespace).
 * Merchant Center fetches this URL on a schedule; products auto-update.
 * Streamed + chunked so a 10k-product catalogue never blows the memory limit.
 */
class GoogleFeedController extends Controller
{
    public function index(): StreamedResponse
    {
        $site = Setting::get('site_name', 'Texpert Deals');
        $currency = strtoupper(Setting::get('stripe_currency', 'USD'));

        return response()->stream(function () use ($site, $currency) {
            $out = fopen('php://output', 'w');

            fwrite($out, '<?xml version="1.0" encoding="UTF-8"?>'."\n");
            fwrite($out, '<rss version="2.0" xmlns:g="http://base.google.com/ns/1.0">'."\n");
            fwrite($out, "<channel>\n");
            fwrite($out, '<title>'.$this->esc($site)." Products</title>\n");
            fwrite($out, '<link>'.$this->esc(url('/'))."</link>\n");
            fwrite($out, '<description>'.$this->esc($site.' product feed for Google Merchant Center')."</description>\n");

            Product::query()->published()
                ->where('price', '>', 0)
                ->whereNotNull('image')->where('image', '!=', '')
                ->with('category')
                ->chunk(500, function ($products) use ($out, $currency) {
                    foreach ($products as $p) {
                        fwrite($out, $this->item($p, $currency));
                    }
                });

            fwrite($out, "</channel>\n</rss>\n");
            fclose($out);
        }, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    private function item(Product $p, string $currency): string
    {
        // Google only accepts real image files. Skip products whose image has no usable
        // extension (this clears Merchant Center's "Unsupported image type" errors).
        $imgExt = strtolower(pathinfo(parse_url((string) $p->image, PHP_URL_PATH) ?: (string) $p->image, PATHINFO_EXTENSION));
        if (! in_array($imgExt, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tif', 'tiff'], true)) {
            return '';
        }

        $title = mb_substr(trim((string) $p->name), 0, 150);
        $desc = mb_substr(trim(strip_tags((string) ($p->short_description ?: $p->description ?: $p->name))), 0, 5000);
        $link = route('products.show', $p->slug);
        $image = $this->absolute($p->image);
        $availability = $p->in_stock ? 'in_stock' : 'out_of_stock';

        // regular price + optional sale price when a higher "compare at" exists
        $price = (float) $p->price;
        $compare = (float) $p->compare_at_price;
        $regular = $compare > $price ? $compare : $price;

        // shipping weight — the product's real package weight, with a sane default
        preg_match('/([\d.]+)/', (string) $p->weight_lbs, $wm);
        $weight = isset($wm[1]) && (float) $wm[1] > 0 ? (float) $wm[1] : \App\Support\Cart::DEFAULT_ITEM_WEIGHT;

        // GTIN from UPC only when it looks valid (8/12/13/14 digits)
        $gtin = preg_replace('/\D/', '', (string) $p->upc);
        $hasGtin = in_array(strlen($gtin), [8, 12, 13, 14], true);
        $mpn = trim((string) $p->sku);
        $brand = trim((string) $p->brand);
        $hasId = $hasGtin || ($brand !== '' && $mpn !== '');

        $x = "<item>\n";
        $x .= $this->tag('g:id', $mpn !== '' ? $mpn : (string) $p->id);
        $x .= $this->tag('g:title', $title);
        $x .= $this->tag('g:description', $desc);
        $x .= $this->tag('g:link', $link);
        $x .= $this->tag('g:image_link', $image);
        if ($p->gallery) {
            foreach (collect((array) $p->gallery)->filter()->take(10) as $g) {
                $x .= $this->tag('g:additional_image_link', $this->absolute($g));
            }
        }
        $x .= $this->tag('g:condition', 'new');
        $x .= $this->tag('g:availability', $availability);
        $x .= $this->tag('g:shipping_weight', rtrim(rtrim(number_format($weight, 2, '.', ''), '0'), '.').' lb');
        $x .= $this->tag('g:price', number_format($regular, 2, '.', '').' '.$currency);
        if ($compare > $price) {
            $x .= $this->tag('g:sale_price', number_format($price, 2, '.', '').' '.$currency);
        }
        if ($brand !== '') {
            $x .= $this->tag('g:brand', $brand);
        }
        if ($hasGtin) {
            $x .= $this->tag('g:gtin', $gtin);
        }
        if ($mpn !== '') {
            $x .= $this->tag('g:mpn', $mpn);
        }
        $x .= $this->tag('g:identifier_exists', $hasId ? 'yes' : 'no');
        if ($p->category) {
            $x .= $this->tag('g:product_type', $p->category->name);
        }
        $x .= "</item>\n";

        return $x;
    }

    private function tag(string $name, string $value): string
    {
        return '  <'.$name.'>'.$this->esc($value).'</'.$name.">\n";
    }

    private function esc(string $s): string
    {
        // Drop invalid UTF-8 byte sequences (bad imports leave stray bytes).
        $s = mb_convert_encoding($s, 'UTF-8', 'UTF-8');
        // Strip characters XML 1.0 forbids (control chars except tab/LF/CR, etc.),
        // which is what triggers Google's "character not permitted in UTF-8" error.
        $s = preg_replace('/[^\x{0009}\x{000A}\x{000D}\x{0020}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $s) ?? '';

        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function absolute(?string $path): string
    {
        $url = media($path ?? '');
        // media() already returns an absolute asset() URL for local paths; pass through http(s) URLs as-is.
        return $url;
    }
}
