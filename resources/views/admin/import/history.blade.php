@extends('admin.layouts.app')

@section('title', 'Import History')
@section('heading', 'Import History')
@section('subheading', 'Every CSV import, its result, and the original file')

@section('content')

<div class="mb-5 flex items-center justify-between">
    <a href="{{ route('admin.import.index') }}" class="text-sm font-semibold text-slate-300 hover:text-white">← Back to Import</a>
    <span class="text-sm text-slate-400">{{ $logs->total() }} import{{ $logs->total() === 1 ? '' : 's' }} recorded</span>
</div>

<div class="overflow-hidden rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="border-b border-white/10 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                <tr>
                    <th class="px-4 py-3">When</th>
                    <th class="px-4 py-3">By</th>
                    <th class="px-4 py-3">File</th>
                    <th class="px-4 py-3 text-right">Rows</th>
                    <th class="px-4 py-3 text-right">Created</th>
                    <th class="px-4 py-3 text-right">Updated</th>
                    <th class="px-4 py-3 text-right">Skipped</th>
                    <th class="px-4 py-3">Also created</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10 text-slate-300">
                @forelse ($logs as $log)
                    <tr class="hover:bg-white/5">
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="font-semibold text-white">{{ $log->created_at->format('M j, Y') }}</span>
                            <span class="block text-xs text-slate-500">{{ $log->created_at->format('g:i A') }}</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $log->user_name ?? $log->user?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="block max-w-[220px] truncate font-medium text-slate-200" title="{{ $log->original_name }}">{{ $log->original_name }}</span>
                            <span class="text-xs text-slate-500">
                                {{ $log->size ? number_format($log->size / 1024, 1).' KB' : '' }}
                                @if ($log->download_images) · images downloaded @endif
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ number_format($log->rows) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums font-semibold text-emerald-400">{{ number_format($log->created_count) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums font-semibold text-sky-400">{{ number_format($log->updated_count) }}</td>
                        <td class="px-4 py-3 text-right tabular-nums text-slate-400">{{ number_format($log->skipped_count) }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-400">
                            {{ $log->categories_count }} cats · {{ $log->brands_count }} brands · {{ $log->images_count }} imgs
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($log->fileExists())
                                <a href="{{ route('admin.import.download', $log) }}" class="inline-flex items-center gap-1.5 rounded-lg border border-white/15 px-3 py-1.5 text-xs font-semibold text-slate-100 transition hover:bg-white/10">
                                    <x-icon name="box" class="h-3.5 w-3.5" /> Download
                                </a>
                            @else
                                <span class="text-xs text-slate-600">file removed</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-16 text-center text-slate-400">
                            No imports yet. <a href="{{ route('admin.import.index') }}" class="font-semibold text-amber-400 hover:text-amber-300">Run your first import →</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6">{{ $logs->links('pagination.admin') }}</div>

@endsection
