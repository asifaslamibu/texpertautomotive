@extends('emails.layout')

@section('content')
    <div style="display:inline-block;background:#fef3c7;color:#b45309;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;padding:4px 10px;border-radius:999px;">New enquiry</div>
    <h1 style="margin:14px 0 6px;font-size:22px;font-weight:800;color:#0f172a;">New contact message</h1>
    <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#475569;">Someone reached out through the website contact form. Details below.</p>

    @include('emails._details', ['rows' => [
        ['Name', $data['name'] ?? ''],
        ['Email', $data['email'] ?? ''],
        ['Phone', $data['phone'] ?? ''],
        ['Subject', $data['subject'] ?? ''],
        ['Message', $data['message'] ?? ''],
    ]])

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin-top:22px;">
        <tr><td style="border-radius:8px;background:#0f172a;">
            <a href="mailto:{{ $data['email'] ?? '' }}" style="display:inline-block;padding:11px 22px;font-size:14px;font-weight:700;color:#ffffff;text-decoration:none;">Reply to {{ \Illuminate\Support\Str::of($data['name'] ?? 'sender')->before(' ') }}</a>
        </td></tr>
    </table>
@endsection
