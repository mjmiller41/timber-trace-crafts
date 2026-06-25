<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EtsyNewOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🛒 New Etsy Order #'.$this->order->id.' — $'.number_format($this->order->total, 2),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.etsy-new-order',
        );
    }
}
