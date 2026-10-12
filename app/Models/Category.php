<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $guarded = [];

    protected $casts = [
        'featured' => 'boolean',
        'hidden' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort')->orderBy('name');
    }

    /** Children loaded recursively to any depth (with product counts). */
    public function childrenRecursive(): HasMany
    {
        return $this->children()->withCount('products')->with('childrenRecursive');
    }

    /** Whether the hidden column exists yet (migration may lag an FTP deploy). Memoised per request. */
    protected static ?bool $supportsHidden = null;

    public static function supportsHidden(): bool
    {
        return static::$supportsHidden ??= \Illuminate\Support\Facades\Schema::hasColumn('categories', 'hidden');
    }

    /** Storefront-only children: skips categories flagged hidden. */
    public function visibleChildren(): HasMany
    {
        $rel = $this->hasMany(Category::class, 'parent_id');
        if (static::supportsHidden()) {
            $rel->where('hidden', false);
        }

        return $rel->orderBy('sort')->orderBy('name');
    }

    /** Visible children, recursively (used for nav, filters, department circles). */
    public function visibleChildrenRecursive(): HasMany
    {
        return $this->visibleChildren()->withCount('products')->with('visibleChildrenRecursive');
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /** Storefront visibility filter. */
    public function scopeVisible($query)
    {
        return static::supportsHidden() ? $query->where('hidden', false) : $query;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** All descendant category ids (self + children, recursive). */
    public function descendantIds(): array
    {
        $ids = [$this->id];
        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->descendantIds());
        }

        return $ids;
    }

    /** Descendant ids that are visible on the storefront (self + non-hidden subtree). */
    public function visibleDescendantIds(): array
    {
        $ids = [$this->id];
        foreach ($this->visibleChildren as $child) {
            $ids = array_merge($ids, $child->visibleDescendantIds());
        }

        return $ids;
    }

    /**
     * Ids of every hidden category PLUS all their descendants — the full set whose
     * products must be kept off the storefront. Cached; cleared on any category save.
     */
    public static function hiddenIds(): array
    {
        if (! static::supportsHidden()) {
            return [];
        }

        return \Illuminate\Support\Facades\Cache::remember('category_hidden_ids', now()->addHours(6), function () {
            $all = static::get(['id', 'parent_id', 'hidden']);
            $byParent = $all->groupBy('parent_id');
            $hidden = [];

            $collect = function ($id) use (&$collect, $byParent, &$hidden) {
                foreach ($byParent->get($id, []) as $child) {
                    $hidden[] = $child->id;
                    $collect($child->id);
                }
            };

            foreach ($all->where('hidden', true) as $h) {
                $hidden[] = $h->id;
                $collect($h->id);
            }

            return array_values(array_unique($hidden));
        });
    }
}
