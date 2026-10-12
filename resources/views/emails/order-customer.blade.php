@extends('emails.layout')

@section('content')
    @php $firstName = \Illuminate\Support\Str::of($order->customer->name)->before(' ') ?: $order->customer->name; @endphp
    <div style="display:inline-block;background:#dcfce7;color:#15803d;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:4px 10px;border-radius:999px;">Order confirmed</div>
    <h1 style="margin:14px 0 6px;font-size:22px;font-weight:800;color:#0f172a;">Thanks for your order, {{ $firstName }}!</h1>
    <p style="margin:0 0 4px;font-size:15px;line-height:1.7;color:#334155;">Your order <strong>{{ $order->order_number }}</strong> has been received and is now <strong style="text-transform:capitalize;">{{ $order->status }}</strong>.</p>
    <p style="margin:0 0 18px;font-size:13px;color:#94a3b8;">Placed {{ $order->placed_at?->format('M d, Y g:i A') }}
        @if ($order->payment_status === 'paid')· <span style="color:#15803d;font-weight:700;">Paid</span>@endif
    </p>

    @include('emails._order_summary', ['order' => $order])

    <p style="margin:20px 0 4px;font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">Shipping to</p>
    <p style="margin:0;font-size:14px;line-height:1.6;color:#334155;">
        {{ $order->customer->name }}<br>
        {{ $order->customer->address }}<br>
        {{ $order->customer->city }}, {{ $order->customer->state }} {{ $order->customer->zip }}
    </p>

    <p style="margin:22px 0 0;font-size:14px;line-height:1.7;color:#334155;">We’ll email you again when your order ships. Questions? Just reply to this email.</p>
@endsection
