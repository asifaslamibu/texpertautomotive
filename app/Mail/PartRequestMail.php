<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PartRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $data) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New part request — '.($this->data['part_name'] ?? 'Website'),
            replyTo: [new Address($this->data['email'], $this->data['name'] ?? '')],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.part-request', with: ['data' => $this->data]);
    }
}
