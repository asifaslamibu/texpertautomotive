<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_at_price' => 'decimal:2',
        'rating' => 'decimal:1',
        'in_stock' => 'boolean',
        'free_shipping' => 'boolean',
        'featured' => 'boolean',
        'published' => 'boolean',
        'gallery' => 'array',
    ];

    /** Only products visible on the storefront (drafts excluded). */
    public function scopePublished($query)
    {
        return $query->where('published', true);
    }

    /** Main image + gallery images, de-duplicated. */
    public function getImagesAttribute(): array
    {
        return array_values(array_unique(array_filter(
            array_merge([$this->image], is_array($this->gallery) ? $this->gallery : [])
        )));
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getDiscountPercentAttribute(): ?int
    {
        if (! $this->compare_at_price || $this->compare_at_price <= $this->price) {
            return null;
        }

        return (int) round((1 - $this->price / $this->compare_at_price) * 100);
    }

    /**
     * The badge to show for this product, e.g. "Ships from manufacturer".
     *
     * A product normally inherits its brand's tag, so tagging a brand covers its
     * whole catalogue. Individual products can override that with their own text
     * or opt out entirely.
     */
    /**
     * At or above this many units we just say "In Stock" — the exact count is
     * only worth showing while it is low enough to matter to the buyer.
     */
    public const SHOW_COUNT_BELOW = 25;

    /** True when the exact quantity should be shown rather than a plain "In Stock". */
    public function getShowsStockCountAttribute(): bool
    {
        return $this->in_stock
            && $this->stock_qty !== null
            && $this->stock_qty > 0
            && $this->stock_qty < self::SHOW_COUNT_BELOW;
    }

    /**
     * Stock badge for product cards and the product page.
     *
     * @return array{label: string, tone: string}  tone: green | amber | red | slate
     */
    public function getStockBadgeAttribute(): array
    {
        if (! $this->in_stock) {
            return ['label' => 'Call for pricing', 'tone' => 'slate'];
        }
        if (! $this->shows_stock_count) {
            return ['label' => 'In Stock', 'tone' => 'green'];
        }

        return match (true) {
            $this->stock_qty == 1 => ['label' => 'Last One Left', 'tone' => 'red'],
            $this->stock_qty <= 5 => ['label' => 'Only '.$this->stock_qty.' Left', 'tone' => 'amber'],
            default => ['label' => $this->stock_qty.' In Stock', 'tone' => 'green'],
        };
    }

    public function getBadgeAttribute(): ?string
    {
        return match ($this->tag_mode) {
            'none' => null,
            'custom' => trim((string) $this->tag) ?: null,
            // the brand's own tag, else the site-wide default from Settings
            default => Brand::tagFor($this->brand) ?: (trim((string) Setting::get('default_product_tag')) ?: null),
        };
    }
}
