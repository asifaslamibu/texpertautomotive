{{-- WordPress-style admin bar — shown on the storefront only to logged-in admins --}}
@auth
    @if (auth()->user()->is_admin)
        <div class="bg-slate-950 text-[13px] text-slate-200">
            <div class="mx-auto flex max-w-7xl items-center gap-1 px-3 py-1.5 sm:px-6">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 rounded px-2 py-1 font-bold text-white transition hover:bg-white/10">
                    <x-icon name="gauge" class="h-4 w-4 text-amber-400" />
                    <span class="hidden sm:inline">{{ \App\Models\Setting::get('site_name', 'Texpert Deals') }}</span>
                    <span class="text-slate-400">Admin</span>
                </a>

                @hasSection('admin_edit_url')
                    <a href="@yield('admin_edit_url')" class="ml-1 flex items-center gap-1.5 rounded bg-amber-400 px-2.5 py-1 font-bold text-slate-900 transition hover:bg-amber-300">
                        <x-icon name="edit" class="h-3.5 w-3.5" />
                        @yield('admin_edit_label', 'Edit')
                    </a>
                @endif

                <div class="ml-auto flex items-center gap-1">
                    <span class="hidden px-2 text-slate-400 md:inline">Hi, {{ \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') ?: auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button class="rounded px-2 py-1 text-slate-300 transition hover:bg-white/10 hover:text-white">Log out</button>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endauth
