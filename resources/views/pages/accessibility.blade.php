@extends('layouts.app')

@section('title', 'Accessibility — Texpert Deals')
@section('meta_description', 'Our commitment to making the Texpert Deals website accessible to everyone.')

@section('content')

@include('pages._hero', [
    'eyebrow' => 'For everyone',
    'title' => 'Accessibility',
    'subtitle' => 'We\'re committed to making our website usable for everyone, including people with disabilities.',
])

<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
    <div class="space-y-8 text-slate-600">

        <div>
            <h2 class="text-lg font-bold text-slate-900">Our commitment</h2>
            <p class="mt-2 leading-relaxed">Texpert Deals is committed to ensuring digital accessibility for people of all abilities. We continually work to improve the experience for everyone and to apply the relevant accessibility standards.</p>
        </div>

        <div>
            <h2 class="text-lg font-bold text-slate-900">Standards we follow</h2>
            <p class="mt-2 leading-relaxed">We aim to conform to the Web Content Accessibility Guidelines (WCAG) 2.1 Level AA. These guidelines explain how to make web content more accessible to people with a wide range of disabilities, including visual, auditory, motor, and cognitive.</p>
        </div>

        <div>
            <h2 class="text-lg font-bold text-slate-900">Features on this site</h2>
            <ul class="mt-2 list-disc space-y-1.5 pl-5 leading-relaxed">
                <li>Keyboard-navigable menus, forms, and controls.</li>
                <li>Descriptive labels and alt text for images and buttons.</li>
                <li>Sufficient color contrast for readable text.</li>
                <li>Responsive layouts that adapt to different screen sizes and zoom levels.</li>
            </ul>
        </div>

        <div>
            <h2 class="text-lg font-bold text-slate-900">Ongoing effort</h2>
            <p class="mt-2 leading-relaxed">Accessibility is an ongoing effort. We regularly review our site and welcome feedback that helps us improve. If you encounter a barrier or have a suggestion, we want to hear from you.</p>
        </div>

        <div>
            <h2 class="text-lg font-bold text-slate-900">Contact us</h2>
            <p class="mt-2 leading-relaxed">If you have difficulty accessing any part of our site, please <a href="{{ route('contact.index') }}" class="font-semibold text-amber-600 hover:text-amber-700">contact us</a> or call us during business hours and we'll be glad to assist you or provide the information you need in an alternative format.</p>
        </div>
    </div>
</div>

@endsection
