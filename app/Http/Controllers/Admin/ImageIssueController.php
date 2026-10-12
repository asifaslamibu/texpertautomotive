<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ImageIssueController extends Controller
{
    private const CACHE_KEY = 'image_issues_report';

    private function guard(): void
    {
        // Master admin + Administrator (super) role.
        abort_unless(auth()->user()?->isSuperAdmin(), 403, 'This section is restricted.');
    }

    public function index(Request $request)
    {
        $this->guard();

        if ($request->boolean('recheck')) {
            Cache::forget(self::CACHE_KEY);
        }

        $report = Cache::remember(self::CACHE_KEY, now()->addMinutes(30), fn () => $this->scan());

        return view('admin.image-issues.index', $report);
    }

    public function export()
    {
        $this->guard();

        $report = Cache::remember(self::CACHE_KEY, now()->addMinutes(30), fn () => $this->scan());
        $filename = 'image-issues-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($report) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Issue', 'SKU', 'Product', 'Image']);
            foreach ($report['issues'] as $i) {
                fputcsv($out, [$i['label'], $i['sku'], $i['name'], $i['image']]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /** Scan all products and return the broken-image report. */
    private function scan(): array
    {
        $issues = [];
        $remote = []; // image url => [products]

        Product::select('id', 'slug', 'name', 'sku', 'image')->orderBy('name')
            ->chunk(500, function ($rows) use (&$issues, &$remote) {
                foreach ($rows as $p) {
                    $img = trim((string) $p->image);

                    if ($img === '') {
                        $issues[] = $this->row($p, 'empty', 'No image');
                    } elseif (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) {
                        $remote[$img][] = $p; // checked in batch below
                    } else {
                        // local public file
                        $path = public_path(ltrim($img, '/'));
                        if (! is_file($path)) {
                            $issues[] = $this->row($p, 'missing_file', 'File not found on server');
                        }
                    }
                }
            });

        // Batch-check remote URLs in parallel, then flag the dead ones.
        if ($remote) {
            $codes = $this->checkUrls(array_keys($remote));
            foreach ($remote as $url => $products) {
                $code = $codes[$url] ?? 0;
                if ($code !== 200) {
                    foreach ($products as $p) {
                        $issues[] = $this->row($p, 'dead_url', 'Remote URL error ('.($code ?: 'no response').')');
                    }
                }
            }
        }

        // sort: empty, missing_file, dead_url
        usort($issues, fn ($a, $b) => [$a['type'], $a['name']] <=> [$b['type'], $b['name']]);

        return [
            'issues' => $issues,
            'checkedAt' => now(),
            'total' => Product::count(),
            'stats' => [
                'empty' => collect($issues)->where('type', 'empty')->count(),
                'missing_file' => collect($issues)->where('type', 'missing_file')->count(),
                'dead_url' => collect($issues)->where('type', 'dead_url')->count(),
            ],
        ];
    }

    private function row(Product $p, string $type, string $label): array
    {
        return [
            'type' => $type,
            'label' => $label,
            'id' => $p->id,
            'slug' => $p->slug,
            'sku' => $p->sku,
            'name' => $p->name,
            'image' => $p->image,
        ];
    }

    /** Parallel HEAD check of URLs → [url => http_code]. */
    private function checkUrls(array $urls): array
    {
        $mh = curl_multi_init();
        $handles = [];
        foreach ($urls as $url) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_NOBODY => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_CONNECTTIMEOUT => 6,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (TexperDeals image checker)',
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$url] = $ch;
        }

        do {
            $status = curl_multi_exec($mh, $running);
            if ($running) {
                curl_multi_select($mh, 1.0);
            }
        } while ($running && $status === CURLM_OK);

        $codes = [];
        foreach ($handles as $url => $ch) {
            $codes[$url] = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        return $codes;
    }
}
