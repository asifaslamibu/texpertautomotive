<?php

namespace App\Mail;

use App\Models\PurchaseOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $pdf  Raw PDF bytes to attach.
     * @param  string|null  $recipientName  Who this PO is being sent to (e.g. the distributor).
     */
    public function __construct(public PurchaseOrder $po, public string $pdf, public ?string $recipientName = null) {}

    public function envelope(): Envelope
    {
        $site = \App\Models\Setting::get('site_name', 'Texpert Deals');

        return new Envelope(subject: 'Purchase Order '.$this->po->po_number.' — '.$site);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.purchase-order', with: [
            'po' => $this->po,
            'recipientName' => $this->recipientName,
        ]);
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdf, $this->po->po_number.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
