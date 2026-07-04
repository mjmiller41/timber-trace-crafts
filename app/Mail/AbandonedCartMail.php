<?php

namespace App\Mail;

use App\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbandonedCartMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Cart $cart,
        public readonly int $stage = 1,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->stage >= 2
            ? 'Still thinking it over? Your Timber Trace Crafts cart is waiting'
            : 'You left something behind at Timber Trace Crafts';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.abandoned-cart',
            with: [
                'items' => $this->cart->contents ?? [],
                'subtotal' => (float) $this->cart->subtotal,
                'cartUrl' => route('cart.index'),
                'unsubscribeUrl' => route('cart.unsubscribe', ['token' => $this->cart->unsubscribe_token]),
                'stage' => $this->stage,
            ],
        );
    }
}
