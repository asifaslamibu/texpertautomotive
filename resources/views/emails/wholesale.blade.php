@extends('emails.layout')

@section('content')
    <div style="display:inline-block;background:#fef3c7;color:#b45309;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:4px 10px;border-radius:999px;">Wholesale request</div>
    <h1 style="margin:14px 0 6px;font-size:22px;font-weight:800;color:#0f172a;">New wholesale / bulk quote request</h1>
    <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#475569;">A pro is requesting volume pricing. Prepare a custom quote and follow up.</p>

    @include('emails._details', ['rows' => [
        ['Name', $data['name'] ?? ''],
        ['Company', $data['company'] ?? ''],
        ['Email', $data['email'] ?? ''],
        ['Phone', $data['phone'] ?? ''],
        ['Products / categories', $data['products'] ?? ''],
        ['Estimated volume', $data['volume'] ?? ''],
        ['Wants Net-30', !empty($data['net30']) ? 'Yes' : 'No'],
        ['Details', $data['message'] ?? ''],
    ]])

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin-top:22px;">
        <tr><td style="border-radius:8px;background:#f59e0b;">
            <a href="mailto:{{ $data['email'] ?? '' }}" style="display:inline-block;padding:11px 22px;font-size:14px;font-weight:700;color:#0f172a;text-decoration:none;">Send a quote</a>
        </td></tr>
    </table>
@endsection
