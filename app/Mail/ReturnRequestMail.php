<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReturnRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $data) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Return request — order '.($this->data['order_number'] ?? 'Website'),
            replyTo: [new Address($this->data['email'], $this->data['name'] ?? '')],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.return-request', with: ['data' => $this->data]);
    }
}
