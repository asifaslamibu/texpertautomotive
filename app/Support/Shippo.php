<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Shippo (goshippo.com) multi-carrier shipping. Live rates at checkout, plus
 * label purchase and tracking. Fully fail-soft: any error returns empty/null so
 * checkout falls back to the flat rate. Test/live is switched by shippo_mode.
 */
class Shippo
{
    private const BASE = 'https://api.goshippo.com';

    public static function enabled(): bool
    {
        return Setting::get('shippo_enabled') === '1' && self::key() !== '';
    }

    /** Active API token — test or live per shippo_mode. */
    public static function key(): string
    {
        $mode = Setting::get('shippo_mode', 'test');
        $key = $mode === 'live' ? Setting::get('shippo_live_key') : Setting::get('shippo_test_key');

        return trim((string) $key);
    }

    /** Store ship-from origin (shared with the UPS ship-from fields). */
    public static function shipFrom(): array
    {
        return [
            'name'    => Setting::get('ups_ship_from_name') ?: Setting::get('site_name', 'Texpert Deals'),
            'street1' => Setting::get('ups_ship_from_address') ?: '2990 Congressman Ln',
            'city'    => Setting::get('ups_ship_from_city') ?: 'Dallas',
            'state'   => Setting::get('ups_ship_from_state') ?: 'TX',
            'zip'     => Setting::get('ups_ship_from_zip') ?: '75220',
            'country' => Setting::get('ups_ship_from_country') ?: 'US',
            'phone'   => preg_replace('/[^0-9]/', '', (string) Setting::get('contact_phone', '2543580017')),
            'email'   => Setting::get('contact_email', 'sales@texpertdeals.us'),
        ];
    }

    private static function request(string $method, string $path, array $payload = []): ?array
    {
        if (self::key() === '') {
            return null;
        }

        try {
            $resp = Http::withHeaders(['Authorization' => 'ShippoToken '.self::key()])
                ->timeout(25)
                ->{$method}(self::BASE.$path, $payload);

            if (! $resp->successful()) {
                Log::warning('Shippo API '.$resp->status().': '.$resp->body());

                return null;
            }

            return $resp->json();
        } catch (\Throwable $e) {
            Log::warning('Shippo API exception: '.$e->getMessage());

            return null;
        }
    }

    private static function parcel(float $weightLbs): array
    {
        return [
            'length' => '12', 'width' => '9', 'height' => '4', 'distance_unit' => 'in',
            'weight' => number_format(max(0.5, $weightLbs), 2, '.', ''), 'mass_unit' => 'lb',
        ];
    }

    private static function address(array $a, array $fallbackName = []): array
    {
        return array_filter([
            'name'    => $a['name'] ?? ($fallbackName['name'] ?? 'Customer'),
            'street1' => $a['address'] ?? ($a['street1'] ?? ($a['city'] ?? 'N/A')),
            'city'    => $a['city'] ?? '',
            'state'   => $a['state'] ?? '',
            'zip'     => $a['zip'] ?? '',
            'country' => $a['country'] ?? 'US',
            'phone'   => $a['phone'] ?? '',
            'email'   => $a['email'] ?? '',
        ], fn ($v) => $v !== '' && $v !== null);
    }

    /**
     * Live rates for a destination. Returns [] on any error (checkout then uses the flat rate).
     * @return array<int, array{service:string, code:string, cost:float, days:?int, rate_id:?string}>
     */
    public static function rates(array $dest, float $weightLbs): array
    {
        if (! self::enabled() || empty($dest['zip'])) {
            return [];
        }

        $from = self::shipFrom();
        $shipment = self::request('post', '/shipments/', [
            'address_from' => self::address([
                'name' => $from['name'], 'street1' => $from['street1'], 'city' => $from['city'],
                'state' => $from['state'], 'zip' => $from['zip'], 'country' => $from['country'],
                'phone' => $from['phone'], 'email' => $from['email'],
            ]),
            'address_to' => self::address($dest),
            'parcels' => [self::parcel($weightLbs)],
            'async' => false,
        ]);

        $rates = $shipment['rates'] ?? [];
        $out = [];
        foreach ($rates as $r) {
            $out[] = [
                'service' => trim(($r['provider'] ?? '').' '.($r['servicelevel']['name'] ?? '')),
                'code' => (string) ($r['servicelevel']['token'] ?? ($r['object_id'] ?? '')),
                'cost' => round((float) ($r['amount'] ?? 0), 2),
                'days' => $r['estimated_days'] ?? null,
                'rate_id' => $r['object_id'] ?? null,
            ];
        }
        usort($out, fn ($a, $b) => $a['cost'] <=> $b['cost']);

        // Keep the cheapest per service token; cap the list so checkout stays tidy.
        $seen = [];
        $out = array_values(array_filter($out, function ($r) use (&$seen) {
            if ($r['cost'] <= 0 || isset($seen[$r['code']])) {
                return false;
            }
            $seen[$r['code']] = true;

            return true;
        }));

        return array_slice($out, 0, 6);
    }

