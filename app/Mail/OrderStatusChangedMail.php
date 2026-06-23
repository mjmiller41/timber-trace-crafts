<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Order $order) {}

    public function envelope(): Envelope
    {
        $statusLabel = match ($this->order->status) {
            'processing' => 'is being processed',
            'in_production' => 'is in production',
            'shipped' => 'has shipped',
            'delivered' => 'has been delivered',
            'refunded' => 'has been refunded',
            'cancelled' => 'has been cancelled',
            default => 'has been updated',
        };

        return new Envelope(
            subject: 'Your order #'.$this->order->id.' '.$statusLabel,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-status-changed',
        );
    }
}
