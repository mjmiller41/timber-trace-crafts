<?php

namespace App\Http\Controllers;

use App\Jobs\ImportEtsyOrder;
use App\Jobs\UpdateEtsyOrderStatus;
use App\Mail\EtsyNewOrderMail;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EtsyWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();

        if (! $this->verifySignature($request, $rawBody)) {
            Log::warning('Etsy webhook rejected: invalid signature', ['ip' => $request->ip()]);
            abort(401);
        }

        $payload = json_decode($rawBody, true);
        $eventType = $payload['event_type'] ?? null;
        $resourceUrl = $payload['resource_url'] ?? null;

        Log::info('Etsy webhook received', ['event_type' => $eventType]);

        try {
            match ($eventType) {
                'order.paid' => $this->handleOrderPaid($resourceUrl),
                'order.canceled' => $this->handleOrderCanceled($resourceUrl),
                'order.shipped' => $this->handleOrderShipped($resourceUrl),
                'order.delivered' => $this->handleOrderDelivered($resourceUrl),
                default => Log::info('Unhandled Etsy webhook event', ['event_type' => $eventType]),
            };
        } catch (\Throwable $e) {
            Log::error('Etsy webhook processing failed', [
                'event_type' => $eventType,
                'error' => $e->getMessage(),
            ]);

            // A genuine processing failure must not ACK — Etsy only redelivers on non-2xx.
            return response()->json(['ok' => false], 500);
        }

        return response()->json(['ok' => true]);
    }

    private function verifySignature(Request $request, string $rawBody): bool
    {
        $secret = config('services.etsy.webhook_secret');

        if (empty($secret)) {
            Log::error('Etsy webhook_secret is not configured.');

            return false;
        }

        $webhookId = $request->header('webhook-id');
        $webhookTimestamp = $request->header('webhook-timestamp');
        $webhookSignature = $request->header('webhook-signature');

        if (! $webhookId || ! $webhookTimestamp || ! $webhookSignature) {
            return false;
        }

        // Reject replayed requests older than 5 minutes
        if (abs(time() - (int) $webhookTimestamp) > 300) {
            Log::warning('Etsy webhook rejected: stale timestamp', ['timestamp' => $webhookTimestamp]);

            return false;
        }

        // Derive secret: strip whsec_ prefix then base64-decode
        $secretPart = str_starts_with($secret, 'whsec_') ? substr($secret, 6) : $secret;
        $secretBytes = base64_decode($secretPart);

        $signedContent = "{$webhookId}.{$webhookTimestamp}.{$rawBody}";
        $expectedSig = base64_encode(hash_hmac('sha256', $signedContent, $secretBytes, true));

        // The header is a space-separated list of versioned signatures, each
        // formatted "v1,<base64sig>" (Svix/Etsy). Strip the scheme prefix and
        // compare the signature portion against our expected value.
        foreach (explode(' ', $webhookSignature) as $versionedSignature) {
            $sig = str_contains($versionedSignature, ',')
                ? explode(',', $versionedSignature, 2)[1]
                : $versionedSignature;

            if (hash_equals($expectedSig, $sig)) {
                return true;
            }
        }

        return false;
    }

    private function handleOrderPaid(string $resourceUrl): void
    {
        $receiptId = $this->extractReceiptId($resourceUrl);

        if (! $receiptId) {
            return;
        }

        $order = Order::where('etsy_receipt_id', $receiptId)->first();

        if (! $order) {
            // New order — import asynchronously to avoid blocking the webhook
            // response. The job persists the order, then increments the counter
            // and notifies the admin (the order does not exist yet here).
            ImportEtsyOrder::dispatch($resourceUrl);

            return;
        }

        $order->update(['etsy_is_paid' => true, 'status' => 'processing']);

        Cache::increment('etsy.new_orders');

        Mail::to(config('mail.from.address'))
            ->queue(new EtsyNewOrderMail($order->load('items')));
    }

    private function handleOrderCanceled(string $resourceUrl): void
    {
        $receiptId = $this->extractReceiptId($resourceUrl);

        if (! $receiptId) {
            return;
        }

        UpdateEtsyOrderStatus::dispatch($receiptId, 'canceled');
    }

    private function handleOrderShipped(string $resourceUrl): void
    {
        $receiptId = $this->extractReceiptId($resourceUrl);

        if (! $receiptId) {
            return;
        }

        UpdateEtsyOrderStatus::dispatch($receiptId, 'shipped');
    }

    private function handleOrderDelivered(string $resourceUrl): void
    {
        $receiptId = $this->extractReceiptId($resourceUrl);

        if (! $receiptId) {
            return;
        }

        UpdateEtsyOrderStatus::dispatch($receiptId, 'delivered');
    }

    private function extractReceiptId(string $resourceUrl): ?string
    {
        if (preg_match('/\/receipts\/(\d+)/', $resourceUrl, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
