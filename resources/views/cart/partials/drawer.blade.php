{{-- ============ Slide-out cart drawer ============ --}}
<div data-cart-drawer class="fixed inset-0 z-50 hidden">
    <div data-cart-overlay class="absolute inset-0 bg-slate-900/50 opacity-0 transition-opacity duration-300"></div>

    <aside data-cart-panel class="absolute right-0 top-0 flex h-full w-96 max-w-[90%] translate-x-full flex-col bg-white shadow-2xl transition-transform duration-300">
        <div class="flex shrink-0 items-center justify-between border-b border-slate-200 px-5 py-4">
            <h2 class="flex items-center gap-2 text-lg font-black text-slate-900">
                <x-icon name="cart" class="h-5 w-5 text-amber-500" />
                Your Cart
            </h2>
            <button type="button" data-cart-close class="grid h-8 w-8 place-items-center rounded-full text-slate-500 transition hover:bg-slate-100" aria-label="Close cart">
                <span class="text-2xl leading-none">&times;</span>
            </button>
        </div>

        <div data-cart-contents class="flex flex-1 flex-col overflow-hidden">
            @include('cart.partials.contents')
        </div>
    </aside>
</div>
