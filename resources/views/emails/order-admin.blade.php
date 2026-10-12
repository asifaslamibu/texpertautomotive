@extends('emails.layout')

@section('content')
    <div style="display:inline-block;background:#fef3c7;color:#b45309;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:4px 10px;border-radius:999px;">New order</div>
    <h1 style="margin:14px 0 6px;font-size:22px;font-weight:800;color:#0f172a;">New order {{ $order->order_number }}</h1>
    <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#475569;">
        {{ $order->payment_method }}
        @if ($order->payment_status === 'paid')· <span style="color:#15803d;font-weight:700;">Paid</span>@else · <span style="color:#b45309;font-weight:700;">Unpaid</span>@endif
        · Placed {{ $order->placed_at?->format('M d, Y g:i A') }}
    </p>

    @include('emails._order_summary', ['order' => $order])

    <p style="margin:20px 0 4px;font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">Customer</p>
    <p style="margin:0;font-size:14px;line-height:1.6;color:#334155;">
        {{ $order->customer->name }}<br>
        <a href="mailto:{{ $order->customer->email }}" style="color:#b45309;text-decoration:none;">{{ $order->customer->email }}</a>
        @if ($order->customer->phone)· {{ $order->customer->phone }}@endif<br>
        {{ $order->customer->address }}, {{ $order->customer->city }}, {{ $order->customer->state }} {{ $order->customer->zip }}
    </p>

    @if ($order->notes)
        <p style="margin:16px 0 4px;font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">Order notes</p>
        <p style="margin:0;font-size:14px;color:#334155;white-space:pre-line;">{{ $order->notes }}</p>
    @endif
@endsection
