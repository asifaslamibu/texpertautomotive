@extends('layouts.app')

@section('title', 'Return, Refund & Cancellation Policy — Texpert Deals')
@section('meta_description', 'Texpert Deals return policy: 30-day returns, quality inspection, restocking fees, refund timelines, damaged or incorrect items and order cancellation terms.')

@section('content')

@include('pages._hero', [
    'eyebrow' => 'Customer care',
    'title' => 'Return, Refund & Cancellation Policy',
    'subtitle' => 'At Texpert Deals, customer satisfaction is important to us. If you are not completely satisfied with your purchase, please review the terms below before starting a return.',
])

<div class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
    @php
        // Each section may carry intro paragraph(s), bullet points, or both; the
        // return process is the one ordered list, so it is flagged separately.
        $sections = [
            [
                'heading' => '30-Day Return Policy',
                'intro' => [
                    'If you are not completely satisfied with your purchase, you may request a return within 30 calendar days from the date your order is delivered.',
                    'To initiate a return, customers must first complete our Return Request Form. Returns sent without prior authorization may be refused.',
                ],
            ],
            [
                'heading' => 'Return Eligibility',
                'intro' => 'To qualify for a return, all of the following conditions must be met:',
                'points' => [
                    'Return request must be submitted within 30 days of delivery.',
                    'Items must be returned in their original packaging whenever possible.',
                    'Products must include all accessories, manuals, and components received.',
                    'Items must not show signs of misuse, abuse, modification, or improper installation.',
                    'The return must be shipped or dropped off within 3 calendar days after the return shipping label has been issued.',
                ],
            ],
            [
                'heading' => 'Quality Inspection',
                'intro' => [
                    'Every returned item undergoes a comprehensive quality inspection upon arrival at our warehouse. Please allow up to 14 business days after we receive your return for inspection and verification.',
                    'Our inspection will determine whether:',
                ],
                'points' => [
                    'The product is defective.',
                    'The product was damaged during shipping.',
                    'The incorrect item was shipped.',
                    'The product is in new and resalable condition.',
                    'The product is fully functional.',
                ],
                'note' => 'The inspection results are final.',
            ],
            [
                'heading' => 'Restocking Fee',
                'intro' => 'A 25% restocking fee will apply if the returned item is found to be:',
                'points' => [
                    'Fully functional.',
                    'Free from manufacturing defects.',
                    'Returned because it is no longer wanted, ordered by mistake, or otherwise not defective.',
                ],
                'note' => 'Restocking fees may also apply to items returned without their original packaging or with missing accessories.',
            ],
            [
                'heading' => 'Return Shipping',
                'points' => [
                    'Customers are initially responsible for paying the return shipping label fee for approved returns. If the return is due to a product that is broken, defective, faulty, or otherwise covered under our return policy, Texpert Deals will provide a complete refund, including reimbursement of the return shipping label fee. If the customer is returning the product due to a change of mind or any other reason not related to a product defect or fault, only the item purchase price will be refunded, and the return shipping label fee will not be reimbursed.',
                    'Customers are responsible for securely packaging the item to prevent damage during transit.',
                    'Failure to ship the return within 3 days after the return label has been issued may result in cancellation of the return authorization.',
                ],
            ],
            [
                'heading' => 'Refund Processing',
                'intro' => 'Once the returned product has successfully passed inspection:',
                'points' => [
                    'Refunds will be processed within 7 business days.',
                    'Refunds will be issued using the original payment method whenever possible.',
                    'Depending on your financial institution, it may take additional time for the funds to appear in your account.',
                    'Shipping charges are non-refundable unless the return is due to our error or a verified defective product.',
                ],
            ],
            [
                'heading' => 'Damaged, Defective or Incorrect Items',
                'intro' => 'If you receive a damaged item, a defective product, or the wrong product, please submit a Return Request within 5 days of delivery.',
                'note' => 'Photographs and supporting documentation may be required before a replacement or refund is approved.',
            ],
            [
                'heading' => 'Non-Returnable Items',
                'intro' => 'The following products are considered final sale unless received damaged or defective:',
                'points' => [
                    'Special-order items',
                    'Custom-made products',
                    'Clearance or discontinued items',
                    'Installed or used products',
                    'Electrical components that have been installed',
                    'Any item specifically marked as Final Sale',
                ],
            ],
            [
                'heading' => 'Order Cancellation Policy',
                'points' => [
                    'Orders may be cancelled within 30 minutes of being placed.',
                    'After the 30-minute cancellation period, we cannot guarantee cancellation because orders may already be in processing or shipped.',
                    'If an order has already shipped, the order will be subject to our standard Return Policy.',
                ],
            ],
        ];

        $process = [
            'Complete the Return Request Form.',
            'Wait for return authorization.',
            'Receive your prepaid return shipping label.',
            'Ship the package within 3 days.',
            'Allow up to 14 business days for inspection.',
            'Approved refunds will be issued within 7 business days after inspection.',
        ];

        $additional = [
            ['Fraud Prevention', 'Texpert Deals reserves the right to refuse returns or refunds where fraudulent activity, excessive return patterns, or abuse of our return policy is suspected.'],
            ['Lost or Missing Returns', 'Customers should retain the return shipment tracking number until the return has been fully processed. Texpert Deals is not responsible for packages lost before they are received at our warehouse.'],
            ['Warranty', "Manufacturer warranties remain subject to each manufacturer's terms and conditions. Warranty claims may require additional documentation and are handled separately from standard returns."],
        ];

        $refusal = [
            'Falls outside the return window.',
            'Shows evidence of misuse, abuse, or improper installation.',
            'Is returned incomplete.',
            'Has missing accessories or packaging.',
            'Does not comply with this Return Policy.',
        ];
    @endphp

    <div class="space-y-9 text-slate-600">

        {{-- key terms at a glance, so shoppers do not have to read the whole page --}}
        <div class="grid gap-3 sm:grid-cols-3">
            @foreach ([
                ['30 days', 'to request a return'],
                ['14 business days', 'for inspection'],
                ['7 business days', 'for approved refunds'],
            ] as [$value, $label])
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-center">
                    <p class="text-lg font-black text-slate-900">{{ $value }}</p>
                    <p class="mt-0.5 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                </div>
            @endforeach
        </div>

        @foreach ($sections as $section)
            <div>
                <h2 class="text-lg font-bold text-slate-900">{{ $section['heading'] }}</h2>

                @foreach ((array) ($section['intro'] ?? []) as $paragraph)
                    <p class="mt-2 leading-relaxed">{{ $paragraph }}</p>
                @endforeach

                @if (! empty($section['points']))
                    <ul class="mt-2 list-disc space-y-1.5 pl-5 leading-relaxed">
                        @foreach ($section['points'] as $point)
                            <li>{{ $point }}</li>
                        @endforeach
                    </ul>
                @endif

                @if (! empty($section['note']))
                    <p class="mt-2 leading-relaxed">{{ $section['note'] }}</p>
                @endif
            </div>
        @endforeach

        {{-- the process, as the one ordered list on the page --}}
        <div>
            <h2 class="text-lg font-bold text-slate-900">Return Request Process</h2>
            <p class="mt-2 leading-relaxed">To request a return:</p>
            <ol class="mt-3 space-y-2.5">
                @foreach ($process as $i => $step)
                    <li class="flex gap-3">
                        <span class="grid h-6 w-6 shrink-0 place-items-center rounded-full bg-amber-400 text-xs font-black text-slate-900">{{ $i + 1 }}</span>
                        <span class="leading-relaxed">{{ $step }}</span>
                    </li>
                @endforeach
            </ol>
            <a href="{{ route('return-request') }}" class="mt-5 inline-block rounded-lg bg-amber-400 px-6 py-2.5 text-sm font-black uppercase tracking-wide text-slate-900 transition hover:bg-amber-500">
                Request a return
            </a>
        </div>

        <div>
            <h2 class="text-lg font-bold text-slate-900">Additional Company Policies</h2>
            @foreach ($additional as [$heading, $body])
                <h3 class="mt-4 font-bold text-slate-800">{{ $heading }}</h3>
                <p class="mt-1 leading-relaxed">{{ $body }}</p>
            @endforeach

            <h3 class="mt-4 font-bold text-slate-800">Right to Refuse Returns</h3>
            <p class="mt-1 leading-relaxed">Texpert Deals reserves the right to refuse any return that:</p>
            <ul class="mt-2 list-disc space-y-1.5 pl-5 leading-relaxed">
                @foreach ($refusal as $point)
                    <li>{{ $point }}</li>
                @endforeach
            </ul>
        </div>

        <div>
            <h2 class="text-lg font-bold text-slate-900">Contact Us</h2>
            <p class="mt-2 leading-relaxed">If you have any questions regarding returns, refunds, or cancellations, please contact our customer support team before initiating your return.</p>
        </div>

        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <p class="text-sm text-amber-800">Ready to start a return, or have a question first? <a href="{{ route('contact.index') }}" class="font-bold underline hover:text-amber-900">Contact our team</a> and we'll issue your return authorization.</p>
        </div>
    </div>
</div>

@endsection
