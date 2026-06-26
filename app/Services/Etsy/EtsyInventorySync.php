<?php

namespace App\Services\Etsy;

use App\Exceptions\EtsyApiException;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class EtsyInventorySync
{
    public function __construct(private readonly EtsyClient $client) {}

    public function syncProduct(Product $product): void
    {
        if (! $product->etsy_listing_id) {
            return;
        }

        $product->load('variationTypes.variants');

        $price = (float) ($product->sale_price ?? $product->price);
        $readinessStateId = (int) ($product->etsy_readiness_state_id ?? Setting::get('etsy.readiness_state_id') ?? 0) ?: null;

        $types = $product->variationTypes;

        if ($types->isEmpty()) {
            // Legacy: check for flat variants with no variation type
            $product->loadMissing('variants');
            $variants = $product->variants;

            if ($variants->isEmpty()) {
                $this->syncBaseProduct($product, $price, $readinessStateId);
            } else {
                $this->syncLegacyVariants($product, $variants, $price, $readinessStateId);
            }

            return;
        }

        if ($types->count() === 1) {
            $this->syncSingleType($product, $types->first(), $price, $readinessStateId);
        } else {
            $this->syncTwoTypes($product, $types->get(0), $types->get(1), $price, $readinessStateId);
        }
    }

    private function syncBaseProduct(Product $product, float $price, ?int $readinessStateId): void
    {
        $offering = ['quantity' => 1, 'is_enabled' => true, 'price' => $price];
        if ($readinessStateId) {
            $offering['readiness_state_id'] = $readinessStateId;
        }

        $this->client->put("/application/listings/{$product->etsy_listing_id}/inventory", [
            'products' => [['sku' => $product->sku_base ?? '', 'offerings' => [$offering], 'property_values' => []]],
            'price_on_property' => [],
            'quantity_on_property' => [],
            'sku_on_property' => [],
        ]);
    }

    private function syncLegacyVariants(Product $product, $variants, float $price, ?int $readinessStateId): void
    {
        $propertyName = $product->etsy_variation_name ?: 'Style';

        $products = $variants->map(function ($v) use ($price, $readinessStateId, $propertyName) {
            $variantPrice = ($v->price !== null) ? (float) $v->price : $price;
            $offering = ['quantity' => max(0, $v->stock_qty), 'is_enabled' => (bool) $v->is_enabled, 'price' => $variantPrice];
            if ($readinessStateId) {
                $offering['readiness_state_id'] = $readinessStateId;
            }

            return [
                'sku' => $v->sku ?? '',
                'offerings' => [$offering],
                'property_values' => [['property_id' => 513, 'property_name' => $propertyName, 'scale_id' => null, 'value_ids' => [], 'values' => [$v->label ?? $v->sku]]],
            ];
        })->values()->all();

        $this->client->put("/application/listings/{$product->etsy_listing_id}/inventory", [
            'products' => $products,
            'price_on_property' => [513],
            'quantity_on_property' => [513],
            'sku_on_property' => [513],
        ]);
    }

    private function syncSingleType(Product $product, $type, float $price, ?int $readinessStateId): void
    {
        $products = $type->variants->map(function ($v) use ($price, $readinessStateId, $type) {
            $variantPrice = ($v->price !== null) ? (float) $v->price : $price;
            $offering = ['quantity' => max(0, $v->stock_qty), 'is_enabled' => (bool) $v->is_enabled, 'price' => $variantPrice];
            if ($readinessStateId) {
                $offering['readiness_state_id'] = $readinessStateId;
            }

            return [
                'sku' => $v->sku ?? '',
                'offerings' => [$offering],
                'property_values' => [['property_id' => 513, 'property_name' => $type->name, 'scale_id' => null, 'value_ids' => [], 'values' => [$v->label ?? $v->sku]]],
            ];
        })->values()->all();

        $this->client->put("/application/listings/{$product->etsy_listing_id}/inventory", [
            'products' => $products,
            'price_on_property' => [513],
            'quantity_on_property' => [513],
            'sku_on_property' => [513],
        ]);
    }

    private function syncTwoTypes(Product $product, $type1, $type2, float $price, ?int $readinessStateId): void
    {
        $products = [];

        foreach ($type1->variants as $v1) {
            foreach ($type2->variants as $v2) {
                $variantPrice = ($v1->price !== null) ? (float) $v1->price : $price;
                $offering = [
                    'quantity' => max(0, $v1->stock_qty),
                    'is_enabled' => (bool) $v1->is_enabled && (bool) $v2->is_enabled,
                    'price' => $variantPrice,
                ];
                if ($readinessStateId) {
                    $offering['readiness_state_id'] = $readinessStateId;
                }

                $sku = implode('-', array_filter([$v1->sku, $v2->sku]));

                $products[] = [
                    'sku' => $sku,
                    'offerings' => [$offering],
                    'property_values' => [
                        ['property_id' => 513, 'property_name' => $type1->name, 'scale_id' => null, 'value_ids' => [], 'values' => [$v1->label ?? $v1->sku]],
                        ['property_id' => 514, 'property_name' => $type2->name, 'scale_id' => null, 'value_ids' => [], 'values' => [$v2->label ?? $v2->sku]],
                    ],
                ];
            }
        }

        $this->client->put("/application/listings/{$product->etsy_listing_id}/inventory", [
            'products' => $products,
            'price_on_property' => [513],
            'quantity_on_property' => [513],
            'sku_on_property' => [513],
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
