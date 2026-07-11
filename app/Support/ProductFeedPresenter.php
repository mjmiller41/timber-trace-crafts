<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Canonical, feed-agnostic view of a catalog product for machine surfaces.
 *
 * Both transactable feeds — the OpenAI Agentic Commerce Protocol JSON feed
 * (F9) and the Google Merchant Center XML feed (F10) — derive every field
 * from this one presenter, and every price/availability value flows through
 * the same {@see Product} helpers used by the product page and the /shop
 * ItemList. That guarantees no cross-surface contradiction (A26) and a single
 * availability source of truth (A10/A25).
 *
 * Reads only the local model/DB — no outbound HTTP in this code path (A25).
 */
class ProductFeedPresenter
{
    public function __construct(private readonly Product $product) {}

    public static function for(Product $product): self
    {
        return new self($product);
    }

    /**
     * The active catalog products that belong in a feed, eager-loaded with the
     * relations the presenter reads. Draft/inactive products are excluded;
     * out-of-stock active products are kept (surfaced as out_of_stock). (A25)
     *
     * @return Collection<int, Product>
     */
    public static function activeProducts(): Collection
    {
        return Product::query()
            ->with(['media.media', 'variants'])
            ->where('status', 'active')
            ->orderBy('id')
            ->get();
    }

    /**
     * Stable feed id — the product's SKU base when set, else a namespaced id.
     */
    public function id(): string
    {
        $sku = trim((string) $this->product->sku_base);

        return $sku !== '' ? $sku : 'ttc-'.$this->product->id;
    }

    public function title(): string
    {
        return (string) $this->product->name;
    }

    /**
     * Full, strip-tagged description (no truncation), falling back to the short
     * description and finally the product name so the field is never empty.
     */
    public function description(): string
    {
        foreach ([$this->product->description, $this->product->short_description] as $candidate) {
            $text = trim(strip_tags((string) ($candidate ?? '')));
            if ($text !== '') {
                return $text;
            }
        }

        return $this->title();
    }

    /**
     * Canonical current price, formatted "N.NN" — the sale price when the
     * product is on sale, matching what the product page renders. (A26)
     */
    public function price(): string
    {
        return number_format((float) $this->product->currentPrice(), 2, '.', '');
    }

    public function currency(): string
    {
        return 'USD';
    }

    /**
     * Feed availability token derived from the single stock helper. An
     * out-of-stock active product is surfaced (not omitted) as out_of_stock.
     */
    public function availability(): string
    {
        return $this->product->isOutOfStock() ? 'out_of_stock' : 'in_stock';
    }

    /**
     * Absolute image URL, falling back to the site's default OG image so the
     * required image field is always populated with an absolute URL.
     */
    public function imageLink(): string
    {
        $image = $this->product->primary_image_url;

        if (is_string($image) && trim($image) !== '') {
            return $image;
        }

        return asset('images/og-default.jpg');
    }

    /**
     * Absolute canonical product URL.
     */
    public function url(): string
    {
        return url('/product/'.$this->product->slug);
    }

    public function brand(): string
    {
        return Product::BRAND;
    }

    /**
     * Stable manufacturer part number for the product-level feed item: the
     * first variant's resolved mpn (persisted mpn, else SKU), falling back to
     * the feed id — never empty, never a bare auto-increment id. (A15)
     */
    public function mpn(): string
    {
        $variant = $this->product->relationLoaded('variants')
            ? $this->product->variants->first()
            : $this->product->variants()->first();

        $mpn = $variant?->resolvedMpn();

        if (is_string($mpn) && trim($mpn) !== '') {
            return trim($mpn);
        }

        return $this->id();
    }

    /**
     * One item for the OpenAI Agentic Commerce Protocol product feed (A23).
     * Carries id, title, description, price + currency (USD), availability,
     * image link, absolute product URL, brand, and mpn, plus the ACP-specific
     * eligibility/targeting niceties.
     *
     * @return array<string, mixed>
     */
    public function acpItem(): array
    {
        return [
            'id' => $this->id(),
            'title' => $this->title(),
            'description' => $this->description(),
            'link' => $this->url(),
            'image_link' => $this->imageLink(),
            'price' => $this->price(),
            'currency' => $this->currency(),
            'availability' => $this->availability(),
            'condition' => 'new',
            'brand' => $this->brand(),
            'mpn' => $this->mpn(),
            'item_group_id' => $this->id(),
            'enable_search' => true,
            'enable_checkout' => true,
            'target_countries' => ['US'],
        ];
    }
}
