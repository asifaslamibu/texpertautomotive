<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Support\ImageStore;
use Illuminate\Console\Command;

/**
 * Pulls product images that still point at a remote URL onto this server, a few
 * at a time. Safe to run repeatedly — each pass handles the next batch, so a cron
 * job can drain a large catalogue without any single run timing out. Products
 * already on /uploads are skipped, making the command idempotent and resumable.
 */
class LocalizeProductImages extends Command
{
    protected $signature = 'products:localize-images
        {--limit=100 : how many products to process this run}
        {--gallery : also download gallery images (slower)}';

    protected $description = 'Download remote product images onto this server, in batches (resumable).';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));

        // Remote = an http(s) URL we have not stored locally. Placeholder art is
        // left alone; there is nothing to gain by hosting a placehold.co image.
        $query = Product::query()
            ->where('image', 'like', 'http%')
            ->where('image', 'not like', '%placehold.co%');

        $remaining = (clone $query)->count();
        if ($remaining === 0) {
            $this->info('Nothing to do — no remote product images left.');

            return self::SUCCESS;
        }

        $this->line("Remote images remaining: {$remaining}. Processing up to {$limit} this run…");

        $done = 0;
        $failed = 0;
        $galleryCount = 0;
        $bar = $this->output->createProgressBar(min($limit, $remaining));
        $bar->start();

        $query->orderBy('id')->limit($limit)->get()->each(function (Product $product) use (&$done, &$failed, &$galleryCount, $bar) {
            $stored = ImageStore::download($product->image);
            $changes = [];

            if ($stored && str_starts_with($stored, '/uploads/')) {
                $changes['image'] = $stored;
                $done++;
            } else {
                $failed++;   // leave the remote URL in place so the image still shows
            }

            if ($this->option('gallery') && is_array($product->gallery) && $product->gallery) {
                $gallery = array_map(function ($url) use (&$galleryCount) {
                    if (! is_string($url) || ! str_starts_with($url, 'http')) {
                        return $url;
                    }
                    $g = ImageStore::download($url);
                    if ($g && str_starts_with($g, '/uploads/')) {
                        $galleryCount++;

                        return $g;
                    }

                    return $url;
                }, $product->gallery);
                $changes['gallery'] = $gallery;
            }

            if ($changes) {
                $product->forceFill($changes)->save();
            }

            $bar->advance();
        });

        $bar->finish();
        $this->newLine(2);

        $left = (clone $query)->count();
        $this->info("Downloaded {$done} image(s), {$failed} failed (left as remote URLs)"
            .($this->option('gallery') ? ", {$galleryCount} gallery image(s)" : '')
            .". Remote images still remaining: {$left}.");

        if ($left > 0) {
            $this->line('Run again (or let the cron run) to continue.');
        }

        return self::SUCCESS;
    }
}
