@extends('layouts.app')

@section('title', 'Secure Checkout — Texpert Deals')
@section('robots', 'noindex, follow')

{{-- keep the header out of the way while paying: no sticky nav --}}
@section('plain_header', true)

@php
    use App\Models\Setting;

    $supportPhone = Setting::get('contact_phone');
    $supportEmail = Setting::get('contact_email');

    // free either because shipping charges are off, or a free-shipping item is in the cart
    $freeShippingCart = ! \App\Support\Cart::shippingEnabled() || \App\Support\Cart::hasFreeShipping();

    // Floating-label fields: the label sits inside the box and lifts once the field
    // has something in it (placeholder=" " makes :placeholder-shown the empty state).
    $fieldClass = fn ($field) => 'peer w-full rounded-md border bg-white px-3 pb-2 pt-6 text-sm text-slate-900 outline-none transition placeholder:text-transparent focus:border-slate-900 focus:ring-1 focus:ring-slate-900 '
        .($errors->has($field) ? 'border-rose-400' : 'border-slate-300');

    $labelClass = 'pointer-events-none absolute left-3 top-2 text-[11px] text-slate-500 transition-all '
        .'peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-sm '
        .'peer-focus:top-2 peer-focus:translate-y-0 peer-focus:text-[11px]';

    // selects always show their small label (there is no empty "placeholder" state)
    $selectClass = 'w-full appearance-none rounded-md border border-slate-300 bg-white px-3 pb-2 pt-6 text-sm text-slate-900 outline-none transition focus:border-slate-900 focus:ring-1 focus:ring-slate-900';
    $staticLabelClass = 'pointer-events-none absolute left-3 top-2 text-[11px] text-slate-500';

    $states = [
        'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas', 'CA' => 'California',
        'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware', 'DC' => 'District of Columbia',
        'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois',
        'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana',
        'ME' => 'Maine', 'MD' => 'Maryland', 'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota',
        'MS' => 'Mississippi', 'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
        'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
        'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma', 'OR' => 'Oregon',
        'PA' => 'Pennsylvania', 'PR' => 'Puerto Rico', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
        'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah', 'VT' => 'Vermont',
        'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming',
    ];

    // the saved value may be a code ("TX") or a full name ("Texas")
    $stateValue = (string) old('state', $customer?->state);
    $selectedState = '';
    foreach ($states as $code => $label) {
        if (strcasecmp($stateValue, $code) === 0 || strcasecmp($stateValue, $label) === 0) {
            $selectedState = $code;
            break;
        }
    }
@endphp

@section('content')

{{-- ===== validation popup (filled by JS, also used for card errors) ===== --}}
<div data-alert hidden class="fixed inset-x-0 top-20 z-[60] mx-auto w-[calc(100%-2rem)] max-w-md lg:top-28">
    <div class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-white p-4 shadow-2xl shadow-rose-900/10">
        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-rose-100 text-rose-600">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 8v5M12 17h.01"/><circle cx="12" cy="12" r="9"/></svg>
        </span>
        <div class="min-w-0 flex-1">
            <p data-alert-title class="text-sm font-bold text-slate-900">Please check your details</p>
            <ul data-alert-list class="mt-1 list-inside list-disc space-y-0.5 text-sm text-slate-600"></ul>
        </div>
        <button type="button" data-alert-close class="shrink-0 rounded-lg p-1 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" aria-label="Close">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
        </button>
    </div>
</div>

<div class="mx-auto grid max-w-6xl lg:min-h-[70vh] lg:grid-cols-2">

    {{-- ===================== left: the form ===================== --}}
    <div class="px-4 py-8 sm:px-6 lg:border-r lg:border-slate-200 lg:py-12 lg:pr-12">
        <div class="mx-auto max-w-lg lg:ml-auto lg:mr-0">

            <div class="flex items-center justify-between gap-3">
                <a href="{{ route('cart.index') }}" class="text-sm font-medium text-slate-500 hover:text-amber-600">← Back to cart</a>
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500">
                    <x-icon name="lock" class="h-3.5 w-3.5 text-emerald-500" /> Secure checkout
                </span>
            </div>

            {{-- server-side errors (also shown as a popup by JS) --}}
            @if ($errors->any())
                <div data-server-errors data-messages="{{ json_encode($errors->all()) }}"
                     class="mt-5 rounded-md border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <p class="font-semibold">Please fix the following:</p>
                    <ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ route('checkout.store') }}" novalidate data-checkout-form class="mt-6 space-y-8">
                @csrf

                {{-- ===== contact ===== --}}
                <section>
                    <h2 class="text-lg font-bold text-slate-900">Contact</h2>

                    <div class="mt-3 space-y-3">
                        <div class="relative">
                            <input id="f-email" name="email" type="email" autocomplete="email" placeholder=" "
                                   value="{{ old('email', $customer?->email) }}" data-required data-email data-label="Email"
                                   class="{{ $fieldClass('email') }}">
                            <label for="f-email" class="{{ $labelClass }}">Email</label>
                        </div>
                        <p data-error-for="email" class="hidden text-xs font-medium text-rose-600"></p>
                        @error('email')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror

                        <label class="flex cursor-pointer items-center gap-2.5 text-sm text-slate-700">
                            <input type="checkbox" name="subscribe" value="1" @checked(old('subscribe'))
                                   class="h-4 w-4 rounded border-slate-300 text-slate-900 focus:ring-slate-900">
                            Email me with news and offers
                        </label>

                        <div>
                            <label for="f-phone" class="mb-1 block text-[11px] text-slate-500">Phone</label>
                            {{-- country picker with flags; the flag follows the number typed --}}
                            <input id="f-phone" name="phone" type="tel" autocomplete="tel"
                                   value="{{ old('phone', $customer?->phone) }}" data-required data-phone data-phone-intl data-label="Phone"
                                   class="w-full rounded-md border bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-900 focus:ring-1 focus:ring-slate-900 {{ $errors->has('phone') ? 'border-rose-400' : 'border-slate-300' }}">
                        </div>
                        <p data-error-for="phone" class="hidden text-xs font-medium text-rose-600"></p>
                        @error('phone')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                        <p class="text-xs text-slate-500">Used for order updates and by the carrier for delivery.</p>
                    </div>
                </section>

                {{-- ===== delivery ===== --}}
                <section>
                    <h2 class="text-lg font-bold text-slate-900">Delivery</h2>

                    <div class="mt-3 space-y-3">
                        <div class="relative">
                            <select disabled class="{{ $selectClass }} cursor-not-allowed bg-slate-50 text-slate-500">
                                <option>United States</option>
                            </select>
                            <label class="{{ $staticLabelClass }}">Country/Region</label>
                        </div>

                        <div class="relative">
                            <input id="f-name" name="name" autocomplete="name" placeholder=" "
                                   value="{{ old('name', $customer?->name) }}" data-required data-label="Full name"
                                   class="{{ $fieldClass('name') }}">
                            <label for="f-name" class="{{ $labelClass }}">Full name</label>
                        </div>
                        <p data-error-for="name" class="hidden text-xs font-medium text-rose-600"></p>
                        @error('name')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror

                        <div class="relative">
                            <input id="f-company" name="company" autocomplete="organization" placeholder=" "
                                   value="{{ old('company', $customer?->company) }}" class="{{ $fieldClass('company') }}">
                            <label for="f-company" class="{{ $labelClass }}">Company (optional)</label>
                        </div>
                        @error('company')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror

                        <div class="relative">
                            <input id="f-address" name="address" autocomplete="address-line1" placeholder=" "
                                   value="{{ old('address', $customer?->address) }}" data-required data-label="Address"
                                   class="{{ $fieldClass('address') }}">
                            <label for="f-address" class="{{ $labelClass }}">Address</label>
                        </div>
                        <p data-error-for="address" class="hidden text-xs font-medium text-rose-600"></p>
                        @error('address')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div>
                                <div class="relative">
                                    <input id="f-city" name="city" autocomplete="address-level2" placeholder=" "
                                           value="{{ old('city', $customer?->city) }}" data-required data-label="City"
                                           class="{{ $fieldClass('city') }}">
                                    <label for="f-city" class="{{ $labelClass }}">City</label>
                                </div>
                                <p data-error-for="city" class="hidden text-xs font-medium text-rose-600"></p>
                                @error('city')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <div class="relative">
                                    <select id="f-state" name="state" autocomplete="address-level1" data-required data-label="State"
                                            class="{{ $selectClass }} {{ $errors->has('state') ? 'border-rose-400' : '' }}">
                                        <option value=""></option>
                                        @foreach ($states as $code => $label)
                                            <option value="{{ $code }}" @selected($selectedState === $code)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    <label for="f-state" class="{{ $staticLabelClass }}">State</label>
                                </div>
                                <p data-error-for="state" class="hidden text-xs font-medium text-rose-600"></p>
                                @error('state')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <div class="relative">
                                    <input id="f-zip" name="zip" inputmode="numeric" autocomplete="postal-code" placeholder=" "
                                           value="{{ old('zip', $customer?->zip) }}" data-required data-zip data-label="ZIP code"
                                           class="{{ $fieldClass('zip') }}">
                                    <label for="f-zip" class="{{ $labelClass }}">ZIP code</label>
                                </div>
                                <p data-error-for="zip" class="hidden text-xs font-medium text-rose-600"></p>
                                @error('zip')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div class="relative">
                            <textarea id="f-notes" name="notes" rows="2" placeholder=" " class="{{ $fieldClass('notes') }} pt-6">{{ old('notes') }}</textarea>
                            <label for="f-notes" class="{{ $labelClass }}">Order notes / PO number (optional)</label>
                        </div>
                        @error('notes')<p class="text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </section>

                {{-- ===== shipping method ===== --}}
                <section data-ship>
                    <h2 class="text-lg font-bold text-slate-900">Shipping method</h2>

                    {{-- carries the chosen service code to the server (recomputed there) --}}
                    <input type="hidden" name="ship_service" value="FLAT" data-ship-code>
                    {{-- 1 when no carrier can rate this order: placed now, shipping quoted after --}}
                    <input type="hidden" name="ship_quote" value="{{ old('ship_quote', '0') }}" data-ship-quote>
                    @error('ship_service')<p class="mt-3 rounded-md border border-rose-300 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">{{ $message }}</p>@enderror

                    {{-- carrier address suggestion, if any --}}
                    <div data-ship-suggest hidden class="mt-3 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        <span class="font-semibold">Did you mean:</span> <span data-ship-suggest-text></span>
                        <button type="button" data-ship-suggest-apply class="ml-1 font-bold underline">Use this</button>
                    </div>

                    <div data-ship-options class="mt-3 space-y-2">
                        @if ($freeShippingCart)
                            <p class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm font-semibold text-emerald-600">Free shipping on this order.</p>
                        @else
                            <p class="rounded-md border border-slate-200 bg-slate-50 px-4 py-3.5 text-sm text-slate-500">
                                Enter your shipping address to view available shipping methods.
                            </p>
                        @endif
                    </div>
                    <p data-ship-loading hidden class="mt-3 flex items-center gap-2 text-sm text-slate-500">
                        <svg class="h-4 w-4 animate-spin text-slate-400" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4z"/></svg>
                        Fetching live rates…
                    </p>
                </section>

                {{-- ===== payment ===== --}}
                <section>
                    <h2 class="text-lg font-bold text-slate-900">Payment</h2>
                    <p class="mt-1 text-sm text-slate-500">All transactions are secure and encrypted.</p>
                    <input type="hidden" name="payment_method" value="Card">

                    <div class="mt-3 overflow-hidden rounded-md border border-slate-300">
                        <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-2 border-b border-slate-200 bg-slate-50 px-4 py-3">
                            <span class="flex shrink-0 items-center gap-2 whitespace-nowrap text-sm font-semibold text-slate-800">
                                <span class="grid h-4 w-4 place-items-center rounded-full border-4 border-slate-900"></span>
                                Credit card
                            </span>
                            <x-payment-icons class="justify-start sm:justify-end" />
                        </div>

                        @if ($stripeEnabled && $stripeCheckoutType === 'embedded')
                            <div data-card-fields class="bg-slate-50 p-4">
                                <div id="payment-element"><div class="py-6 text-center text-sm text-slate-400">Loading secure card form…</div></div>
                                <p data-card-error class="mt-2 hidden text-sm font-medium text-rose-600"></p>
                            </div>
                        @elseif ($stripeEnabled)
                            <p class="flex items-start gap-2 bg-slate-50 p-4 text-sm text-slate-600">
                                <x-icon name="lock" class="h-4 w-4 shrink-0 text-emerald-500" />
                                You'll be taken to Stripe's secure payment page to enter your card and finish the order.
                            </p>
                        @else
                            <p class="bg-slate-50 p-4 text-sm text-slate-600">Our team will contact you to arrange payment.</p>
                        @endif
                    </div>
                </section>

                {{-- ===== pay ===== --}}
                <div>
                    <button type="submit" data-checkout-submit
                            class="flex w-full items-center justify-center gap-2 rounded-md bg-slate-900 py-4 text-base font-bold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-70">
                        <span data-lock class="{{ $stripeEnabled ? '' : 'hidden' }}"><x-icon name="lock" class="h-4 w-4" /></span>
                        <span data-label>{{ $stripeEnabled ? 'Pay now' : 'Place order' }}</span>
                    </button>

                    <p data-quote-note hidden class="mt-3 text-center text-xs text-slate-500">Nothing is charged now — we'll email the shipping cost and a secure payment link.</p>
                    @if ($stripeEnabled)
                        <p data-pay-note class="mt-3 flex items-center justify-center gap-1.5 text-center text-xs text-slate-400">
                            <x-icon name="lock" class="h-3.5 w-3.5" /> Secure payment by Stripe.{{ $stripeCheckoutType === 'redirect' ? " You'll be redirected to complete payment." : ' We never see your card details.' }}
                        </p>
                    @else
                        <p class="mt-3 text-center text-xs text-slate-400">By placing your order you agree to our terms.</p>
                    @endif
                </div>
            </form>

            <div class="mt-8 flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-slate-200 pt-5 text-xs text-slate-500">
                <span class="flex items-center gap-1.5"><x-icon name="shield" class="h-4 w-4 text-emerald-500" /> Genuine parts</span>
                <span class="flex items-center gap-1.5"><x-icon name="refresh" class="h-4 w-4 text-emerald-500" /> 30-day returns</span>
                @if ($supportPhone)
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $supportPhone) }}" class="flex items-center gap-1.5 hover:text-amber-600">
                        <x-icon name="phone" class="h-4 w-4 text-emerald-500" /> {{ $supportPhone }}
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- ===================== right: order summary ===================== --}}
    <aside class="order-first bg-slate-50 px-4 py-8 sm:px-6 lg:order-none lg:py-12 lg:pl-12">
        <div class="mx-auto max-w-lg lg:ml-0 lg:mr-auto lg:sticky lg:top-24">

            {{-- phones: the summary folds away behind its total, like the rest of checkout --}}
            <button type="button" data-summary-toggle aria-expanded="true"
                    class="flex w-full items-center justify-between gap-3 border-b border-slate-200 pb-4 lg:hidden">
                <span class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                    <x-icon name="cart" class="h-4 w-4 text-slate-500" />
                    <span data-summary-toggle-text>Hide order summary</span>
                    <svg data-summary-chevron class="h-4 w-4 rotate-180 text-slate-500 transition-transform" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
                </span>
                <span class="text-base font-bold text-slate-900">
                    <span data-summary-total>${{ number_format($subtotal - \App\Support\Cart::discount($subtotal) + $tax, 2) }}</span>
                </span>
            </button>

            <div data-summary-body class="pt-5 lg:pt-0">
            @include('checkout.partials.summary')
            </div>
        </div>
    </aside>
</div>
</div>

@push('scripts')
@php
    $trackCheckout = [
        'currency' => 'USD',
        'value' => round((float) $subtotal, 2),
        'items' => $items->map(fn ($i) => \App\Support\Ecom::item($i['product'], $i['product']->name, (float) $i['product']->price, (int) $i['qty']))->values(),
    ];
@endphp
<script>window.trackEcom && window.trackEcom.begin_checkout(@json($trackCheckout));</script>
<script>
// Checkout validation + error popup. Registered before the Stripe handler so an
// invalid form never starts a payment.
(function () {
    const form = document.querySelector('[data-checkout-form]');
    const alertBox = document.querySelector('[data-alert]');
    if (!form || !alertBox) return;

    const list = alertBox.querySelector('[data-alert-list]');
    const title = alertBox.querySelector('[data-alert-title]');

    const hideAlert = () => { alertBox.hidden = true; };
    alertBox.querySelector('[data-alert-close]').addEventListener('click', hideAlert);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') hideAlert(); });

    // shown for validation problems and for card/payment errors
    window.checkoutAlert = function (messages, heading) {
        const items = Array.isArray(messages) ? messages : [messages];
        title.textContent = heading || 'Please check your details';
        list.innerHTML = '';
        items.filter(Boolean).forEach((m) => {
            const li = document.createElement('li');
            li.textContent = m;
            list.appendChild(li);
        });
        alertBox.hidden = false;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    const OK_BORDER = ['border-slate-300', 'focus:border-amber-400', 'focus:ring-amber-100'];
    const BAD_BORDER = ['border-rose-300', 'bg-rose-50/40', 'focus:border-rose-400', 'focus:ring-rose-100'];

    const msgEl = (field) => form.querySelector(`[data-error-for="${field.name}"]`);

    const markBad = (field, message) => {
        field.classList.remove(...OK_BORDER);
        field.classList.add(...BAD_BORDER);
        field.setAttribute('aria-invalid', 'true');
        const el = msgEl(field);
        if (el) { el.textContent = message; el.classList.remove('hidden'); }
    };

    const markOk = (field) => {
        field.classList.remove(...BAD_BORDER);
        field.classList.add(...OK_BORDER);
        field.removeAttribute('aria-invalid');
        const el = msgEl(field);
        if (el) { el.classList.add('hidden'); }
        // nothing left to fix: drop the popup as well
        if (!form.querySelector('[aria-invalid="true"]')) hideAlert();
    };

    // one rule per field; returns an error message or ''
    const check = (field) => {
        const label = field.dataset.label || field.name;
        const value = (field.value || '').trim();

        if (field.hasAttribute('data-required') && !value) return `${label} is required.`;
        if (!value) return '';
        if (field.hasAttribute('data-email') && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value)) return 'Enter a valid email address, e.g. name@company.com.';
        if (field.hasAttribute('data-phone')) {
            // the country picker knows each country's rules; fall back to a digit count
            const iti = field.itiInstance;
            if (iti && typeof iti.isValidNumber === 'function') {
                if (iti.isValidNumber() === false) return 'Enter a valid phone number for the country shown.';
            } else if (value.replace(/\D/g, '').length < 10) {
                return 'Enter a phone number with at least 10 digits.';
            }
        }
        if (field.hasAttribute('data-zip') && !/^\d{5}(-\d{4})?$/.test(value)) return 'Enter a 5-digit US ZIP code, e.g. 75001.';
        return '';
    };

    const fields = () => Array.from(form.querySelectorAll('[data-required], [data-email], [data-phone], [data-zip]'));

    // validate a field once the buyer has moved on, and clear the error as they fix it
    fields().forEach((field) => {
        field.addEventListener('blur', () => {
            const error = check(field);
            error ? markBad(field, error) : markOk(field);
        });
        field.addEventListener('input', () => { if (field.getAttribute('aria-invalid')) markOk(field); });
        field.addEventListener('change', () => { if (field.getAttribute('aria-invalid')) markOk(field); });
    });

    form.addEventListener('submit', (e) => {
        const problems = [];
        let first = null;

        fields().forEach((field) => {
            const error = check(field);
            if (error) {
                markBad(field, error);
                problems.push(error);
                if (!first) first = field;
            } else {
                markOk(field);
            }
        });

        if (problems.length) {
            e.preventDefault();
            e.stopImmediatePropagation();   // keep the Stripe handler from running
            window.checkoutAlert(problems);
            if (first) {
                first.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(() => first.focus({ preventScroll: true }), 300);
            }
        }
    }, true);

    // Save what has been typed so far, so an abandoned checkout can be followed up.
    // The URL is null until the route exists (live route cache not rebuilt yet).
    (function autosave() {
        const url = @json(\Illuminate\Support\Facades\Route::has('checkout.lead') ? route('checkout.lead') : null);
        if (!url) return;

        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const names = ['name', 'email', 'phone', 'company', 'address', 'city', 'state', 'zip', 'notes'];
        const read = () => {
            const out = {};
            names.forEach((n) => { out[n] = (form.querySelector(`[name="${n}"]`)?.value || '').trim(); });
            return out;
        };

        let last = JSON.stringify(read());
        let timer = null;

        const send = (useBeacon) => {
            const values = read();
            const body = JSON.stringify(values);
            if (body === last || !Object.values(values).some(Boolean)) return;
            last = body;

            // leaving the page: a beacon still gets through
            if (useBeacon && navigator.sendBeacon) {
                const fd = new FormData();
                Object.entries(values).forEach(([k, v]) => fd.append(k, v));
                fd.append('_token', token);
                navigator.sendBeacon(url, fd);
                return;
            }

            fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                body,
                keepalive: true,
            }).catch(() => { last = ''; });   // retry on the next change
        };

        names.forEach((n) => {
            const el = form.querySelector(`[name="${n}"]`);
            if (!el) return;
            el.addEventListener('change', () => send(false));
            el.addEventListener('blur', () => send(false));
            el.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(() => send(false), 1500); });
        });

        // closing the tab / switching away
        window.addEventListener('pagehide', () => send(true));
        document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'hidden') send(true); });
    })();

    // errors that came back from the server: show them in the popup too
    const server = document.querySelector('[data-server-errors]');
    if (server) {
        try { window.checkoutAlert(JSON.parse(server.dataset.messages)); } catch (err) { /* banner is enough */ }
    }
})();
</script>
<script>
// Live shipping rates: fetch options as the address fills in, let the buyer pick,
// and keep the order summary (shipping + total) in sync with the choice.
(function () {
    const form = document.querySelector('[data-checkout-form]');
    const wrap = document.querySelector('[data-ship]');
    if (!form || !wrap) return;

    const optionsEl = wrap.querySelector('[data-ship-options]');
    const loadingEl = wrap.querySelector('[data-ship-loading]');
    const codeEl    = wrap.querySelector('[data-ship-code]');
    const suggest   = wrap.querySelector('[data-ship-suggest]');
    const suggestTx = wrap.querySelector('[data-ship-suggest-text]');
    // these live inside the summary, which is re-rendered on a quantity change
    const summary    = () => document.querySelector('[data-subtotal]');
    // what the order is charged on: the subtotal after any discount code
    const netSub     = () => (parseFloat(summary().dataset.subtotal) || 0) - (parseFloat(summary().dataset.discount) || 0);
    const shipCells  = () => document.querySelectorAll('[data-summary-shipping]');
    const totalCells = () => document.querySelectorAll('[data-summary-total]');
    const token     = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const field = (n) => form.querySelector(`[name="${n}"]`);
    const money = (n) => n > 0 ? '$' + n.toFixed(2) : 'Free';

    const quoteEl   = wrap.querySelector('[data-ship-quote]');
    const submitLbl = form.querySelector('[data-checkout-submit] [data-label]');

    let chosenCost = null;   // remembered so a quantity change keeps the picked rate

    const setShipping = (cost) => {
        chosenCost = cost;
        const sub = netSub();
        const tax = parseFloat(summary().dataset.tax) || 0;
        shipCells().forEach((el) => {
            el.classList.remove('text-slate-400', 'text-slate-500');   // was the "Enter shipping address" hint
            el.textContent = money(cost);
        });
        totalCells().forEach((el) => { el.textContent = '$' + (sub + tax + cost).toFixed(2); });
    };

    // Quote mode: no carrier can take this order, so it is placed without a
    // shipping price and without payment; the team emails the shipping cost and
    // a payment link. The Stripe script listens for the change to hide the card form.
    const defaultLbl = submitLbl ? submitLbl.textContent : '';

    const setQuote = (on) => {
        quoteEl.value = on ? '1' : '0';
        form.dataset.quoteMode = on ? '1' : '';

        if (on) {
            const sub = netSub();
            const tax = parseFloat(summary().dataset.tax) || 0;
            chosenCost = null;
            codeEl.value = 'QUOTE';
            shipCells().forEach((el) => {
                el.classList.remove('text-slate-400', 'text-slate-500');
                el.textContent = 'Quoted after order';
            });
            totalCells().forEach((el) => { el.textContent = '$' + (sub + tax).toFixed(2) + ' + shipping'; });
        }
        if (submitLbl) submitLbl.textContent = on ? 'Place order — shipping quoted' : defaultLbl;
        document.querySelectorAll('[data-quote-note]').forEach((el) => { el.hidden = !on; });
        document.querySelectorAll('[data-pay-note]').forEach((el) => { el.hidden = on; });

        // the Stripe script (when on) re-syncs the card form and button label
        form.dispatchEvent(new CustomEvent('checkout:shipping-mode'));
    };

    const renderQuote = () => {
        optionsEl.innerHTML =
            '<div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3.5 text-sm text-amber-900">' +
                '<p class="font-bold">This order needs a custom shipping quote</p>' +
                '<p class="mt-1 text-amber-800">Some items are too large or heavy for standard parcel rates. Place your order now — ' +
                'our team will email you the exact shipping cost and a secure payment link, usually within one business day. ' +
                '<b>You won\'t be charged anything until you approve it.</b></p>' +
            '</div>';
        setQuote(true);
    };

    // "3 business days · arrives by Thu, Sep 25" — counted in business days from the
    // ship date (same day before 12 pm, otherwise the next business day).
    const deliveryText = (days) => {
        const n = parseInt(days, 10);
        if (!n || n < 1) return '';

        const d = new Date();
        const isWeekend = (x) => x.getDay() === 0 || x.getDay() === 6;
        if (d.getHours() >= 12 || isWeekend(d)) {
            do { d.setDate(d.getDate() + 1); } while (isWeekend(d));
        }
        for (let left = n; left > 0;) {
            d.setDate(d.getDate() + 1);
            if (!isWeekend(d)) left--;
        }

        const by = d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
        return n + (n === 1 ? ' business day' : ' business days') + ' · arrives by ' + by;
    };

    const escapeHtml = (s) => String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    const render = (rates) => {
        optionsEl.innerHTML = '';
        rates.forEach((r, i) => {
            const eta = deliveryText(r.days);
            const row = document.createElement('label');
            row.className = 'flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-slate-200 px-4 py-3 text-sm transition hover:border-slate-300 has-[:checked]:border-amber-400 has-[:checked]:bg-amber-50';
            row.innerHTML =
                '<span class="flex items-center gap-2.5">' +
                    '<input type="radio" name="ship_pick" value="' + escapeHtml(r.code) + '" data-cost="' + r.cost + '" ' + (i === 0 ? 'checked' : '') + ' class="text-amber-600 focus:ring-amber-400">' +
                    '<span>' +
                        '<span class="block font-semibold text-slate-800">' + escapeHtml(r.service) + '</span>' +
                        (eta ? '<span class="mt-0.5 flex items-center gap-1 text-xs text-slate-500"><svg class="h-3.5 w-3.5 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>' + eta + '</span>' : '') +
                    '</span>' +
                '</span>' +
                '<span class="shrink-0 font-bold text-slate-900">' + money(r.cost) + '</span>';
            optionsEl.appendChild(row);
        });
        if (rates.some((r) => parseInt(r.days, 10) > 0)) {
            const note = document.createElement('p');
            note.className = 'pt-1 text-xs text-slate-400';
            note.textContent = 'Delivery dates are the carrier\'s estimate, counted in business days (Mon–Fri) after your order ships.';
            optionsEl.appendChild(note);
        }
        optionsEl.querySelectorAll('input[name="ship_pick"]').forEach((r) => {
            r.addEventListener('change', () => { codeEl.value = r.value; setShipping(parseFloat(r.dataset.cost)); });
        });
        setQuote(false);
        const first = optionsEl.querySelector('input[name="ship_pick"]:checked');
        if (first) { codeEl.value = first.value; setShipping(parseFloat(first.dataset.cost)); }
    };

    let lastKey = '';
    let busy = false;
    const fetchRates = async () => {
        const zip = field('zip')?.value.trim();
        const state = field('state')?.value.trim();
        const city = field('city')?.value.trim();
        const address = field('address')?.value.trim();

        // Nothing is quoted until the whole delivery address is there — a rate for
        // half an address would only be a guess.
        if (!address || !city || !state || !/^\d{5}(-\d{4})?$/.test(zip || '')) return;

        const key = [address, city, state, zip].join('|');
        if (key === lastKey || busy) return;
        lastKey = key;
        busy = true;
        loadingEl.hidden = false;

        try {
            const res = await fetch('{{ route('checkout.rates') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ zip, state, city, address }),
            });
            const data = await res.json();
            if (data.quote) renderQuote();
            else if (data.rates && data.rates.length) render(data.rates);

            if (data.suggestion) {
                const s = data.suggestion;
                suggestTx.textContent = [s.address, s.city, s.state, s.zip].filter(Boolean).join(', ');
                suggest.hidden = false;
                suggest.querySelector('[data-ship-suggest-apply]').onclick = () => {
                    if (s.address) field('address').value = s.address;
                    if (s.city) field('city').value = s.city;
                    if (s.state) field('state').value = s.state;
                    if (s.zip) field('zip').value = s.zip;
                    suggest.hidden = true;
                    lastKey = '';
                    fetchRates();
                };
            } else {
                suggest.hidden = true;
            }
        } catch (e) { /* keep flat default */ }
        finally { busy = false; loadingEl.hidden = true; }
    };

    ['address', 'city', 'state', 'zip'].forEach((n) => {
        field(n)?.addEventListener('change', fetchRates);
        field(n)?.addEventListener('blur', fetchRates);
    });
    // if the address is already filled (logged-in customer), fetch on load
    fetchRates();

    /* ----- quantities in the order summary ----- */
    const qtyUrl = @json(\Illuminate\Support\Facades\Route::has('checkout.quantity') ? route('checkout.quantity') : null);
    const summaryBody = document.querySelector('[data-summary-body]');
    let qtyBusy = false;

    const changeQty = async (productId, qty) => {
        if (!qtyUrl || qtyBusy) return;
        qtyBusy = true;
        try {
            const res = await fetch(qtyUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ product_id: productId, qty }),
            });
            const data = await res.json();

            // last item removed → back to the cart
            if (data.redirect) { window.location = data.redirect; return; }
            if (!data.html) return;

            summaryBody.innerHTML = data.html;
            if (data.message && window.cartToast) window.cartToast(data.message);

            // totals moved, so re-apply the chosen rate and re-price the delivery
            if (chosenCost !== null) {
                setShipping(chosenCost);
            } else {
                // no rate picked yet: carry the fresh total up to the mobile bar
                const panelTotal = summaryBody.querySelector('[data-summary-total]');
                totalCells().forEach((el) => {
                    if (panelTotal && !summaryBody.contains(el)) el.textContent = panelTotal.textContent;
                });
            }
            lastKey = '';
            fetchRates();
        } catch (err) {
            window.location.reload();   // fall back to a fresh page rather than wrong totals
        } finally {
            qtyBusy = false;
        }
    };

    const lineQty = (el) => {
        const row = el.closest('[data-co-qty]');
        const input = row.querySelector('[data-co-qty-input]');
        return { id: parseInt(row.dataset.product, 10), value: parseInt(input.value, 10) || 1, input };
    };

    document.addEventListener('click', (e) => {
        const remove = e.target.closest('[data-co-qty-remove]');
        if (remove) { changeQty(parseInt(remove.dataset.product, 10), 0); return; }

        const dec = e.target.closest('[data-co-qty-dec]');
        const inc = e.target.closest('[data-co-qty-inc]');
        if (!dec && !inc) return;

        const { id, value, input } = lineQty(dec || inc);
        const max = parseInt(input.getAttribute('max'), 10);
        let next = inc ? value + 1 : value - 1;
        if (max > 0 && next > max) {
            if (window.cartToast) window.cartToast(`Only ${max} in stock — you can't add more than that.`);
            return;
        }
        if (next < 1) { changeQty(id, 0); return; }   // stepping below 1 removes the line
        changeQty(id, next);
    });

    document.addEventListener('change', (e) => {
        const input = e.target.closest('[data-co-qty-input]');
        if (!input) return;
        const { id, value } = lineQty(input);
        changeQty(id, Math.max(0, value));
    });

    // ---- discount code -------------------------------------------------
    const couponUrl = @json(route('cart.coupon.apply'));
    const couponRemoveUrl = @json(route('cart.coupon.remove'));
    let couponBusy = false;

    const sendCoupon = async (method, code) => {
        if (couponBusy) return;
        couponBusy = true;
        try {
            const res = await fetch(method === 'DELETE' ? couponRemoveUrl : couponUrl, {
                method,
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ code: code || '' }),
            });
            const data = await res.json();

            if (data.summary) {
                summaryBody.innerHTML = data.summary;
                // the discount moved the totals: re-price with the chosen rate
                if (chosenCost !== null) {
                    setShipping(chosenCost);
                } else {
                    const panelTotal = summaryBody.querySelector('[data-summary-total]');
                    totalCells().forEach((el) => {
                        if (panelTotal && !summaryBody.contains(el)) el.textContent = panelTotal.textContent;
                    });
                }
            }

            const note = summaryBody.querySelector('[data-coupon-message]');
            if (note && data.message) {
                note.textContent = data.message;
                note.classList.remove('hidden', 'text-emerald-700', 'text-rose-600');
                note.classList.add(data.ok ? 'text-emerald-700' : 'text-rose-600');
            }
        } catch (err) {
            window.location.reload();
        } finally {
            couponBusy = false;
        }
    };

    document.addEventListener('click', (e) => {
        if (e.target.closest('[data-coupon-apply]')) {
            const input = summaryBody.querySelector('[data-coupon-input]');
            const code = (input?.value || '').trim();
            if (!code) { input?.focus(); return; }
            sendCoupon('POST', code);
        }
        if (e.target.closest('[data-coupon-remove]')) sendCoupon('DELETE', '');
    });

    // Enter in the code box applies it instead of submitting the order
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter' || !e.target.closest('[data-coupon-input]')) return;
        e.preventDefault();
        sendCoupon('POST', e.target.value.trim());
    });
})();
</script>
@endpush

@if ($stripeEnabled)
@push('scripts')
@if ($stripeCheckoutType === 'embedded')
<script src="https://js.stripe.com/v3/"></script>
@endif
<script>
(function () {
    const form = document.querySelector('[data-checkout-form]');
    if (!form) return;

    const label = form.querySelector('[data-checkout-submit] [data-label]');
    const lock = form.querySelector('[data-lock]');
    const submitBtn = form.querySelector('[data-checkout-submit]');
    const cardFields = form.querySelector('[data-card-fields]');
    const cardError = form.querySelector('[data-card-error]');
    const CHECKOUT_TYPE = @json($stripeCheckoutType);

    // a shipping-quote order is placed without taking a card payment
    const isQuote = () => form.dataset.quoteMode === '1';

    const isCard = () => {
        if (isQuote()) return false;
        const m = form.querySelector('input[name="payment_method"]:checked')
            || form.querySelector('input[name="payment_method"]');
        return !!m && m.value === 'Card';
    };

    const idleLabel = () => isQuote() ? 'Place order — shipping quoted' : (isCard() ? 'Pay now' : 'Place order');

    function sync() {
        const card = isCard();
        if (label) label.textContent = idleLabel();
        if (lock) lock.classList.toggle('hidden', !card);
        if (cardFields) cardFields.classList.toggle('hidden', !card);
    }
    form.querySelectorAll('input[name="payment_method"]').forEach((r) => r.addEventListener('change', sync));
    form.addEventListener('checkout:shipping-mode', sync);
    sync();

    function showError(msg, heading) {
        const text = msg || 'Payment failed. Please try again.';
        if (cardError) { cardError.textContent = text; cardError.classList.remove('hidden'); }
        if (window.checkoutAlert) window.checkoutAlert(text, heading || 'Payment could not be completed');
    }
    function setBusy(busy) {
        if (!submitBtn) return;
        submitBtn.disabled = busy;
        submitBtn.classList.toggle('opacity-70', busy);
        if (label) label.textContent = busy ? 'Processing…' : idleLabel();
    }

    // ===== Redirect style: let the form submit normally (server 303s to Stripe). =====
    if (CHECKOUT_TYPE !== 'embedded' || !window.Stripe) {
        return;
    }

    // ===== Embedded style: Stripe Payment Element confirmed in-page. =====
    const stripe = Stripe(@json($stripeKey));
    const elements = stripe.elements({
        mode: 'payment',
        amount: {{ $totalCents }},
        currency: @json($stripeCurrency),
        // card only — matches the PaymentIntent and keeps Stripe's Link
        // "save my information" block out of the form
        paymentMethodTypes: ['card'],
        appearance: { theme: 'stripe', variables: { colorPrimary: '#f59e0b', borderRadius: '8px' } },
    });
    const paymentElement = elements.create('payment', {
        layout: 'tabs',
        wallets: { applePay: 'never', googlePay: 'never' },
    });
    const mountTarget = document.getElementById('payment-element');
    if (mountTarget) { mountTarget.innerHTML = ''; paymentElement.mount('#payment-element'); }

    form.addEventListener('submit', async (e) => {
        if (!isCard()) return; // non-card methods submit normally
        e.preventDefault();
        if (cardError) cardError.classList.add('hidden');
        setBusy(true);

        try {
            // 1) validate the Payment Element
            const { error: submitError } = await elements.submit();
            if (submitError) { showError(submitError.message); setBusy(false); return; }

            // 2) create the order + PaymentIntent on the server
            const res = await fetch(form.action, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: new FormData(form),
            });

            if (res.status === 422) {
                const data = await res.json().catch(() => ({}));
                const messages = data.errors ? Object.values(data.errors).flat() : [data.error || data.message];
                if (window.checkoutAlert) window.checkoutAlert(messages.filter(Boolean));
                if (cardError) { cardError.textContent = messages[0] || 'Please check your details and try again.'; cardError.classList.remove('hidden'); }
                setBusy(false);
                return;
            }
            if (!res.ok) { showError('Something went wrong. Please try again.'); setBusy(false); return; }

            const payload = await res.json();
            // the server placed it as a shipping-quote order — nothing to charge now
            if (payload.redirect) { window.location = payload.redirect; return; }
            const { clientSecret, returnUrl } = payload;

            // 3) confirm the card payment in-page
            const { error } = await stripe.confirmPayment({
                elements,
                clientSecret,
                confirmParams: { return_url: returnUrl },
            });
            // If we reach here, confirmation failed (otherwise the browser redirects to return_url).
            if (error) { showError(error.message); setBusy(false); }
        } catch (err) {
            showError('Network error. Please try again.');
            setBusy(false);
        }
    });
})();
</script>
@endpush
@endif

@endsection
