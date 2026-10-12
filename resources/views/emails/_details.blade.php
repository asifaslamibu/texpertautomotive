{{-- $rows = [[label, value], ...] ; skips empty values --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;margin-top:6px;">
    @foreach ($rows as $i => [$label, $value])
        @if (trim((string) $value) !== '')
            <tr style="background:{{ $i % 2 === 0 ? '#f8fafc' : '#ffffff' }};">
                <td style="padding:11px 14px;font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;width:38%;vertical-align:top;border-bottom:1px solid #eef2f6;">{{ $label }}</td>
                <td style="padding:11px 14px;font-size:14px;color:#0f172a;border-bottom:1px solid #eef2f6;white-space:pre-line;">{{ $value }}</td>
            </tr>
        @endif
    @endforeach
</table>
