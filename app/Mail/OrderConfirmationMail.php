<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        $site = \App\Models\Setting::get('site_name', 'Texpert Deals');

        return new Envelope(subject: 'Order confirmed — '.$this->order->order_number.' · '.$site);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-customer', with: ['order' => $this->order]);
    }
}
