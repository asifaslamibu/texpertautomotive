<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Creates a .webp copy next to every local jpg/jpeg/png under public/uploads.
 * Originals are KEPT (nothing is deleted), so this step is fully non-destructive
 * and reversible — the database still points at the originals until you run
 * images:relink-webp. Resumable: files that already have a .webp sibling are
 * skipped, and it works in batches so a single run never times out on shared
 * hosting. Drain the whole library from SSH with:
 *
 *   while php artisan images:to-webp --limit=200 | grep -q "Converted [1-9]"; do :; done
 */
class ImagesToWebp extends Command
{
    protected $signature = 'images:to-webp
        {--limit=200 : how many files to convert this run}
        {--dir= : restrict to one subfolder of uploads (e.g. products)}
        {--quality=82 : WebP quality 1-100}
        {--force : re-create the .webp even if one already exists}
        {--dry : report what would convert without writing}';

    protected $description = 'Create a .webp copy of every local jpg/png under public/uploads (originals kept).';

    /** Higher quality for logos/brand art where artefacts show more. */
    private array $folderQuality = [
        'logos'  => 90,
        'brands' => 90,
    ];

    public function handle(): int
    {
        if (! function_exists('imagewebp')) {
            $this->error('GD WebP support is not available on this server.');

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
        $q = max(1, min(100, (int) $this->option('quality')));
        $force = (bool) $this->option('force');
        $dry = (bool) $this->option('dry');

        $done = 0;
        $skipped = 0;
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
            if (! in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                continue;
            }

            $path = $file->getPathname();
            $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);

            if (! $force && is_file($webp)) {
                $skipped++;
                continue;
            }

            $folder = basename(dirname($path));
            $quality = $this->folderQuality[$folder] ?? $q;

            if ($dry) {
                $this->line('[dry] would convert  '.$this->rel($path));
                $done++;
                continue;
            }

            $newSize = $this->convert($path, $ext, $webp, $quality);
            if ($newSize === null) {
                $this->warn('  failed: '.$this->rel($path));
                continue;
            }

            $savedBytes += max(0, $file->getSize() - $newSize);
            $done++;
            $this->line(sprintf('ok  %s → %s  %s',
                $this->fmt($file->getSize()), $this->fmt($newSize), $this->rel($webp)));
        }

        $this->info(sprintf('Converted %d file(s)%s, skipped %d already-done, saved ~%s.',
            $done, $dry ? ' (dry run)' : '', $skipped, $this->fmt($savedBytes)));

        return self::SUCCESS;
    }

    private function convert(string $path, string $ext, string $webp, int $quality): ?int
    {
        $src = match ($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'png'         => @imagecreatefrompng($path),
            default       => null,
        };
        if (! $src) {
            return null;
        }

        if ($ext === 'png') {
            imagepalettetotruecolor($src);
            imagealphablending($src, false);
            imagesavealpha($src, true);
        }

        $tmp = $webp.'.tmp';
        $ok = imagewebp($src, $tmp, $quality);
        imagedestroy($src);

        if (! $ok || ! is_file($tmp)) {
            @unlink($tmp);

            return null;
        }
        @rename($tmp, $webp);

        return filesize($webp);
    }

    private function rel(string $path): string
    {
        return str_replace(public_path().DIRECTORY_SEPARATOR, '', $path);
    }

    private function fmt(int $bytes): string
    {
        return $bytes >= 1048576 ? round($bytes / 1048576, 1).' MB' : round($bytes / 1024).' KB';
    }
}
