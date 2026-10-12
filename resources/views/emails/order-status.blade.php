@extends('emails.layout')

@section('content')
    @php
        $firstName = \Illuminate\Support\Str::of($order->customer->name)->before(' ') ?: $order->customer->name;
        $map = [
            'processing' => ['Order being processed', 'Good news — we’re preparing your order for shipment.', '#3b82f6'],
            'shipped'    => ['Your order has shipped', 'Your order is on its way! It should arrive soon.', '#6366f1'],
            'completed'  => ['Order complete', 'Your order is complete. Thank you for shopping with us!', '#16a34a'],
            'cancelled'  => ['Order cancelled', 'Your order has been cancelled. If this wasn’t expected, please contact us.', '#e11d48'],
            'pending'    => ['Order pending', 'Your order is pending and will be processed shortly.', '#f59e0b'],
        ];
        [$heading, $line, $color] = $map[$order->status] ?? ['Order updated', 'Your order status has been updated.', '#0f172a'];
    @endphp

    <div style="display:inline-block;background:{{ $color }}1f;color:{{ $color }};font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:4px 10px;border-radius:999px;">{{ ucfirst($order->status) }}</div>
    <h1 style="margin:14px 0 6px;font-size:22px;font-weight:800;color:#0f172a;">{{ $heading }}</h1>
    <p style="margin:0 0 4px;font-size:15px;line-height:1.7;color:#334155;">Hi {{ $firstName }}, {{ $line }}</p>
    <p style="margin:0 0 18px;font-size:14px;color:#475569;">Order <strong>{{ $order->order_number }}</strong></p>

    @include('emails._order_summary', ['order' => $order])

    <p style="margin:22px 0 0;font-size:14px;line-height:1.7;color:#334155;">Questions about your order? Just reply to this email and we’ll help.</p>
@endsection
