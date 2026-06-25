<?php

namespace App\Http\Controllers;

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
        $configuredSecret = config('services.etsy.webhook_secret');

        if (
            empty($configuredSecret) ||
            ! hash_equals($configuredSecret, (string) $request->query('secret', ''))
        ) {
            Log::warning('Etsy webhook rejected: invalid secret', [
                'ip' => $request->ip(),
            ]);

            abort(401);
        }

        $topic = $request->input('topic');

        Log::info('Etsy webhook received', ['topic' => $topic]);

        try {
            match ($topic) {
                'receipt.created', 'receipt.updated' => $this->handleReceipt(),
                'listing.activate', 'listing.deactivate' => $this->handleListingStateChange($request->input('listing_id')),
                'tracking.created' => null, // Etsy echoes our own push; no action needed
                default => Log::info('Unhandled Etsy webhook topic', ['topic' => $topic]),
            };
        } catch (\Throwable $e) {
            Log::error('Etsy webhook processing failed', [
                'topic' => $topic,
                'error' => $e->getMessage(),
            ]);

            // Always return 200 — Etsy retries on non-2xx and we handle failures via logs
        }

        return response()->json(['ok' => true]);
    }

    private function handleReceipt(): void
    {
        $client = new EtsyClient(app(EtsyOAuthService::class));
        (new EtsyOrderSync($client))->sync();
    }

    private function handleListingStateChange(?string $listingId): void
    {
        // App is source of truth; Etsy-side listing state changes are logged only
        Log::info('Etsy listing state changed externally', ['listing_id' => $listingId]);
    }
}
