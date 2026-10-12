<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Repoints database image references from .jpg/.jpeg/.png to .webp, but ONLY
 * where the .webp file actually exists on disk (run images:to-webp first). This
 * makes the switch safe: a reference is never pointed at a file that isn't there.
 * Remote URLs (http/https/data:) are left untouched. Use --dry to preview and
 * --revert to switch references back to an existing original.
 *
 * Covers: products.image, products.gallery, categories.image, brands.logo,
 * banners.image, blogs.image, and image-bearing settings values.
 */
class RelinkWebp extends Command
{
    protected $signature = 'images:relink-webp
        {--dry : preview changes without writing}
        {--revert : point references back to an existing original (jpg/png)}';

    protected $description = 'Repoint DB image references to .webp where the webp file exists (reversible).';

    private bool $dry = false;
    private bool $revert = false;
    private int $changed = 0;

    public function handle(): int
    {
        $this->dry = (bool) $this->option('dry');
        $this->revert = (bool) $this->option('revert');

        // ---- simple string columns ----
        $this->relinkColumn('products', 'image');
        $this->relinkColumn('categories', 'image');
        $this->relinkColumn('brands', 'logo');
        $this->relinkColumn('banners', 'image');
        $this->relinkColumn('blogs', 'image');

        // ---- product gallery (JSON array) ----
        $this->relinkGallery();

        // ---- settings values that hold an image path ----
        $this->relinkSettings();

        $this->newLine();
        $this->info(($this->dry ? 'Dry run — ' : 'Done — ').$this->changed.' reference(s) '
            .($this->dry ? 'would be ' : '').($this->revert ? 'reverted.' : 'switched to .webp.'));

        return self::SUCCESS;
    }

    /** For a stored path, return the target path if a switch is possible, else null. */
    private function target(?string $path): ?string
    {
        $path = trim((string) $path);
        if ($path === '' || Str::startsWith($path, ['http://', 'https://', '//', 'data:'])) {
            return null;
        }

        if ($this->revert) {
            if (! preg_match('/\.webp$/i', $path)) {
                return null;
            }
            foreach (['jpg', 'jpeg', 'png'] as $ext) {
                $cand = preg_replace('/\.webp$/i', '.'.$ext, $path);
                if (is_file(public_path(ltrim($cand, '/')))) {
                    return $cand;
                }
            }

            return null;
        }

        if (! preg_match('/\.(jpe?g|png)$/i', $path)) {
            return null;
        }
        $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $path);
        if (is_file(public_path(ltrim($webp, '/')))) {
            return $webp;
        }

        return null;
    }

    private function relinkColumn(string $table, string $col): void
    {
        if (! DB::getSchemaBuilder()->hasTable($table) || ! DB::getSchemaBuilder()->hasColumn($table, $col)) {
            return;
        }

        $n = 0;
        DB::table($table)->select('id', $col)->orderBy('id')->chunkById(500, function ($rows) use ($table, $col, &$n) {
            foreach ($rows as $row) {
                $new = $this->target($row->$col);
                if ($new === null) {
                    continue;
                }
                if (! $this->dry) {
                    DB::table($table)->where('id', $row->id)->update([$col => $new]);
                }
                $n++;
                $this->changed++;
            }
        });
        if ($n) {
            $this->line(sprintf('  %-24s %d', "{$table}.{$col}", $n));
        }
    }

    private function relinkGallery(): void
    {
        if (! DB::getSchemaBuilder()->hasColumn('products', 'gallery')) {
            return;
        }
        $n = 0;
        DB::table('products')->select('id', 'gallery')->orderBy('id')->chunkById(500, function ($rows) use (&$n) {
            foreach ($rows as $row) {
                if (empty($row->gallery)) {
                    continue;
                }
                $arr = json_decode($row->gallery, true);
                if (! is_array($arr) || ! $arr) {
                    continue;
                }
                $touched = false;
                foreach ($arr as $i => $p) {
                    $new = $this->target($p);
                    if ($new !== null) {
                        $arr[$i] = $new;
                        $touched = true;
                    }
                }
                if ($touched) {
                    if (! $this->dry) {
                        DB::table('products')->where('id', $row->id)->update(['gallery' => json_encode($arr)]);
                    }
                    $n++;
                    $this->changed++;
                }
            }
        });
        if ($n) {
            $this->line(sprintf('  %-24s %d', 'products.gallery', $n));
        }
    }

    private function relinkSettings(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('settings')) {
            return;
        }
        $n = 0;
        foreach (DB::table('settings')->get() as $row) {
            $new = $this->target($row->value ?? null);
            if ($new === null) {
                continue;
            }
            if (! $this->dry) {
                DB::table('settings')->where('key', $row->key)->update(['value' => $new]);
            }
            $n++;
            $this->changed++;
        }
        if ($n) {
            $this->line(sprintf('  %-24s %d', 'settings.value', $n));
        }
    }
}
