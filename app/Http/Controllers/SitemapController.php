<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;

class SitemapController extends Controller
{
    public function index()
    {
        $urls = [];
        $add = function (string $loc, ?string $lastmod = null, string $freq = 'weekly', string $priority = '0.7') use (&$urls) {
            $urls[] = ['loc' => $loc, 'lastmod' => $lastmod, 'freq' => $freq, 'priority' => $priority];
        };

        // ---- core / static pages ----
        $add(route('home'), null, 'daily', '1.0');
        $add(route('products.index'), null, 'daily', '0.9');
        $add(route('brands.index'), null, 'weekly', '0.6');
        foreach (['about', 'contact.index', 'returns', 'shipping', 'track', 'part-request', 'privacy', 'terms', 'accessibility'] as $name) {
            $add(route($name), null, 'monthly', '0.4');
        }

        // ---- printer supplies finder (one landing page + one page per printer model) ----
        $add(route('printer-finder.index'), null, 'weekly', '0.8');
        foreach (\App\Support\PrinterCompatibility::models() as $model) {
            $add(route('printer-finder.show', $model['slug']), null, 'weekly', '0.7');
        }

        // ---- categories ----
        Category::orderBy('name')->get(['slug', 'updated_at'])->each(function ($c) use ($add) {
            $add(route('products.index', ['category' => $c->slug]), optional($c->updated_at)->toDateString(), 'weekly', '0.7');
        });

        // ---- brands (only those with published products) ----
        $brandNames = Product::published()->whereNotNull('brand')->where('brand', '!=', '')
            ->select('brand')->distinct()->orderBy('brand')->pluck('brand');
        foreach ($brandNames as $brand) {
            $add(route('products.index', ['brand' => $brand]), null, 'weekly', '0.5');
        }

        // ---- published products ----
        Product::published()->select('slug', 'updated_at')->orderBy('id')
            ->chunk(1000, function ($products) use ($add) {
                foreach ($products as $p) {
                    $add(route('products.show', $p->slug), optional($p->updated_at)->toDateString(), 'weekly', '0.8');
                }
            });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= '  <url>'."\n";
            $xml .= '    <loc>'.htmlspecialchars($u['loc'], ENT_XML1).'</loc>'."\n";
            if ($u['lastmod']) {
                $xml .= '    <lastmod>'.$u['lastmod'].'</lastmod>'."\n";
            }
            $xml .= '    <changefreq>'.$u['freq'].'</changefreq>'."\n";
            $xml .= '    <priority>'.$u['priority'].'</priority>'."\n";
            $xml .= '  </url>'."\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
