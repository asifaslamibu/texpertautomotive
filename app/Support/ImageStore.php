<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ImageStore
{
    /** Public, web-accessible directory (no storage:link needed on XAMPP). */
    public static function dir(string $folder = 'products'): string
    {
        $dir = public_path('uploads/'.$folder);
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        return $dir;
    }

    /** Store an uploaded file and return its public URL path (e.g. /uploads/products/xyz.jpg). */
    public static function store(UploadedFile $file, string $folder = 'products'): string
    {
        self::dir($folder);
        $name = Str::random(24).'.'.strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $file->move(public_path('uploads/'.$folder), $name);

        return '/uploads/'.$folder.'/'.$name;
    }

    /**
     * Download a remote image to local storage and return its public URL path.
     * Returns the original URL on failure (so the product still has an image).
     */
    public static function download(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        try {
            // Short, bounded waits — one slow host must not stall a whole import.
            $response = Http::connectTimeout(5)->timeout(8)->withHeaders(['User-Agent' => 'TexperDeals-Importer/1.0'])->get($url);
            if (! $response->successful() || strlen($response->body()) < 100) {
                return $url; // keep remote URL as fallback
            }

            $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'])) {
                $ext = match (true) {
                    str_contains($response->header('Content-Type'), 'png')  => 'png',
                    str_contains($response->header('Content-Type'), 'webp') => 'webp',
                    str_contains($response->header('Content-Type'), 'gif')  => 'gif',
                    default => 'jpg',
                };
            }

            self::dir();
            $name = Str::random(24).'.'.$ext;
            file_put_contents(public_path('uploads/products/'.$name), $response->body());

            return '/uploads/products/'.$name;
        } catch (\Throwable $e) {
            return $url; // network error — keep remote URL
        }
    }
}
