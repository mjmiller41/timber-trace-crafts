<?php

namespace App\Mail;

use App\Models\GiftCard;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent to the recipient of a self-service gift-card purchase. Carries the
 * code, balance, optional personal message, and a redeem-at-checkout CTA.
 */
class GiftCardIssuedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly GiftCard $giftCard) {}

    public function envelope(): Envelope
    {
        $from = $this->giftCard->recipient_name
            ? "You've received a Timber Trace Crafts gift card"
            : 'Your Timber Trace Crafts gift card';

        return new Envelope(subject: $from);
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.gift-card-issued',
            with: ['giftCard' => $this->giftCard],
        );
    }
}
