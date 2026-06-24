<?php

namespace App\Mail;

use App\Models\ProductVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RestockNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly ProductVariant $variant) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->variant->product->name.' is back in stock!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.restock-notification',
        );
    }
}
