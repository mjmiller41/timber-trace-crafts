<?php

namespace App\Jobs;

use App\Mail\EtsyNewOrderMail;
use App\Services\Etsy\EtsyOrderSync;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ImportEtsyOrder implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public readonly string $resourceUrl) {}

    public function handle(): void
    {
        $order = app(EtsyOrderSync::class)->importFromResourceUrl($this->resourceUrl);

        if (! $order) {
            return;
        }

        // This job only runs for order.paid webhooks, so mark the freshly
        // imported order as paid and notify the admin once it's persisted.
        $order->update(['etsy_is_paid' => true]);

        Cache::increment('etsy.new_orders');

        Mail::to(config('mail.from.address'))
            ->queue(new EtsyNewOrderMail($order->load('items')));
    }

    public function failed(\Throwable $e): void
    {
        Log::error('ImportEtsyOrder job failed', [
            'resource_url' => $this->resourceUrl,
            'error' => $e->getMessage(),
        ]);
    }
}
