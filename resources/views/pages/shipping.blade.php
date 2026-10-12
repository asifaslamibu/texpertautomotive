@extends('layouts.app')

@section('title', 'Shipping Policy — Texpert Deals')
@section('meta_description', 'Texpert Deals shipping policy — order processing, shipping methods and rates, delivery times, freight handling and international shipping.')

@section('content')

@include('pages._hero', [
    'eyebrow' => 'Delivery info',
    'title' => 'Shipping Policy',
    'subtitle' => 'At Texpert Deals, we strive to process and ship orders as quickly and efficiently as possible. Please review our shipping guidelines below.',
])

<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
    @php
        $sections = [
            ['Order Processing', [
                'Orders are typically processed within 1–5 business days after payment is confirmed.',
                'Processing times may vary during holidays, peak seasons, or when inventory is limited.',
                'Orders placed after business hours, on weekends, or on holidays will be processed on the next business day.',
            ]],
            ['Shipping Methods', [
                'We use trusted shipping carriers to deliver orders safely and on time.',
                'Shipping options and costs will be calculated at checkout based on the order size, weight, and destination.',
                'Larger or bulk orders may require special freight arrangements.',
            ]],
            ['Shipping Rates', [
                'Shipping charges are determined by product weight, dimensions, destination, and shipping method selected.',
                'Customers are responsible for all shipping costs unless otherwise stated in a specific promotion or agreement.',
                'Any additional charges related to freight, special handling, or remote delivery locations may also apply.',
            ]],
            ['Delivery Times', [
                'Estimated delivery times vary depending on the shipping method and destination.',
                'Once an order has shipped, customers will receive tracking details when available.',
                'Texpert Deals is not responsible for delays caused by carriers, weather, customs, or other circumstances beyond our control.',
            ]],
            ['Freight and Large Orders', [
                'Oversized, heavy, or palletized items may ship via freight service.',
                'For freight shipments, someone may be required to be present to receive the delivery.',
                'Additional fees may apply for liftgate service, residential delivery, or special access locations.',
            ]],
            ['Damaged or Missing Shipments', [
                'Please inspect all shipments upon delivery.',
                'If your order arrives damaged, incomplete, or incorrect, contact us as soon as possible so we can assist you.',
                'Keep all packaging materials and photos of the shipment for review.',
            ]],
            ['International Shipping', [
                'International shipping may be available for select orders.',
                'Customs duties, taxes, and import fees are the responsibility of the customer unless otherwise agreed.',
            ]],
            ['Important Notes', [
                'Shipping times are estimates and cannot be guaranteed.',
                'Texpert Deals reserves the right to adjust shipping methods when necessary to protect the integrity of the order.',
            ]],
        ];
    @endphp

    <div class="space-y-8 text-slate-600">
        @foreach ($sections as [$heading, $points])
            <div>
                <h2 class="text-lg font-bold text-slate-900">{{ $heading }}</h2>
                <ul class="mt-2 list-disc space-y-1.5 pl-5 leading-relaxed">
                    @foreach ($points as $point)
                        <li>{{ $point }}</li>
                    @endforeach
                </ul>
            </div>
        @endforeach

        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <p class="text-sm text-amber-800">Questions about a shipment? <a href="{{ route('contact.index') }}" class="font-bold underline hover:text-amber-900">Contact our team</a> or check our <a href="{{ route('returns') }}" class="font-bold underline hover:text-amber-900">Return Policy</a>.</p>
        </div>
    </div>
</div>

@endsection
