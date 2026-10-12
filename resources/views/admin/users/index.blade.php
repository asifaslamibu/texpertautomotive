@extends('admin.layouts.app')

@section('title', 'Staff')
@section('heading', 'Staff')
@section('subheading', 'Admin users and the role that controls their access')

@section('content')

<div class="mb-5 flex flex-wrap items-center gap-3">
    <p class="text-sm text-slate-400">{{ $users->total() }} staff member(s)</p>
    <a href="{{ route('admin.users.create') }}" class="ml-auto rounded-lg bg-white px-4 py-2 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-200">+ Add Staff</a>
</div>

<div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-white/10 bg-white/5 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Role</th>
                    <th class="px-4 py-3">Access</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @forelse ($users as $user)
                    <tr class="hover:bg-white/5">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-gradient-to-br from-slate-800 to-slate-950 text-sm font-bold text-white">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                <span class="font-bold text-slate-100">{{ $user->name }}
                                    @if ($user->id === auth()->id())<span class="ml-1 text-xs font-medium text-slate-200">(you)</span>@endif
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-slate-300">{{ $user->email }}</td>
                        <td class="px-4 py-3">
                            @if ($user->role)
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-bold {{ $user->role->is_super ? 'bg-white/10 text-slate-200' : 'bg-white/5 text-slate-300' }}">{{ $user->role->name }}</span>
                            @else
                                <span class="text-xs text-rose-300">No role</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-400">
                            @if ($user->role?->is_super)
                                Full access
                            @elseif ($user->role?->read_only)
                                Read-only · {{ count($user->role->permissions ?? []) }} section(s)
                            @else
                                {{ count($user->role->permissions ?? []) }} section(s)
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.users.edit', $user) }}" class="rounded-lg border border-white/10 px-3 py-1.5 text-xs font-semibold text-slate-300 hover:border-white/15 hover:text-white">Edit</a>
                                @if ($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Remove this staff member?')">
                                        @csrf @method('DELETE')
                                        <button class="rounded-lg border border-white/10 px-2.5 py-1.5 text-xs font-semibold text-rose-300 hover:border-rose-400/30 hover:bg-rose-500/15" aria-label="Delete">&times;</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-12 text-center text-slate-400">No staff yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6">{{ $users->links('pagination.admin') }}</div>

@endsection
