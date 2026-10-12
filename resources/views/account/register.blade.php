@extends('layouts.app')

@section('title', 'Create Account — Texpert Deals')
@section('robots', 'noindex, follow')

@section('content')
<div class="mx-auto max-w-md px-4 py-14 sm:px-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-black tracking-tight text-slate-900">Create account</h1>
        <p class="mt-1 text-sm text-slate-500">Track orders and check out faster.</p>

        @if ($errors->any())
            <div class="mt-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <ul class="list-inside list-disc">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('account.register.attempt') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700">Full name</label>
                <input name="name" value="{{ old('name') }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700">Phone <span class="font-normal text-slate-400">(optional)</span></label>
                <input name="phone" value="{{ old('phone') }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Password</label>
                    <input type="password" name="password" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold text-slate-700">Confirm</label>
                    <input type="password" name="password_confirmation" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
                </div>
            </div>
            <button class="w-full rounded-lg bg-amber-400 py-3 text-sm font-black uppercase tracking-wide text-slate-900 transition hover:bg-amber-500">Create Account</button>
        </form>

        <p class="mt-5 text-center text-sm text-slate-500">
            Already have an account? <a href="{{ route('account.login') }}" class="font-semibold text-amber-600 hover:text-amber-700">Sign in</a>
        </p>
    </div>
</div>
@endsection
