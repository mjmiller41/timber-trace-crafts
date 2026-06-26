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
            $response = $this->client->post("/application/shops/{$shopId}/listings", $payload);
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

    public function deleteProduct(Product $product): void
    {
        if (! $product->etsy_listing_id) {
            return;
        }

        $shopId = Setting::get('etsy.shop_id');
        $this->client->delete("/application/shops/{$shopId}/listings/{$product->etsy_listing_id}");
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
            'who_made' => $product->etsy_who_made ?? 'i_did',
            'when_made' => $product->etsy_when_made ?? 'made_to_order',
            'is_supply' => $product->etsy_is_supply ?? false,
            'is_taxable' => $product->etsy_is_taxable ?? true,
            'is_customizable' => $product->etsy_is_customizable ?? false,
            'should_auto_renew' => $product->etsy_should_auto_renew ?? true,
            'listing_type' => $product->etsy_listing_type ?? 'physical',
        ];

        if ($product->etsy_processing_min !== null) {
            $payload['processing_min'] = $product->etsy_processing_min;
        }

        if ($product->etsy_processing_max !== null) {
            $payload['processing_max'] = $product->etsy_processing_max;
        }

        if ($product->etsy_item_weight !== null) {
            $payload['item_weight'] = $product->etsy_item_weight;
            $payload['item_weight_unit'] = $product->etsy_item_weight_unit ?? 'oz';
        }

        if ($product->etsy_item_length !== null) {
            $payload['item_length'] = $product->etsy_item_length;
            $payload['item_width'] = $product->etsy_item_width;
            $payload['item_height'] = $product->etsy_item_height;
            $payload['item_dimensions_unit'] = $product->etsy_item_dimensions_unit ?? 'in';
        }

        if (! empty($product->etsy_tags)) {
            $payload['tags'] = array_values(array_slice($product->etsy_tags, 0, 13));
        }

        if (! empty($product->etsy_materials)) {
            $payload['materials'] = array_values($product->etsy_materials);
        }

        if (! empty($product->etsy_style)) {
            $payload['style'] = array_values(array_slice($product->etsy_style, 0, 2));
        }

        if ($product->etsy_shop_section_id !== null) {
            $payload['shop_section_id'] = $product->etsy_shop_section_id;
        }

        $returnPolicyId = $product->etsy_return_policy_id ?? Setting::get('etsy.return_policy_id');
        if ($returnPolicyId !== null) {
            $payload['return_policy_id'] = (int) $returnPolicyId;
        }

        if (! $product->etsy_listing_id) {
            $taxonomyId = $product->etsy_taxonomy_id ?? Setting::get('etsy.taxonomy_id');
            $shippingProfileId = $product->etsy_shipping_profile_id ?? Setting::get('etsy.shipping_profile_id');
            $readinessStateId = $product->etsy_readiness_state_id ?? Setting::get('etsy.readiness_state_id');

            if (! $readinessStateId) {
                throw new \RuntimeException(
                    'Cannot create Etsy listing: no readiness_state_id set. Configure it in Admin → Settings → Etsy.'
                );
            }

            $readinessStateId = (int) $readinessStateId;

            if (! $taxonomyId) {
                throw new \RuntimeException(
                    'Cannot create Etsy listing: no taxonomy_id set. Use etsy:link to copy from an existing listing first.'
                );
            }

            $payload['taxonomy_id'] = (int) $taxonomyId;

            if ($shippingProfileId) {
                $payload['shipping_profile_id'] = (int) $shippingProfileId;
            }

            $payload['readiness_state_id'] = $readinessStateId;
        }

        return $payload;
    }
}
