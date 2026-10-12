@extends('admin.layouts.app')

@section('title', $coupon->exists ? 'Edit Discount Code' : 'New Discount Code')
@section('heading', $coupon->exists ? 'Edit Discount Code' : 'New Discount Code')

@section('content')

@php $fld = 'w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30'; @endphp

<form method="POST" action="{{ $coupon->exists ? route('admin.coupons.update', $coupon) : route('admin.coupons.store') }}" class="mx-auto max-w-2xl">
    @csrf
    @if ($coupon->exists) @method('PUT') @endif

    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-rose-400/30 bg-rose-500/15 p-4">
            <p class="text-sm font-bold text-rose-300">Please fix the following:</p>
            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-300">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="space-y-5 rounded-2xl border border-white/10 bg-slate-900/70 p-6 shadow-sm backdrop-blur">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-200">Code *</label>
            <input name="code" value="{{ old('code', $coupon->code) }}" required maxlength="40" placeholder="WELCOME5"
                   class="{{ $fld }} uppercase tracking-wide">
            <p class="mt-1 text-xs text-slate-400">This is what the shopper types in the cart or at checkout. Letters, numbers and dashes — no spaces.</p>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Discount type *</label>
                <select name="type" class="{{ $fld }}">
                    <option value="fixed" @selected(old('type', $coupon->type) === 'fixed')>Amount off ($)</option>
                    <option value="percent" @selected(old('type', $coupon->type) === 'percent')>Percentage off (%)</option>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Value *</label>
                <input name="value" type="number" step="0.01" min="0.01" required
                       value="{{ old('value', $coupon->exists ? number_format((float) $coupon->value, 2, '.', '') : '5.00') }}" class="{{ $fld }}">
                <p class="mt-1 text-xs text-slate-400">5 means $5 off, or 10% off — whichever type is picked.</p>
            </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Minimum order</label>
                <input name="min_subtotal" type="number" step="0.01" min="0"
                       value="{{ old('min_subtotal', number_format((float) $coupon->min_subtotal, 2, '.', '')) }}" class="{{ $fld }}">
                <p class="mt-1 text-xs text-slate-400">Leave 0 for no minimum.</p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Maximum uses</label>
                <input name="max_uses" type="number" min="1" value="{{ old('max_uses', $coupon->max_uses) }}" placeholder="Unlimited" class="{{ $fld }}">
                <p class="mt-1 text-xs text-slate-400">Used so far: {{ $coupon->used_count ?? 0 }}.</p>
            </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Starts</label>
                <input name="starts_at" type="date" value="{{ old('starts_at', $coupon->starts_at?->format('Y-m-d')) }}" class="{{ $fld }}">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Expires</label>
                <input name="expires_at" type="date" value="{{ old('expires_at', $coupon->expires_at?->format('Y-m-d')) }}" class="{{ $fld }}">
                <p class="mt-1 text-xs text-slate-400">Leave both blank to run forever.</p>
            </div>
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-medium text-slate-200">Note (admin only)</label>
            <input name="note" value="{{ old('note', $coupon->note) }}" maxlength="190" placeholder="e.g. Newsletter sign-up" class="{{ $fld }}">
        </div>

        <label class="flex items-center gap-2 text-sm text-slate-200">
            <input type="checkbox" name="active" value="1" @checked(old('active', $coupon->exists ? $coupon->active : true)) class="rounded border-white/20 bg-slate-950/50">
            Code is on
        </label>
    </div>

    <div class="mt-5 flex items-center gap-3">
        <button class="rounded-lg bg-amber-500 px-6 py-2.5 text-sm font-bold text-slate-900 transition hover:bg-amber-400">{{ $coupon->exists ? 'Save changes' : 'Create code' }}</button>
        <a href="{{ route('admin.coupons.index') }}" class="text-sm font-semibold text-slate-400 hover:text-slate-200">Cancel</a>
    </div>
</form>

@endsection
