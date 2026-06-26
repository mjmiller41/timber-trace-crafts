<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\Etsy\EtsyClient;
use App\Services\Etsy\EtsyOAuthService;
use App\Services\Etsy\EtsyProductSync;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncProductToEtsy implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(public readonly int $productId) {}

    public function handle(): void
    {
        $product = Product::with(['variants', 'media'])->find($this->productId);

        if (! $product || ! $product->sold_on_etsy) {
            return;
        }

        // Skip if no existing listing and product isn't active yet (don't auto-create for drafts)
        if (! $product->etsy_listing_id && $product->status !== 'active') {
            return;
        }

        $sync = new EtsyProductSync(new EtsyClient(app(EtsyOAuthService::class)));
        $sync->syncProduct($product);

        Log::info('Etsy product synced via queue', ['product_id' => $product->id]);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SyncProductToEtsy job failed', [
            'product_id' => $this->productId,
            'error' => $e->getMessage(),
        ]);
    }
}
