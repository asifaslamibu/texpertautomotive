@if ($paginator->hasPages())
    <nav class="flex items-center justify-between gap-4" role="navigation" aria-label="Pagination">
        {{-- mobile prev/next --}}
        <div class="flex flex-1 justify-between sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="cursor-default rounded-lg border border-white/10 px-4 py-2 text-sm font-semibold text-slate-500">&laquo; Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="rounded-lg border border-white/10 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:bg-white/10">&laquo; Previous</a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rounded-lg border border-white/10 px-4 py-2 text-sm font-semibold text-slate-200 transition hover:bg-white/10">Next &raquo;</a>
            @else
                <span class="cursor-default rounded-lg border border-white/10 px-4 py-2 text-sm font-semibold text-slate-500">Next &raquo;</span>
            @endif
        </div>

        {{-- desktop --}}
        <div class="hidden w-full items-center justify-between sm:flex">
            <p class="text-sm text-slate-400">
                Showing
                <span class="font-semibold text-slate-200">{{ $paginator->firstItem() }}</span>
                to
                <span class="font-semibold text-slate-200">{{ $paginator->lastItem() }}</span>
                of
                <span class="font-semibold text-slate-200">{{ $paginator->total() }}</span>
                results
            </p>

            <div class="flex items-center gap-1">
                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" class="grid h-9 min-w-9 cursor-default place-items-center rounded-lg border border-white/10 px-3 text-sm font-semibold text-slate-600">&laquo;</span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="grid h-9 min-w-9 place-items-center rounded-lg border border-white/10 px-3 text-sm font-semibold text-slate-200 transition hover:bg-white/10">&laquo;</a>
                @endif

                {{-- Page numbers --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="grid h-9 min-w-9 place-items-center px-2 text-sm text-slate-500">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="grid h-9 min-w-9 place-items-center rounded-lg bg-white px-3 text-sm font-bold text-slate-900">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="grid h-9 min-w-9 place-items-center rounded-lg border border-white/10 px-3 text-sm font-semibold text-slate-200 transition hover:bg-white/10">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="grid h-9 min-w-9 place-items-center rounded-lg border border-white/10 px-3 text-sm font-semibold text-slate-200 transition hover:bg-white/10">&raquo;</a>
                @else
                    <span aria-disabled="true" class="grid h-9 min-w-9 cursor-default place-items-center rounded-lg border border-white/10 px-3 text-sm font-semibold text-slate-600">&raquo;</span>
                @endif
            </div>
        </div>
    </nav>
@endif
