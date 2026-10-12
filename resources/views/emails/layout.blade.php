@php
    $siteName = \App\Models\Setting::get('site_name', 'Texpert Deals');
    $logo     = \App\Models\Setting::get('email_logo') ?: \App\Models\Setting::get('header_logo');
    $addr     = \App\Models\Setting::get('contact_address', '2990 Congressman Ln, Dallas, TX 75220');
    $cEmail   = \App\Models\Setting::get('contact_email', 'sales@texpertdeals.us');
    $cPhone   = \App\Models\Setting::get('contact_phone', '+1-254-358-0017');
    $footerNote = \App\Models\Setting::get('email_footer_note');
    $accent   = \App\Models\Setting::get('color_primary') ?: \App\Support\Theme::DEFAULT_PRIMARY;
    $dark     = \App\Models\Setting::get('color_secondary') ?: \App\Support\Theme::DEFAULT_SECONDARY;
    $year     = date('Y');
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>{{ $siteName }}</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:600px;max-width:100%;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e2e8f0;">

                    {{-- header: uploaded logo, else site name --}}
                    <tr>
                        <td style="background:{{ $dark }};padding:22px 32px;">
                            @if ($logo)
                                <img src="{{ media($logo) }}" alt="{{ $siteName }}" height="40" style="height:40px;max-height:40px;width:auto;display:block;border:0;">
                            @else
                                <span style="font-size:22px;font-weight:800;letter-spacing:-0.5px;color:#ffffff;">
                                    {{ \Illuminate\Support\Str::of($siteName)->before(' ') ?: $siteName }}<span style="color:{{ $accent }};">{{ \Illuminate\Support\Str::of($siteName)->after(' ') ? ' '.\Illuminate\Support\Str::of($siteName)->after(' ') : '' }}</span>
                                </span>
                            @endif
                        </td>
                    </tr>

                    {{-- accent bar --}}
                    <tr><td style="height:4px;background:{{ $accent }};line-height:4px;font-size:0;">&nbsp;</td></tr>

                    {{-- body --}}
                    <tr>
                        <td style="padding:32px;">
                            @yield('content')
                        </td>
                    </tr>

                    {{-- footer --}}
                    <tr>
                        <td style="background:{{ $dark }};padding:24px 32px;color:#94a3b8;font-size:12px;line-height:1.7;">
                            <div style="color:#ffffff;font-weight:700;font-size:14px;margin-bottom:6px;">{{ $siteName }}</div>
                            @if ($footerNote)<div style="margin-bottom:8px;color:#cbd5e1;">{{ $footerNote }}</div>@endif
                            {{ $addr }}<br>
                            @php $cToll = \App\Models\Setting::get('toll_free', '(945) 427-9519'); @endphp
                            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $cPhone) }}" style="color:{{ $accent }};text-decoration:none;">{{ $cPhone }}</a>
                            @if ($cToll)&nbsp;·&nbsp;<a href="tel:{{ preg_replace('/[^0-9+]/', '', $cToll) }}" style="color:{{ $accent }};text-decoration:none;">Toll-Free {{ $cToll }}</a>@endif
                            &nbsp;·&nbsp;
                            <a href="mailto:{{ $cEmail }}" style="color:{{ $accent }};text-decoration:none;">{{ $cEmail }}</a>
                            <div style="margin-top:12px;color:#64748b;">&copy; {{ $year }} {{ $siteName }}. All rights reserved.</div>
                        </td>
                    </tr>
                </table>
                <div style="color:#94a3b8;font-size:11px;margin-top:14px;">This message was sent by {{ $siteName }}.</div>
            </td>
        </tr>
    </table>
</body>
</html>
