<?php

namespace App\Services;

use App\Models\GiftCard;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class GiftCardService
{
    /**
     * Issue a new gift card with the given starting balance.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function issue(float $amount, array $attributes = []): GiftCard
    {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw new \InvalidArgumentException('Gift card amount must be greater than zero.');
        }

        return DB::transaction(function () use ($amount, $attributes) {
            $card = GiftCard::create(array_merge([
                'code' => GiftCard::generateCode(),
                'initial_balance' => $amount,
                'balance' => $amount,
                'currency' => 'USD',
                'active' => true,
            ], $attributes));

            $card->transactions()->create([
                'amount' => $amount,
                'balance_after' => $amount,
                'type' => 'issue',
                'note' => $attributes['issue_note'] ?? 'Gift card issued.',
            ]);

            return $card;
        });
    }

    /**
     * Issue a card for a confirmed self-service online purchase, keyed by the
     * Stripe PaymentIntent id. Idempotent: a redelivered webhook (or a race
     * between two deliveries) never issues a second card for the same payment.
     *
     * @param  array<string, mixed>  $attributes
     * @return array{0: GiftCard, 1: bool} [card, wasNewlyCreated]
     */
    public function fulfillPurchase(string $paymentIntentId, float $amount, array $attributes = []): array
    {
        $existing = GiftCard::where('purchase_payment_intent_id', $paymentIntentId)->first();

        if ($existing) {
            return [$existing, false];
        }

        try {
            $card = $this->issue($amount, array_merge($attributes, [
                'purchase_payment_intent_id' => $paymentIntentId,
                'issue_note' => 'Purchased online — Stripe '.$paymentIntentId,
            ]));
        } catch (UniqueConstraintViolationException) {
            // A concurrent duplicate webhook won the race; return the card it made.
            return [GiftCard::where('purchase_payment_intent_id', $paymentIntentId)->firstOrFail(), false];
        }

        return [$card, true];
    }

    /**
     * Redeem up to $amount from the card as tender against an order.
     *
     * Re-reads the card under a row lock so concurrent checkouts can never
     * double-spend the balance. Returns the amount actually redeemed (which
     * may be less than requested if the balance was partially consumed by a
     * concurrent order). MUST be called inside a database transaction so the
     * lock is held until the surrounding order commit.
     */
    public function redeem(GiftCard $card, float $amount, ?int $orderId = null, string $note = 'Redeemed at checkout.'): float
    {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            return 0.0;
        }

        $locked = GiftCard::whereKey($card->id)->lockForUpdate()->first();

        if (! $locked || ! $locked->isRedeemable()) {
            return 0.0;
        }

        $applied = round(min($amount, (float) $locked->balance), 2);

        if ($applied <= 0) {
            return 0.0;
        }

        $balanceAfter = round((float) $locked->balance - $applied, 2);

        $locked->forceFill(['balance' => $balanceAfter])->save();

        $locked->transactions()->create([
            'order_id' => $orderId,
            'amount' => -$applied,
            'balance_after' => $balanceAfter,
            'type' => 'redeem',
            'note' => $note,
        ]);

        // Keep the caller's in-memory model consistent with the locked write.
        $card->forceFill(['balance' => $balanceAfter]);

        return $applied;
    }

    /**
     * Credit an amount back to a card (e.g. on order refund/cancel).
     */
    public function refund(GiftCard $card, float $amount, ?int $orderId = null, string $note = 'Refunded to gift card.'): float
    {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            return 0.0;
        }

        return DB::transaction(function () use ($card, $amount, $orderId, $note) {
            $locked = GiftCard::whereKey($card->id)->lockForUpdate()->first();

            if (! $locked) {
                return 0.0;
            }

            $balanceAfter = round((float) $locked->balance + $amount, 2);
            $locked->forceFill(['balance' => $balanceAfter])->save();

            $locked->transactions()->create([
                'order_id' => $orderId,
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'type' => 'refund',
                'note' => $note,
            ]);

            $card->forceFill(['balance' => $balanceAfter]);

            return $amount;
        });
    }
}
