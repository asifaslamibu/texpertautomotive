@extends('layouts.app')

@section('title', 'Sign In — Texpert Deals')
@section('robots', 'noindex, follow')

@section('content')
<div class="mx-auto max-w-md px-4 py-14 sm:px-6">
    <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
        <h1 class="text-2xl font-black tracking-tight text-slate-900">Sign in</h1>
        <p class="mt-1 text-sm text-slate-500">Access your orders and account details.</p>

        @if ($errors->any())
            <div class="mt-5 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('account.login.attempt') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700">Password</label>
                <input type="password" name="password" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-100">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="rounded border-slate-300 text-amber-500"> Remember me
            </label>
            <button class="w-full rounded-lg bg-slate-900 py-3 text-sm font-black uppercase tracking-wide text-white transition hover:bg-slate-800">Sign In</button>
        </form>

        <p class="mt-5 text-center text-sm text-slate-500">
            New here? <a href="{{ route('account.register') }}" class="font-semibold text-amber-600 hover:text-amber-700">Create an account</a>
        </p>
    </div>
</div>
@endsection
