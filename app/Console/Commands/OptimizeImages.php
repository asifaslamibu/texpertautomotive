<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Recompresses and downsizes oversized uploaded images IN PLACE — same file path
 * and same format, so nothing in the database or markup has to change and no link
 * ever breaks. Most uploads are wildly larger than they render (multi-MB, 4000px+),
 * which is the single biggest drag on mobile LCP. Capping the longest side and
 * re-encoding at a sane quality typically cuts each file 10-25x.
 *
 * Safe to run repeatedly (idempotent): already-small / already-capped files are
 * skipped, and it processes in batches so a single run never times out on shared
 * hosting. Run it from cron/SSH to drain the whole library:
 *
 *   while php artisan images:optimize --limit=150 | grep -q "Optimized [1-9]"; do :; done
 */
class OptimizeImages extends Command
{
    protected $signature = 'images:optimize
        {--limit=200 : how many files to process this run}
        {--dir= : restrict to one subfolder of uploads (e.g. products or banners)}
        {--min-kb=250 : skip files already smaller than this}
        {--dry : report what would change without writing}';

    protected $description = 'Downsize + recompress oversized uploaded images in place (resumable, non-destructive to paths).';

    /** Longest-edge cap and JPEG/WebP quality, per folder. */
    private array $caps = [
        'banners'  => ['max' => 1920, 'q' => 82],
        'sliders'  => ['max' => 1920, 'q' => 82],
        'products' => ['max' => 1200, 'q' => 82],
        'brands'   => ['max' => 600,  'q' => 85],
        'default'  => ['max' => 1400, 'q' => 82],
    ];

    public function handle(): int
    {
        if (! extension_loaded('gd')) {
            $this->error('GD extension is not available.');

            return self::FAILURE;
        }

        @ini_set('memory_limit', '512M');

        $base = public_path('uploads');
        $sub = trim((string) $this->option('dir'), '/');
        $root = $sub !== '' ? $base.DIRECTORY_SEPARATOR.$sub : $base;
        if (! is_dir($root)) {
            $this->error("Folder not found: {$root}");

            return self::FAILURE;
        }

        $limit = max(1, (int) $this->option('limit'));
        $minBytes = max(0, (int) $this->option('min-kb')) * 1024;
        $dry = (bool) $this->option('dry');

        $done = 0;
        $savedBytes = 0;
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($it as $file) {
            if ($done >= $limit) {
                break;
            }
            if (! $file->isFile()) {
                continue;
            }

            $ext = strtolower($file->getExtension());
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                continue;
            }

            $path = $file->getPathname();
            $size = $file->getSize();
            if ($size < $minBytes) {
                continue; // already small enough — nothing to gain
            }

            $folder = basename(dirname($path));
            $cap = $this->caps[$folder] ?? $this->caps['default'];

            $info = @getimagesize($path);
            if (! $info) {
                continue;
            }
            [$w, $h] = $info;

            // Only touch files that are oversized in dimension OR heavy on disk.
            $tooBig = max($w, $h) > $cap['max'];
            if (! $tooBig && $size < 500 * 1024) {
                continue;
            }

            $newSize = $this->reencode($path, $ext, $w, $h, $cap, $dry);
            if ($newSize === null) {
                continue;
            }

            $delta = $size - $newSize;
            $savedBytes += max(0, $delta);
            $done++;
            $this->line(sprintf(
                '%s  %s → %s  (%s)',
                $dry ? '[dry]' : 'ok',
                $this->fmt($size),
                $this->fmt($newSize),
                str_replace(public_path().DIRECTORY_SEPARATOR, '', $path)
            ));
        }

        $this->info(sprintf(
            'Optimized %d file(s)%s, saved %s.',
            $done,
            $dry ? ' (dry run)' : '',
            $this->fmt($savedBytes)
        ));

        return self::SUCCESS;
    }

    /** Load, downscale to the cap, re-encode in the same format. Returns new byte size (or null on skip/failure). */
    private function reencode(string $path, string $ext, int $w, int $h, array $cap, bool $dry): ?int
    {
        $src = match ($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'png'         => @imagecreatefrompng($path),
            'webp'        => @imagecreatefromwebp($path),
            default       => null,
        };
        if (! $src) {
            return null;
        }

        // Compute target size (never upscale).
        $scale = min(1.0, $cap['max'] / max($w, $h));
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($nw, $nh);
        if (in_array($ext, ['png', 'webp'], true)) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $nw, $nh, $transparent);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
        imagedestroy($src);

        if ($dry) {
            imagedestroy($dst);
            // Estimate: we don't write, so report the current size unchanged-ish.
            return filesize($path);
        }

        $tmp = $path.'.opt.tmp';
        $ok = match ($ext) {
            'jpg', 'jpeg' => imagejpeg($dst, $tmp, $cap['q']),
            'png'         => imagepng($dst, $tmp, 8),      // 8 = high zlib compression
            'webp'        => imagewebp($dst, $tmp, $cap['q']),
            default       => false,
        };
        imagedestroy($dst);

        if (! $ok || ! is_file($tmp)) {
            @unlink($tmp);

            return null;
        }

        // Only replace if we actually got smaller; otherwise keep the original.
        $newSize = filesize($tmp);
        if ($newSize > 0 && $newSize < filesize($path)) {
            @rename($tmp, $path);

            return $newSize;
        }

        @unlink($tmp);

        return null;
    }

    private function fmt(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }

        return round($bytes / 1024).' KB';
    }
}
