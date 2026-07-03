<?php

namespace App\Services;

use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Refund;
use Stripe\Stripe;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a PaymentIntent for the given amount.
     *
     * @throws ApiErrorException
     */
    public function createPaymentIntent(int $amountCents, ?string $receiptEmail = null): PaymentIntent
    {
        return PaymentIntent::create([
            'amount' => $amountCents,
            'currency' => 'usd',
            'receipt_email' => $receiptEmail,
            'metadata' => ['store' => config('app.name')],
        ]);
    }

    /**
     * Retrieve a PaymentIntent by ID and verify it succeeded.
     *
     * @throws ApiErrorException
     * @throws \RuntimeException
     */
    public function verifyPaymentIntent(string $paymentIntentId): object
    {
        $intent = PaymentIntent::retrieve($paymentIntentId);

        if ($intent->status !== 'succeeded') {
            throw new \RuntimeException("Payment not completed. Status: {$intent->status}");
        }

        return $intent;
    }

    /**
     * Refund a PaymentIntent, fully or partially.
     *
     * @param  int|null  $amountCents  Amount to refund in cents; null refunds the full remaining balance.
     *
     * @throws ApiErrorException
     */
    public function refundPayment(string $paymentIntentId, ?int $amountCents = null): Refund
    {
        $params = [
            'payment_intent' => $paymentIntentId,
            'reason' => 'requested_by_customer',
        ];

        if ($amountCents !== null) {
            $params['amount'] = $amountCents;
        }

        return Refund::create($params);
    }
}
