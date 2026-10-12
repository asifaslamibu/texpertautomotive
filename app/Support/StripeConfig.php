<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Resolves Stripe credentials from admin Settings (DB) with .env fallback.
 * Supports separate Test and Live key sets, switched by the "stripe_mode" setting.
 */
class StripeConfig
{
    /** Current mode: 'test' or 'live'. */
    public static function mode(): string
    {
        return Setting::get('stripe_mode', 'test') === 'live' ? 'live' : 'test';
    }

    /** Card payment UI style: 'embedded' (on-page Payment Element) or 'redirect' (Stripe-hosted Checkout). */
    public static function checkoutType(): string
    {
        return Setting::get('stripe_checkout_type', 'embedded') === 'redirect' ? 'redirect' : 'embedded';
    }

    /** Is Stripe turned on AND has a usable secret key for the current mode? */
    public static function enabled(): bool
    {
        return Setting::get('stripe_enabled', '0') === '1' && (bool) self::secret();
    }

    /** Publishable key (pk_...). */
    public static function key(): ?string
    {
        return self::byMode('stripe_test_key', 'stripe_live_key') ?: config('services.stripe.key');
    }

    /** Secret key (sk_...). */
    public static function secret(): ?string
    {
        return self::byMode('stripe_test_secret', 'stripe_live_secret') ?: config('services.stripe.secret');
    }

    /** Webhook signing secret (whsec_...). */
    public static function webhookSecret(): ?string
    {
        return self::byMode('stripe_test_webhook_secret', 'stripe_live_webhook_secret') ?: config('services.stripe.webhook_secret');
    }

    /** Three-letter currency code. */
    public static function currency(): string
    {
        return strtolower(Setting::get('stripe_currency') ?: config('services.stripe.currency', 'usd'));
    }

    /** Pick the test or live setting based on the active mode. */
    private static function byMode(string $testKey, string $liveKey): ?string
    {
        $value = Setting::get(self::mode() === 'live' ? $liveKey : $testKey);

        return $value !== '' ? $value : null;
    }
}
