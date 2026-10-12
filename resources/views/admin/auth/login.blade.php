<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Sign In — {{ \App\Models\Setting::get('site_name', 'Texpert Deals') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900" rel="stylesheet">
    @php $favicon = \App\Models\Setting::get('favicon'); @endphp
    @if ($favicon)
        <link rel="icon" href="{{ media($favicon) }}">
        <link rel="shortcut icon" href="{{ media($favicon) }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>:root{--font-weight-black:700}body{font-family:'Inter',sans-serif}</style>
</head>
<body class="min-h-screen bg-slate-950 text-slate-100">
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10">
        {{-- ambient background --}}
        <div class="pointer-events-none absolute -left-24 -top-24 h-96 w-96 animate-blob rounded-full bg-amber-500/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 -right-24 h-96 w-96 animate-blob rounded-full bg-amber-500/10 blur-3xl" style="animation-delay:-5s"></div>
        <div class="pointer-events-none absolute inset-0 opacity-[0.04] [background-image:linear-gradient(#fff_1px,transparent_1px),linear-gradient(90deg,#fff_1px,transparent_1px)] [background-size:42px_42px]"></div>

        <div data-reveal class="relative w-full max-w-md">
            {{-- brand --}}
            <div class="mb-8 text-center">
                @php $headerLogo = \App\Models\Setting::get('header_logo'); @endphp
                @if ($headerLogo)
                    <img src="{{ media($headerLogo) }}" alt="{{ \App\Models\Setting::get('site_name', 'Texpert Deals') }}" class="mx-auto h-12 w-auto">
                @else
                    <span class="inline-grid h-14 w-14 place-items-center rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 shadow-lg shadow-amber-900/30">
                        <x-icon name="lock" class="h-7 w-7 text-slate-900" />
                    </span>
                @endif
                <h1 class="mt-4 text-2xl font-black tracking-tight">{{ \App\Models\Setting::get('site_name', 'Texpert Deals') }}</h1>
                <p class="mt-1 text-sm text-slate-400">Admin Dashboard — sign in to continue</p>
            </div>

            {{-- card --}}
            <div class="rounded-2xl border border-white/10 bg-white/[0.04] p-8 shadow-2xl shadow-black/40 backdrop-blur">
                @if ($errors->any())
                    <div class="mb-5 flex items-start gap-2.5 rounded-lg border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
                        <svg class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login.attempt') }}" class="space-y-5" data-login-form>
                    @csrf

                    {{-- email --}}
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-300">Email address</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500">
                                <x-icon name="user" class="h-5 w-5" />
                            </span>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                                   autocomplete="email" placeholder="you@company.com"
                                   class="w-full rounded-lg border border-white/10 bg-slate-900/60 py-2.5 pl-10 pr-4 text-sm outline-none transition placeholder:text-slate-600 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30">
                        </div>
                    </div>

                    {{-- password --}}
                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-slate-300">Password</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500">
                                <x-icon name="lock" class="h-5 w-5" />
                            </span>
                            <input id="password" type="password" name="password" required
                                   autocomplete="current-password" placeholder="••••••••" data-password
                                   class="w-full rounded-lg border border-white/10 bg-slate-900/60 py-2.5 pl-10 pr-12 text-sm outline-none transition placeholder:text-slate-600 focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30">
                            <button type="button" data-toggle-password aria-label="Show password"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 rounded px-2 py-1 text-[11px] font-bold text-slate-500 transition hover:text-amber-400">Show</button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-400">
                            <input type="checkbox" name="remember" class="h-4 w-4 rounded border-white/20 bg-slate-900 text-amber-500 focus:ring-amber-400/30"> Remember me
                        </label>
                    </div>

                    <button type="submit" data-submit
                            class="sheen flex w-full items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-amber-400 to-amber-500 py-3 text-sm font-bold text-slate-900 shadow-lg shadow-amber-900/20 transition hover:-translate-y-0.5 hover:shadow-xl disabled:cursor-not-allowed disabled:opacity-70">
                        <span data-submit-label>Sign In</span>
                        <x-icon name="arrow" class="h-4 w-4" />
                    </button>
                </form>
            </div>

            <p class="mt-6 flex items-center justify-center gap-1.5 text-center text-xs text-slate-500">
                <x-icon name="shield" class="h-3.5 w-3.5" /> Secure area — authorised personnel only.
            </p>
            <p class="mt-3 text-center text-sm text-slate-500">
                <a href="{{ route('home') }}" class="transition hover:text-amber-400">← Back to store</a>
            </p>
        </div>
    </div>

    <script>
        (function () {
            // password show / hide
            const toggle = document.querySelector('[data-toggle-password]');
            const pwd = document.querySelector('[data-password]');
            if (toggle && pwd) {
                toggle.addEventListener('click', () => {
                    const show = pwd.type === 'password';
                    pwd.type = show ? 'text' : 'password';
                    toggle.textContent = show ? 'Hide' : 'Show';
                });
            }
            // prevent double submit
            const form = document.querySelector('[data-login-form]');
            if (form) {
                form.addEventListener('submit', () => {
                    const btn = form.querySelector('[data-submit]');
                    const label = form.querySelector('[data-submit-label]');
                    if (btn) btn.disabled = true;
                    if (label) label.textContent = 'Signing in…';
                });
            }
        })();
    </script>
</body>
</html>
