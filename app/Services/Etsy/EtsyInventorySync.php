<?php

namespace App\Services\Etsy;

use App\Exceptions\EtsyApiException;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class EtsyInventorySync
{
    public function __construct(private readonly EtsyClient $client) {}

    public function syncProduct(Product $product): void
    {
        if (! $product->etsy_listing_id) {
            return;
        }

        $product->loadMissing('variants');

        $price = (float) ($product->sale_price ?? $product->price);

        $products = $product->variants->map(function ($variant) use ($price) {
            return [
                'sku' => $variant->sku ?? '',
                'offerings' => [
                    [
                        'price' => $price,
                        'quantity' => max(0, $variant->stock_qty),
                        'is_enabled' => $variant->stock_qty > 0,
                    ],
                ],
                'property_values' => [
                    [
                        'property_id' => 513,
                        'value_ids' => [],
                        'scale_id' => null,
                        'property_name' => 'Style',
                        'values' => [$variant->label ?? $variant->sku],
                    ],
                ],
            ];
        })->values()->all();

        $this->client->put("/application/listings/{$product->etsy_listing_id}/inventory", [
            'products' => $products,
        ]);
    }

    public function syncAll(): SyncResult
    {
        $result = new SyncResult;

        Product::whereNotNull('etsy_listing_id')
            ->cursor()
            ->each(function (Product $product) use ($result) {
                try {
                    $this->syncProduct($product);
                    $result->updated++;
                } catch (EtsyApiException $e) {
                    $result->failed++;
                    Log::error('Etsy inventory sync failed', [
                        'product_id' => $product->id,
                        'listing_id' => $product->etsy_listing_id,
                        'error' => $e->getMessage(),
                    ]);
                }

                usleep(250_000);
            });

        return $result;
    }
}
