@extends('admin.layouts.app')

@section('title', $role->exists ? 'Edit Role' : 'New Role')
@section('heading', $role->exists ? 'Edit Role' : 'New Role')

@section('content')

@php $selected = old('permissions', $role->permissions ?? []); @endphp

<form method="POST" action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}" class="mx-auto max-w-2xl">
    @csrf
    @if ($role->exists) @method('PUT') @endif

    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <div class="space-y-5">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Role name *</label>
                <input name="name" value="{{ old('name', $role->name) }}" required
                       class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Description</label>
                <input name="description" value="{{ old('description', $role->description) }}" placeholder="What is this role for?"
                       class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>

            @if ($role->is_super)
                <div class="rounded-xl border border-white/15 bg-white/10 p-4 text-sm text-slate-100">
                    <p class="font-bold">This is the Administrator role.</p>
                    <p class="mt-1">It always has full access to every section and cannot be restricted or deleted. You can rename it, but permissions are fixed.</p>
                </div>
            @else
                <div>
                    <p class="mb-2 text-sm font-medium text-slate-200">Sections this role can access</p>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($permissions as $key => $label)
                            <label class="flex items-center gap-2.5 rounded-lg border border-white/10 px-3 py-2.5 text-sm font-medium text-slate-200 hover:bg-white/5">
                                <input type="checkbox" name="permissions[]" value="{{ $key }}" @checked(in_array($key, $selected, true)) class="rounded border-white/10 text-slate-200 checked:bg-white checked:border-white focus:ring-slate-500/30">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <label class="flex items-start gap-3 rounded-xl border border-white/10 bg-white/5 p-4">
                    <input type="checkbox" name="read_only" value="1" @checked(old('read_only', $role->read_only)) class="mt-0.5 rounded border-white/10 text-slate-200 checked:bg-white checked:border-white focus:ring-slate-500/30">
                    <span class="text-sm">
                        <span class="font-semibold text-slate-100">Read-only (audit mode)</span>
                        <span class="mt-0.5 block text-xs text-slate-400">Staff with this role can view the sections above but cannot create, edit or delete anything.</span>
                    </span>
                </label>
            @endif
        </div>
    </div>

    <div class="mt-6 flex items-center justify-end gap-3">
        <a href="{{ route('admin.roles.index') }}" class="rounded-lg border border-white/10 px-5 py-2.5 text-sm font-semibold text-slate-300 hover:bg-white/5">Cancel</a>
        <button class="rounded-lg bg-white px-6 py-2.5 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-200">{{ $role->exists ? 'Update Role' : 'Create Role' }}</button>
    </div>
</form>

@endsection
