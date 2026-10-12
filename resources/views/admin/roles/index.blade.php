@extends('admin.layouts.app')

@section('title', 'Roles')
@section('heading', 'Roles')
@section('subheading', 'Define what each kind of staff member can access')

@section('content')

<div class="mb-5 flex flex-wrap items-center gap-3">
    <p class="text-sm text-slate-400">{{ $roles->count() }} role(s)</p>
    <a href="{{ route('admin.roles.create') }}" class="ml-auto rounded-lg bg-white px-4 py-2 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-200">+ Add Role</a>
</div>

<div class="grid gap-4 sm:grid-cols-2">
    @foreach ($roles as $role)
        <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-5 shadow-sm">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="flex items-center gap-2 font-bold text-white">
                        {{ $role->name }}
                        @if ($role->is_super)<span class="rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-bold uppercase text-slate-200">Super</span>@endif
                        @if ($role->read_only)<span class="rounded-full bg-sky-500/15 px-2 py-0.5 text-[10px] font-bold uppercase text-sky-300">Read-only</span>@endif
                    </h3>
                    <p class="mt-1 text-xs text-slate-400">{{ $role->users_count }} staff · {{ $role->description }}</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <a href="{{ route('admin.roles.edit', $role) }}" class="rounded-lg border border-white/10 px-3 py-1.5 text-xs font-semibold text-slate-300 hover:border-white/15 hover:text-white">Edit</a>
                    @unless ($role->is_super)
                        <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?')">
                            @csrf @method('DELETE')
                            <button class="rounded-lg border border-white/10 px-2.5 py-1.5 text-xs font-semibold text-rose-300 hover:border-rose-400/30 hover:bg-rose-500/15" aria-label="Delete">&times;</button>
                        </form>
                    @endunless
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-1.5">
                @forelse ($role->permissionLabels() as $label)
                    <span class="rounded-md bg-white/5 px-2 py-1 text-[11px] font-semibold text-slate-300">{{ $label }}</span>
                @empty
                    <span class="text-xs text-slate-400">No sections assigned.</span>
                @endforelse
            </div>
        </div>
    @endforeach
</div>

@endsection
