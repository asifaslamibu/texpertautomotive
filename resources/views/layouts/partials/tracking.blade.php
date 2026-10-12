{{-- ===== Marketing & ads tracking (admin-configurable) ===== --}}
@php
    $ga4   = \App\Models\Setting::get('ga4_id');
    $gtm   = \App\Models\Setting::get('gtm_id');
    $ads   = \App\Models\Setting::get('google_ads_id');
    $adsLb = \App\Models\Setting::get('google_ads_purchase_label');
    $pixel = \App\Models\Setting::get('meta_pixel_id');
    $tiktok = \App\Models\Setting::get('tiktok_pixel_id');
    $clarity = \App\Models\Setting::get('clarity_id');
    $headCode = \App\Models\Setting::get('head_code');
@endphp

@if ($clarity)
    {{-- Microsoft Clarity (heatmaps + session recordings) --}}
    <script type="text/javascript">
        (function(c,l,a,r,i,t,y){c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);})(window,document,"clarity","script","{{ $clarity }}");
    </script>
@endif

@if ($gtm)
    {{-- Google Tag Manager --}}
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $gtm }}');</script>
@endif

@if ($ga4 || $ads)
    {{-- Google Analytics 4 / Google Ads (gtag) --}}
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4 ?: $ads }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        @if ($ga4) gtag('config', '{{ $ga4 }}'); @endif
        @if ($ads) gtag('config', '{{ $ads }}'); @endif
    </script>
@endif

@if ($pixel)
    {{-- Meta (Facebook) Pixel --}}
    <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '{{ $pixel }}');
        fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ $pixel }}&ev=PageView&noscript=1"/></noscript>
@endif

@if ($tiktok)
    {{-- TikTok Pixel --}}
    <script>
        !function(w,d,t){w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=d.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=d.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};ttq.load('{{ $tiktok }}');ttq.page();}(window,document,'ttq');
    </script>
@endif

@if ($headCode)
    {{-- Custom head code (verification tags, extra pixels, etc.) --}}
    {!! $headCode !!}
@endif

{{-- Ecommerce event helpers — safe no-ops when a platform isn't configured.
     Every helper takes a GA4-format ecommerce object: {currency, value, items:[{item_id, item_name, price, quantity}], ...}.
     Each event is pushed to the dataLayer (for GTM Custom Event triggers) and sent via gtag / pixels. --}}
<script>
    (function () {
        window.dataLayer = window.dataLayer || [];

        function send(event, ec) {
            window.dataLayer.push({ ecommerce: null }); // clear the previous ecommerce object
            window.dataLayer.push({ event: event, ecommerce: ec });
            if (window.gtag) gtag('event', event, ec);
        }
        function ids(ec) { return (ec.items || []).map(function (i) { return i.item_id; }); }
        function first(ec) { return (ec.items || [])[0] || {}; }

        window.trackEcom = {
            view_item: function (ec) {
                send('view_item', ec);
                if (window.fbq) fbq('track','ViewContent',{content_ids:ids(ec),content_name:first(ec).item_name,content_type:'product',value:ec.value,currency:ec.currency});
                if (window.ttq) ttq.track('ViewContent',{content_id:first(ec).item_id,content_name:first(ec).item_name,value:ec.value,currency:ec.currency});
            },
            add_to_cart: function (ec) {
                send('add_to_cart', ec);
                if (window.fbq) fbq('track','AddToCart',{content_ids:ids(ec),content_name:first(ec).item_name,content_type:'product',value:ec.value,currency:ec.currency});
                if (window.ttq) ttq.track('AddToCart',{content_id:first(ec).item_id,content_name:first(ec).item_name,value:ec.value,currency:ec.currency});
            },
            begin_checkout: function (ec) {
                send('begin_checkout', ec);
                if (window.fbq) fbq('track','InitiateCheckout',{content_ids:ids(ec),value:ec.value,currency:ec.currency});
                if (window.ttq) ttq.track('InitiateCheckout',{value:ec.value,currency:ec.currency});
            },
            purchase: function (ec) {
                send('purchase', ec);
                @if ($ads && $adsLb)
                    if (window.gtag) gtag('event','conversion',{send_to:'{{ $ads }}/{{ $adsLb }}',value:ec.value,currency:ec.currency,transaction_id:ec.transaction_id});
                @endif
                if (window.fbq) fbq('track','Purchase',{content_ids:ids(ec),value:ec.value,currency:ec.currency},{eventID:ec.transaction_id});
                if (window.ttq) ttq.track('CompletePayment',{value:ec.value,currency:ec.currency},{event_id:ec.transaction_id});
            }
        };

        @if (session()->has('ecom_add_to_cart'))
            {{-- add-to-cart from a full-page submit (Buy Now / no-JS) — fire on the page it landed on --}}
            window.trackEcom.add_to_cart(@json(session('ecom_add_to_cart')));
        @endif
    })();
</script>
