<?php

use Illuminate\Support\Str;

if (! function_exists('media')) {
    /**
     * Resolve a stored media path to a browser URL.
     * - Full URLs (http/https///) are returned unchanged.
     * - Local paths like "/uploads/products/x.jpg" are resolved through asset()
     *   so they respect the app's base path (works under a subfolder e.g. /staging/public).
     */
    function media(?string $path): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }
        if (Str::startsWith($path, ['http://', 'https://', '//', 'data:'])) {
            return $path;
        }

        return asset(ltrim($path, '/'));
    }
}
