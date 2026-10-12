@extends('emails.layout')

@section('content')
    <div style="display:inline-block;background:#fef3c7;color:#b45309;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:4px 10px;border-radius:999px;">Part request</div>
    <h1 style="margin:14px 0 6px;font-size:22px;font-weight:800;color:#0f172a;">New part request</h1>
    <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#475569;">A customer is looking for a part. Source it and reply with pricing &amp; availability.</p>

    @include('emails._details', ['rows' => [
        ['Part name', $data['part_name'] ?? ''],
        ['Part # / SKU', $data['part_sku'] ?? ''],
        ['Brand', $data['brand'] ?? ''],
        ['Quantity', $data['quantity'] ?? ''],
        ['Customer', $data['name'] ?? ''],
        ['Email', $data['email'] ?? ''],
        ['Phone', $data['phone'] ?? ''],
        ['Details', $data['message'] ?? ''],
    ]])

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin-top:22px;">
        <tr><td style="border-radius:8px;background:#f59e0b;">
            <a href="mailto:{{ $data['email'] ?? '' }}" style="display:inline-block;padding:11px 22px;font-size:14px;font-weight:700;color:#0f172a;text-decoration:none;">Reply with a quote</a>
        </td></tr>
    </table>
@endsection
