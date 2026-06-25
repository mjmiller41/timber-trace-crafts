<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\Etsy\EtsyClient;
use App\Services\Etsy\EtsyOAuthService;
use App\Services\Etsy\EtsyOrderSync;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            // Always return 200 — Etsy retries on non-2xx
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

        // webhook-signature header may contain multiple comma-separated signatures
        foreach (explode(' ', $webhookSignature) as $sig) {
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

        if ($order) {
            $order->update(['etsy_is_paid' => true, 'status' => 'processing']);
        } else {
            // New order — fetch full receipt and import it
            $receipt = $this->fetchReceipt($resourceUrl);

            if ($receipt) {
                app(EtsyOrderSync::class)->importReceipt($receipt);
            }
        }
    }

    private function handleOrderCanceled(string $resourceUrl): void
    {
        $receiptId = $this->extractReceiptId($resourceUrl);

        if (! $receiptId) {
            return;
        }

        Order::where('etsy_receipt_id', $receiptId)->update(['status' => 'cancelled']);
    }

    private function handleOrderShipped(string $resourceUrl): void
    {
        $receiptId = $this->extractReceiptId($resourceUrl);

        if (! $receiptId) {
            return;
        }

        Order::where('etsy_receipt_id', $receiptId)->update([
            'etsy_is_shipped' => true,
            'status' => 'shipped',
        ]);
    }

    private function handleOrderDelivered(string $resourceUrl): void
    {
        $receiptId = $this->extractReceiptId($resourceUrl);

        if (! $receiptId) {
            return;
        }

        Order::where('etsy_receipt_id', $receiptId)->update(['status' => 'delivered']);
    }

    private function extractReceiptId(string $resourceUrl): ?string
    {
        if (preg_match('/\/receipts\/(\d+)/', $resourceUrl, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function fetchReceipt(string $resourceUrl): ?array
    {
        try {
            $oauth = app(EtsyOAuthService::class);
            $client = new EtsyClient($oauth);
            // Extract path after /v3 from resource_url
            $path = preg_replace('#^https://api\.etsy\.com/v3#', '', $resourceUrl);

            return $client->get($path);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch Etsy receipt', ['url' => $resourceUrl, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
