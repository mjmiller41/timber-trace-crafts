<?php

namespace App\Observers;

use App\Jobs\SyncProductToEtsy;
use App\Models\Product;

class ProductObserver
{
    public function saved(Product $product): void
    {
        if (! $product->sold_on_etsy) {
            return;
        }

        // Push updates to an existing listing regardless of draft/active status.
        // Only auto-create a new listing when the product is active.
        if (! $product->etsy_listing_id && $product->status !== 'active') {
            return;
        }

        SyncProductToEtsy::dispatch($product->id)->delay(now()->addSeconds(5));
    }
}
