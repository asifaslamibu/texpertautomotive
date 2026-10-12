<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

/**
 * Pulls a lightweight summary of the connected Stripe account (balance, account
 * status, recent payments) for the admin dashboard. Cached briefly so the
 * dashboard never blocks on Stripe's API.
 */
class StripeReport
{
    /** @return array|null  null when Stripe isn't configured; otherwise a summary (with 'error' set on failure). */
    public static function summary(): ?array
    {
        if (! StripeConfig::enabled()) {
            return null;
        }

        $mode = StripeConfig::mode();
        $cacheKey = 'stripe_summary_'.$mode.'_'.substr(md5((string) StripeConfig::secret()), 0, 8);

        return Cache::remember($cacheKey, 120, function () use ($mode) {
            try {
                $stripe = new StripeClient(StripeConfig::secret());

                $account = $stripe->accounts->retrieve();
                $balance = $stripe->balance->retrieve();
                $charges = $stripe->charges->all(['limit' => 6]);

                $fmtBalance = fn ($list) => collect($list)->map(fn ($b) => [
                    'amount' => $b->amount / 100,
                    'currency' => strtoupper($b->currency),
                ])->all();

                $payments = collect($charges->data)->map(fn ($c) => [
                    'amount' => $c->amount / 100,
                    'currency' => strtoupper($c->currency),
                    'status' => $c->status,                       // succeeded / pending / failed
                    'paid' => (bool) $c->paid,
                    'refunded' => (bool) $c->refunded,
                    'email' => $c->billing_details->email ?? ($c->receipt_email ?? '—'),
                    'desc' => $c->description ?: '—',
                    'date' => $c->created,                        // unix ts
                ])->all();

                // gross volume from the most recent charges we pulled (succeeded only)
                $recentGross = collect($charges->data)
                    ->where('paid', true)->where('refunded', false)
                    ->sum(fn ($c) => $c->amount / 100);

                return [
                    'mode' => $mode,
                    'error' => null,
                    'account' => [
                        'id' => $account->id,
                        'country' => $account->country,
                        'currency' => strtoupper($account->default_currency ?? StripeConfig::currency()),
                        'charges_enabled' => (bool) $account->charges_enabled,
                        'payouts_enabled' => (bool) $account->payouts_enabled,
                        'email' => $account->email ?? null,
                        'name' => $account->business_profile->name ?? ($account->settings->dashboard->display_name ?? null),
                    ],
                    'available' => $fmtBalance($balance->available ?? []),
                    'pending' => $fmtBalance($balance->pending ?? []),
                    'payments' => $payments,
                    'recent_gross' => $recentGross,
                ];
            } catch (\Throwable $e) {
                Log::warning('Stripe dashboard summary failed: '.$e->getMessage());

                return ['mode' => $mode, 'error' => $e->getMessage()];
            }
        });
    }

    /** Clear the cached summary (e.g. after saving new keys). */
    public static function forget(): void
    {
        Cache::flush();
    }
}
