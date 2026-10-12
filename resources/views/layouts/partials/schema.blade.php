@php
    $siteName = \App\Models\Setting::get('site_name', 'Texpert Deals');
    $logo     = \App\Models\Setting::get('header_logo') ?: \App\Models\Setting::get('og_image');
    $phone    = \App\Models\Setting::get('contact_phone');
    $tollFree = \App\Models\Setting::get('toll_free', '(945) 427-9519');
    $email    = \App\Models\Setting::get('contact_email');
    $address  = \App\Models\Setting::get('contact_address');
    $desc     = \App\Models\Setting::get('site_description') ?: 'Shop HVAC equipment, mini splits, plumbing, tools and parts online — top brands, bulk pricing, fast shipping.';

    $sameAs = array_values(array_filter([
        \App\Models\Setting::get('social_facebook'),
        \App\Models\Setting::get('social_instagram'),
        \App\Models\Setting::get('social_linkedin'),
    ]));

    $org = array_filter([
        '@type' => 'Organization',
        '@id'   => url('/').'#organization',
        'name'  => $siteName,
        'url'   => url('/'),
        'description' => $desc,
        'logo'  => $logo ? media($logo) : null,
        'image' => $logo ? media($logo) : null,
        'email' => $email ?: null,
        'telephone' => $phone ?: null,
        'contactPoint' => $tollFree ? [
            '@type' => 'ContactPoint',
            'telephone' => $tollFree,
            'contactType' => 'customer service',
            'areaServed' => 'US',
        ] : null,
        'address' => $address ? [
            '@type' => 'PostalAddress',
            'streetAddress' => $address,
            'addressCountry' => 'US',
        ] : null,
        'sameAs' => $sameAs ?: null,
    ], fn ($v) => $v !== null && $v !== '');

    $website = [
        '@type' => 'WebSite',
        '@id'   => url('/').'#website',
        'name'  => $siteName,
        'url'   => url('/'),
        'publisher' => ['@id' => url('/').'#organization'],
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => route('products.index').'?q={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];

    $graph = [
        '@context' => 'https://schema.org',
        '@graph' => [$org, $website],
    ];
@endphp
<script type="application/ld+json">{!! json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
