<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $guarded = [];

    public $timestamps = true;

    protected static ?array $cache = null;

    /** Get a setting value with optional default. */
    public static function get(string $key, $default = null)
    {
        if (static::$cache === null) {
            static::$cache = static::query()->pluck('value', 'key')->all();
        }

        return static::$cache[$key] ?? $default;
    }

    /** Create or update a setting. */
    public static function put(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        static::$cache = null;
    }
}