    /**
     * Live rates for a cart split into boxes (see Parcels). Each box is rated on its
     * own — in parallel — and a service is offered only when it can carry every box;
     * its price is the sum and its delivery time the slowest box.
     *
     * [] = Shippo answered but no service can take the whole order (too heavy / too
     * big). null = Shippo could not be reached or refused the request (outage, bad
     * key) — the caller falls back to the flat rate. Only real answers are cached.
     *
     * @param  array<int, array{length: float, width: float, height: float, weight: float}>  $parcels
     * @return array<int, array{service:string, code:string, cost:float, days:?int, parcels:int}>|null
     */
    public static function ratesForParcels(array $dest, array $parcels): ?array
    {
        if (! self::enabled() || empty($dest['zip']) || ! $parcels) {
            return null;
        }

        $key = 'shippo-rates:'.md5(json_encode([$dest['zip'], $dest['state'] ?? '', $dest['city'] ?? '', $dest['address'] ?? '', $parcels, self::key()]));
        $cached = \Illuminate\Support\Facades\Cache::get($key);
        if (is_array($cached)) {
            return $cached;
        }

        $result = (function () use ($dest, $parcels) {
            $from = self::shipFrom();
            $addressFrom = self::address([
                'name' => $from['name'], 'street1' => $from['street1'], 'city' => $from['city'],
                'state' => $from['state'], 'zip' => $from['zip'], 'country' => $from['country'],
                'phone' => $from['phone'], 'email' => $from['email'],
            ]);
            $addressTo = self::address($dest);
            $token = self::key();

            try {
                $responses = Http::pool(fn ($pool) => array_map(fn ($p) => $pool
                    ->withHeaders(['Authorization' => 'ShippoToken '.$token])
                    ->timeout(25)
                    ->post(self::BASE.'/shipments/', [
                        'address_from' => $addressFrom,
                        'address_to' => $addressTo,
                        'parcels' => [[
                            'length' => (string) $p['length'], 'width' => (string) $p['width'], 'height' => (string) $p['height'],
                            'distance_unit' => 'in',
                            'weight' => number_format($p['weight'], 2, '.', ''), 'mass_unit' => 'lb',
                        ]],
                        'async' => false,
                    ]), $parcels));
            } catch (\Throwable $e) {
                Log::warning('Shippo API exception: '.$e->getMessage());

                return null;
            }

            // service token => [service, cost total, slowest days, boxes covered]
            $combined = null;
            foreach ($responses as $i => $resp) {
                if (! $resp instanceof \Illuminate\Http\Client\Response) {
                    Log::warning('Shippo API: no response ('.($resp instanceof \Throwable ? $resp->getMessage() : 'unknown').')');

                    return null;
                }
                if (! $resp->successful()) {
                    Log::warning('Shippo API '.$resp->status().': '.$resp->body());

                    // outage / bad key → flat-rate fallback; a rejected box (4xx) → no service can take it
                    if ($resp->serverError() || in_array($resp->status(), [401, 403, 429], true)) {
                        return null;
                    }

                    return [];
                }

                // cheapest rate per service for this box
                $best = [];
                foreach ($resp->json('rates') ?? [] as $r) {
                    $code = (string) ($r['servicelevel']['token'] ?? '');
                    $cost = (float) ($r['amount'] ?? 0);
                    if ($code === '' || $cost <= 0) {
                        continue;
                    }
                    if (! isset($best[$code]) || $cost < $best[$code]['cost']) {
                        $best[$code] = [
                            'service' => trim(($r['provider'] ?? '').' '.($r['servicelevel']['name'] ?? '')),
                            'cost' => $cost,
                            'days' => isset($r['estimated_days']) ? (int) $r['estimated_days'] : null,
                        ];
                    }
                }

                if (! $best) {
                    Log::info('Shippo: no rates for box '.($i + 1).' '.json_encode($parcels[$i]).' — '.json_encode($resp->json('messages') ?? []));
                }

                if ($combined === null) {
                    $combined = $best;
                    continue;
                }

                // keep only services that can also take this box
                foreach ($combined as $code => $row) {
                    if (! isset($best[$code])) {
                        unset($combined[$code]);
                        continue;
                    }
                    $combined[$code]['cost'] += $best[$code]['cost'];
                    $a = $row['days'];
                    $b = $best[$code]['days'];
                    $combined[$code]['days'] = ($a === null || $b === null) ? null : max($a, $b);
                }
            }

            $out = [];
            foreach ($combined ?? [] as $code => $row) {
                $out[] = [
                    'service' => $row['service'],
                    'code' => $code,
                    'cost' => round($row['cost'], 2),
                    'days' => $row['days'],
                    'parcels' => count($parcels),
                ];
            }
            usort($out, fn ($a, $b) => $a['cost'] <=> $b['cost']);

            return array_slice($out, 0, 6);
        })();

        // cache real answers (rates, or "nothing can take it") — never an outage
        if (is_array($result)) {
            \Illuminate\Support\Facades\Cache::put($key, $result, now()->addMinutes(15));
        }

        return $result;
    }

