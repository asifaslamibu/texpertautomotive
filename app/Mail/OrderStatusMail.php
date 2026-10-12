<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        $site = \App\Models\Setting::get('site_name', 'Texpert Deals');
        $labels = [
            'processing' => 'is being processed',
            'shipped'    => 'has shipped',
            'completed'  => 'is complete',
            'cancelled'  => 'has been cancelled',
            'pending'    => 'is pending',
        ];
        $verb = $labels[$this->order->status] ?? 'has been updated';

        return new Envelope(subject: 'Order '.$this->order->order_number.' '.$verb.' — '.$site);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-status', with: ['order' => $this->order]);
    }
}
