@extends('layouts.app')

@section('title', 'Contact Us — Texpert Deals')
@section('meta_description', 'Get in touch with the Texpert Deals team for product help, wholesale quotes, order support and more.')

@section('content')

@include('pages._hero', [
    'eyebrow' => 'We\'re here to help',
    'title' => 'Contact Us',
    'subtitle' => 'We\'re here to assist you with all your queries and provide the best solutions for your needs. Whether you have questions about our products, need support, or simply want to connect, don\'t hesitate to reach out — fill out the form below and our team will respond promptly.',
])

<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
    <div class="grid gap-10 lg:grid-cols-[1fr_1.3fr]">

        {{-- ===== contact info ===== --}}
        <div class="space-y-4">
            @php
                $phone = \App\Models\Setting::get('contact_phone', '+1-254-358-0017');
                $tollFree = \App\Models\Setting::get('toll_free', '(945) 427-9519');
                $email = \App\Models\Setting::get('contact_email', 'sales@texpertdeals.us');
                $address = \App\Models\Setting::get('contact_address', '2990 Congressman Ln, Dallas, TX 75220');
            @endphp

            <div class="flex items-start gap-4 rounded-2xl border border-slate-200 bg-white p-5">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-amber-100 text-amber-600"><x-icon name="phone" class="h-5 w-5" /></span>
                <div>
                    <p class="text-sm font-bold text-slate-900">Call us</p>
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="text-sm font-semibold text-amber-600 hover:text-amber-700">{{ $phone }}</a>
                    @if ($tollFree)
                        <p class="mt-1 text-sm text-slate-700">Toll-Free: <a href="tel:{{ preg_replace('/[^0-9+]/', '', $tollFree) }}" class="font-semibold text-amber-600 hover:text-amber-700">{{ $tollFree }}</a></p>
                    @endif
                    <p class="mt-0.5 text-xs text-slate-500">Mon–Fri, 9am–6pm EST</p>
                </div>
            </div>

            <div class="flex items-start gap-4 rounded-2xl border border-slate-200 bg-white p-5">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-amber-100 text-amber-600"><x-icon name="chat" class="h-5 w-5" /></span>
                <div>
                    <p class="text-sm font-bold text-slate-900">Email us</p>
                    <a href="mailto:{{ $email }}" class="text-sm font-semibold text-amber-600 hover:text-amber-700">{{ $email }}</a>
                    <p class="mt-0.5 text-xs text-slate-500">We reply within 1 business day</p>
                </div>
            </div>

            <div class="flex items-start gap-4 rounded-2xl border border-slate-200 bg-white p-5">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-amber-100 text-amber-600"><x-icon name="box" class="h-5 w-5" /></span>
                <div>
                    <p class="text-sm font-bold text-slate-900">Visit / ship to</p>
                    <p class="text-sm text-slate-600">{{ $address }}</p>
                </div>
            </div>

            <div class="rounded-2xl bg-slate-900 p-6 text-white">
                <h3 class="font-bold">Need a bulk or contractor quote?</h3>
                <p class="mt-1 text-sm text-amber-100">Call us directly for volume pricing on equipment and parts.</p>
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="mt-4 inline-block rounded-full bg-amber-400 px-5 py-2 text-sm font-bold text-slate-900 transition hover:bg-amber-300">{{ $phone }}</a>
            </div>
        </div>

        {{-- ===== contact form ===== --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="text-xl font-black text-slate-900">Send us a message</h2>
            <p class="mt-1 text-sm text-slate-500">Fill out the form and we'll be in touch shortly.</p>

            <form method="POST" action="{{ route('contact.store') }}" class="mt-6 space-y-4">
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Name <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" value="{{ old('name') }}" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
                        @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Email <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
                        @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Phone</label>
                        <input type="text" name="phone" value="{{ old('phone') }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Subject</label>
                        <input type="text" name="subject" value="{{ old('subject', request('subject')) }}" placeholder="Order, product question…"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Message <span class="text-rose-500">*</span></label>
                    <textarea name="message" rows="6" required
                              class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">{{ old('message') }}</textarea>
                    @error('message')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                @include('partials.recaptcha')

                <button type="submit" class="w-full rounded-lg bg-slate-900 py-3 text-sm font-black uppercase tracking-wide text-white transition hover:bg-slate-800 sm:w-auto sm:px-10">
                    Send Message
                </button>
            </form>
        </div>
    </div>
</div>

@endsection
