@extends('admin.layouts.app')

@section('title', 'Settings')
@section('heading', 'Store Settings')
@section('subheading', 'Control storefront content — changes go live instantly')

@section('content')

@if (session('status'))
    <div class="mx-auto mb-6 max-w-3xl rounded-xl border border-emerald-400/30 bg-emerald-500/15 px-4 py-3 text-sm font-semibold text-emerald-300">
        {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div class="mx-auto mb-6 max-w-3xl rounded-xl border border-rose-400/30 bg-rose-500/15 p-4">
        <p class="text-sm font-bold text-rose-300">Please fix the following:</p>
        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-rose-300">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="mx-auto max-w-3xl space-y-6">
    @csrf @method('PUT')

    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <h2 class="mb-4 font-bold text-white">General</h2>
        <div class="space-y-4">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Site name *</label>
                <input name="site_name" value="{{ old('site_name', $settings['site_name']) }}" required class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Company description <span class="font-normal text-slate-400">(shown in the footer)</span></label>
                <textarea name="site_description" rows="3" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">{{ old('site_description', $settings['site_description']) }}</textarea>
            </div>
            @php $annOn = old('announcement_enabled', $settings['announcement_enabled'] ?? '1') !== '0'; @endphp
            <label class="flex items-center justify-between gap-4 rounded-lg border border-white/10 bg-white/5 px-4 py-3">
                <span>
                    <span class="block text-sm font-medium text-slate-200">Show announcement bar</span>
                    <span class="block text-xs text-slate-400">The thin promo strip at the very top of every storefront page.</span>
                </span>
                <input type="hidden" name="announcement_enabled" value="0">
                <input type="checkbox" name="announcement_enabled" value="1" {{ $annOn ? 'checked' : '' }}
                       class="h-5 w-9 shrink-0 cursor-pointer appearance-none rounded-full bg-slate-300 transition checked:bg-emerald-500 relative before:absolute before:top-0.5 before:left-0.5 before:h-4 before:w-4 before:rounded-full before:bg-white before:transition checked:before:translate-x-4">
            </label>

            @php $promoOn = old('promo_banner_enabled', $settings['promo_banner_enabled'] ?? '1') !== '0'; @endphp
            <label class="flex items-center justify-between gap-4 rounded-lg border border-white/10 bg-white/5 px-4 py-3">
                <span>
                    <span class="block text-sm font-medium text-slate-200">Show promo banner</span>
                    <span class="block text-xs text-slate-400">The gold “Save 5% — code…” banner on the homepage.</span>
                </span>
                <input type="hidden" name="promo_banner_enabled" value="0">
                <input type="checkbox" name="promo_banner_enabled" value="1" {{ $promoOn ? 'checked' : '' }}
                       class="h-5 w-9 shrink-0 cursor-pointer appearance-none rounded-full bg-slate-300 transition checked:bg-emerald-500 relative before:absolute before:top-0.5 before:left-0.5 before:h-4 before:w-4 before:rounded-full before:bg-white before:transition checked:before:translate-x-4">
            </label>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Announcement bar text</label>
                    <input name="announcement" value="{{ old('announcement', $settings['announcement']) }}" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Default product badge</label>
                    <input name="default_product_tag" value="{{ old('default_product_tag', $settings['default_product_tag'] ?? '') }}" placeholder="e.g. Ships from manufacturer"
                           class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                    <p class="mt-1 text-xs text-slate-400">Shown on every product that has no badge of its own. A brand's own tag wins, and a product set to “No badge” still shows none. Leave blank for no default.</p>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Free-shipping note</label>
                    <input name="free_shipping_note" value="{{ old('free_shipping_note', $settings['free_shipping_note']) }}" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                </div>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Promo code (shown in announcement)</label>
                <input name="announcement_code" value="{{ old('announcement_code', $settings['announcement_code']) }}" class="w-48 rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>
        </div>
    </div>

    {{-- ============ Payments (Stripe) — master admin only ============ --}}
    @if (auth()->user()?->isMaster())
    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm" data-stripe-section>
        <div class="mb-1 flex items-center gap-2">
            <h2 class="font-bold text-white">Payments — Stripe</h2>
            @php $mode = old('stripe_mode', $settings['stripe_mode'] ?? 'test'); @endphp
            <span class="rounded-full px-2 py-0.5 text-[11px] font-bold {{ $mode === 'live' ? 'bg-emerald-500/15 text-emerald-300' : 'bg-white/10 text-slate-200' }}" data-mode-badge>
                {{ $mode === 'live' ? 'LIVE' : 'TEST' }}
            </span>
        </div>
        <p class="mb-5 text-sm text-slate-400">Accept card payments at checkout. Get your keys from the
            <a href="https://dashboard.stripe.com/apikeys" target="_blank" rel="noopener" class="font-semibold text-slate-200">Stripe Dashboard → Developers → API keys</a>.</p>

        {{-- enable toggle --}}
        @php $stripeOn = old('stripe_enabled', $settings['stripe_enabled'] ?? '0') === '1'; @endphp
        <label class="flex items-center justify-between gap-4 rounded-lg border border-white/10 bg-white/5 px-4 py-3">
            <span>
                <span class="block text-sm font-medium text-slate-200">Enable Stripe card payments</span>
                <span class="block text-xs text-slate-400">When off, the “Card” option falls back to a manual order (no charge).</span>
            </span>
            <input type="hidden" name="stripe_enabled" value="0">
            <input type="checkbox" name="stripe_enabled" value="1" {{ $stripeOn ? 'checked' : '' }}
                   class="h-5 w-9 shrink-0 cursor-pointer appearance-none rounded-full bg-slate-300 transition checked:bg-emerald-500 relative before:absolute before:top-0.5 before:left-0.5 before:h-4 before:w-4 before:rounded-full before:bg-white before:transition checked:before:translate-x-4">
        </label>

        {{-- mode + currency --}}
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Mode</label>
                <div class="flex gap-2">
                    @foreach (['test' => 'Test', 'live' => 'Live'] as $val => $label)
                        <label class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-lg border border-white/10 px-4 py-2.5 text-sm font-semibold text-slate-200 transition has-[:checked]:border-slate-400 has-[:checked]:bg-white/10">
                            <input type="radio" name="stripe_mode" value="{{ $val }}" @checked($mode === $val) data-mode-radio class="text-slate-200"> {{ $label }}
                        </label>
                    @endforeach
                </div>
                <p class="mt-1 text-xs text-slate-400">Use <b>Test</b> while setting up; switch to <b>Live</b> to take real payments.</p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Currency</label>
                <input name="stripe_currency" value="{{ old('stripe_currency', $settings['stripe_currency'] ?: 'usd') }}" placeholder="usd"
                       class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm lowercase outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                <p class="mt-1 text-xs text-slate-400">3-letter ISO code, e.g. <b>usd</b>, <b>eur</b>, <b>gbp</b>.</p>
            </div>
        </div>

        {{-- card payment style --}}
        @php $checkoutType = old('stripe_checkout_type', $settings['stripe_checkout_type'] ?: 'embedded'); @endphp
        <div class="mt-4">
            <label class="mb-1.5 block text-sm font-medium text-slate-200">Card payment style</label>
            <div class="grid gap-2 sm:grid-cols-2">
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-white/10 px-4 py-3 transition has-[:checked]:border-slate-400 has-[:checked]:bg-white/10">
                    <input type="radio" name="stripe_checkout_type" value="embedded" @checked($checkoutType === 'embedded') class="mt-0.5 text-slate-200">
                    <span>
                        <span class="block text-sm font-semibold text-slate-100">On-page (embedded)</span>
                        <span class="block text-xs text-slate-400">Card form right on the checkout page. No redirect.</span>
                    </span>
                </label>
                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-white/10 px-4 py-3 transition has-[:checked]:border-slate-400 has-[:checked]:bg-white/10">
                    <input type="radio" name="stripe_checkout_type" value="redirect" @checked($checkoutType === 'redirect') class="mt-0.5 text-slate-200">
                    <span>
                        <span class="block text-sm font-semibold text-slate-100">Redirect to Stripe</span>
                        <span class="block text-xs text-slate-400">Buyer is sent to Stripe's hosted payment page.</span>
                    </span>
                </label>
            </div>
        </div>

        {{-- TEST keys --}}
        <div class="mt-5 rounded-xl border border-white/15 bg-white/5 p-4" data-keys-group="test">
            <h3 class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-300">Test keys</h3>
            <div class="space-y-3">
                @include('admin.settings._stripe_field', ['name' => 'stripe_test_key', 'label' => 'Publishable key', 'ph' => 'pk_test_…', 'secret' => false, 'val' => old('stripe_test_key', $settings['stripe_test_key'])])
                @include('admin.settings._stripe_field', ['name' => 'stripe_test_secret', 'label' => 'Secret key', 'ph' => 'sk_test_…', 'secret' => true, 'val' => old('stripe_test_secret', $settings['stripe_test_secret'])])
                @include('admin.settings._stripe_field', ['name' => 'stripe_test_webhook_secret', 'label' => 'Webhook signing secret', 'ph' => 'whsec_…', 'secret' => true, 'val' => old('stripe_test_webhook_secret', $settings['stripe_test_webhook_secret'])])
            </div>
        </div>

        {{-- LIVE keys --}}
        <div class="mt-4 rounded-xl border border-emerald-400/30 bg-emerald-500/10 p-4" data-keys-group="live">
            <h3 class="mb-3 text-xs font-bold uppercase tracking-wider text-emerald-300">Live keys</h3>
            <div class="space-y-3">
                @include('admin.settings._stripe_field', ['name' => 'stripe_live_key', 'label' => 'Publishable key', 'ph' => 'pk_live_…', 'secret' => false, 'val' => old('stripe_live_key', $settings['stripe_live_key'])])
                @include('admin.settings._stripe_field', ['name' => 'stripe_live_secret', 'label' => 'Secret key', 'ph' => 'sk_live_…', 'secret' => true, 'val' => old('stripe_live_secret', $settings['stripe_live_secret'])])
                @include('admin.settings._stripe_field', ['name' => 'stripe_live_webhook_secret', 'label' => 'Webhook signing secret', 'ph' => 'whsec_…', 'secret' => true, 'val' => old('stripe_live_webhook_secret', $settings['stripe_live_webhook_secret'])])
            </div>
        </div>

        {{-- webhook endpoint helper --}}
        <div class="mt-5 rounded-xl border border-white/10 bg-white/5 p-4">
            <p class="text-sm font-semibold text-slate-200">Webhook endpoint</p>
            <p class="mt-1 text-xs text-slate-400">In Stripe → Developers → Webhooks, add this URL and select the event <b>checkout.session.completed</b>. Paste the signing secret it gives you above.</p>
            <div class="mt-2 flex items-center gap-2">
                <input type="text" readonly value="{{ url('/stripe/webhook') }}" data-webhook-url
                       class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2 font-mono text-xs outline-none">
                <button type="button" data-copy-webhook class="shrink-0 rounded-lg bg-white px-3 py-2 text-xs font-bold text-slate-900 hover:bg-slate-200">Copy</button>
            </div>
        </div>
    </div>
    @endif

    {{-- ============ Shipping charges ============ --}}
    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <h2 class="font-bold text-white">Shipping charges</h2>
        <p class="mb-4 mt-1 text-sm text-slate-400">Turn this off to ship everything free — the cart, checkout and orders then show <b>Free</b> and no shipping is added to any total.</p>

        @php $shipOn = old('shipping_enabled', $settings['shipping_enabled'] ?? '1') !== '0'; @endphp
        <label class="flex cursor-pointer items-center justify-between rounded-xl border border-white/10 bg-slate-950/40 px-4 py-3">
            <span>
                <span class="block text-sm font-medium text-slate-200">Charge customers for shipping</span>
                <span class="block text-xs text-slate-400">On: live carrier rates if enabled below, otherwise a flat ${{ \App\Support\Cart::FLAT_SHIPPING }} per order.</span>
            </span>
            <input type="hidden" name="shipping_enabled" value="0">
            <input type="checkbox" name="shipping_enabled" value="1" {{ $shipOn ? 'checked' : '' }} class="rounded border-white/10 text-slate-200">
        </label>
    </div>

    {{-- ============ Shipping (Shippo) ============ --}}
    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <div class="mb-1 flex items-center gap-2">
            <h2 class="font-bold text-white">Shipping — Shippo</h2>
            <span class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-[11px] font-bold text-emerald-400">Multi-carrier live rates · labels · tracking</span>
        </div>
        <p class="mb-4 text-sm text-slate-400">Get API tokens from
            <a href="https://apps.goshippo.com/settings/api" target="_blank" rel="noopener" class="font-semibold text-slate-200">goshippo.com → Settings → API</a>.
            Use <b>Test mode</b> first, then switch to Live. Rates use the <b>Ship-From address</b> in the UPS section below.</p>

        @php $shippoOn = old('shippo_enabled', $settings['shippo_enabled'] ?? '0') === '1'; @endphp
        <label class="flex cursor-pointer items-center justify-between rounded-xl border border-white/10 bg-slate-950/40 px-4 py-3">
            <span class="block text-sm font-medium text-slate-200">Enable Shippo shipping rates</span>
            <input type="hidden" name="shippo_enabled" value="0">
            <input type="checkbox" name="shippo_enabled" value="1" {{ $shippoOn ? 'checked' : '' }} class="rounded border-white/10 text-slate-200">
        </label>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Mode</label>
                <select name="shippo_mode" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 px-3 py-2.5 text-sm outline-none focus:border-slate-400">
                    <option value="test" @selected(old('shippo_mode', $settings['shippo_mode'] ?: 'test') === 'test')>Test</option>
                    <option value="live" @selected(old('shippo_mode', $settings['shippo_mode'] ?? '') === 'live')>Live</option>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Test API token <span class="font-normal text-slate-400">(shippo_test_…)</span></label>
                <input name="shippo_test_key" value="{{ old('shippo_test_key', $settings['shippo_test_key'] ?? '') }}" placeholder="shippo_test_xxxxxxxx" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 font-mono text-xs outline-none focus:border-slate-400">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Live API token <span class="font-normal text-slate-400">(shippo_live_…)</span></label>
                <input name="shippo_live_key" value="{{ old('shippo_live_key', $settings['shippo_live_key'] ?? '') }}" placeholder="shippo_live_xxxxxxxx" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 font-mono text-xs outline-none focus:border-slate-400">
            </div>
        </div>
        <p class="mt-2 text-xs text-slate-400">When enabled, checkout shows live multi-carrier rates from Shippo. Any error falls back to the flat rate automatically.</p>
    </div>

    {{-- ============ Shipping (UPS) ============ --}}
    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <div class="mb-1 flex items-center gap-2">
            <h2 class="font-bold text-white">Shipping — UPS</h2>
            <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-[11px] font-bold text-amber-400">Live rates · tracking · labels</span>
        </div>
        <p class="mb-4 text-sm text-slate-400">Get credentials from
            <a href="https://developer.ups.com" target="_blank" rel="noopener" class="font-semibold text-slate-200">developer.ups.com → Apps</a>
            (enable Rating, Tracking, Shipping &amp; Address Validation). Test on <b>sandbox</b> first, then switch to production.</p>

        @php $upsOn = old('ups_enabled', $settings['ups_enabled'] ?? '0') === '1'; @endphp
        <label class="flex items-center gap-2.5">
            <span class="block text-sm font-medium text-slate-200">Enable UPS integration</span>
            <input type="hidden" name="ups_enabled" value="0">
            <input type="checkbox" name="ups_enabled" value="1" {{ $upsOn ? 'checked' : '' }} class="rounded border-white/10 text-slate-200">
        </label>
        <p class="mt-1 text-xs text-slate-400">When off (or credentials blank), checkout uses the flat shipping rate — nothing breaks.</p>

        <div class="mt-5 grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Environment</label>
                <select name="ups_env" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 px-3 py-2.5 text-sm outline-none focus:border-slate-400">
                    <option value="sandbox" @selected(old('ups_env', $settings['ups_env'] ?? 'sandbox') === 'sandbox')>Sandbox (test / CIE)</option>
                    <option value="production" @selected(old('ups_env', $settings['ups_env'] ?? '') === 'production')>Production (live)</option>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">UPS Account # <span class="font-normal text-slate-400">(for labels &amp; negotiated rates)</span></label>
                <input name="ups_account" value="{{ old('ups_account', $settings['ups_account'] ?? '') }}" placeholder="A1B2C3" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Client ID</label>
                <input name="ups_client_id" value="{{ old('ups_client_id', $settings['ups_client_id'] ?? '') }}" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 px-3 py-2.5 font-mono text-xs outline-none focus:border-slate-400">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Client Secret</label>
                <input name="ups_client_secret" value="{{ old('ups_client_secret', $settings['ups_client_secret'] ?? '') }}" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 px-3 py-2.5 font-mono text-xs outline-none focus:border-slate-400">
            </div>
        </div>

        <h3 class="mt-6 mb-3 text-sm font-bold uppercase tracking-wide text-slate-400">Ship-from address <span class="font-normal normal-case text-slate-500">(rating &amp; label origin)</span></h3>
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Business name</label>
                <input name="ups_ship_from_name" value="{{ old('ups_ship_from_name', $settings['ups_ship_from_name'] ?? '') }}" placeholder="Texpert Deals" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Street address</label>
                <input name="ups_ship_from_address" value="{{ old('ups_ship_from_address', $settings['ups_ship_from_address'] ?? '') }}" placeholder="2990 Congressman Ln" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">City</label>
                <input name="ups_ship_from_city" value="{{ old('ups_ship_from_city', $settings['ups_ship_from_city'] ?? '') }}" placeholder="Dallas" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400">
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">State</label>
                    <input name="ups_ship_from_state" value="{{ old('ups_ship_from_state', $settings['ups_ship_from_state'] ?? '') }}" placeholder="TX" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">ZIP</label>
                    <input name="ups_ship_from_zip" value="{{ old('ups_ship_from_zip', $settings['ups_ship_from_zip'] ?? '') }}" placeholder="75220" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-200">Country</label>
                    <input name="ups_ship_from_country" value="{{ old('ups_ship_from_country', $settings['ups_ship_from_country'] ?? 'US') }}" placeholder="US" maxlength="2" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400">
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <h2 class="mb-1 font-bold text-white">Logos</h2>
        <p class="mb-4 text-sm text-slate-400">Upload your logo for the site header and footer. Leave empty to use the default text logo.</p>
        <div class="grid gap-6 sm:grid-cols-2">

            {{-- header logo --}}
            <div data-image-field>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Header logo</label>
                <div class="flex items-center gap-4">
                    <span class="grid h-16 w-28 shrink-0 place-items-center overflow-hidden rounded-xl border border-white/10 bg-white/5">
                        <img data-image-preview src="{{ $settings['header_logo'] ? media($settings['header_logo']) : '' }}" class="{{ $settings['header_logo'] ? '' : 'hidden' }} max-h-full max-w-full object-contain" alt="">
                    </span>
                    <input type="file" name="header_logo_file" accept="image/*" data-image-input
                           class="block w-full text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-200 hover:file:bg-white/20">
                </div>
                <input name="header_logo" value="{{ old('header_logo', $settings['header_logo']) }}" placeholder="…or paste a logo URL" class="mt-3 w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>

            {{-- footer logo --}}
            <div data-image-field>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Footer logo</label>
                <div class="flex items-center gap-4">
                    <span class="grid h-16 w-28 shrink-0 place-items-center overflow-hidden rounded-xl border border-white/10 bg-slate-950">
                        <img data-image-preview src="{{ $settings['footer_logo'] ? media($settings['footer_logo']) : '' }}" class="{{ $settings['footer_logo'] ? '' : 'hidden' }} max-h-full max-w-full object-contain" alt="">
                    </span>
                    <input type="file" name="footer_logo_file" accept="image/*" data-image-input
                           class="block w-full text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-200 hover:file:bg-white/20">
                </div>
                <input name="footer_logo" value="{{ old('footer_logo', $settings['footer_logo']) }}" placeholder="…or paste a logo URL" class="mt-3 w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>

            {{-- favicon --}}
            <div data-image-field>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Favicon <span class="font-normal text-slate-400">(browser tab icon)</span></label>
                <div class="flex items-center gap-4">
                    <span class="grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-xl border border-white/10 bg-white/5">
                        <img data-image-preview src="{{ $settings['favicon'] ? media($settings['favicon']) : '' }}" class="{{ $settings['favicon'] ? '' : 'hidden' }} max-h-10 max-w-10 object-contain" alt="">
                    </span>
                    <input type="file" name="favicon_file" accept=".png,.ico,.svg,image/*" data-image-input
                           class="block w-full text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-200 hover:file:bg-white/20">
                </div>
                <input name="favicon" value="{{ old('favicon', $settings['favicon']) }}" placeholder="…or paste a favicon URL" class="mt-3 w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>
        </div>
        <p class="mt-3 text-xs text-slate-400">Logos: PNG (transparent), JPG, WEBP or SVG · max 2MB. Favicon: square PNG/ICO/SVG (32×32 or 512×512) · max 1MB.</p>
    </div>

    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm" data-theme-section>
        <h2 class="mb-1 font-bold text-white">Theme colors</h2>
        <p class="mb-4 text-sm text-slate-400">These re-colour the whole website instantly — buttons, links, highlights and dark sections.</p>

        @php
            $primary = old('color_primary', $settings['color_primary'] ?: \App\Support\Theme::DEFAULT_PRIMARY);
            $secondary = old('color_secondary', $settings['color_secondary'] ?: \App\Support\Theme::DEFAULT_SECONDARY);
        @endphp

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Primary (accent) color</label>
                <div class="flex items-center gap-3">
                    <input type="color" value="{{ $primary }}" data-color-picker="color_primary" class="h-11 w-14 shrink-0 cursor-pointer rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 p-1">
                    <input name="color_primary" value="{{ $primary }}" data-color-text="color_primary" placeholder="#f59e0b"
                           class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 font-mono text-sm uppercase outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                </div>
                <p class="mt-1 text-xs text-slate-400">Buttons, links, highlights. Default <button type="button" data-color-reset="color_primary" data-default="{{ \App\Support\Theme::DEFAULT_PRIMARY }}" class="font-semibold text-slate-200">#F59E0B</button>.</p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Secondary (dark) color</label>
                <div class="flex items-center gap-3">
                    <input type="color" value="{{ $secondary }}" data-color-picker="color_secondary" class="h-11 w-14 shrink-0 cursor-pointer rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 p-1">
                    <input name="color_secondary" value="{{ $secondary }}" data-color-text="color_secondary" placeholder="#0f172a"
                           class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 font-mono text-sm uppercase outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                </div>
                <p class="mt-1 text-xs text-slate-400">Header nav, footer, dark buttons. Default <button type="button" data-color-reset="color_secondary" data-default="{{ \App\Support\Theme::DEFAULT_SECONDARY }}" class="font-semibold text-slate-200">#0F172A</button>.</p>
            </div>
        </div>

        <div class="mt-5">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-400">Quick presets</p>
            <div class="flex flex-wrap gap-2">
                @foreach ([['Amber','#f59e0b','#0f172a'],['Blue','#2563eb','#0f172a'],['Emerald','#059669','#0f172a'],['Rose','#e11d48','#1f2937'],['Violet','#7c3aed','#1e1b4b'],['Orange','#ea580c','#1c1917']] as [$name,$p,$s])
                    <button type="button" data-color-preset data-primary="{{ $p }}" data-secondary="{{ $s }}"
                            class="flex items-center gap-2 rounded-full border border-white/10 px-3 py-1.5 text-xs font-semibold text-slate-300 hover:border-slate-400">
                        <span class="h-3.5 w-3.5 rounded-full" style="background:{{ $p }}"></span>{{ $name }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <h2 class="mb-4 font-bold text-white">Homepage hero</h2>
        <div class="space-y-4">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Hero badge</label>
                <input name="hero_badge" value="{{ old('hero_badge', $settings['hero_badge']) }}" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Hero title *</label>
                <input name="hero_title" value="{{ old('hero_title', $settings['hero_title']) }}" required class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Hero subtitle</label>
                <textarea name="hero_subtitle" rows="2" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">{{ old('hero_subtitle', $settings['hero_subtitle']) }}</textarea>
            </div>
            <div>
                @php $ov = old('hero_overlay', ($settings['hero_overlay'] === null || $settings['hero_overlay'] === '') ? 40 : $settings['hero_overlay']); @endphp
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Hero image darkness <span class="font-normal text-slate-400">(overlay on the background image)</span></label>
                <div class="flex items-center gap-3">
                    <input type="range" name="hero_overlay" min="0" max="90" step="5" value="{{ $ov }}"
                           oninput="this.nextElementSibling.textContent = this.value + '%'"
                           class="h-2 w-full cursor-pointer accent-amber-500">
                    <span class="w-12 shrink-0 text-right text-sm font-semibold text-slate-200">{{ $ov }}%</span>
                </div>
                <p class="mt-1 text-xs text-slate-400">0% = no darkness (bright image); higher = darker, helps text readability. Applies to all hero slides.</p>
            </div>

            <div>
                @php $dur = old('hero_slider_duration', ($settings['hero_slider_duration'] === null || $settings['hero_slider_duration'] === '') ? 5 : $settings['hero_slider_duration']); @endphp
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Slider duration <span class="font-normal text-slate-400">(seconds each slide shows before auto-advancing)</span></label>
                <div class="flex items-center gap-3">
                    <input type="range" name="hero_slider_duration" min="2" max="30" step="1" value="{{ $dur }}"
                           oninput="this.nextElementSibling.textContent = this.value + 's'"
                           class="h-2 w-full cursor-pointer accent-amber-500">
                    <span class="w-12 shrink-0 text-right text-sm font-semibold text-slate-200">{{ $dur }}s</span>
                </div>
                <p class="mt-1 text-xs text-slate-400">How long each hero slide stays before moving to the next one. Auto-advance pauses while the mouse is over the slider.</p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <h2 class="mb-1 font-bold text-white">Home &ldquo;Why shop with us&rdquo; band</h2>
        <p class="mb-4 text-sm text-slate-400">The five-card section on the home page: its background picture and the icon on each card.</p>

        <div class="max-w-xl">
            <div data-image-field>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Background image</label>
                <div class="flex items-center gap-4">
                    <span class="grid h-16 w-28 shrink-0 place-items-center overflow-hidden rounded-xl border border-white/10 bg-white/5">
                        <img data-image-preview src="{{ $settings['why_bg'] ? media($settings['why_bg']) : '' }}" class="{{ $settings['why_bg'] ? '' : 'hidden' }} max-h-full max-w-full object-cover" alt="">
                    </span>
                    <input type="file" name="why_bg_file" accept="image/*" data-image-input
                           class="block w-full text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-200 hover:file:bg-white/20">
                </div>
                <input name="why_bg" value="{{ old('why_bg', $settings['why_bg']) }}" placeholder="…or paste an image URL" class="mt-3 w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                <p class="mt-1 text-xs text-slate-400">Leave empty for the plain light-blue background. A wide photo (about 1920&times;600) works best.</p>
            </div>
        </div>

        @php
            $whyIconChoices = ['shield-check' => 'Shield (tick)', 'shield' => 'Shield', 'tag' => 'Price tag', 'box' => 'Box',
                'truck' => 'Truck', 'headset' => 'Headset', 'star' => 'Star', 'clock' => 'Clock', 'check' => 'Tick',
                'lock' => 'Lock', 'refresh' => 'Returns', 'tools' => 'Tools', 'parts' => 'Parts', 'chat' => 'Chat',
                'phone' => 'Phone', 'mail' => 'Mail', 'gauge' => 'Gauge', 'cart' => 'Cart', 'ac' => 'Air conditioner'];
            $whyCardNames = [1 => 'Trusted Brands', 2 => 'Competitive Prices', 3 => 'Reliable Inventory', 4 => 'Fast Fulfillment', 5 => 'Responsive Support'];
            $whyDefaults = [1 => 'shield-check', 2 => 'tag', 3 => 'box', 4 => 'truck', 5 => 'headset'];
        @endphp
        <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ($whyCardNames as $n => $cardName)
                @php
                    $current = old('why_icon_'.$n, $settings['why_icon_'.$n] ?: $whyDefaults[$n]);
                    $uploaded = old('why_img_'.$n, $settings['why_img_'.$n] ?? null);
                @endphp
                <div class="rounded-xl border border-white/10 bg-slate-950/30 p-3">
                    <p class="mb-2 text-sm font-semibold text-slate-200">{{ $cardName }}</p>

                    <div class="flex items-center gap-2">
                        <span class="grid h-9 w-9 shrink-0 place-items-center overflow-hidden rounded-full {{ $uploaded ? '' : 'bg-amber-400 text-slate-900' }}">
                            @if ($uploaded)
                                <img data-image-preview src="{{ media($uploaded) }}" class="h-9 w-9 object-contain" alt="">
                            @else
                                <x-icon name="{{ $current }}" class="h-5 w-5" solid />
                            @endif
                        </span>
                        <select name="why_icon_{{ $n }}" class="w-full rounded-lg border border-white/10 bg-slate-950/50 px-2.5 py-2 text-sm text-slate-100 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                            @foreach ($whyIconChoices as $value => $label)
                                <option value="{{ $value }}" @selected($current === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div data-image-field class="mt-3">
                        <input type="file" name="why_img_{{ $n }}_file" accept="image/*,.svg" data-image-input
                               class="block w-full text-xs text-slate-400 file:mr-2 file:rounded-lg file:border-0 file:bg-white/10 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-200 hover:file:bg-white/20">
                        <input name="why_img_{{ $n }}" value="{{ $uploaded }}" placeholder="…or paste an icon URL"
                               class="mt-2 w-full rounded-lg border border-white/10 bg-slate-950/50 px-2.5 py-2 text-xs text-slate-100 placeholder:text-slate-500 outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                    </div>
                </div>
            @endforeach
        </div>
        <p class="mt-3 text-xs text-slate-400">
            Pick a built-in icon, or upload your own picture for that card — an uploaded picture replaces the icon and its yellow circle.
            To go back to the icon, clear the URL box under that card. PNG or SVG with a transparent background works best (about 80&times;80).
        </p>
    </div>

    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <h2 class="mb-4 font-bold text-white">Contact info</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Phone</label>
                <input name="contact_phone" value="{{ old('contact_phone', $settings['contact_phone']) }}" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Toll-Free Number</label>
                <input name="toll_free" value="{{ old('toll_free', $settings['toll_free']) }}" placeholder="(945) 427-9519" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                <p class="mt-1 text-xs text-slate-400">Shown site-wide next to the phone number.</p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Newsletter discount code</label>
                <input name="newsletter_code" value="{{ old('newsletter_code', $settings['newsletter_code']) }}" placeholder="WELCOME5" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                <p class="mt-1 text-xs text-slate-400">Emailed to new "Join our list" subscribers ($5 off).</p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Email</label>
                <input name="contact_email" value="{{ old('contact_email', $settings['contact_email']) }}" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Address</label>
                <input name="contact_address" value="{{ old('contact_address', $settings['contact_address']) }}" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-slate-200">WhatsApp number</label>
                <input name="whatsapp" value="{{ old('whatsapp', $settings['whatsapp']) }}" placeholder="+1 (254) 358-0017" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                <p class="mt-1 text-xs text-slate-400">Shown as a floating chat button on the storefront.</p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <h2 class="mb-1 font-bold text-white">Security — reCAPTCHA</h2>
        <p class="mb-4 text-xs text-slate-400">Stops bot spam on the Contact, Part Request &amp; Bulk Order forms. Get free keys at <span class="text-slate-300">google.com/recaptcha/admin</span> (choose <strong>reCAPTCHA v2 → “I'm not a robot” Checkbox</strong>).</p>
        <label class="mb-4 flex items-center gap-2 text-sm font-medium text-slate-200">
            <input type="hidden" name="recaptcha_enabled" value="0">
            <input type="checkbox" name="recaptcha_enabled" value="1" @checked(old('recaptcha_enabled', $settings['recaptcha_enabled'])) class="rounded border-white/10 text-slate-200"> Enable reCAPTCHA on forms
        </label>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Site key</label>
                <input name="recaptcha_site_key" value="{{ old('recaptcha_site_key', $settings['recaptcha_site_key']) }}" placeholder="6Lc…" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Secret key</label>
                <input name="recaptcha_secret_key" value="{{ old('recaptcha_secret_key', $settings['recaptcha_secret_key']) }}" placeholder="6Lc…" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <h2 class="mb-1 font-bold text-white">Chat Assistant (AI)</h2>
        <p class="mb-4 text-xs text-slate-400">A floating AI chat bubble that answers product, order &amp; shipping questions and emails complex requests to your team. Get a <span class="text-slate-300">free Groq API key</span> from <span class="text-slate-300">console.groq.com/keys</span>.</p>
        <label class="mb-4 flex items-center gap-2 text-sm font-medium text-slate-200">
            <input type="hidden" name="chatbot_enabled" value="0">
            <input type="checkbox" name="chatbot_enabled" value="1" @checked(old('chatbot_enabled', $settings['chatbot_enabled'])) class="rounded border-white/10 text-slate-200"> Enable chat assistant on the storefront
        </label>
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Groq API key</label>
                <input name="chatbot_api_key" value="{{ old('chatbot_api_key', $settings['chatbot_api_key']) }}" placeholder="gsk_…" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Model</label>
                <select name="chatbot_model" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                    @php $cm = old('chatbot_model', $settings['chatbot_model'] ?: 'llama-3.3-70b-versatile'); @endphp
                    <option value="llama-3.3-70b-versatile" @selected($cm === 'llama-3.3-70b-versatile')>Llama 3.3 70B — best quality (recommended)</option>
                    <option value="llama-3.1-8b-instant" @selected($cm === 'llama-3.1-8b-instant')>Llama 3.1 8B — fastest</option>
                    <option value="openai/gpt-oss-20b" @selected($cm === 'openai/gpt-oss-20b')>GPT-OSS 20B</option>
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Greeting message</label>
                <input name="chatbot_greeting" value="{{ old('chatbot_greeting', $settings['chatbot_greeting']) }}" placeholder="Hi! How can I help you today?" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <h2 class="mb-4 font-bold text-white">Social links</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Facebook URL</label>
                <input name="social_facebook" value="{{ old('social_facebook', $settings['social_facebook']) }}" placeholder="https://www.facebook.com/…" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Instagram URL</label>
                <input name="social_instagram" value="{{ old('social_instagram', $settings['social_instagram']) }}" placeholder="https://www.instagram.com/…" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">LinkedIn URL</label>
                <input name="social_linkedin" value="{{ old('social_linkedin', $settings['social_linkedin']) }}" placeholder="https://www.linkedin.com/company/…" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>
        </div>
    </div>

    {{-- ============ Marketing & Tracking (Ads) ============ --}}
    <div class="rounded-2xl border border-white/10 bg-slate-900/70 backdrop-blur p-6 shadow-sm">
        <h2 class="mb-1 font-bold text-white">Marketing &amp; Tracking</h2>
        <p class="mb-5 text-sm text-slate-400">Paste your IDs to run ads &amp; analytics. Conversion events (view, add-to-cart, checkout, purchase) fire automatically.</p>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Google Analytics 4 ID</label>
                <input name="ga4_id" value="{{ old('ga4_id', $settings['ga4_id']) }}" placeholder="G-XXXXXXXXXX" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Google Tag Manager ID</label>
                <input name="gtm_id" value="{{ old('gtm_id', $settings['gtm_id']) }}" placeholder="GTM-XXXXXXX" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Google Ads ID</label>
                <input name="google_ads_id" value="{{ old('google_ads_id', $settings['google_ads_id']) }}" placeholder="AW-XXXXXXXXX" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Google Ads purchase conversion label</label>
                <input name="google_ads_purchase_label" value="{{ old('google_ads_purchase_label', $settings['google_ads_purchase_label']) }}" placeholder="AbC-D_efG…" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                <p class="mt-1 text-xs text-slate-400">Sent as <code class="text-slate-300">AW-ID/label</code> on purchase.</p>
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Meta (Facebook) Pixel ID</label>
                <input name="meta_pixel_id" value="{{ old('meta_pixel_id', $settings['meta_pixel_id']) }}" placeholder="1234567890" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">TikTok Pixel ID</label>
                <input name="tiktok_pixel_id" value="{{ old('tiktok_pixel_id', $settings['tiktok_pixel_id']) }}" placeholder="C XXXXXXXXXXXX" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-200">Microsoft Clarity Project ID</label>
                <input name="clarity_id" value="{{ old('clarity_id', $settings['clarity_id']) }}" placeholder="abcdef1234" class="w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
                <p class="mt-1 text-xs text-slate-400">Heatmaps &amp; session recordings. Find it in your <a href="https://clarity.microsoft.com/" target="_blank" rel="noopener" class="text-slate-300 underline">Clarity</a> project → Settings → Overview.</p>
            </div>
        </div>

        {{-- social share image --}}
        <div class="mt-4" data-image-field>
            <label class="mb-1.5 block text-sm font-medium text-slate-200">Social share image <span class="font-normal text-slate-400">(Open Graph — shown when your links are shared / in ads)</span></label>
            <div class="flex items-center gap-4">
                <span class="grid h-16 w-28 shrink-0 place-items-center overflow-hidden rounded-xl border border-white/10 bg-white/5">
                    <img data-image-preview src="{{ $settings['og_image'] ? media($settings['og_image']) : '' }}" class="{{ $settings['og_image'] ? '' : 'hidden' }} max-h-full max-w-full object-contain" alt="">
                </span>
                <input type="file" name="og_image_file" accept="image/*" data-image-input
                       class="block w-full text-sm text-slate-400 file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-slate-200 hover:file:bg-white/20">
            </div>
            <input name="og_image" value="{{ old('og_image', $settings['og_image']) }}" placeholder="…or paste an image URL" class="mt-3 w-full rounded-lg border border-white/10 bg-slate-950/50 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-500/30">
            <p class="mt-1 text-xs text-slate-400">Recommended 1200×630px.</p>
        </div>

        {{-- custom head code --}}
        <div class="mt-4 rounded-xl border border-amber-400/20 bg-amber-400/5 p-4">
            <label class="mb-1.5 block text-sm font-bold text-slate-100">&lt;head&gt; code <span class="font-normal text-slate-400">— goes inside &lt;head&gt; (scripts, meta, verification tags)</span></label>
            <textarea name="head_code" rows="8" placeholder="&lt;script&gt;…&lt;/script&gt;, &lt;meta name=&quot;…&quot;&gt;, or any tracking / verification snippet" class="w-full rounded-lg border border-white/10 bg-slate-950/60 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 font-mono text-xs leading-relaxed outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-500/20">{{ old('head_code', $settings['head_code']) }}</textarea>
            <p class="mt-1.5 text-xs text-slate-400">Injected raw into every page's &lt;head&gt;.</p>
        </div>

        {{-- custom body code --}}
        <div class="mt-4 rounded-xl border border-amber-400/20 bg-amber-400/5 p-4">
            <label class="mb-1.5 block text-sm font-bold text-slate-100">&lt;body&gt; code <span class="font-normal text-slate-400">— goes right after the opening &lt;body&gt; tag (e.g. GTM noscript, chat widgets)</span></label>
            <textarea name="body_code" rows="8" placeholder="&lt;noscript&gt;…&lt;/noscript&gt;, chat widget script, or any snippet that belongs in the body" class="w-full rounded-lg border border-white/10 bg-slate-950/60 text-slate-100 placeholder:text-slate-500 px-3 py-2.5 font-mono text-xs leading-relaxed outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-500/20">{{ old('body_code', $settings['body_code']) }}</textarea>
            <p class="mt-1.5 text-xs text-slate-400">Injected raw at the top of every page's &lt;body&gt;. Paste exactly as given by the provider. Only add code from sources you trust.</p>
        </div>
    </div>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('admin.dashboard') }}" class="rounded-lg border border-white/10 px-5 py-2.5 text-sm font-semibold text-slate-300 hover:bg-white/5">Cancel</a>
        <button class="rounded-lg bg-white px-6 py-2.5 text-sm font-bold text-slate-900 shadow-sm transition hover:bg-slate-200">Save Settings</button>
    </div>
</form>

@push('scripts')
<script>
(function () {
    const section = document.querySelector('[data-theme-section]');
    if (!section) return;
    const isHex = (v) => /^#[0-9a-fA-F]{6}$/.test(v);

    function setColor(key, value) {
        if (!isHex(value)) return;
        const picker = section.querySelector(`[data-color-picker="${key}"]`);
        const text = section.querySelector(`[data-color-text="${key}"]`);
        if (picker) picker.value = value;
        if (text) text.value = value.toLowerCase();
    }

    // picker -> text
    section.querySelectorAll('[data-color-picker]').forEach((p) => {
        p.addEventListener('input', () => setColor(p.dataset.colorPicker, p.value));
    });
    // text -> picker
    section.querySelectorAll('[data-color-text]').forEach((t) => {
        t.addEventListener('input', () => { if (isHex(t.value)) setColor(t.dataset.colorText, t.value); });
    });
    // presets
    section.querySelectorAll('[data-color-preset]').forEach((b) => {
        b.addEventListener('click', () => {
            setColor('color_primary', b.dataset.primary);
            setColor('color_secondary', b.dataset.secondary);
        });
    });
    // reset to default
    section.querySelectorAll('[data-color-reset]').forEach((b) => {
        b.addEventListener('click', () => setColor(b.dataset.colorReset, b.dataset.default));
    });
})();
</script>
@endpush

@push('scripts')
<script>
(function () {
    const section = document.querySelector('[data-stripe-section]');
    if (!section) return;

    // show / hide secret keys
    section.querySelectorAll('[data-secret-toggle]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = btn.parentElement.querySelector('[data-secret-input]');
            if (!input) return;
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.textContent = show ? 'Hide' : 'Show';
        });
    });

    // highlight active mode + dim the inactive key group
    const badge = section.querySelector('[data-mode-badge]');
    function syncMode() {
        const checked = section.querySelector('[data-mode-radio]:checked');
        const mode = checked ? checked.value : 'test';
        if (badge) {
            badge.textContent = mode === 'live' ? 'LIVE' : 'TEST';
            badge.className = 'rounded-full px-2 py-0.5 text-[11px] font-bold ' +
                (mode === 'live' ? 'bg-emerald-500/15 text-emerald-300' : 'bg-white/10 text-slate-200');
        }
        section.querySelectorAll('[data-keys-group]').forEach((g) => {
            g.classList.toggle('opacity-50', g.dataset.keysGroup !== mode);
        });
    }
    section.querySelectorAll('[data-mode-radio]').forEach((r) => r.addEventListener('change', syncMode));
    syncMode();

    // copy webhook URL
    const copyBtn = section.querySelector('[data-copy-webhook]');
    if (copyBtn) {
        copyBtn.addEventListener('click', () => {
            const url = section.querySelector('[data-webhook-url]');
            if (!url) return;
            navigator.clipboard?.writeText(url.value).then(() => {
                const t = copyBtn.textContent;
                copyBtn.textContent = 'Copied!';
                setTimeout(() => (copyBtn.textContent = t), 1500);
            });
        });
    }
})();
</script>
@endpush

@endsection
