@php $purchaseFeed = \App\Support\PurchaseFeed::items(); @endphp

@if (! empty($purchaseFeed))
<div data-purchase-notify
     class="pointer-events-none fixed bottom-4 left-4 z-40 w-[19rem] max-w-[calc(100vw-2rem)] translate-y-4 opacity-0 transition-all duration-500"
     hidden>
    <a data-pn-link href="#"
       class="pointer-events-auto flex items-center gap-3 rounded-xl border border-slate-200 bg-white/95 p-2.5 pr-4 shadow-xl shadow-slate-400/20 backdrop-blur">
        <span class="grid h-12 w-12 shrink-0 place-items-center overflow-hidden rounded-lg bg-slate-50 ring-1 ring-slate-100">
            <img data-pn-image src="" alt="" class="h-full w-full object-contain">
        </span>
        <span class="min-w-0 flex-1">
            <span class="block truncate text-[13px] font-bold text-slate-900"><span data-pn-name></span> <span class="font-medium text-slate-500">from</span> <span data-pn-place></span></span>
            <span class="block truncate text-xs text-slate-600">purchased <span data-pn-product class="font-semibold text-amber-600"></span></span>
            <span class="mt-0.5 flex items-center gap-1 text-[11px] font-medium text-emerald-600">
                <span class="inline-block h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                <span data-pn-ago></span>
            </span>
        </span>
        <button type="button" data-pn-close aria-label="Dismiss"
                class="pointer-events-auto absolute right-1.5 top-1.5 grid h-5 w-5 place-items-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-600">
            <svg viewBox="0 0 20 20" class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 5l10 10M15 5L5 15"/></svg>
        </button>
    </a>
</div>

@push('scripts')
<script>
(function () {
    const box = document.querySelector('[data-purchase-notify]');
    if (!box) return;

    const feed = @json($purchaseFeed);
    if (!feed.length) return;

    const link  = box.querySelector('[data-pn-link]');
    const img   = box.querySelector('[data-pn-image]');
    const elName = box.querySelector('[data-pn-name]');
    const elPlace = box.querySelector('[data-pn-place]');
    const elProd  = box.querySelector('[data-pn-product]');
    const elAgo   = box.querySelector('[data-pn-ago]');

    // How long after the page loads each popup appears: first at 3s, the next at
    // 15s, then every 30s after that.
    const delays = [3000, 15000, 30000];

    let i = 0;         // which feed item
    let shown = 0;     // how many popups have appeared
    let hideT = null;
    let cycleT = null;
    let stopped = false;

    const show = () => {
        const item = feed[i % feed.length];
        i++;

        link.href = item.url;
        img.src = item.image;
        elName.textContent = item.name;
        elPlace.textContent = item.place;
        elProd.textContent = item.product;
        elAgo.textContent = item.ago;

        box.hidden = false;
        requestAnimationFrame(() => {
            box.classList.remove('translate-y-4', 'opacity-0');
        });

        // visible for 6s, then slide out
        hideT = setTimeout(hide, 6000);

        // schedule the next popup with the escalating delay (3s → 15s → 30s → …)
        shown++;
        if (!stopped) {
            cycleT = setTimeout(show, delays[Math.min(shown, delays.length - 1)]);
        }
    };

    const hide = () => {
        box.classList.add('translate-y-4', 'opacity-0');
        clearTimeout(hideT);
    };

    box.querySelector('[data-pn-close]').addEventListener('click', (e) => {
        e.preventDefault();
        stopped = true;
        clearTimeout(hideT);
        clearTimeout(cycleT);
        box.classList.add('translate-y-4', 'opacity-0');
        setTimeout(() => box.remove(), 500);
    });

    // first popup 3s after load
    cycleT = setTimeout(show, delays[0]);
})();
</script>
@endpush
@endif
