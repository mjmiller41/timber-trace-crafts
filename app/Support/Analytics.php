<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * First-party, privacy-respecting funnel event recorder.
 *
 * Records the key commerce conversion events server-side to a dedicated log
 * channel. This is the ad-blocker-proof source of truth for the revenue funnel
 * and complements the client-side Umami tracker (page views + engagement).
 *
 * Only non-PII attributes should be passed (product/order ids, counts, amounts).
 * Never pass names, emails, addresses, or payment details.
 */
class Analytics
{
    /**
     * Canonical funnel events. Keep this list in sync with the baseline report
     * (docs/analytics-seo-baseline.md) and any client-side data-umami-event tags.
     */
    public const EVENTS = [
        'add_to_cart',
        'begin_checkout',
        'purchase',
    ];

    /**
     * Record a funnel event. Never throws — analytics must not break a request.
     *
     * @param  array<string, scalar|null>  $props
     */
    public static function record(string $event, array $props = []): void
    {
        if (! config('analytics.server_events.enabled', true)) {
            return;
        }

        try {
            $channel = config('analytics.server_events.channel', 'analytics');

            Log::channel($channel)->info('funnel.'.$event, $props + [
                'event' => $event,
                'session' => substr(hash('sha256', (string) session()->getId()), 0, 16),
            ]);
        } catch (Throwable $e) {
            // Swallow: a logging failure must never affect the customer flow.
        }
    }
}
