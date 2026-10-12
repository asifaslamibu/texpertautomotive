@extends('emails.layout')

@section('content')
    <div style="display:inline-block;background:#fee2e2;color:#b91c1c;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:4px 10px;border-radius:999px;">Return request</div>
    <h1 style="margin:14px 0 6px;font-size:22px;font-weight:800;color:#0f172a;">New return request</h1>
    <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#475569;">Review the request, then issue a return authorization and prepaid label if it qualifies.</p>

    @include('emails._details', ['rows' => [
        ['Order number', $data['order_number'] ?? ''],
        ['Product SKU', $data['product_sku'] ?? ''],
        ['Quantity to return', $data['quantity'] ?? ''],
        ['Reason', $data['reason'] ?? ''],
        ['Shipping carrier', $data['carrier'] ?? ''],
        ['Customer', $data['name'] ?? ''],
        ['Email', $data['email'] ?? ''],
        ['Phone', $data['phone'] ?? ''],
        ['Certified new & uninstalled', ! empty($data['certified']) ? 'Yes' : 'No'],
        ['Comments', $data['message'] ?? ''],
    ]])

    @if (! empty($data['images']))
        <p style="margin:18px 0 6px;font-size:13px;font-weight:700;color:#0f172a;">Product images</p>
        @foreach ($data['images'] as $image)
            <a href="{{ $image }}" style="display:block;font-size:13px;color:#b45309;">{{ $image }}</a>
        @endforeach
    @endif

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin-top:22px;">
        <tr><td style="border-radius:8px;background:#f59e0b;">
            <a href="mailto:{{ $data['email'] ?? '' }}" style="display:inline-block;padding:11px 22px;font-size:14px;font-weight:700;color:#0f172a;text-decoration:none;">Reply to customer</a>
        </td></tr>
    </table>
@endsection
