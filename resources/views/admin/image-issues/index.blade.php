@extends('admin.layouts.app')

@section('title', 'Image Issues')
@section('heading', 'Image Issues')
@section('subheading', 'Products whose image is missing or broken')

@section('content')

{{-- stats --}}
<div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
    @php
        $cards = [
            ['Broken total', count($issues), 'from-rose-500 to-rose-700'],
            ['Dead URLs', $stats['dead_url'], 'from-amber-500 to-orange-600'],
            ['Missing files', $stats['missing_file'], 'from-sky-500 to-indigo-600'],
            ['No image', $stats['empty'], 'from-slate-600 to-slate-800'],
        ];
    @endphp
    @foreach ($cards as [$label, $val, $grad])
        <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">{{ $label }}</p>
            <p class="mt-1 text-2xl font-black text-white">{{ number_format($val) }}</p>
        </div>
    @endforeach
</div>

<div class="mt-5 flex flex-wrap items-center justify-between gap-3">
    <p class="text-sm text-slate-400">
        Scanned {{ number_format($total) }} products · last checked {{ $checkedAt->diffForHumans() }}
    </p>
    <div class="flex items-center gap-2">
        @if (count($issues))
            <a href="{{ route('admin.image-issues.export') }}" class="inline-flex items-center gap-2 rounded-lg border border-white/10 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:bg-white/10">Export CSV</a>
        @endif
        <a href="{{ route('admin.image-issues.index', ['recheck' => 1]) }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-bold text-slate-900 transition hover:bg-slate-200">
            <x-icon name="refresh" class="h-4 w-4" /> Re-check now
        </a>
    </div>
</div>

<div class="mt-4 rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-white/5 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                <tr>
                    <th class="px-4 py-3">Preview</th>
                    <th class="px-4 py-3">Product</th>
                    <th class="px-4 py-3">SKU</th>
                    <th class="px-4 py-3">Issue</th>
                    <th class="px-4 py-3 text-right"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
                @forelse ($issues as $i)
                    <tr class="transition hover:bg-white/5">
                        <td class="px-4 py-3">
                            <span class="grid h-12 w-12 place-items-center overflow-hidden rounded-lg border border-white/10 bg-white/5">
                                @if ($i['image'])
                                    <img src="{{ media($i['image']) }}" alt="" class="max-h-full max-w-full object-contain" loading="lazy" onerror="this.style.display='none'">
                                @else
                                    <x-icon name="box" class="h-5 w-5 text-slate-500" />
                                @endif
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-slate-100">{{ \Illuminate\Support\Str::limit($i['name'], 60) }}</p>
                            <p class="max-w-md truncate text-xs text-slate-500">{{ $i['image'] ?: '—' }}</p>
                        </td>
                        <td class="px-4 py-3 text-slate-300">{{ $i['sku'] }}</td>
                        <td class="px-4 py-3">
                            @php
                                $color = ['dead_url' => 'amber', 'missing_file' => 'sky', 'empty' => 'slate'][$i['type']] ?? 'rose';
                            @endphp
                            <span class="rounded-full bg-{{ $color }}-500/15 px-2.5 py-1 text-xs font-semibold text-{{ $color }}-300">{{ $i['label'] }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.products.edit', $i['slug']) }}" class="rounded-lg border border-white/10 px-3 py-1.5 text-xs font-semibold text-slate-200 transition hover:bg-white/10">Fix image →</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-14 text-center text-slate-400">🎉 No broken images — every product image loads fine.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection
