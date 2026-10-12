@extends('layouts.app')

@section('title', 'Privacy Policy — Texpert Deals')
@section('meta_description', 'How Texpert Deals collects, uses and protects your personal information.')

@section('content')

@include('pages._hero', [
    'eyebrow' => 'Your privacy',
    'title' => 'Privacy Policy',
    'subtitle' => 'This policy explains what information we collect, how we use it, and the choices you have.',
])

<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
    <div class="space-y-8 text-slate-600">
        <p class="text-sm text-slate-400">Last updated: {{ date('F Y') }}</p>

        <div>
            <h2 class="text-lg font-bold text-slate-900">Information we collect</h2>
            <p class="mt-2 leading-relaxed">We collect information you provide when you place an order, create an account, or contact us — such as your name, email, phone number, billing and shipping address, and payment details. We also automatically collect basic usage data (e.g. pages visited, device and browser type) through cookies and similar technologies.</p>
        </div>

        <div>
            <h2 class="text-lg font-bold text-slate-900">How we use your information</h2>
            <ul class="mt-2 list-disc space-y-1.5 pl-5 leading-relaxed">
                <li>To process and ship your orders and provide customer support.</li>
                <li>To send order confirmations, shipping updates, and service messages.</li>
                <li>To improve our website, products, and overall shopping experience.</li>
                <li>To send marketing communications, where you've opted in (you can unsubscribe anytime).</li>
                <li>To prevent fraud and comply with legal obligations.</li>
            </ul>
        </div>

        <div>
            <h2 class="text-lg font-bold text-slate-900">Sharing your information</h2>
            <p class="mt-2 leading-relaxed">We do not sell your personal information. We share it only with trusted service providers who help us operate our business — such as payment processors, shipping carriers, and email providers — and only as needed to perform their services, or when required by law.</p>
        </div>

        <div>
            <h2 class="text-lg font-bold text-slate-900">Cookies</h2>
            <p class="mt-2 leading-relaxed">We use cookies to keep your cart, remember preferences, and understand how the site is used. You can disable cookies in your browser settings, though some features may not work as intended.</p>
        </div>

        <div>
            <h2 class="text-lg font-bold text-slate-900">Data security</h2>
            <p class="mt-2 leading-relaxed">We use industry-standard safeguards to protect your information. However, no method of transmission over the internet is 100% secure, and we cannot guarantee absolute security.</p>
        </div>

        <div>
            <h2 class="text-lg font-bold text-slate-900">Your rights</h2>
            <p class="mt-2 leading-relaxed">You may request access to, correction of, or deletion of your personal information, and opt out of marketing at any time. To exercise these rights, please <a href="{{ route('contact.index') }}" class="font-semibold text-amber-600 hover:text-amber-700">contact us</a>.</p>
        </div>

        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <p class="text-sm text-amber-800">Questions about your privacy? <a href="{{ route('contact.index') }}" class="font-bold underline hover:text-amber-900">Get in touch</a> and we'll be happy to help.</p>
        </div>
    </div>
</div>

@endsection
