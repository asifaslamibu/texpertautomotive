<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Notify
{
    /**
     * Send a form submission: notify the store admin + confirm to the customer.
     * Mail failures never break the request — they're logged and the submission is recorded.
     */
    public static function send(
        Mailable $adminMailable,
        ?string $customerEmail = null,
        ?Mailable $confirmation = null,
        string $logLabel = 'Form submission',
        array $logData = [],
    ): void {
        // Always keep a record so nothing is ever lost.
        Log::info($logLabel, $logData);

        $adminTo = Setting::get('contact_email') ?: config('mail.from.address');

        try {
            if ($adminTo) {
                Mail::to($adminTo)->send($adminMailable);
            }
            if ($confirmation && $customerEmail) {
                Mail::to($customerEmail)->send($confirmation);
            }
        } catch (\Throwable $e) {
            Log::warning($logLabel.' — email send failed: '.$e->getMessage());
        }
    }
}
