@extends('admin.layouts.app')

@section('title', $user->exists ? 'Edit Staff' : 'New Staff')
@section('heading', $user->exists ? 'Edit Staff' : 'New Staff')

@section('content')

<form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="mx-auto max-w-2xl">
    @csrf
    @if ($user->exists) @method('PUT') @endif

    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <div class="space-y-5">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Full name *</label>
                <input name="name" value="{{ old('name', $user->name) }}" required
                       class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Email *</label>
                <input name="email" type="email" value="{{ old('email', $user->email) }}" required
                       class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Role *</label>
                <select name="role_id" required class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                    <option value="">— Select a role —</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>
                            {{ $role->name }}{{ $role->is_super ? ' (full access)' : '' }}{{ $role->read_only ? ' · read-only' : '' }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-400">The role decides which sections this person can open. Manage roles under <a href="{{ route('admin.roles.index') }}" class="font-semibold text-slate-200">Roles</a>.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Password {{ $user->exists ? '' : '*' }}</label>
                    <input name="password" type="password" {{ $user->exists ? '' : 'required' }} autocomplete="new-password"
                           class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                    @if ($user->exists)<p class="mt-1 text-xs text-slate-400">Leave blank to keep current password.</p>@endif
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Confirm password</label>
                    <input name="password_confirmation" type="password" autocomplete="new-password"
                           class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 flex items-center justify-end gap-3">
        <a href="{{ route('admin.users.index') }}" class="rounded-lg border border-white/10 px-5 py-2.5 text-sm font-semibold text-slate-300 hover:bg-white/5">Cancel</a>
        <button class="rounded-lg bg-white px-6 py-2.5 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-200">{{ $user->exists ? 'Update Staff' : 'Create Staff' }}</button>
    </div>
</form>

@endsection
