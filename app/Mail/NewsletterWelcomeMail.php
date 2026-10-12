<?php

namespace App\Mail;

use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $email) {}

    public function envelope(): Envelope
    {
        $site = Setting::get('site_name', 'Texpert Deals');

        return new Envelope(subject: "Welcome to {$site} — here's your \$5 off");
    }

    public function content(): Content
    {
        $site = Setting::get('site_name', 'Texpert Deals');
        $code = Setting::get('newsletter_code', 'WELCOME5');

        $body = "Thanks for joining the {$site} list! 🎉\n\n"
            ."Use code {$code} on your next order to get \$5 off — plus you'll be first to hear about deals, new arrivals and pro tips.";

        return new Content(view: 'emails.confirmation', with: [
            'name' => 'there',
            'bodyText' => $body,
            'signoff' => '— The '.$site.' Team',
            'summary' => [['Your discount code', $code]],
        ]);
    }
}
