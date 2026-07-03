<?php

namespace App\Http\Controllers;

use App\Mail\OrderStatusChangedMail;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $secret = config('services.stripe.webhook_secret');

        if (empty($secret)) {
            Log::error('Stripe webhook_secret is not configured.');

            return response()->json(['ok' => false], 400);
        }

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature', ''),
                $secret
            );
        } catch (\UnexpectedValueException $e) {
            Log::warning('Stripe webhook rejected: invalid payload', ['ip' => $request->ip()]);

            return response()->json(['ok' => false], 400);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook rejected: invalid signature', ['ip' => $request->ip()]);

            return response()->json(['ok' => false], 400);
        }

        Log::info('Stripe webhook received', ['type' => $event->type]);

        try {
            match ($event->type) {
                'charge.refunded' => $this->handleChargeRefunded($event->data->object),
                'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($event->data->object),
                'payment_intent.payment_failed' => $this->handlePaymentIntentFailed($event->data->object),
                default => Log::info('Unhandled Stripe webhook event', ['type' => $event->type]),
            };
        } catch (\Throwable $e) {
            Log::error('Stripe webhook processing failed', [
                'type' => $event->type,
                'error' => $e->getMessage(),
            ]);

            // Non-2xx tells Stripe to redeliver — do not ACK a genuine failure.
            return response()->json(['ok' => false], 500);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Reflect a refund (including one issued from the Stripe Dashboard) back
     * onto the local order. Idempotent: a refund we issued ourselves through
     * the admin card has already updated the order, so a matching cumulative
     * amount is a no-op and never re-emails the customer.
     */
    private function handleChargeRefunded(object $charge): void
    {
        $paymentIntentId = is_string($charge->payment_intent ?? null) ? $charge->payment_intent : null;

        if (! $paymentIntentId) {
            return;
        }

        $order = Order::where('stripe_payment_intent_id', $paymentIntentId)->first();

        if (! $order) {
            Log::warning('Stripe refund for unknown order', ['payment_intent' => $paymentIntentId]);

            return;
        }

        $refundedTotal = round((int) $charge->amount_refunded / 100, 2);

        // Already in sync (e.g. we issued this refund ourselves) — nothing to do.
        if (abs((float) $order->refunded_amount - $refundedTotal) < 0.01) {
            return;
        }

        $wasFullyRefunded = $order->status === 'refunded';
        $isFullyRefunded = $refundedTotal >= (float) $order->total;

        $order->update([
            'refunded_amount' => $refundedTotal,
            'refunded_at' => now(),
            'stripe_refund_id' => $this->latestRefundId($charge) ?? $order->stripe_refund_id,
            'status' => $isFullyRefunded ? 'refunded' : $order->status,
        ]);

        $order->statusHistory()->create([
            'status' => $isFullyRefunded ? 'refunded' : $order->status,
            'note' => 'Refund synced from Stripe — $'.number_format($refundedTotal, 2).' refunded total.',
            'created_by' => null,
        ]);

        if ($isFullyRefunded && ! $wasFullyRefunded) {
            try {
                $email = $order->user?->email ?? $order->guest_email;
                if ($email) {
                    Mail::to($email)->queue(new OrderStatusChangedMail($order));
                }
            } catch (\Throwable $e) {
                Log::error('Refund-sync email failed', ['order' => $order->id, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * A successful payment with no local order means the buyer was charged but
     * checkout never persisted the order (e.g. the browser died after Stripe
     * confirmed). We can't rebuild the cart here, so surface it for manual
     * follow-up rather than silently dropping it.
     */
    private function handlePaymentIntentSucceeded(object $intent): void
    {
        if (Order::where('stripe_payment_intent_id', $intent->id)->exists()) {
            return;
        }

        Log::warning('Stripe payment succeeded with no matching order', [
            'payment_intent' => $intent->id,
            'amount' => $intent->amount_received ?? $intent->amount ?? null,
            'email' => $intent->receipt_email ?? null,
        ]);
    }

    private function handlePaymentIntentFailed(object $intent): void
    {
        Log::info('Stripe payment failed', [
            'payment_intent' => $intent->id,
            'email' => $intent->receipt_email ?? null,
        ]);
    }

    /**
     * Pull the most recent refund id off a Charge's embedded refunds list, if present.
     */
    private function latestRefundId(object $charge): ?string
    {
        $refunds = $charge->refunds->data ?? null;

        if (empty($refunds)) {
            return null;
        }

        return $refunds[0]->id ?? null;
    }
}
