<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New order '.$this->order->order_number.' — $'.number_format($this->order->total, 2),
            replyTo: [new Address($this->order->customer->email, $this->order->customer->name)],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-admin', with: ['order' => $this->order]);
    }
}
