@extends('layouts.app')

@section('title', 'Wholesale & Bulk Orders — Texpert Deals')
@section('meta_description', 'Request wholesale & contractor pricing — volume discounts and Net-30 terms for pros. Submit a bulk order request and get a custom quote.')

@section('content')

@include('pages._hero', [
    'eyebrow' => 'Bulk pricing',
    'title' => 'Wholesale & Contractor Deals',
    'subtitle' => 'Volume discounts and Net-30 terms for pros. Tell us what you need and our team will send you a custom quote.',
])

<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
    <div class="grid gap-10 lg:grid-cols-[1fr_1.3fr]">

        {{-- benefits --}}
        <div class="space-y-4">
            @php
                $phone = \App\Models\Setting::get('contact_phone', '+1-254-358-0017');
                $tollFree = \App\Models\Setting::get('toll_free', '(945) 427-9519');
                $email = \App\Models\Setting::get('contact_email', 'sales@texpertdeals.us');
            @endphp

            @foreach ([
                ['box', 'Volume discounts', 'The more you order, the more you save — tiered pricing on bulk quantities.'],
                ['shield', 'Net-30 terms', 'Approved contractors & businesses can order now and pay in 30 days.'],
                ['truck', 'Priority fulfillment', '100,000+ items in stock, shipped fast so your projects stay on schedule.'],
            ] as [$icon, $title, $desc])
                <div class="flex items-start gap-4 rounded-2xl border border-slate-200 bg-white p-5">
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-amber-100 text-amber-600"><x-icon name="{{ $icon }}" class="h-5 w-5" /></span>
                    <div>
                        <p class="text-sm font-bold text-slate-900">{{ $title }}</p>
                        <p class="text-sm text-slate-600">{{ $desc }}</p>
                    </div>
                </div>
            @endforeach

            <div class="rounded-2xl bg-slate-900 p-6 text-white">
                <h3 class="font-bold">Prefer to talk?</h3>
                <p class="mt-1 text-sm text-amber-100">Call our wholesale desk for immediate volume pricing.</p>
                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="inline-block rounded-full bg-amber-400 px-5 py-2 text-sm font-bold text-slate-900 transition hover:bg-amber-300">{{ $phone }}</a>
                    @if ($tollFree)
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $tollFree) }}" class="inline-block rounded-full border border-amber-400/40 px-5 py-2 text-sm font-bold text-amber-100 transition hover:bg-white/5">Toll-Free {{ $tollFree }}</a>
                    @endif
                </div>
            </div>
        </div>

        {{-- request form --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @if (session('status'))
                <div class="mb-6 flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    <x-icon name="check" class="mt-0.5 h-5 w-5 shrink-0" /> {{ session('status') }}
                </div>
            @endif

            <h2 class="text-xl font-black text-slate-900">Request a wholesale quote</h2>
            <p class="mt-1 text-sm text-slate-500">Fields marked <span class="text-rose-500">*</span> are required.</p>

            <form method="POST" action="{{ route('bulk-order.store') }}" class="mt-6 space-y-4">
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Your name <span class="text-rose-500">*</span></label>
                        <input name="name" value="{{ old('name') }}" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
                        @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Company</label>
                        <input name="company" value="{{ old('company') }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Email <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
                        @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Phone</label>
                        <input name="phone" value="{{ old('phone') }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Products / categories you need</label>
                    <textarea name="products" rows="3" placeholder="e.g. Refrigerant, copper tubing, thermostats — with part numbers if you have them"
                              class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">{{ old('products') }}</textarea>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Estimated order size / monthly volume</label>
                    <input name="volume" value="{{ old('volume') }}" placeholder="e.g. $5,000/order or 200 units/month"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
                </div>

                <label class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-700">
                    <input type="checkbox" name="net30" value="1" @checked(old('net30')) class="rounded border-slate-300 text-amber-500"> I'm interested in Net-30 payment terms
                </label>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Additional details</label>
                    <textarea name="message" rows="3" placeholder="Timeline, delivery location, or anything else that helps us quote accurately…"
                              class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">{{ old('message') }}</textarea>
                </div>

                @include('partials.recaptcha')

                <button type="submit" class="w-full rounded-lg bg-slate-900 py-3 text-sm font-black uppercase tracking-wide text-white transition hover:bg-slate-800 sm:w-auto sm:px-10">
                    Request Quote
                </button>
            </form>
        </div>
    </div>
</div>

@endsection
