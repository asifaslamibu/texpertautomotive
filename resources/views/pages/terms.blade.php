@extends('layouts.app')

@section('title', 'Terms of Use — Texpert Deals')
@section('meta_description', 'The terms and conditions governing your use of the Texpert Deals website and services.')

@section('content')

@include('pages._hero', [
    'eyebrow' => 'Legal',
    'title' => 'Terms of Use',
    'subtitle' => 'Please read these terms carefully — by using our website you agree to them.',
])

<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
    <div class="space-y-8 text-slate-600">
        <p class="text-sm text-slate-400">Last updated: {{ date('F Y') }}</p>

        <div>
            <h2 class="text-lg font-bold text-slate-900">Acceptance of terms</h2>
            <p class="mt-2 leading-relaxed">By accessing or using the Texpert Deals website, you agree to be bound by these Terms of Use and all applicable laws. If you do not agree, please do not use the site.</p>
        </div>

        <div>
            <h2 class="text-lg font-bold text-slate-900">Use of the site</h2>
            <ul class="mt-2 list-disc space-y-1.5 pl-5 leading-relaxed">
                <li>You agree to use the site only for lawful purposes.</li>
                <li>You will not attempt to disrupt, damage, or gain unauthorized access to the site or its systems.</li>
                <li>You are responsible for keeping your account credentials confidential.</li>
            </ul>
        </div>

        <div>
            <h2 class="text-lg font-bold text-slate-900">Products &amp; pricing</h2>
            <p class="mt-2 leading-relaxed">We strive for accuracy, but product descriptions, images, availability, and pricing may contain errors and are subject to change without notice. We reserve the right to correct errors and to cancel or refuse any order, including after an order has been submitted.</p>
        </div>

        <div>
            <h2 class="text-lg font-bold text-slate-900">Orders &amp; payment</h2>
            <p class="mt-2 leading-relaxed">All orders are subject to acceptance and product availability. By placing an order you represent that the payment information you provide is accurate and that you are authorized to use it.</p>
        </div>

        <div>
            <h2 class="text-lg font-bold text-slate-900">Intellectual property</h2>
            <p class="mt-2 leading-relaxed">All content on this site — including text, graphics, logos, and images — is the property of Texpert Deals or its suppliers and is protected by applicable laws. You may not reproduce or redistribute it without permission.</p>
        </div>

        <div>
            <h2 class="text-lg font-bold text-slate-900">Limitation of liability</h2>
            <p class="mt-2 leading-relaxed">To the fullest extent permitted by law, Texpert Deals is not liable for any indirect, incidental, or consequential damages arising from your use of the site or products purchased through it.</p>
        </div>

        <div>
            <h2 class="text-lg font-bold text-slate-900">Changes to these terms</h2>
            <p class="mt-2 leading-relaxed">We may update these Terms of Use from time to time. Continued use of the site after changes are posted constitutes your acceptance of the revised terms.</p>
        </div>

        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <p class="text-sm text-amber-800">Have a question about these terms? <a href="{{ route('contact.index') }}" class="font-bold underline hover:text-amber-900">Contact us</a>.</p>
        </div>
    </div>
</div>

@endsection
