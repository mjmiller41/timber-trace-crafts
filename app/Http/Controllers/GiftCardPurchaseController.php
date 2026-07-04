<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use App\Support\Analytics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * Self-service gift-card purchase. The buyer picks an amount + recipient here
 * and pays via Stripe; the card is only issued once the payment is confirmed
 * server-side (StripeWebhookController), never from the client's success call.
 */
class GiftCardPurchaseController extends Controller
{
    public function __construct(
        private readonly StripeService $stripeService,
    ) {}

    public function show(): View
    {
        return view('gift-cards.purchase', [
            'tiers' => config('giftcards.tiers'),
            'min' => (int) config('giftcards.min'),
            'max' => (int) config('giftcards.max'),
        ]);
    }

    /**
     * Validate the purchase and create a Stripe PaymentIntent (called via AJAX).
     * All fulfilment details ride along in the intent metadata so the webhook
     * can issue the card without trusting anything the browser reports back.
     */
    public function createPaymentIntent(Request $request): JsonResponse
    {
        $min = (int) config('giftcards.min');
        $max = (int) config('giftcards.max');

        // Validate explicitly and return JSON: this is an AJAX endpoint, but the
        // app only auto-renders JSON errors for api/* routes (bootstrap/app.php),
        // so a thrown ValidationException here would redirect instead.
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'integer', "min:$min", "max:$max"],
            'recipient_email' => ['required', 'email', 'max:255'],
            'recipient_name' => ['nullable', 'string', 'max:100'],
            'purchaser_email' => ['required', 'email', 'max:255'],
            'purchaser_name' => ['nullable', 'string', 'max:100'],
            'message' => ['nullable', 'string', 'max:450'],
            'send_date' => ['nullable', 'date', 'after_or_equal:today'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $amountCents = (int) round($validated['amount'] * 100);

        $metadata = [
            'type' => 'giftcard_purchase',
            'amount' => (string) $validated['amount'],
            'recipient_email' => $validated['recipient_email'],
            'recipient_name' => $validated['recipient_name'] ?? '',
            'purchaser_email' => $validated['purchaser_email'],
            'purchaser_name' => $validated['purchaser_name'] ?? '',
            'message' => $validated['message'] ?? '',
            'send_date' => $validated['send_date'] ?? '',
        ];

        try {
            $intent = $this->stripeService->createPaymentIntent(
                $amountCents,
                $validated['purchaser_email'],
                $metadata,
            );

            Analytics::record('giftcard_purchase_intent', ['value' => $validated['amount']]);

            return response()->json(['client_secret' => $intent->client_secret]);
        } catch (\Throwable $e) {
            Log::error('Gift-card PaymentIntent creation failed', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Could not initialise payment. Please try again.'], 500);
        }
    }

    /**
     * Post-payment landing page. Purely informational — issuance and emailing
     * happen asynchronously off the confirmed Stripe webhook, so we never key
     * anything off the id the browser hands us here.
     */
    public function thankYou(): View
    {
        return view('gift-cards.thank-you');
    }
}
