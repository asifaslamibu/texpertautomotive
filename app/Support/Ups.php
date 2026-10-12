<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin client over the UPS REST APIs — rating, tracking, address validation and
 * shipping labels. Credentials come from Admin → Settings (falling back to env).
 *
 * Every public call fails soft: on a missing config, a network error or a UPS
 * error response it returns null / an empty array and logs, so the storefront
 * always keeps working (checkout falls back to the flat rate, etc.).
 */
class Ups
{
    /** UPS REST API versions (bump here if UPS deprecates one). */
    private const RATE_VERSION = 'v2409';
    private const SHIP_VERSION = 'v2409';

    /* ---------------- config ---------------- */

    public static function cfg(string $key, $default = null)
    {
        // e.g. ups_client_id, ups_client_secret, ups_account, ups_env
        $val = Setting::get('ups_'.$key);

        return ($val === null || $val === '') ? (config('services.ups.'.$key) ?? $default) : $val;
    }

    public static function enabled(): bool
    {
        return Setting::get('ups_enabled') === '1'
            && trim((string) self::cfg('client_id')) !== ''
            && trim((string) self::cfg('client_secret')) !== '';
    }

    /** Production hits onlinetools; everything else uses the CIE sandbox. */
    private static function base(): string
    {
        return self::cfg('env', 'sandbox') === 'production'
            ? 'https://onlinetools.ups.com'
            : 'https://wwwcie.ups.com';
    }

    /** Store's ship-from address, used as the rating/label origin. */
    public static function shipFrom(): array
    {
        return [
            'name'    => self::cfg('ship_from_name') ?: Setting::get('site_name', 'Texpert Deals'),
            'address' => self::cfg('ship_from_address') ?: '2990 Congressman Ln',
            'city'    => self::cfg('ship_from_city') ?: 'Dallas',
            'state'   => self::cfg('ship_from_state') ?: 'TX',
            'zip'     => self::cfg('ship_from_zip') ?: '75220',
            'country' => self::cfg('ship_from_country') ?: 'US',
            'phone'   => preg_replace('/[^0-9]/', '', (string) Setting::get('contact_phone', '2543580017')),
        ];
    }

    /* ---------------- auth ---------------- */

    /** OAuth client-credentials token, cached until shortly before it expires. */
    private static function token(): ?string
    {
        $id = trim((string) self::cfg('client_id'));
        $secret = trim((string) self::cfg('client_secret'));
        if ($id === '' || $secret === '') {
            return null;
        }

        $cacheKey = 'ups_token_'.md5($id.self::base());

        return Cache::get($cacheKey) ?: (function () use ($id, $secret, $cacheKey) {
            try {
                $res = Http::asForm()
                    ->withBasicAuth($id, $secret)
                    ->withHeaders(['x-merchant-id' => self::cfg('account') ?: ''])
                    ->timeout(15)
                    ->post(self::base().'/security/v1/oauth/token', ['grant_type' => 'client_credentials']);

                if (! $res->successful()) {
                    Log::warning('UPS token error', ['status' => $res->status(), 'body' => $res->body()]);

                    return null;
                }

                $token = $res->json('access_token');
                $ttl = max(60, (int) $res->json('expires_in', 3600) - 60);
                if ($token) {
                    Cache::put($cacheKey, $token, $ttl);
                }

                return $token;
            } catch (\Throwable $e) {
                Log::warning('UPS token exception', ['msg' => $e->getMessage()]);

                return null;
            }
        })();
    }

    /** Authenticated JSON request helper. Returns the decoded body or null. */
    private static function request(string $method, string $path, array $payload = [], array $query = []): ?array
    {
        $token = self::token();
        if (! $token) {
            return null;
        }

        try {
            $req = Http::withToken($token)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'transId' => \Illuminate\Support\Str::uuid()->toString(),
                    'transactionSrc' => 'texpertdeals',
                ])
                ->timeout(20);

            $url = self::base().$path;
            $res = $method === 'GET'
                ? $req->get($url, $query)
                : $req->send($method, $url, ['json' => $payload, 'query' => $query]);

            if (! $res->successful()) {
                Log::info('UPS API error', ['path' => $path, 'status' => $res->status(), 'body' => $res->body()]);

                return null;
            }

