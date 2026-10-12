{{-- $order (with items) --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:10px;border-collapse:separate;overflow:hidden;margin-top:6px;">
    <tr style="background:#f8fafc;">
        <td style="padding:10px 14px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">Item</td>
        <td style="padding:10px 14px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;text-align:center;">Qty</td>
        <td style="padding:10px 14px;font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;text-align:right;">Total</td>
    </tr>
    @foreach ($order->items as $item)
        <tr>
            <td style="padding:10px 14px;font-size:14px;color:#0f172a;border-top:1px solid #eef2f6;">{{ $item->name }}<br><span style="font-size:12px;color:#94a3b8;">${{ number_format($item->price, 2) }} each</span></td>
            <td style="padding:10px 14px;font-size:14px;color:#334155;text-align:center;border-top:1px solid #eef2f6;">{{ $item->quantity }}</td>
            <td style="padding:10px 14px;font-size:14px;font-weight:600;color:#0f172a;text-align:right;border-top:1px solid #eef2f6;">${{ number_format($item->line_total, 2) }}</td>
        </tr>
    @endforeach
    <tr><td colspan="3" style="border-top:2px solid #e2e8f0;padding:0;"></td></tr>
    <tr><td colspan="2" style="padding:6px 14px;font-size:13px;color:#64748b;">Subtotal</td><td style="padding:6px 14px;font-size:13px;color:#334155;text-align:right;">${{ number_format($order->subtotal, 2) }}</td></tr>
    <tr><td colspan="2" style="padding:6px 14px;font-size:13px;color:#64748b;">Shipping</td><td style="padding:6px 14px;font-size:13px;color:#334155;text-align:right;">{{ $order->shipping > 0 ? '$'.number_format($order->shipping, 2) : ($order->shipping_quote_pending ? 'To be quoted' : 'Free') }}</td></tr>
    @if ((float) ($order->discount ?? 0) > 0)
        <tr><td colspan="2" style="padding:6px 14px;font-size:13px;color:#059669;">Discount ({{ (float) ($order->discount_percent ?? 0) > 0 ? rtrim(rtrim(number_format((float) $order->discount_percent, 2), '0'), '.').'%' : ($order->coupon_code ?? 'code') }})</td><td style="padding:6px 14px;font-size:13px;color:#059669;text-align:right;">&minus;${{ number_format((float) $order->discount, 2) }}</td></tr>
    @endif
    <tr><td colspan="2" style="padding:6px 14px;font-size:13px;color:#64748b;">Tax</td><td style="padding:6px 14px;font-size:13px;color:#334155;text-align:right;">${{ number_format($order->tax, 2) }}</td></tr>
    <tr style="background:#f8fafc;"><td colspan="2" style="padding:10px 14px;font-size:15px;font-weight:800;color:#0f172a;">Total</td><td style="padding:10px 14px;font-size:15px;font-weight:800;color:#0f172a;text-align:right;">${{ number_format($order->total, 2) }}</td></tr>
</table>
