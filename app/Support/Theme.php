<?php

namespace App\Support;

use App\Models\Setting;

class Theme
{
    /** Default brand colours (amber-500 accent, slate-900 dark). */
    public const DEFAULT_PRIMARY = '#f59e0b';
    public const DEFAULT_SECONDARY = '#0f172a';

    /** Mix weights for building a 50–950 shade ramp from a single 500-anchor colour. */
    private const LIGHT = [50 => 0.95, 100 => 0.90, 200 => 0.78, 300 => 0.60, 400 => 0.32];
    private const DARK = [600 => 0.16, 700 => 0.32, 800 => 0.46, 900 => 0.58, 950 => 0.72];

    public static function primary(): string
    {
        return self::normalize(Setting::get('color_primary'), self::DEFAULT_PRIMARY);
    }

    public static function secondary(): string
    {
        return self::normalize(Setting::get('color_secondary'), self::DEFAULT_SECONDARY);
    }

    /** A `<style>` block overriding the Tailwind colour variables used across the site. */
    public static function styleTag(): string
    {
        $primary = self::primary();
        $secondary = self::secondary();

        $ramp = self::ramp($primary);                  // accent (amber)
        $dark = self::darkRamp($secondary);            // dark surfaces (slate 800–950)

        $vars = [];

        // Accent palette → Tailwind `amber-*` + the project's `brand-*`
        foreach ($ramp as $shade => $hex) {
            $vars["--color-amber-{$shade}"] = $hex;
        }
        foreach ([50, 100, 500, 600, 700] as $shade) {
            $vars["--color-brand-{$shade}"] = $ramp[$shade];
        }

        // Dark surfaces → only the darkest `slate-*` shades so neutral greys stay clean
        foreach ($dark as $shade => $hex) {
            $vars["--color-slate-{$shade}"] = $hex;
        }

        $body = '';
        foreach ($vars as $name => $value) {
            $body .= "{$name}:{$value};";
        }

        return "<style id=\"theme-vars\">:root{{$body}}</style>";
    }

    /** Build a full 50–950 ramp from a 500-anchor hex. */
    public static function ramp(string $hex): array
    {
        $out = [500 => $hex];
        foreach (self::LIGHT as $shade => $w) {
            $out[$shade] = self::mix($hex, '#ffffff', $w);
        }
        foreach (self::DARK as $shade => $w) {
            $out[$shade] = self::mix($hex, '#000000', $w);
        }
        ksort($out);

        return $out;
    }

    /** Override only the dark slate shades (800/900/950) from a 900-anchor hex. */
    private static function darkRamp(string $hex): array
    {
        return [
            800 => self::mix($hex, '#ffffff', 0.14),
            900 => $hex,
            950 => self::mix($hex, '#000000', 0.30),
        ];
    }

    /** Linearly blend $hex toward $target ($weight = amount of target, 0–1). */
    private static function mix(string $hex, string $target, float $weight): string
    {
        [$r1, $g1, $b1] = self::rgb($hex);
        [$r2, $g2, $b2] = self::rgb($target);
        $r = (int) round($r1 + ($r2 - $r1) * $weight);
        $g = (int) round($g1 + ($g2 - $g1) * $weight);
        $b = (int) round($b1 + ($b2 - $b1) * $weight);

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    private static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    /** Validate a #rrggbb value, falling back to a default. */
    private static function normalize($value, string $default): string
    {
        $value = is_string($value) ? trim($value) : '';

        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : $default;
    }
}
