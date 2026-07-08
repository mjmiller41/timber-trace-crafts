<?php

namespace App\Services\Etsy;

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Support\Collection;

class EtsyListingDiff
{
    /** Fields compared by the diff; also the whitelist for conflict resolution. */
    public const FIELDS = ['title', 'description', 'price', 'quantity', 'tags', 'status'];

    public function __construct(private readonly EtsyClient $client) {}

    /**
     * Diff all Etsy listings against local products linked by etsy_listing_id.
     *
     * @return array{generated_at: string, etsyOnly: array, dbOnly: array, conflicts: array, matched: int}
     */
    public function diff(): array
    {
        $shopId = (string) Setting::get('etsy.shop_id');
        $etsyListings = $this->fetchAllListings($shopId);

        $dbProducts = Product::whereNotNull('etsy_listing_id')->with('variants')->get()->keyBy('etsy_listing_id');
        $etsyById = $etsyListings->keyBy('listing_id');

        $etsyOnly = [];
        $conflicts = [];
        $matched = 0;

        foreach ($etsyListings as $listing) {
            $id = (string) $listing['listing_id'];

            if (! $dbProducts->has($id)) {
                $etsyOnly[] = [
                    'listing_id' => $id,
                    'title' => html_entity_decode($listing['title'] ?? '', ENT_QUOTES | ENT_HTML5),
                    'state' => $listing['state'] ?? null,
                    'price' => $this->money($listing['price'] ?? null),
                    'tags' => $listing['tags'] ?? [],
                    'shop_section_id' => $listing['shop_section_id'] ?? null,
                ];

                continue;
            }

            $product = $dbProducts->get($id);
            $differences = $this->compareListingToProduct($listing, $product);

            if (! empty($differences)) {
                $conflicts[] = [
                    'listing_id' => $id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'variant_count' => $product->variants->count(),
                    'differences' => $differences,
                ];
            } else {
                $matched++;
            }
        }

        $dbOnly = $dbProducts->filter(fn ($p) => ! $etsyById->has($p->etsy_listing_id))
            ->map(fn ($p) => ['product_id' => $p->id, 'name' => $p->name, 'etsy_listing_id' => $p->etsy_listing_id])
            ->values()
            ->toArray();

        return [
            'generated_at' => now()->toISOString(),
            'etsyOnly' => $etsyOnly,
            'dbOnly' => $dbOnly,
            'conflicts' => $conflicts,
            'matched' => $matched,
        ];
    }

    /**
     * Compare an Etsy listing to its linked product. Mirrors the fields that
     * EtsyProductSync::buildListingPayload() actually pushes.
     *
     * @return array<string, array{db: mixed, etsy: mixed}>
     */
    public function compareListingToProduct(array $listing, Product $product): array
    {
        $diff = [];

        // Etsy returns titles HTML-encoded; decode before comparing to DB text
        $etsyTitle = isset($listing['title']) ? html_entity_decode($listing['title'], ENT_QUOTES | ENT_HTML5) : null;
        if ($etsyTitle !== null && $product->name !== $etsyTitle) {
            $diff['title'] = ['db' => $product->name, 'etsy' => $etsyTitle];
        }

        if (isset($listing['description'])) {
            $etsyDescription = html_entity_decode($listing['description'], ENT_QUOTES | ENT_HTML5);
            $dbDescription = strip_tags($product->description ?? $product->short_description ?? '');

            if ($this->normalizeText($etsyDescription) !== $this->normalizeText($dbDescription)) {
                $diff['description'] = ['db' => $dbDescription, 'etsy' => $etsyDescription];
            }
        }

        $etsyPrice = $this->money($listing['price'] ?? null);
        $dbPrice = (float) ($product->sale_price ?? $product->price);
        if ($etsyPrice !== null && $dbPrice !== $etsyPrice) {
            $diff['price'] = ['db' => $dbPrice, 'etsy' => $etsyPrice];
        }

        // Quantity only compares when variants exist — the site's stock lives on
        // variants, and a variant-less product has no stock to reconcile.
        if (isset($listing['quantity']) && $product->variants->isNotEmpty()) {
            $dbQuantity = (int) $product->variants->sum('stock_qty');
            if ((int) $listing['quantity'] !== $dbQuantity) {
                $diff['quantity'] = ['db' => $dbQuantity, 'etsy' => (int) $listing['quantity']];
            }
        }

        if (isset($listing['tags'])) {
            $etsyTags = array_values($listing['tags']);
            $dbTags = array_values($product->etsy_tags ?? []);
            $sortedEtsy = $etsyTags;
            $sortedDb = $dbTags;
            sort($sortedEtsy);
            sort($sortedDb);

            if ($sortedEtsy !== $sortedDb) {
                $diff['tags'] = ['db' => $dbTags, 'etsy' => $etsyTags];
            }
        }

        $etsyState = $listing['state'] ?? null;
        $mappedState = match ($product->status) {
            'active' => 'active',
            'draft' => 'draft',
            default => null,
        };
        if ($etsyState && $mappedState && $etsyState !== $mappedState) {
            $diff['status'] = ['db' => $product->status, 'etsy' => $etsyState];
        }

        return $diff;
    }

    /**
     * Apply an Etsy-side value to the local product ("keep Etsy"). Writes
     * quietly so ProductObserver does not re-queue a push back to Etsy.
     */
    public function applyEtsyValue(Product $product, string $field, mixed $etsyValue): void
    {
        match ($field) {
            'title' => $product->updateQuietly(['name' => $etsyValue]),
            'description' => $product->updateQuietly(['description' => $etsyValue]),
            'price' => $product->updateQuietly(
                $product->sale_price !== null ? ['sale_price' => $etsyValue] : ['price' => $etsyValue]
            ),
            'tags' => $product->updateQuietly(['etsy_tags' => $etsyValue]),
            'status' => $product->updateQuietly([
                'status' => in_array($etsyValue, ['active', 'draft'], true) ? $etsyValue : 'draft',
            ]),
            'quantity' => $this->applyEtsyQuantity($product, (int) $etsyValue),
            default => throw new \InvalidArgumentException("Unknown diff field: {$field}"),
        };
    }

    /**
     * Etsy quantity can only map back to a single variant — a listing total
     * cannot be split across multiple variants.
     */
    private function applyEtsyQuantity(Product $product, int $quantity): void
    {
        $variants = $product->variants()->get();

        if ($variants->count() !== 1) {
            throw new \InvalidArgumentException(
                "Cannot apply Etsy quantity to product #{$product->id}: it has {$variants->count()} variants."
            );
        }

        $variants->first()->update(['stock_qty' => $quantity]);
    }

    private function fetchAllListings(string $shopId): Collection
    {
        $all = collect();
        $limit = 100;
        $offset = 0;

        do {
            $response = $this->client->get("/application/shops/{$shopId}/listings", [
                'limit' => $limit,
                'offset' => $offset,
            ]);
            $results = $response['results'] ?? [];
            $all = $all->merge($results);
            $offset += $limit;
        } while (count($results) === $limit);

        return $all;
    }

    private function money(?array $money): ?float
    {
        if (! $money || ! isset($money['amount'], $money['divisor']) || $money['divisor'] === 0) {
            return null;
        }

        return round($money['amount'] / $money['divisor'], 2);
    }

    private function normalizeText(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }
}
