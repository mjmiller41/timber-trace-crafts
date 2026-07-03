<?php

namespace App\Services\Etsy;

use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EtsyListingImageSync
{
    private const SUPPORTED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif'];

    public function __construct(private readonly EtsyClient $client) {}

    /**
     * Upload the product's local images to its Etsy listing.
     *
     * Only media rows without an etsy_listing_image_id are uploaded, so re-runs
     * never duplicate. When the listing already has images on Etsy that we did
     * not upload (e.g. added manually via the Etsy UI), the product is skipped
     * unless $force is set — appending local copies would duplicate them.
     */
    public function syncProduct(Product $product, bool $force = false): SyncResult
    {
        $result = new SyncResult;

        if (! $product->etsy_listing_id) {
            $result->skipped++;

            return $result;
        }

        $product->load('media.media');

        $pending = $product->media
            ->whereNull('etsy_listing_image_id')
            ->sortBy([['is_primary', 'desc'], ['sort_order', 'asc']])
            ->values();

        if ($pending->isEmpty()) {
            $result->skipped++;

            return $result;
        }

        if (! $force && $this->listingHasUntrackedImages($product)) {
            Log::info('Etsy image sync skipped: listing already has images not uploaded by us', [
                'product_id' => $product->id,
                'listing_id' => $product->etsy_listing_id,
            ]);
            $result->skipped++;

            return $result;
        }

        $shopId = Setting::get('etsy.shop_id');
        $rank = $product->media->whereNotNull('etsy_listing_image_id')->count() + 1;

        foreach ($pending as $productMedia) {
            if ($this->uploadImage($shopId, $product, $productMedia, $rank)) {
                $result->created++;
                $rank++;
            } else {
                $result->failed++;
            }
        }

        return $result;
    }

    private function uploadImage(string $shopId, Product $product, ProductMedia $productMedia, int $rank): bool
    {
        $media = $productMedia->media;

        if (! $media || ! in_array($media->mime_type, self::SUPPORTED_MIME_TYPES, true)) {
            Log::warning('Etsy image sync: unsupported or missing media', [
                'product_id' => $product->id,
                'product_media_id' => $productMedia->id,
                'mime_type' => $media?->mime_type,
            ]);

            return false;
        }

        $contents = Storage::disk($media->disk)->get($media->path);

        if ($contents === null) {
            Log::warning('Etsy image sync: file missing from storage', [
                'product_id' => $product->id,
                'disk' => $media->disk,
                'path' => $media->path,
            ]);

            return false;
        }

        try {
            $response = $this->client->postFile(
                "/application/shops/{$shopId}/listings/{$product->etsy_listing_id}/images",
                ['rank' => $rank],
                'image',
                $contents,
                basename($media->path)
            );
        } catch (\Throwable $e) {
            Log::error('Etsy image upload failed', [
                'product_id' => $product->id,
                'listing_id' => $product->etsy_listing_id,
                'product_media_id' => $productMedia->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        $productMedia->update(['etsy_listing_image_id' => (string) $response['listing_image_id']]);

        return true;
    }

    private function listingHasUntrackedImages(Product $product): bool
    {
        $response = $this->client->get("/application/listings/{$product->etsy_listing_id}/images");

        $etsyImageIds = collect($response['results'] ?? [])
            ->pluck('listing_image_id')
            ->map(fn ($id) => (string) $id);

        if ($etsyImageIds->isEmpty()) {
            return false;
        }

        $trackedIds = $product->media->pluck('etsy_listing_image_id')->filter();

        return $etsyImageIds->diff($trackedIds)->isNotEmpty();
    }
}
