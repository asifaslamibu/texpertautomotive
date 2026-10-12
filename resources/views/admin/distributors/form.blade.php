@extends('admin.layouts.app')

@section('title', $distributor->exists ? 'Edit Distributor' : 'New Distributor')
@section('heading', $distributor->exists ? 'Edit Distributor' : 'New Distributor')

@section('content')

@php
    $label = 'mb-1.5 block text-sm font-medium text-slate-200';
    $input = 'w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30';
@endphp

<form method="POST" action="{{ $distributor->exists ? route('admin.distributors.update', $distributor) : route('admin.distributors.store') }}" class="mx-auto max-w-2xl">
    @csrf
    @if ($distributor->exists) @method('PUT') @endif

    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm space-y-5">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="{{ $label }}">Name</label>
                <input name="name" value="{{ old('name', $distributor->name) }}" required class="{{ $input }}" placeholder="Distributor / contact name">
            </div>
            <div>
                <label class="{{ $label }}">Company</label>
                <input name="company" value="{{ old('company', $distributor->company) }}" class="{{ $input }}" placeholder="Company (optional)">
            </div>
            <div>
                <label class="{{ $label }}">Email <span class="font-normal text-slate-400">(receives the PO)</span></label>
                <input type="email" name="email" value="{{ old('email', $distributor->email) }}" required class="{{ $input }}" placeholder="orders@distributor.com">
            </div>
            <div>
                <label class="{{ $label }}">Phone</label>
                <input name="phone" value="{{ old('phone', $distributor->phone) }}" class="{{ $input }}" placeholder="(214)-000-0000">
            </div>
        </div>
        <div>
            <label class="{{ $label }}">Address</label>
            <textarea name="address" rows="2" class="{{ $input }}" placeholder="Address (optional)">{{ old('address', $distributor->address) }}</textarea>
        </div>
        <div>
            <label class="{{ $label }}">Notes</label>
            <textarea name="notes" rows="2" class="{{ $input }}" placeholder="Internal notes (optional)">{{ old('notes', $distributor->notes) }}</textarea>
        </div>
        <div class="flex flex-wrap items-center gap-6">
            <label class="flex items-center gap-2 text-sm font-medium text-slate-200">
                <input type="checkbox" name="active" value="1" @checked(old('active', $distributor->active ?? true)) class="rounded border-white/10 text-slate-200"> Active
            </label>
            <label class="flex items-center gap-2 text-sm font-medium text-slate-200">
                <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $distributor->is_default)) class="rounded border-white/10 text-slate-200"> Default distributor <span class="font-normal text-slate-400">(auto-assigned to new POs)</span>
            </label>
        </div>
    </div>

    <div class="mt-6 flex items-center justify-end gap-3">
        <a href="{{ route('admin.distributors.index') }}" class="rounded-lg border border-white/10 px-5 py-2.5 text-sm font-semibold text-slate-300 hover:bg-white/5">Cancel</a>
        <button class="rounded-lg bg-white px-6 py-2.5 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-200">{{ $distributor->exists ? 'Update' : 'Add Distributor' }}</button>
    </div>
</form>

@endsection
