@extends('admin.layouts.app')

@section('title', 'Emails')
@section('heading', 'Email Content')
@section('subheading', 'Edit the emails customers receive from every form')

@section('content')

@if (session('status'))
    <div class="mx-auto mb-6 max-w-3xl rounded-xl border border-emerald-400/30 bg-emerald-500/15 px-4 py-3 text-sm font-semibold text-emerald-300">{{ session('status') }}</div>
@endif
@if ($errors->any())
    <div class="mx-auto mb-6 max-w-3xl rounded-xl border border-rose-400/30 bg-rose-500/15 p-4 text-sm text-rose-300">
        <ul class="list-disc space-y-1 pl-5">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.emails.update') }}" enctype="multipart/form-data" class="mx-auto max-w-3xl space-y-6">
    @csrf @method('PUT')

    {{-- branding --}}
    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <h2 class="mb-1 font-bold text-white">Branding</h2>
        <p class="mb-4 text-sm text-slate-400">Header/footer colours follow your <a href="{{ route('admin.settings.edit') }}" class="text-slate-200 underline">Theme colors</a>.</p>

        <div data-image-field>
            <label class="mb-1.5 block text-sm font-medium text-slate-200">Email logo</label>
            <div class="flex items-center gap-4">
                <span class="grid h-16 w-28 shrink-0 place-items-center overflow-hidden rounded-xl border border-white/10 bg-slate-950">
                    <img data-image-preview src="{{ $settings['email_logo'] ? media($settings['email_logo']) : '' }}" class="{{ $settings['email_logo'] ? '' : 'hidden' }} max-h-full max-w-full object-contain" alt="">
                </span>
                <input type="file" name="email_logo_file" accept="image/*" data-image-input
                       class="block w-full text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-200 hover:file:bg-white/20">
            </div>
            <input name="email_logo" value="{{ old('email_logo', $settings['email_logo']) }}" placeholder="…or paste a logo URL (empty = use header logo)" class="mt-3 w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Footer note</label>
                <input name="email_footer_note" value="{{ old('email_footer_note', $settings['email_footer_note']) }}" placeholder="e.g. Your trusted HVAC/R supply partner." class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Sign-off</label>
                <input name="email_signoff" value="{{ old('email_signoff', $settings['email_signoff']) }}" placeholder="— The {site} Team" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>
        </div>
        <p class="mt-3 rounded-lg bg-white/5 px-3 py-2 text-xs text-slate-400">Placeholders you can use: <code class="text-slate-200">{name}</code> (customer), <code class="text-slate-200">{type}</code> (message / part request / wholesale request), <code class="text-slate-200">{site}</code> (store name).</p>
    </div>

    {{-- per-form confirmation emails --}}
    @php
        $forms = [
            ['contact', 'Contact form', 'The reply customers get after sending a contact message.'],
            ['part', 'Part request form', 'Sent after a customer requests a part.'],
            ['wholesale', 'Wholesale / bulk form', 'Sent after a wholesale quote request.'],
        ];
    @endphp
    @foreach ($forms as [$key, $title, $desc])
        <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
            <h2 class="mb-1 font-bold text-white">{{ $title }}</h2>
            <p class="mb-4 text-sm text-slate-400">{{ $desc }} Leave empty to use the default.</p>
            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Subject</label>
                    <input name="email_{{ $key }}_subject" value="{{ old('email_'.$key.'_subject', $settings['email_'.$key.'_subject']) }}" placeholder="We received your {type} — {site}"
                           class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Message</label>
                    <textarea name="email_{{ $key }}_body" rows="3" placeholder="We’ve received your {type} and our team is on it. You’ll hear back shortly."
                              class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">{{ old('email_'.$key.'_body', $settings['email_'.$key.'_body']) }}</textarea>
                </div>
            </div>
        </div>
    @endforeach

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('admin.dashboard') }}" class="rounded-lg border border-white/10 px-5 py-2.5 text-sm font-semibold text-slate-300 hover:bg-white/5">Cancel</a>
        <button class="rounded-lg bg-white px-6 py-2.5 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-200">Save Emails</button>
    </div>
</form>

@endsection
