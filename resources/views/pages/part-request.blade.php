@extends('layouts.app')

@section('title', 'Part Request Form — Texpert Deals')
@section('meta_description', "Can't find the part you need? Submit a part request and our team will source it and get back to you with pricing and availability.")

@section('content')

@include('pages._hero', [
    'eyebrow' => 'Source any part',
    'title' => 'Part Request Form',
    'subtitle' => "Tell us what you're looking for — part name, number or a short description — and our team will track it down and reply with pricing and availability.",
])

<div class="mx-auto max-w-7xl px-4 py-12 sm:px-6">
    <div class="grid gap-10 lg:grid-cols-[1fr_1.3fr]">

        {{-- ===== help info ===== --}}
        <div class="space-y-4">
            @php
                $phone = \App\Models\Setting::get('contact_phone', '+1-254-358-0017');
                $tollFree = \App\Models\Setting::get('toll_free', '(945) 427-9519');
                $email = \App\Models\Setting::get('contact_email', 'sales@texpertdeals.us');
            @endphp

            <div class="flex items-start gap-4 rounded-2xl border border-slate-200 bg-white p-5">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-amber-100 text-amber-600"><x-icon name="search" class="h-5 w-5" /></span>
                <div>
                    <p class="text-sm font-bold text-slate-900">Hard-to-find parts</p>
                    <p class="text-sm text-slate-600">Access to 1,000,000+ parts across top brands — even if it's not listed.</p>
                </div>
            </div>

            <div class="flex items-start gap-4 rounded-2xl border border-slate-200 bg-white p-5">
                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-amber-100 text-amber-600"><x-icon name="phone" class="h-5 w-5" /></span>
                <div>
                    <p class="text-sm font-bold text-slate-900">Prefer to call?</p>
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="text-sm font-semibold text-amber-600 hover:text-amber-700">{{ $phone }}</a>
                    @if ($tollFree)
                        <p class="text-sm text-slate-700">Toll-Free: <a href="tel:{{ preg_replace('/[^0-9+]/', '', $tollFree) }}" class="font-semibold text-amber-600 hover:text-amber-700">{{ $tollFree }}</a></p>
                    @endif
                    <p class="mt-0.5 text-xs text-slate-500">Mon–Fri, 9am–6pm EST</p>
                </div>
            </div>

            <div class="rounded-2xl bg-slate-900 p-6 text-white">
                <h3 class="font-bold">Bulk / contractor orders</h3>
                <p class="mt-1 text-sm text-amber-100">Need quantity pricing? Mention the quantity below and we'll quote volume rates.</p>
                <a href="mailto:{{ $email }}" class="mt-4 inline-block rounded-full bg-amber-400 px-5 py-2 text-sm font-bold text-slate-900 transition hover:bg-amber-300">{{ $email }}</a>
            </div>
        </div>

        {{-- ===== request form ===== --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            @if (session('status'))
                <div class="mb-6 flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    <x-icon name="check" class="mt-0.5 h-5 w-5 shrink-0" /> {{ session('status') }}
                </div>
            @endif

            <h2 class="text-xl font-black text-slate-900">Request a part</h2>
            <p class="mt-1 text-sm text-slate-500">Fields marked <span class="text-rose-500">*</span> are required.</p>

            <form method="POST" action="{{ route('part-request.store') }}" class="mt-6 space-y-4">
                @csrf

                {{-- part details --}}
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Part name / description <span class="text-rose-500">*</span></label>
                    <input type="text" name="part_name" value="{{ old('part_name', $prefillProduct) }}" required placeholder="e.g. Zebra ZT411 Printhead 203dpi"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
                    @error('part_name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Part # / SKU</label>
                        <input type="text" name="part_sku" value="{{ old('part_sku', $prefillSku) }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Brand</label>
                        <input type="text" name="brand" value="{{ old('brand') }}"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Quantity</label>
                        <input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1"
                               class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
                    </div>
                </div>

                {{-- contact details --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">Your name <span class="text-rose-500">*</span></label>
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

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone') }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Additional details</label>
                    <textarea name="message" rows="5" placeholder="Equipment model, specs, deadline, or anything else that helps us find the right part…"
                              class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">{{ old('message') }}</textarea>
                    @error('message')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                @include('partials.recaptcha')

                <button type="submit" class="w-full rounded-lg bg-slate-900 py-3 text-sm font-black uppercase tracking-wide text-white transition hover:bg-slate-800 sm:w-auto sm:px-10">
                    Submit Request
                </button>
            </form>
        </div>
    </div>
</div>

@endsection
