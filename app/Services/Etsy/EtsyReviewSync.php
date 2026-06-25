<?php

namespace App\Services\Etsy;

use App\Exceptions\EtsyApiException;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class EtsyReviewSync
{
    public function __construct(private readonly EtsyClient $client) {}

    public function sync(): SyncResult
    {
        $result = new SyncResult;
        $shopId = Setting::get('etsy.shop_id');

        $offset = 0;
        $limit = 100;

        do {
            try {
                $response = $this->client->get("/application/shops/{$shopId}/reviews", [
                    'limit' => $limit,
                    'offset' => $offset,
                ]);
            } catch (EtsyApiException $e) {
                Log::error('Etsy review sync failed', ['error' => $e->getMessage()]);
                $result->failed++;
                break;
            }

            $reviews = $response['results'] ?? [];

            foreach ($reviews as $review) {
                $transactionId = (string) ($review['transaction_id'] ?? '');

                if (! $transactionId) {
                    $result->skipped++;

                    continue;
                }

                try {
                    $existing = ProductReview::where('etsy_transaction_id', $transactionId)->first();

                    if ($existing) {
                        $this->updateReview($existing, $review);
                        $result->updated++;
                    } else {
                        $this->importReview($review);
                        $result->created++;
                    }
                } catch (\Throwable $e) {
                    $result->failed++;
                    Log::error('Etsy review import failed', [
                        'transaction_id' => $transactionId,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $offset += $limit;
            usleep(250_000);
        } while (count($reviews) === $limit);

        return $result;
    }

    private function importReview(array $review): void
    {
        ProductReview::create([
            'etsy_transaction_id' => (string) $review['transaction_id'],
            'product_id' => $this->resolveProductId($review['listing_id'] ?? null),
            'rating' => $review['rating'],
            'body' => $review['review'] ?? null,
            'guest_name' => 'Etsy Buyer',
            'guest_email' => null,
            'source' => 'etsy',
            'etsy_image_url' => $review['image_url_fullxfull'] ?? null,
            'language' => $review['language'] ?? 'en',
            'status' => 'pending',
        ]);
    }

    private function updateReview(ProductReview $existing, array $review): void
    {
        $existing->update([
            'rating' => $review['rating'],
            'body' => $review['review'] ?? null,
            'etsy_image_url' => $review['image_url_fullxfull'] ?? null,
        ]);
    }

    private function resolveProductId(?int $listingId): ?int
    {
        if (! $listingId) {
            return null;
        }

        return Product::where('etsy_listing_id', (string) $listingId)->value('id');
    }
}
