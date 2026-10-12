<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'perm' => \App\Http\Middleware\EnsurePermission::class,
            'recaptcha' => \App\Http\Middleware\VerifyRecaptcha::class,
        ]);

        // Stripe posts to the webhook without a CSRF token.
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',
        ]);

        // Unauthenticated customers go to the storefront account login.
        $middleware->redirectGuestsTo(fn () => route('account.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Gracefully handle an expired session / CSRF token (419 "Page Expired").
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            // If they were logging out, finish the logout cleanly instead of erroring.
            if ($request->routeIs('admin.logout') || str_contains($request->path(), 'logout')) {
                \Illuminate\Support\Facades\Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('admin.login')->with('status', 'You have been logged out.');
            }

            // Any other expired form: send them back with a friendly message and their input.
            return redirect()->back()
                ->withInput($request->except('_token', 'password', 'password_confirmation'))
                ->withErrors(['error' => 'Your session expired — please try again.']);
        });
    })->create();
