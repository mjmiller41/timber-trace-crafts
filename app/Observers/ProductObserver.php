<?php

namespace App\Observers;

use App\Jobs\SyncProductToEtsy;
use App\Models\Product;

class ProductObserver
{
    public function saved(Product $product): void
    {
        if (! $product->sold_on_etsy || $product->status !== 'active') {
            return;
        }

        SyncProductToEtsy::dispatch($product->id)->delay(now()->addSeconds(5));
    }
}
