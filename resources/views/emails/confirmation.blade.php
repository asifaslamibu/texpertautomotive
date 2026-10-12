@extends('emails.layout')

@section('content')
    @php
        $firstName = \Illuminate\Support\Str::of($name)->before(' ') ?: $name;
        $phone = \App\Models\Setting::get('contact_phone', '+1-254-358-0017');
        $tollFree = \App\Models\Setting::get('toll_free', '(945) 427-9519');
    @endphp

    <h1 style="margin:0 0 10px;font-size:22px;font-weight:800;color:#0f172a;">Thanks, {{ $firstName }}! 👋</h1>
    <p style="margin:0 0 14px;font-size:15px;line-height:1.7;color:#334155;">{!! nl2br(e($bodyText)) !!}</p>

    @if (! empty($summary))
        <p style="margin:18px 0 4px;font-size:13px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;">Your submission</p>
        @include('emails._details', ['rows' => $summary])
    @endif

    <p style="margin:22px 0 0;font-size:14px;line-height:1.7;color:#334155;">
        Need something urgent? Call us at
        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" style="color:#b45309;text-decoration:none;font-weight:700;">{{ $phone }}</a>@if ($tollFree) or toll-free <a href="tel:{{ preg_replace('/[^0-9+]/', '', $tollFree) }}" style="color:#b45309;text-decoration:none;font-weight:700;">{{ $tollFree }}</a>@endif.
    </p>
    <p style="margin:14px 0 0;font-size:14px;color:#334155;">{{ $signoff }}</p>
@endsection
