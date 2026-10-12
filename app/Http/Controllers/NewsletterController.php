<?php

namespace App\Http\Controllers;

use App\Mail\NewsletterWelcomeMail;
use App\Models\Subscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:160'],
            'source' => ['nullable', 'string', 'max:40'],
        ]);

        $email = strtolower(trim($data['email']));

        $subscriber = Subscriber::firstOrCreate(
            ['email' => $email],
            ['source' => $data['source'] ?? 'website']
        );

        // Send the welcome / $5-off email once, on first subscribe.
        if ($subscriber->wasRecentlyCreated) {
            try {
                Mail::to($email)->send(new NewsletterWelcomeMail($email));
            } catch (\Throwable $e) {
                Log::warning('Newsletter welcome email failed for '.$email.': '.$e->getMessage());
            }
        }

        $message = $subscriber->wasRecentlyCreated
            ? "You're in! Check your inbox for your $5 off code."
            : "You're already on the list — thanks!";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }

        return back()->with('status', $message);
    }
}
