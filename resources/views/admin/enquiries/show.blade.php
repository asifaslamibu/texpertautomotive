@extends('admin.layouts.app')

@section('title', 'Enquiry')
@section('heading', 'Enquiry')
@section('subheading', $enquiry->type_label.' · '.$enquiry->created_at?->format('M d, Y g:i A'))

@section('content')

<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <a href="{{ route('admin.enquiries.index') }}" class="text-sm font-semibold text-slate-400 hover:text-white">← Back to enquiries</a>
    <div class="flex items-center gap-2">
        <form method="POST" action="{{ route('admin.enquiries.toggle', $enquiry) }}">
            @csrf @method('PATCH')
            <button class="rounded-lg border border-white/10 px-3 py-1.5 text-sm font-semibold text-slate-200 transition hover:bg-white/10">Mark {{ $enquiry->is_read ? 'unread' : 'read' }}</button>
        </form>
        <form method="POST" action="{{ route('admin.enquiries.destroy', $enquiry) }}" onsubmit="return confirm('Delete this enquiry?')">
            @csrf @method('DELETE')
            <button class="rounded-lg border border-white/10 px-3 py-1.5 text-sm font-semibold text-slate-300 transition hover:border-rose-400/40 hover:bg-rose-500/10 hover:text-rose-300">Delete</button>
        </form>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    {{-- message --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
            <div class="mb-4 flex items-center gap-2">
                <span class="rounded-full bg-{{ $enquiry->type_color }}-500/15 px-2.5 py-1 text-xs font-semibold text-{{ $enquiry->type_color }}-300">{{ $enquiry->type_label }}</span>
                @if ($enquiry->subject)<span class="text-sm font-bold text-white">{{ $enquiry->subject }}</span>@endif
            </div>
            <p class="whitespace-pre-line text-sm leading-relaxed text-slate-200">{{ $enquiry->message ?: '— no message —' }}</p>

            @if (!empty($enquiry->meta))
                <div class="mt-5 border-t border-white/10 pt-4">
                    <p class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-400">Details</p>
                    <table class="w-full text-sm">
                        @foreach ($enquiry->meta as $k => $v)
                            @if (!is_null($v) && $v !== '')
                                <tr>
                                    <td class="py-1.5 pr-4 align-top text-slate-400 capitalize">{{ str_replace('_', ' ', $k) }}</td>
                                    <td class="py-1.5 font-medium text-slate-100">{{ is_bool($v) ? ($v ? 'Yes' : 'No') : $v }}</td>
                                </tr>
                            @endif
                        @endforeach
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- sender --}}
    <div class="space-y-6">
        <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-5 shadow-sm">
            <h2 class="mb-3 font-bold text-white">From</h2>
            <div class="space-y-1 text-sm">
                <p class="font-semibold text-slate-100">{{ $enquiry->name }}</p>
                <p><a href="mailto:{{ $enquiry->email }}" class="text-slate-300 hover:text-white">{{ $enquiry->email }}</a></p>
                @if ($enquiry->phone)<p><a href="tel:{{ $enquiry->phone }}" class="text-slate-300 hover:text-white">{{ $enquiry->phone }}</a></p>@endif
            </div>
            <a href="mailto:{{ $enquiry->email }}?subject=Re: {{ urlencode($enquiry->subject ?: 'Your enquiry') }}"
               class="mt-4 inline-block w-full rounded-lg bg-white py-2.5 text-center text-sm font-bold text-slate-900 transition hover:bg-slate-200">Reply by email</a>
        </div>
    </div>
</div>

@endsection
