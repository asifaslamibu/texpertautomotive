@extends('layouts.app')

@section('title', 'Request a Return — Texpert Deals')
@section('meta_description', 'Start a return at Texpert Deals. Complete the return request form and our team will review it and issue your return authorization and prepaid shipping label.')

@section('content')

@include('pages._hero', [
    'eyebrow' => 'Customer care',
    'title' => 'Request a Return',
    'subtitle' => 'Complete the form below to request a return. Our team will review it and email your return authorization.',
])

<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6">

    {{-- what to know before submitting --}}
    <div class="space-y-3 text-sm leading-relaxed text-slate-600">
        <p>The following form must be completed to request a return. For more information regarding returns, please review our <a href="{{ route('returns') }}" class="font-semibold text-amber-600 underline hover:text-amber-700">Return Policy</a>.</p>
        <p>Refunds will only be issued for items in new, unused condition.</p>
        <p>If your item is defective, we require documentation from a licensed HVAC professional confirming proper installation and diagnosis of the defective item.</p>
        <p>If your item was damaged on arrival, do not use this form. Please <a href="{{ route('contact.index') }}" class="font-semibold text-amber-600 underline hover:text-amber-700">file a damage claim here</a>.</p>
    </div>

    <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        @if (session('status'))
            <div class="mb-6 flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                <x-icon name="check" class="mt-0.5 h-5 w-5 shrink-0" /> {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <p class="font-semibold">Please correct the following:</p>
                <ul class="mt-1.5 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        @php
            $field = 'w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100';
            $label = 'mb-1.5 block text-sm font-semibold text-slate-700';
            $req = '<span class="text-rose-500">*</span>';
        @endphp

        <form method="POST" action="{{ route('return-request.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            <div>
                <label class="{{ $label }}">Order Number {!! $req !!}</label>
                <input type="text" name="order_number" value="{{ old('order_number', $prefillOrder) }}" required placeholder="#VS1234"
                       class="{{ $field }} {{ $errors->has('order_number') ? 'border-rose-400' : '' }}">
                @error('order_number')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="{{ $label }}">Name {!! $req !!}</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="John"
                           class="{{ $field }} {{ $errors->has('name') ? 'border-rose-400' : '' }}">
                    @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $label }}">Email {!! $req !!}</label>
                    <input type="email" name="email" value="{{ old('email') }}" required placeholder="example@domain.com"
                           class="{{ $field }} {{ $errors->has('email') ? 'border-rose-400' : '' }}">
                    @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="{{ $label }}">Phone {!! $req !!}</label>
                    <input type="tel" name="phone" value="{{ old('phone') }}" required placeholder="(+1)"
                           class="{{ $field }} {{ $errors->has('phone') ? 'border-rose-400' : '' }}">
                    @error('phone')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="{{ $label }}">Product SKU # {!! $req !!}</label>
                    <input type="text" name="product_sku" value="{{ old('product_sku', $prefillSku) }}" required placeholder="4900-20"
                           class="{{ $field }} {{ $errors->has('product_sku') ? 'border-rose-400' : '' }}">
                    @error('product_sku')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="sm:w-1/2 sm:pr-2">
                <label class="{{ $label }}">Quantity To Return {!! $req !!}</label>
                <input type="number" name="quantity" value="{{ old('quantity', 1) }}" min="1" step="1" required
                       class="{{ $field }} {{ $errors->has('quantity') ? 'border-rose-400' : '' }}">
                @error('quantity')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $label }}">Reason For Return {!! $req !!}</label>
                <select name="reason" required class="{{ $field }} bg-white {{ $errors->has('reason') ? 'border-rose-400' : '' }}">
                    @foreach (\App\Http\Controllers\ReturnRequestController::REASONS as $reason)
                        <option value="{{ $reason }}" @selected(old('reason') === $reason)>{{ $reason }}</option>
                    @endforeach
                </select>
                @error('reason')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $label }}">Shipping Carrier {!! $req !!}</label>
                <select name="carrier" required class="{{ $field }} bg-white {{ $errors->has('carrier') ? 'border-rose-400' : '' }}">
                    @foreach (\App\Http\Controllers\ReturnRequestController::CARRIERS as $carrier)
                        <option value="{{ $carrier }}" @selected(old('carrier') === $carrier)>{{ $carrier }}</option>
                    @endforeach
                </select>
                @error('carrier')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $label }}">Upload Product Images</label>
                <input type="file" name="images[]" accept="image/*" multiple
                       class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-900 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-slate-800">
                <p class="mt-1 text-xs text-slate-500">Up to 5 images, 4 MB each. Photos speed up damage and defect claims.</p>
                @error('images.*')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="{{ $label }}">Comments</label>
                <textarea name="message" rows="4" placeholder="Please provide any additional information regarding the return"
                          class="{{ $field }}">{{ old('message') }}</textarea>
                @error('message')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <label class="flex items-start gap-2.5 text-sm text-slate-700">
                <input type="checkbox" name="certified" value="1" required @checked(old('certified'))
                       class="mt-0.5 rounded border-slate-300 text-amber-600 focus:ring-amber-400">
                <span>I certify the product being returned is new, unused, and has never been installed. {!! $req !!}</span>
            </label>
            @error('certified')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror

            @include('partials.recaptcha')

            <div class="pt-2 text-center">
                <button type="submit" class="rounded-lg bg-amber-400 px-8 py-3 text-sm font-black uppercase tracking-wide text-slate-900 shadow-sm transition hover:bg-amber-500">
                    Submit Return Request
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
