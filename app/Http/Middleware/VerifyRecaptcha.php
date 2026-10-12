<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VerifyRecaptcha
{
    /**
     * Verify Google reCAPTCHA v2 on form submissions.
     * If reCAPTCHA isn't enabled/configured, requests pass through unchanged.
     */
    public function handle(Request $request, Closure $next)
    {
        $enabled = Setting::get('recaptcha_enabled') && trim((string) Setting::get('recaptcha_secret_key')) !== '';
        if (! $enabled) {
            return $next($request);
        }

        $token = (string) $request->input('g-recaptcha-response');

        $ok = false;
        if ($token !== '') {
            try {
                $resp = Http::asForm()->timeout(10)->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret' => Setting::get('recaptcha_secret_key'),
                    'response' => $token,
                    'remoteip' => $request->ip(),
                ]);
                $ok = (bool) $resp->json('success');
            } catch (\Throwable $e) {
                $ok = false;
            }
        }

        if (! $ok) {
            return back()->withInput()->withErrors([
                'captcha' => 'Please confirm you are not a robot and try again.',
            ]);
        }

        return $next($request);
    }
}
