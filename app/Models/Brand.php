<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $guarded = [];

    protected $casts = [
        'featured' => 'boolean',
        'in_marquee' => 'boolean',
        'tag_enabled' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    /** Storefront link — custom link, else the brand-filtered product listing. */
    public function getUrlAttribute(): string
    {
        return $this->link ?: route('products.index', ['brand' => $this->name]);
    }

    /**
     * Tagged brand names (lowercased) => tag text.
     *
     * Products reference their brand by name rather than by key, so the tag has to
     * be resolved per product. Listing pages render dozens at a time, so the whole
     * table (a few dozen rows) is read once per request instead of once per card.
     */
    public static function tagMap(): array
    {
        static $map = null;

        if ($map !== null) {
            return $map;
        }

        // The badge columns arrive by migration; until they exist on a given
        // environment there simply are no badges — never a 500.
        if (! \Illuminate\Support\Facades\Schema::hasColumn('brands', 'tag')) {
            return $map = [];
        }

        return $map = static::query()
            ->where('tag_enabled', true)
            ->whereNotNull('tag')->where('tag', '<>', '')
            ->pluck('tag', 'name')
            ->mapWithKeys(fn ($tag, $name) => [mb_strtolower(trim($name)) => $tag])
            ->all();
    }

    /** The tag configured for this brand name, if any. */
    public static function tagFor(?string $brandName): ?string
    {
        $key = mb_strtolower(trim((string) $brandName));

        return $key === '' ? null : (static::tagMap()[$key] ?? null);
    }
}
