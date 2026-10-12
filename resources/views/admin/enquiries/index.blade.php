@extends('admin.layouts.app')

@section('title', 'Enquiries')
@section('heading', 'Enquiries')
@section('subheading', 'Messages & requests submitted from the website forms')

@section('content')

{{-- filter tabs --}}
<div class="mb-5 flex flex-wrap gap-2">
    @php
        $tabs = ['' => 'All ('.$counts['all'].')'] + collect(\App\Models\Enquiry::TYPES)->mapWithKeys(fn ($label, $k) => [$k => $label.' ('.($counts[$k] ?? 0).')'])->all();
    @endphp
    @foreach ($tabs as $key => $label)
        <a href="{{ route('admin.enquiries.index', array_filter(['type' => $key])) }}"
           class="rounded-full px-4 py-1.5 text-sm font-semibold transition {{ (string) $type === (string) $key ? 'bg-white text-slate-900' : 'border border-white/10 text-slate-300 hover:bg-white/10' }}">
            {{ $label }}
        </a>
    @endforeach
    @if ($counts['unread'] > 0)
        <span class="ml-auto self-center rounded-full bg-amber-500/15 px-3 py-1 text-xs font-bold text-amber-300">{{ $counts['unread'] }} unread</span>
    @endif
</div>

<div data-bulk class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur shadow-sm">
    <div class="px-4 pt-4"><x-bulk-bar :action="route('admin.enquiries.bulk-destroy')" /></div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-white/5 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                <tr>
                    <th class="px-4 py-3 w-8"><input type="checkbox" data-bulk-all class="rounded border-white/20 bg-transparent"></th>
                    <x-th-sort column="type" label="Type" />
                    <x-th-sort column="name" label="From" />
                    <th class="px-4 py-3">Subject</th>
                    <x-th-sort column="created_at" label="Date" />
                    <th class="px-4 py-3 text-right"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @forelse ($enquiries as $e)
                    <tr class="transition hover:bg-white/5 {{ $e->is_read ? '' : 'bg-white/[0.03]' }}">
                        <td class="px-4 py-3"><input type="checkbox" name="ids[]" value="{{ $e->id }}" data-bulk-item class="rounded border-white/20 bg-transparent"></td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-{{ $e->type_color }}-500/15 px-2.5 py-1 text-xs font-semibold text-{{ $e->type_color }}-300">{{ $e->type_label }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.enquiries.show', $e) }}" class="font-semibold {{ $e->is_read ? 'text-slate-200' : 'text-white' }} hover:underline">
                                @unless ($e->is_read)<span class="mr-1 inline-block h-2 w-2 rounded-full bg-amber-400 align-middle"></span>@endunless
                                {{ $e->name }}
                            </a>
                            <div class="text-xs text-slate-400">{{ $e->email }}</div>
                        </td>
                        <td class="px-4 py-3 text-slate-300">{{ \Illuminate\Support\Str::limit($e->subject ?: $e->message, 50) ?: '—' }}</td>
                        <td class="px-4 py-3 text-slate-400">{{ $e->created_at?->format('M d, Y g:i A') }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('admin.enquiries.show', $e) }}" class="text-xs font-semibold text-slate-300 hover:text-white">View →</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-12 text-center text-slate-400">No enquiries yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="border-t border-white/10 p-4">{{ $enquiries->links('pagination.admin') }}</div>
</div>

@endsection