            return $res->json();
        } catch (\Throwable $e) {
            Log::warning('UPS API exception', ['path' => $path, 'msg' => $e->getMessage()]);

            return null;
        }
    }

    /* ---------------- rating ---------------- */

    /**
     * Shop rates to a destination for a total package weight.
     * @return array<int, array{service:string, code:string, cost:float}>  cheapest first
     */
    public static function rates(array $dest, float $weightLbs): array
    {
        if (! self::enabled()) {
            return [];
        }

        $from = self::shipFrom();
        $weight = number_format(max(1, $weightLbs), 1, '.', '');

        $payload = [
            'RateRequest' => [
                'Request' => ['SubVersion' => '2409', 'TransactionReference' => ['CustomerContext' => 'Rating']],
                'Shipment' => [
                    'Shipper' => [
                        'ShipperNumber' => self::cfg('account') ?: '',
                        'Address' => ['PostalCode' => $from['zip'], 'CountryCode' => $from['country'], 'StateProvinceCode' => $from['state']],
                    ],
                    'ShipFrom' => [
                        'Address' => ['PostalCode' => $from['zip'], 'CountryCode' => $from['country'], 'StateProvinceCode' => $from['state']],
                    ],
                    'ShipTo' => [
                        'Address' => array_filter([
                            'PostalCode' => $dest['zip'] ?? '',
                            'CountryCode' => $dest['country'] ?? 'US',
                            'StateProvinceCode' => $dest['state'] ?? '',
                            'City' => $dest['city'] ?? '',
                            'ResidentialAddressIndicator' => 'Y',
                        ]),
                    ],
                    'Package' => [
                        'PackagingType' => ['Code' => '02'],   // customer packaging
                        'PackageWeight' => ['UnitOfMeasurement' => ['Code' => 'LBS'], 'Weight' => $weight],
                    ],
                ],
            ],
        ];

        $body = self::request('POST', '/api/rating/'.self::RATE_VERSION.'/Shop', $payload);
        $rated = data_get($body, 'RateResponse.RatedShipment');
        if (! $rated) {
            return [];
        }
        $rated = isset($rated['TotalCharges']) ? [$rated] : $rated;   // single vs array

        $out = [];
        foreach ($rated as $r) {
            $code = (string) data_get($r, 'Service.Code');
            $cost = (float) data_get($r, 'NegotiatedRateCharges.TotalCharge.MonetaryValue',
                data_get($r, 'TotalCharges.MonetaryValue', 0));
            if ($cost > 0) {
                $out[] = ['service' => self::serviceName($code), 'code' => $code, 'cost' => round($cost, 2)];
            }
        }
        usort($out, fn ($a, $b) => $a['cost'] <=> $b['cost']);

        return $out;
    }

    /* ---------------- tracking ---------------- */

    /** @return array{status:string, delivered:bool, activity:array}|null */
    public static function track(string $number): ?array
    {
        $number = trim($number);
        if ($number === '' || ! self::enabled()) {
            return null;
        }

        $body = self::request('GET', '/api/track/v1/details/'.urlencode($number), [], ['locale' => 'en_US']);
        $pkg = data_get($body, 'trackResponse.shipment.0.package.0');
        if (! $pkg) {
            return null;
        }

        $status = (string) data_get($pkg, 'currentStatus.description', 'In transit');
        $activity = collect((array) data_get($pkg, 'activity', []))->map(fn ($a) => [
            'status' => (string) data_get($a, 'status.description', ''),
            'location' => trim((string) data_get($a, 'location.address.city', '').' '.(string) data_get($a, 'location.address.stateProvince', '')),
            'date' => (string) data_get($a, 'date', ''),
            'time' => (string) data_get($a, 'time', ''),
        ])->all();

        return [
            'status' => $status,
            'delivered' => str_contains(mb_strtolower($status), 'delivered'),
            'activity' => $activity,
        ];
    }

    /* ---------------- address validation ---------------- */

    /**
     * Validate/normalise a US address.
     * @return array{valid:bool, candidates:array}  candidates are suggested corrections
     */
    public static function validateAddress(array $addr): array
    {
        if (! self::enabled()) {
            return ['valid' => true, 'candidates' => []];   // don't block checkout when UPS is off
        }

        $payload = [
            'XAVRequest' => [
                'AddressKeyFormat' => array_filter([
                    'AddressLine' => $addr['address'] ?? '',
                    'PoliticalDivision2' => $addr['city'] ?? '',
                    'PoliticalDivision1' => $addr['state'] ?? '',
                    'PostcodePrimaryLow' => $addr['zip'] ?? '',
                    'CountryCode' => $addr['country'] ?? 'US',
                ]),
            ],
        ];

        // requestoption 1 = address validation
        $body = self::request('POST', '/api/addressvalidation/v2/1', $payload, ['regionalrequestindicator' => 'true']);
        if ($body === null) {
            return ['valid' => true, 'candidates' => []];
        }

        $valid = data_get($body, 'XAVResponse.ValidAddressIndicator') !== null;
        $candidates = collect((array) data_get($body, 'XAVResponse.Candidate', []))
            ->map(fn ($c) => [
                'address' => (string) data_get($c, 'AddressKeyFormat.AddressLine'),
                'city' => (string) data_get($c, 'AddressKeyFormat.PoliticalDivision2'),
                'state' => (string) data_get($c, 'AddressKeyFormat.PoliticalDivision1'),
                'zip' => trim((string) data_get($c, 'AddressKeyFormat.PostcodePrimaryLow').'-'.(string) data_get($c, 'AddressKeyFormat.PostcodeExtendedLow'), '-'),
            ])->all();

        return ['valid' => $valid || ! empty($candidates), 'candidates' => $candidates];
    }

    /* ---------------- shipping label ---------------- */

    /**
     * Buy a label for a shipment.
     * @return array{tracking:string, service:string, label_base64:string, label_format:string}|null
     */
    public static function createLabel(array $to, float $weightLbs, string $serviceCode = '03'): ?array
    {
        if (! self::enabled() || ! self::cfg('account')) {
            return null;
        }

        $from = self::shipFrom();
        $weight = number_format(max(1, $weightLbs), 1, '.', '');

        $payload = [
            'ShipmentRequest' => [
                'Shipment' => [
                    'Description' => 'Order',
                    'Shipper' => [
                        'Name' => $from['name'],
                        'ShipperNumber' => self::cfg('account'),
                        'Phone' => ['Number' => $from['phone'] ?: '0000000000'],
                        'Address' => [
                            'AddressLine' => [$from['address']],
                            'City' => $from['city'], 'StateProvinceCode' => $from['state'],
                            'PostalCode' => $from['zip'], 'CountryCode' => $from['country'],
                        ],
                    ],
                    'ShipTo' => [
                        'Name' => $to['name'] ?? 'Customer',
                        'Phone' => ['Number' => preg_replace('/[^0-9]/', '', (string) ($to['phone'] ?? '')) ?: '0000000000'],
                        'Address' => array_filter([
                            'AddressLine' => [$to['address'] ?? ''],
                            'City' => $to['city'] ?? '', 'StateProvinceCode' => $to['state'] ?? '',
                            'PostalCode' => $to['zip'] ?? '', 'CountryCode' => $to['country'] ?? 'US',
                            'ResidentialAddressIndicator' => 'Y',
                        ]),
                    ],
                    'ShipFrom' => [
                        'Name' => $from['name'],
                        'Address' => [
                            'AddressLine' => [$from['address']],
                            'City' => $from['city'], 'StateProvinceCode' => $from['state'],
                            'PostalCode' => $from['zip'], 'CountryCode' => $from['country'],
                        ],
                    ],
                    'PaymentInformation' => [
                        'ShipmentCharge' => [
                            'Type' => '01',
                            'BillShipper' => ['AccountNumber' => self::cfg('account')],
                        ],
                    ],
                    'Service' => ['Code' => $serviceCode, 'Description' => self::serviceName($serviceCode)],
                    'Package' => [
                        'Packaging' => ['Code' => '02'],
                        'PackageWeight' => ['UnitOfMeasurement' => ['Code' => 'LBS'], 'Weight' => $weight],
                    ],
                ],
                'LabelSpecification' => [
                    'LabelImageFormat' => ['Code' => 'GIF'],
                    'HTTPUserAgent' => 'Mozilla/5.0',
                ],
            ],
        ];

        $body = self::request('POST', '/api/shipments/'.self::SHIP_VERSION.'/ship', $payload);
        $result = data_get($body, 'ShipmentResponse.ShipmentResults');
        $pkg = data_get($result, 'PackageResults');
        $pkg = isset($pkg['TrackingNumber']) ? $pkg : ($pkg[0] ?? null);
        if (! $pkg) {
            return null;
        }

        return [
            'tracking' => (string) data_get($pkg, 'TrackingNumber'),
            'service' => self::serviceName($serviceCode),
            'label_base64' => (string) data_get($pkg, 'ShippingLabel.GraphicImage'),
            'label_format' => 'gif',
        ];
    }

    /* ---------------- helpers ---------------- */

    /** Map a UPS service code to a readable name. */
    public static function serviceName(string $code): string
    {
        return [
            '01' => 'UPS Next Day Air', '02' => 'UPS 2nd Day Air', '03' => 'UPS Ground',
            '07' => 'UPS Worldwide Express', '08' => 'UPS Worldwide Expedited',
            '11' => 'UPS Standard', '12' => 'UPS 3 Day Select', '13' => 'UPS Next Day Air Saver',
            '14' => 'UPS Next Day Air Early', '54' => 'UPS Worldwide Express Plus',
            '59' => 'UPS 2nd Day Air A.M.', '65' => 'UPS Worldwide Saver',
        ][$code] ?? 'UPS ('.$code.')';
    }
}
