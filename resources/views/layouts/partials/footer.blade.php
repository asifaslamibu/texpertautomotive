@php
    $siteName   = \App\Models\Setting::get('site_name', 'Texpert Deals');
    $footerLogo = \App\Models\Setting::get('footer_logo');
    $fbUrl  = \App\Models\Setting::get('social_facebook');
    $igUrl  = \App\Models\Setting::get('social_instagram');
    $liUrl  = \App\Models\Setting::get('social_linkedin');
    $addr   = \App\Models\Setting::get('contact_address', '2990 Congressman Ln, Dallas, TX 75220');
    $phone  = \App\Models\Setting::get('contact_phone', '+1-254-358-0017');
    $tollFree = \App\Models\Setting::get('toll_free', '(945) 427-9519');
    $email  = \App\Models\Setting::get('contact_email', 'sales@texpertdeals.us');
    $desc   = \App\Models\Setting::get('site_description', 'Texpert Deals is a Texas-based wholesale distributor putting 1,000+ trusted brands in one catalog at wholesale prices — genuine, OEM, warranty-backed products with volume discounts, Net-30 terms for contractors, and expert support behind every order.');
@endphp
<footer class="mt-20 border-t border-white/10 bg-black">
    <div class="mx-auto max-w-7xl px-4 sm:px-6">

        {{-- ===== trust strip ===== --}}
        <div class="grid grid-cols-2 gap-6 border-b border-white/10 py-8 sm:grid-cols-4">
            @foreach ([['truck','3–5 Day Shipping','On 100,000+ in-stock items'],['shield','Genuine OEM Parts','Warranty-backed & authentic'],['lock','Secure Checkout','Encrypted payments'],['phone','Expert Support','Mon–Fri, 9–6 EST']] as [$ic,$t,$d])
                <div class="flex items-center justify-center gap-3">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-amber-500/10 text-amber-500"><x-icon name="{{ $ic }}" class="h-5 w-5" /></span>
                    <div>
                        <div class="text-sm font-bold text-white">{{ $t }}</div>
                        <div class="text-xs text-slate-400">{{ $d }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ===== main columns ===== --}}
        <div class="grid grid-cols-2 gap-x-8 gap-y-10 py-14 md:grid-cols-3 lg:grid-cols-12">

            {{-- brand + contact --}}
            <div class="col-span-2 md:col-span-3 lg:col-span-4">
                @if ($footerLogo)
                    <img src="{{ media($footerLogo) }}" alt="{{ $siteName }}" class="h-10 w-auto">
                @else
                    <span class="text-2xl font-black text-amber-500">texpert<span class="text-white">deals</span></span>
                @endif
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-slate-300">{{ $desc }}</p>
                <div class="mt-5 space-y-2 text-sm text-slate-300">
                    <p class="flex gap-2"><span class="font-semibold text-white/50">Address:</span> {{ $addr }}</p>
                    <p class="flex gap-2"><span class="font-semibold text-white/50">Call:</span> <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="font-semibold text-amber-500 hover:text-amber-400">{{ $phone }}</a></p>
                    @if ($tollFree)
                        <p class="flex gap-2"><span class="font-semibold text-white/50">Toll-Free:</span> <a href="tel:{{ preg_replace('/[^0-9+]/', '', $tollFree) }}" class="font-semibold text-amber-500 hover:text-amber-400">{{ $tollFree }}</a></p>
                    @endif
                    <p class="flex gap-2"><span class="font-semibold text-white/50">Email:</span> <a href="mailto:{{ $email }}" class="text-amber-500 hover:text-amber-400">{{ $email }}</a></p>
                </div>
                @if ($fbUrl || $igUrl || $liUrl)
                    <div class="mt-6 flex gap-3">
                        @if ($fbUrl)<a href="{{ $fbUrl }}" target="_blank" rel="noopener" class="grid h-9 w-9 place-items-center rounded-full bg-white/10 text-white transition hover:bg-amber-500 hover:text-slate-900" aria-label="Facebook"><x-icon name="facebook" class="h-4 w-4" /></a>@endif
                        @if ($igUrl)<a href="{{ $igUrl }}" target="_blank" rel="noopener" class="grid h-9 w-9 place-items-center rounded-full bg-white/10 text-white transition hover:bg-amber-500 hover:text-slate-900" aria-label="Instagram"><x-icon name="instagram" class="h-4 w-4" /></a>@endif
                        @if ($liUrl)<a href="{{ $liUrl }}" target="_blank" rel="noopener" class="grid h-9 w-9 place-items-center rounded-full bg-white/10 text-white transition hover:bg-amber-500 hover:text-slate-900" aria-label="LinkedIn"><x-icon name="linkedin" class="h-4 w-4" /></a>@endif
                    </div>
                @endif
            </div>

            {{-- shop --}}
            <div class="lg:col-span-2">
                <h4 class="text-xs font-bold uppercase tracking-wider text-amber-500">Shop</h4>
                <ul class="mt-4 space-y-2.5 text-sm text-slate-300">
                    @foreach (($navCategories ?? collect())->take(6) as $cat)
                        <li><a href="{{ route('products.index', ['category' => $cat->slug]) }}" class="transition hover:text-amber-500">{{ $cat->name }}</a></li>
                    @endforeach
                    <li><a href="{{ route('products.index') }}" class="font-semibold text-white transition hover:text-amber-500">All Products →</a></li>
                </ul>
            </div>

            {{-- top brands --}}
            <div class="lg:col-span-2">
                <h4 class="text-xs font-bold uppercase tracking-wider text-amber-500">Top Brands</h4>
                <ul class="mt-4 space-y-2.5 text-sm text-slate-300">
                    @forelse (($footerBrands ?? collect())->take(6) as $brand)
                        <li><a href="{{ $brand->link ?: route('products.index', ['brand' => $brand->name]) }}" class="transition hover:text-amber-500">{{ $brand->name }}</a></li>
                    @empty
                        <li><a href="{{ route('products.index') }}" class="transition hover:text-amber-500">Shop by product</a></li>
                    @endforelse
                    <li><a href="{{ route('brands.index') }}" class="font-semibold text-white transition hover:text-amber-500">All Brands →</a></li>
                </ul>
            </div>

            {{-- support & company --}}
            <div class="lg:col-span-2">
                <h4 class="text-xs font-bold uppercase tracking-wider text-amber-500">Support</h4>
                <ul class="mt-4 space-y-2.5 text-sm text-slate-300">
                    <li><a href="{{ route('about') }}" class="transition hover:text-amber-500">About Us</a></li>
                    <li><a href="{{ route('blog.index') }}" class="transition hover:text-amber-500">Blog</a></li>
                    <li><a href="{{ route('contact.index') }}" class="transition hover:text-amber-500">Contact Us</a></li>
                    <li><a href="{{ route('bulk-order') }}" class="transition hover:text-amber-500">Bulk &amp; Wholesale</a></li>
                    <li><a href="https://texpertdeals.us/public/texpert-line-card/index.html" target="_blank" rel="noopener" class="transition hover:text-amber-500">Line Card</a></li>
                    <li><a href="{{ route('part-request') }}" class="transition hover:text-amber-500">Part Request</a></li>
                    <li><a href="{{ route('returns') }}" class="transition hover:text-amber-500">Return Policy</a></li>
                    <li><a href="{{ route('shipping') }}" class="transition hover:text-amber-500">Shipping Policy</a></li>
                    <li><a href="{{ route('track') }}" class="transition hover:text-amber-500">Track My Order</a></li>
                </ul>
            </div>

            {{-- newsletter + hours --}}
            <div class="col-span-2 lg:col-span-2">
                <h4 class="text-xs font-bold uppercase tracking-wider text-amber-500">Join our list</h4>
                <p class="mt-4 text-sm text-slate-300">Get <span class="font-bold text-white">$5 off</span> your next order plus deals &amp; new arrivals.</p>
                <form method="POST" action="{{ route('newsletter.subscribe') }}" data-newsletter class="mt-3">
                    @csrf
                    <input type="hidden" name="source" value="footer">
                    <input type="email" name="email" required placeholder="Your email address" class="w-full rounded-lg border border-white/15 bg-white/5 px-4 py-2.5 text-sm text-white placeholder-slate-500 outline-none focus:border-amber-500/50">
                    <button class="mt-2 w-full rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-bold text-slate-900 transition hover:bg-amber-400">Subscribe</button>
                    <p data-newsletter-msg class="mt-2 hidden text-xs font-semibold text-emerald-400"></p>
                </form>
                <div class="mt-6 rounded-xl border border-white/10 bg-white/[0.03] p-4">
                    <div class="flex items-center gap-2 text-sm font-bold text-white"><x-icon name="phone" class="h-4 w-4 text-amber-500" /> Need help?</div>
                    <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="mt-1 block text-lg font-black text-amber-500 hover:text-amber-400">{{ $phone }}</a>
                    @if ($tollFree)
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $tollFree) }}" class="block text-sm font-bold text-amber-500 hover:text-amber-400">Toll-Free: {{ $tollFree }}</a>
                    @endif
                    <p class="text-xs text-slate-400">Mon–Fri, 9:00 AM – 6:00 PM EST</p>
                </div>
            </div>
        </div>

        {{-- ===== payment strip ===== --}}
        <div class="flex flex-col items-center justify-between gap-4 border-t border-white/10 py-6 sm:flex-row">
            <span class="text-xs font-semibold uppercase tracking-widest text-slate-400">We accept</span>
            <x-payment-icons />
        </div>

        {{-- ===== copyright ===== --}}
        <div class="flex flex-col items-center justify-between gap-3 border-t border-white/10 py-6 text-sm text-slate-400 sm:flex-row">
            <p>&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
            <div class="flex gap-5">
                <a href="{{ route('privacy') }}" class="transition hover:text-amber-500">Privacy Policy</a>
                <a href="{{ route('terms') }}" class="transition hover:text-amber-500">Terms of Use</a>
                <a href="{{ route('accessibility') }}" class="transition hover:text-amber-500">Accessibility</a>
            </div>
        </div>
    </div>
</footer>
