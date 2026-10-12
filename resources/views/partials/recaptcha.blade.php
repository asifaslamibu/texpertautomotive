@php
    $rcEnabled = \App\Models\Setting::get('recaptcha_enabled');
    $rcSiteKey = trim((string) \App\Models\Setting::get('recaptcha_site_key'));
@endphp
@if ($rcEnabled && $rcSiteKey)
    <div class="mt-2">
        <div class="g-recaptcha" data-sitekey="{{ $rcSiteKey }}"></div>
        @error('captcha')
            <p class="mt-2 text-sm font-medium text-red-600">{{ $message }}</p>
        @enderror
    </div>
    @once
        @push('scripts')
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        @endpush
    @endonce
@endif
