<?php

namespace App\Mail;

use App\Models\GiftCard;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Purchase confirmation / receipt sent to the buyer of a gift card.
 */
class GiftCardPurchaseReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly GiftCard $giftCard) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Timber Trace Crafts gift card purchase',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.gift-card-purchase-receipt',
            with: ['giftCard' => $this->giftCard],
        );
    }
}
