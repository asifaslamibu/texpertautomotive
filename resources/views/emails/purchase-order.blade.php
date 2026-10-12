@extends('emails.layout')

@section('content')
    @php
        $accent = \App\Models\Setting::get('color_primary') ?: \App\Support\Theme::DEFAULT_PRIMARY;
    @endphp

    <div style="display:inline-block;background:{{ $accent }}1f;color:{{ $accent }};font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:4px 10px;border-radius:999px;">Purchase Order</div>
    <h1 style="margin:14px 0 6px;font-size:22px;font-weight:800;color:#0f172a;">Purchase Order {{ $po->po_number }}</h1>
    <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#334155;">
        @php $greetName = ($recipientName ?? null) ? \Illuminate\Support\Str::of($recipientName)->before(' ') : null; @endphp
        @if ($greetName)Hi {{ $greetName }}, @endif please find the order form attached as a PDF — <strong>ORDER NO. {{ $po->po_number }}</strong>, dated {{ optional($po->po_date)->format('j F Y') }}.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:10px;margin:0 0 18px;">
        <tr>
            <td style="padding:12px 16px;font-size:13px;color:#64748b;border-bottom:1px solid #f1f5f9;">ORDER NO.</td>
            <td style="padding:12px 16px;font-size:13px;font-weight:700;color:#0f172a;text-align:right;border-bottom:1px solid #f1f5f9;">{{ $po->po_number }}</td>
        </tr>
        <tr>
            <td style="padding:12px 16px;font-size:13px;color:#64748b;border-bottom:1px solid #f1f5f9;">DATE</td>
            <td style="padding:12px 16px;font-size:13px;font-weight:700;color:#0f172a;text-align:right;border-bottom:1px solid #f1f5f9;">{{ optional($po->po_date)->format('j F Y') }}</td>
        </tr>
        <tr>
            <td style="padding:12px 16px;font-size:13px;color:#64748b;">Order Total</td>
            <td style="padding:12px 16px;font-size:15px;font-weight:800;color:#0f172a;text-align:right;">${{ number_format($po->total, 2) }}</td>
        </tr>
    </table>

    <p style="margin:0;font-size:14px;line-height:1.7;color:#334155;">Full item details are in the attached PDF. Questions? Just reply to this email.</p>
@endsection