    /**
     * Buy a label for a destination + service token. Re-rates the shipment and buys the
     * matching rate. Returns ['tracking','service','carrier','label_url'] or null.
     */
    public static function createLabel(array $dest, float $weightLbs, ?string $serviceToken = null): ?array
    {
        if (! self::enabled()) {
            return null;
        }

        $from = self::shipFrom();
        $shipment = self::request('post', '/shipments/', [
            'address_from' => self::address([
                'name' => $from['name'], 'street1' => $from['street1'], 'city' => $from['city'],
                'state' => $from['state'], 'zip' => $from['zip'], 'country' => $from['country'],
                'phone' => $from['phone'], 'email' => $from['email'],
            ]),
            'address_to' => self::address($dest),
            'parcels' => [self::parcel($weightLbs)],
            'async' => false,
        ]);

        $rates = $shipment['rates'] ?? [];
        if (empty($rates)) {
            return null;
        }

        // pick the requested service, else the cheapest
        $chosen = null;
        foreach ($rates as $r) {
            if ($serviceToken && ($r['servicelevel']['token'] ?? '') === $serviceToken) {
                $chosen = $r;
                break;
            }
        }
        if (! $chosen) {
            usort($rates, fn ($a, $b) => (float) $a['amount'] <=> (float) $b['amount']);
            $chosen = $rates[0];
        }

        $tx = self::request('post', '/transactions/', [
            'rate' => $chosen['object_id'],
            'label_file_type' => 'PDF',
            'async' => false,
        ]);

        if (! $tx || ($tx['status'] ?? '') !== 'SUCCESS') {
            Log::warning('Shippo label failed: '.json_encode($tx['messages'] ?? []));

            return null;
        }

        return [
            'tracking' => $tx['tracking_number'] ?? '',
            'service' => trim(($chosen['provider'] ?? '').' '.($chosen['servicelevel']['name'] ?? '')),
            'carrier' => $chosen['provider'] ?? '',
            'label_url' => $tx['label_url'] ?? '',
        ];
    }

    /** Track a shipment by carrier token + number. */
    public static function track(string $carrier, string $number): ?array
    {
        if (! self::enabled() || $carrier === '' || $number === '') {
            return null;
        }

        return self::request('get', '/tracks/'.rawurlencode($carrier).'/'.rawurlencode($number));
    }
}
