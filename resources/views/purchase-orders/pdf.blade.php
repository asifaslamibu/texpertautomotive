<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    @php
        $site   = \App\Models\Setting::get('site_name', 'Texpert Deals');
        $cEmail = \App\Models\Setting::get('contact_email', 'sales@texpertdeals.us');
        $cPhone = \App\Models\Setting::get('contact_phone', '(214)-896-2808');
        $accent = \App\Models\Setting::get('color_primary') ?: '#e8912a';

        $brandTop = \Illuminate\Support\Str::of($site)->before(' ') ?: $site;   // "Texpert"
        $brandBot = \Illuminate\Support\Str::of($site)->after(' ');             // "Deals"

        $items = $po->lineItems();
        $rows  = max(6, count($items));   // pad to a form-like minimum
    @endphp
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #111; font-size: 11px; margin: 0; }
        .black { background: #111; color: #fff; }
        .tag { display: inline-block; background: #111; color: #fff; font-weight: bold;
               font-size: 11px; letter-spacing: .5px; padding: 5px 12px; text-transform: uppercase; }
        table { border-collapse: collapse; width: 100%; }
        .muted { color: #333; }
        .line { border-bottom: 1px solid #111; height: 15px; }
        .rt { text-align: right; }
        .ct { text-align: center; }
    </style>
</head>
<body>

    {{-- ===== Header bar ===== --}}
    <table>
        <tr>
            <td class="black" style="width:44%; padding:12px 14px; vertical-align:middle;">
                <span style="font-size:22px; font-weight:bold; color:#fff; letter-spacing:-.5px;">{{ strtoupper($brandTop) }}</span>@if($brandBot)<span style="font-size:12px; font-weight:bold; color:{{ $accent }}; letter-spacing:1px;">&nbsp;{{ strtoupper($brandBot) }}</span>@endif
            </td>
            <td class="black" style="padding:12px 14px; vertical-align:middle;">
                <span style="font-size:22px; font-weight:bold; color:#fff; letter-spacing:1px;">ORDER FORM</span>
            </td>
            <td class="black rt" style="width:26%; padding:12px 14px; vertical-align:middle;">
                @php $cToll = \App\Models\Setting::get('toll_free', '(945) 427-9519'); @endphp
                <span style="font-size:8.5px; color:{{ $accent }}; font-weight:bold; letter-spacing:.5px;">
                    {{ strtoupper($cEmail) }}<br>{{ $cPhone }}@if ($cToll)<br>TOLL-FREE {{ $cToll }}@endif
                </span>
            </td>
        </tr>
    </table>

    {{-- ===== Order no / date ===== --}}
    <table style="margin-top:18px;">
        <tr>
            <td style="width:30%; vertical-align:top; padding-top:2px;">
                <span class="tag">ORDER</span>
            </td>
            <td style="vertical-align:top;">
                <table>
                    <tr>
                        <td style="width:90px; font-weight:bold; padding:4px 0;">ORDER NO.</td>
                        <td style="border-bottom:1px solid #111; padding:4px 6px; font-weight:bold;">{{ $po->po_number }}</td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; padding:8px 0 4px;">DATE</td>
                        <td style="border-bottom:1px solid #111; padding:8px 6px 4px;">{{ optional($po->po_date)->format('j F Y') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ===== Customer information ===== --}}
    <div style="margin-top:20px;"><span class="tag">CUSTOMER INFORMATION</span></div>
    <table style="margin-top:12px;">
        @php
            $infoRow = function ($label, $value) {
                return '<tr>'
                    .'<td style="width:120px; font-weight:bold; padding:7px 0; vertical-align:bottom;">'.$label.'</td>'
                    .'<td style="border-bottom:1px solid #111; padding:7px 6px;">'.e($value).'</td>'
                    .'</tr>';
            };
        @endphp
        {!! $infoRow('NAME', $po->distributor_name) !!}
        {!! $infoRow('PHONE NO', $po->distributor_phone) !!}
        {!! $infoRow('EMAIL', $po->distributor_email) !!}
        {!! $infoRow('BILLING ADDRESS', $po->billing_address) !!}
        {!! $infoRow('SHIPPING ADDRESS', $po->shipping_address) !!}
    </table>

    {{-- ===== Order details ===== --}}
    <div style="margin-top:20px;"><span class="tag">ORDER DETAILS</span></div>
    <table style="margin-top:12px; border:1px solid #111;">
        <thead>
            <tr class="black">
                <th style="border:1px solid #111; padding:6px 4px; width:8%;" class="ct">NO.</th>
                <th style="border:1px solid #111; padding:6px 8px; text-align:left;">ITEM DESCRIPTION</th>
                <th style="border:1px solid #111; padding:6px 4px; width:9%;" class="ct">QTY</th>
                <th style="border:1px solid #111; padding:6px 4px; width:14%;" class="ct">PRICE</th>
                <th style="border:1px solid #111; padding:6px 4px; width:14%;" class="ct">DISCOUNT</th>
                <th style="border:1px solid #111; padding:6px 4px; width:16%;" class="ct">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @for ($i = 0; $i < $rows; $i++)
                @php $it = $items[$i] ?? null; @endphp
                <tr>
                    <td style="border:1px solid #111; padding:6px 4px; height:22px;" class="ct">{{ $it ? $i + 1 : '' }}</td>
                    <td style="border:1px solid #111; padding:6px 8px;">{{ $it['description'] ?? '' }}</td>
                    <td style="border:1px solid #111; padding:6px 4px;" class="ct">{{ $it ? rtrim(rtrim(number_format($it['qty'], 2), '0'), '.') : '' }}</td>
                    <td style="border:1px solid #111; padding:6px 6px;" class="rt">{{ $it ? '$'.number_format($it['price'], 2) : '' }}</td>
                    <td style="border:1px solid #111; padding:6px 6px;" class="rt">{{ $it && $it['discount'] ? '$'.number_format($it['discount'], 2) : '' }}</td>
                    <td style="border:1px solid #111; padding:6px 6px;" class="rt">{{ $it ? '$'.number_format($it['total'], 2) : '' }}</td>
                </tr>
            @endfor
        </tbody>
    </table>

    {{-- ===== Totals ===== --}}
    <table style="margin-top:10px;">
        <tr>
            <td style="width:60%;"></td>
            <td>
                <table style="border:1px solid #111;">
                    <tr>
                        <td style="border:1px solid #111; padding:6px 10px; font-weight:bold; width:55%;">SUBTOTAL</td>
                        <td style="border:1px solid #111; padding:6px 10px;" class="rt">${{ number_format($po->subtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid #111; padding:6px 10px; font-weight:bold;">TAX</td>
                        <td style="border:1px solid #111; padding:6px 10px;" class="rt">${{ number_format($po->tax, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="border:1px solid #111; padding:6px 10px; font-weight:bold;">SHIPPING</td>
                        <td style="border:1px solid #111; padding:6px 10px;" class="rt">${{ number_format($po->shipping, 2) }}</td>
                    </tr>
                    <tr class="black">
                        <td style="border:1px solid #111; padding:7px 10px; font-weight:bold;">TOTAL</td>
                        <td style="border:1px solid #111; padding:7px 10px; font-weight:bold;" class="rt">${{ number_format($po->total, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ===== Notes ===== --}}
    <div style="margin-top:16px;"><span class="tag">NOTES</span></div>
    <div style="margin-top:10px; border:1px solid #111; min-height:70px; padding:8px 10px; line-height:1.6;">{{ $po->notes }}</div>

</body>
</html>
