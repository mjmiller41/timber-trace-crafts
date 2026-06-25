<?php

namespace App\Services\Etsy;

use App\Exceptions\EtsyApiException;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class EtsyProductSync
{
    public function __construct(private readonly EtsyClient $client) {}

    public function syncProduct(Product $product): void
    {
        $product->loadMissing(['variants', 'media']);

        $payload = $this->buildListingPayload($product);

        $shopId = Setting::get('etsy.shop_id');

        if ($product->etsy_listing_id) {
            $this->client->patch("/application/shops/{$shopId}/listings/{$product->etsy_listing_id}", $payload);
        } else {
            $response = $this->client->post("/application/shops/{$shopId}/listings?legacy=true", $payload);
            $product->update(['etsy_listing_id' => (string) $response['listing_id']]);
        }
    }

    public function syncAll(): SyncResult
    {
        $result = new SyncResult;

        Product::where('status', 'active')
            ->cursor()
            ->each(function (Product $product) use ($result) {
                try {
                    $isNew = $product->etsy_listing_id === null;
                    $this->syncProduct($product);
                    $isNew ? $result->created++ : $result->updated++;
                } catch (EtsyApiException $e) {
                    $result->failed++;
                    Log::error('Etsy product sync failed', [
                        'product_id' => $product->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                usleep(250_000);
            });

        return $result;
    }

    private function buildListingPayload(Product $product): array
    {
        $totalStock = $product->variants->sum('stock_qty');
        $price = (float) ($product->sale_price ?? $product->price);

        $payload = [
            'title' => $product->name,
            'description' => strip_tags($product->description ?? $product->short_description ?? ''),
            'price' => $price,
            'quantity' => max(1, $totalStock),
            'who_made' => 'i_did',
            'when_made' => 'made_to_order',
            'is_supply' => false,
        ];

        // Only set state on new listings; preserve existing state on updates
        if (! $product->etsy_listing_id) {
            $payload['state'] = 'draft';

            $taxonomyId = $product->etsy_taxonomy_id ?? Setting::get('etsy.taxonomy_id');
            $shippingProfileId = $product->etsy_shipping_profile_id ?? Setting::get('etsy.shipping_profile_id');

            if (! $taxonomyId) {
                throw new \RuntimeException(
                    'Cannot create Etsy listing: no taxonomy_id set. Use etsy:link to copy from an existing listing first.'
                );
            }

            $payload['taxonomy_id'] = (int) $taxonomyId;

            if ($shippingProfileId) {
                $payload['shipping_profile_id'] = (int) $shippingProfileId;
            }
        }

        return $payload;
    }
}
