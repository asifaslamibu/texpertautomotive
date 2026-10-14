@php
    $siteName   = \App\Models\Setting::get('site_name', 'Texpert Automotive');
    $footerLogo = \App\Models\Setting::get('footer_logo');
    $socials = array_filter([
        'facebook'  => \App\Models\Setting::get('social_facebook'),
        'instagram' => \App\Models\Setting::get('social_instagram'),
        'youtube'   => \App\Models\Setting::get('social_youtube'),
        'tiktok'    => \App\Models\Setting::get('social_tiktok'),
        'linkedin'  => \App\Models\Setting::get('social_linkedin'),
    ]);
    $link = 'transition hover:text-amber-500';
@endphp
{{-- the homepage ends on its own dark brand banner, so the footer butts straight onto it --}}
<footer class="{{ request()->routeIs('home') ? '' : 'mt-20' }} border-t border-white/10 bg-[#111] text-white">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">
        <div class="grid grid-cols-2 gap-x-8 gap-y-10 py-12 md:grid-cols-3 lg:grid-cols-[1.4fr_1fr_1fr_1fr_1.5fr]">

            {{-- brand --}}
            <div class="col-span-2 md:col-span-3 lg:col-span-1">
                <a href="{{ route('home') }}" class="inline-block">
                    @if ($footerLogo)
                        <img src="{{ media($footerLogo) }}" alt="{{ $siteName }}" class="h-11 w-auto">
                    @else
                        @include('layouts.partials.logo-mark', ['size' => 'lg'])
                    @endif
                </a>
                <p class="mt-4 max-w-[220px] text-sm leading-relaxed text-white/70">Car Audio, Electronics &amp; Accessories for Every Vehicle.</p>
                @if ($socials)
                    <div class="mt-5 flex gap-4">
                        @foreach ($socials as $network => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" class="text-white/80 {{ $link }}" aria-label="{{ ucfirst($network) }}"><x-icon name="{{ $network }}" class="h-5 w-5" /></a>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- shop --}}
            <div>
                <h4 class="text-sm font-bold">Shop</h4>
                <ul class="mt-4 space-y-2.5 text-[13px] text-white/70">
                    @forelse (($navCategories ?? collect())->take(5) as $cat)
                        <li><a href="{{ route('products.index', ['category' => $cat->slug]) }}" class="{{ $link }}">{{ $cat->name }}</a></li>
                    @empty
                        @foreach (['Car Audio & Electronics', 'Installation & Integration', 'Speakers & Subwoofers', 'Amplifiers', 'Stereo Receivers'] as $name)
                            <li><a href="{{ route('products.index', ['q' => $name]) }}" class="{{ $link }}">{{ $name }}</a></li>
                        @endforeach
                    @endforelse
                </ul>
            </div>

            {{-- customer service --}}
            <div>
                <h4 class="text-sm font-bold">Customer Service</h4>
                <ul class="mt-4 space-y-2.5 text-[13px] text-white/70">
                    <li><a href="{{ route('contact.index') }}" class="{{ $link }}">Contact Us</a></li>
                    <li><a href="{{ route('track') }}" class="{{ $link }}">Track Your Order</a></li>
                    <li><a href="{{ route('returns') }}" class="{{ $link }}">Returns &amp; Refunds</a></li>
                    <li><a href="{{ route('returns') }}" class="{{ $link }}">Warranty Information</a></li>
                    <li><a href="{{ route('shipping') }}" class="{{ $link }}">Shipping Information</a></li>
                    <li><a href="{{ route('contact.index') }}" class="{{ $link }}">FAQ</a></li>
                </ul>
            </div>

            {{-- useful links --}}
            <div>
                <h4 class="text-sm font-bold">Useful Links</h4>
                <ul class="mt-4 space-y-2.5 text-[13px] text-white/70">
                    <li><a href="{{ route('about') }}" class="{{ $link }}">About Us</a></li>
                    <li><a href="{{ route('blog.index') }}" class="{{ $link }}">Buying Guides &amp; Articles</a></li>
                    <li><a href="{{ route('brands.index') }}" class="{{ $link }}">Brand Directory</a></li>
                    <li><a href="{{ route('contact.index', ['subject' => 'Request a Quote']) }}" class="{{ $link }}">Get a Quote</a></li>
                    <li><a href="{{ route('terms') }}" class="{{ $link }}">Terms &amp; Conditions</a></li>
                    <li><a href="{{ route('privacy') }}" class="{{ $link }}">Privacy Policy</a></li>
                </ul>
            </div>

            {{-- newsletter + payments --}}
            <div class="col-span-2 md:col-span-3 lg:col-span-1">
                <h4 class="text-sm font-bold">Subscribe &amp; Save</h4>
                <p class="mt-4 text-[13px] text-white/70">Get the latest deals, new products and expert tips.</p>
                <form method="POST" action="{{ route('newsletter.subscribe') }}" data-newsletter class="mt-4">
                    @csrf
                    <input type="hidden" name="source" value="footer">
                    <div class="flex h-11 overflow-hidden rounded bg-white">
                        <input type="email" name="email" required placeholder="Enter your email" class="min-w-0 flex-1 px-4 text-sm text-slate-800 outline-none">
                        <button class="shrink-0 bg-amber-500 px-5 text-sm font-bold text-white transition hover:bg-amber-600">Subscribe</button>
                    </div>
                    <p data-newsletter-msg class="mt-2 hidden text-xs font-semibold text-emerald-400"></p>
                </form>
                <x-payment-icons class="mt-5" />
            </div>
        </div>

        <div class="border-t border-white/10 py-5 text-xs text-white/50">
            &copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.
        </div>
    </div>
</footer>
