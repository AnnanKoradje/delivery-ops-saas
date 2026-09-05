<?php

namespace App\Mail;

use App\Models\Delivery;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeliveryTrackingUpdateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Delivery $delivery, public string $trackingUrl, public ?string $replyToEmail) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address((string) config('delivery-email.from_address'), (string) config('delivery-email.from_name')),
            replyTo: $this->replyToEmail ? [new Address($this->replyToEmail)] : [],
            subject: 'Delivery update: '.$this->delivery->reference,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.delivery-tracking-update');
    }
}
