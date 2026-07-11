<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    /**
     * Canonical brand name for structured data. The literal brand — NOT the
     * settings-driven store name — so the Product JSON-LD brand is stable even
     * if the `store.name` setting is changed. (A14)
     */
    public const BRAND = 'Timber Trace Crafts';

    protected $fillable = [
        'name',
        'slug',
        'sku_base',
        'description',
        'short_description',
        'category_id',
        'price',
        'sale_price',
        'personalization_type',
        'personalization_price',
        'personalization_prompt',
        'personalization_max_chars',
        'status',
        'etsy_listing_id',
        'sold_on_etsy',
        'etsy_state',
        'etsy_listing_type',
        'etsy_is_taxable',
        'etsy_is_customizable',
        'etsy_is_private',
        'etsy_should_auto_renew',
        'etsy_featured_rank',
        'etsy_style',
        'etsy_tags',
        'etsy_materials',
        'etsy_who_made',
        'etsy_when_made',
        'etsy_is_supply',
        'etsy_processing_min',
        'etsy_processing_max',
        'etsy_item_weight',
        'etsy_item_weight_unit',
        'etsy_item_length',
        'etsy_item_width',
        'etsy_item_height',
        'etsy_item_dimensions_unit',
        'etsy_taxonomy_id',
        'etsy_shop_section_id',
        'etsy_shipping_profile_id',
        'etsy_return_policy_id',
        'etsy_readiness_state_id',
        'etsy_url',
        'etsy_language',
        'etsy_is_personalizable',
        'etsy_has_variations',
        'etsy_variation_name',
        'etsy_views',
        'etsy_num_favorers',
        'etsy_ending_timestamp',
        'etsy_last_synced_at',
        'featured',
        'sort_order',
        'meta_title',
        'meta_description',
        'specs',
        'gtin13',
        'identifier_exists',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'personalization_price' => 'decimal:2',
            'featured' => 'boolean',
            'sold_on_etsy' => 'boolean',
            'etsy_is_taxable' => 'boolean',
            'etsy_is_customizable' => 'boolean',
            'etsy_is_private' => 'boolean',
            'etsy_should_auto_renew' => 'boolean',
            'etsy_is_supply' => 'boolean',
            'etsy_tags' => 'array',
            'etsy_materials' => 'array',
            'etsy_style' => 'array',
            'etsy_is_personalizable' => 'boolean',
            'etsy_has_variations' => 'boolean',
            'etsy_views' => 'integer',
            'etsy_num_favorers' => 'integer',
            'etsy_ending_timestamp' => 'datetime',
            'etsy_last_synced_at' => 'datetime',
            'sort_order' => 'integer',
            'personalization_max_chars' => 'integer',
            'specs' => 'array',
            'identifier_exists' => 'boolean',
        ];
    }

    public function getPrimaryImageUrlAttribute(): ?string
    {
        $primary = $this->relationLoaded('media')
            ? $this->media->firstWhere('is_primary', true) ?? $this->media->first()
            : $this->media()->with('media')->where('is_primary', true)->first()
                ?? $this->media()->with('media')->first();

        if (! $primary || ! $primary->media) {
            return null;
        }

        return $primary->media->url();
    }

    public function isOnSale(): bool
    {
        return $this->sale_price !== null && (float) $this->sale_price < (float) $this->price;
    }

    /**
     * Total on-hand stock across every variant.
     *
     * Uses the already-loaded relation when available (avoids an extra query
     * on pages that eager-load `variants`) and falls back to a DB aggregate.
     * A product with zero variants totals 0.
     */
    public function totalStock(): int
    {
        return (int) ($this->relationLoaded('variants')
            ? $this->variants->sum('stock_qty')
            : $this->variants()->sum('stock_qty'));
    }

    /**
     * Single source of truth for availability across every surface
     * (product page, product card, shop ItemList, and the ACP + Merchant feeds).
     *
     * Out of stock when the variants' total stock is 0 — including the
     * zero-variant edge case. In stock when any variant has stock.
     */
    public function isOutOfStock(): bool
    {
        return $this->totalStock() <= 0;
    }

    public function isInStock(): bool
    {
        return ! $this->isOutOfStock();
    }

    /**
     * Human-facing name of this product's variation axis, used as the
     * variant-selector heading (e.g. "Wood", "Style", "Design"). Resolved
     * from the product's real variation type — the first ProductVariationType
     * record when present, otherwise the stored Etsy variation name — never a
     * hardcoded "Wood". Falls back to a neutral "Option" when nothing is set.
     */
    public function variationTypeName(): string
    {
        $types = $this->relationLoaded('variationTypes')
            ? $this->variationTypes
            : $this->variationTypes()->get();

        $name = optional($types->first())->name;

        if (is_string($name) && trim($name) !== '') {
            return trim($name);
        }

        if (is_string($this->etsy_variation_name) && trim($this->etsy_variation_name) !== '') {
            return trim($this->etsy_variation_name);
        }

        return 'Option';
    }

    /**
     * Whether the product page should render a variant selector at all.
     * A selector only makes sense when there are at least two variants to
     * choose between; a single-variant (or zero-variant) product renders none.
     */
    public function hasSelectableVariants(): bool
    {
        $count = $this->relationLoaded('variants')
            ? $this->variants->count()
            : $this->variants()->count();

        return $count >= 2;
    }

    /**
     * schema.org availability URL derived from the single stock helper,
     * for use in Product / ItemList JSON-LD and structured feeds.
     */
    public function availabilitySchemaUrl(): string
    {
        return $this->isOutOfStock()
            ? 'https://schema.org/OutOfStock'
            : 'https://schema.org/InStock';
    }

    public function currentPrice(): string
    {
        if ($this->isOnSale()) {
            return $this->sale_price;
        }

        return $this->price;
    }

    /**
     * Product JSON-LD `offers` structure.
     *
     * When the product has variants, emits an AggregateOffer whose top-level
     * `availability` is the single product-level state (A8) and whose nested
     * `offers` carry each variant's stable mpn (A15 — resolvedMpn(), never a
     * bare id) plus that variant's own price and availability. A product with
     * no variants emits a single Offer.
     */
    public function jsonLdOffers(string $url): array
    {
        $currency = 'USD';
        $sellerRef = ['@id' => url('/').'#organization'];
        $priceValidUntil = now()->addYear()->toDateString();

        $variants = $this->relationLoaded('variants')
            ? $this->variants
            : $this->variants()->get();

        $variantOffers = [];
        $prices = [];

        foreach ($variants as $variant) {
            $price = $variant->price !== null
                ? (float) $variant->price
                : (float) $this->currentPrice();
            $prices[] = $price;

            $offer = [
                '@type' => 'Offer',
                'name' => $variant->label,
                'url' => $url,
                'priceCurrency' => $currency,
                'price' => number_format($price, 2, '.', ''),
                'itemCondition' => 'https://schema.org/NewCondition',
                'availability' => $variant->isInStock()
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock',
                'seller' => $sellerRef,
            ];

            $mpn = $variant->resolvedMpn();
            if ($mpn !== null) {
                $offer['mpn'] = $mpn;
            }

            $variantOffers[] = $offer;
        }

        if (empty($variantOffers)) {
            return [
                '@type' => 'Offer',
                'url' => $url,
                'priceCurrency' => $currency,
                'price' => number_format((float) $this->currentPrice(), 2, '.', ''),
                'priceValidUntil' => $priceValidUntil,
                'itemCondition' => 'https://schema.org/NewCondition',
                'availability' => $this->availabilitySchemaUrl(),
                'seller' => $sellerRef,
            ];
        }

        return [
            '@type' => 'AggregateOffer',
            'priceCurrency' => $currency,
            'lowPrice' => number_format(min($prices), 2, '.', ''),
            'highPrice' => number_format(max($prices), 2, '.', ''),
            'offerCount' => count($variantOffers),
            'priceValidUntil' => $priceValidUntil,
            'itemCondition' => 'https://schema.org/NewCondition',
            'availability' => $this->availabilitySchemaUrl(),
            'seller' => $sellerRef,
            'offers' => $variantOffers,
        ];
    }

    /**
     * One `ListItem` entry for the /shop `ItemList` JSON-LD (A17).
     *
     * Carries the product's name, canonical URL, current price + availability
     * (derived from the single availability helper, A10) and its image when it
     * has one. `$position` is the 1-based slot in the rendered listing.
     */
    public function jsonLdShopItem(int $position): array
    {
        $url = url('/product/'.$this->slug);

        $item = [
            '@type' => 'Product',
            'name' => $this->name,
            'url' => $url,
        ];

        if ($image = $this->primary_image_url) {
            $item['image'] = $image;
        }

        $item['offers'] = [
            '@type' => 'Offer',
            'url' => $url,
            'priceCurrency' => 'USD',
            'price' => number_format((float) $this->currentPrice(), 2, '.', ''),
            'availability' => $this->availabilitySchemaUrl(),
        ];

        return [
            '@type' => 'ListItem',
            'position' => $position,
            'url' => $url,
            'name' => $this->name,
            'item' => $item,
        ];
    }

    /**
     * Product JSON-LD spec fields derived from the structured `specs` column.
     *
     * Every spec becomes an `additionalProperty` PropertyValue (with unitText
     * where a unit was given), and well-known keys are additionally mapped to
     * top-level schema fields (`material`, `weight`, `size`). A product with no
     * specs returns an empty array — no empty `additionalProperty`. (A12)
     */
    public function jsonLdSpecFields(): array
    {
        $specs = $this->specs;

        if (! is_array($specs) || empty($specs)) {
            return [];
        }

        $fields = [];
        $additional = [];

        foreach ($specs as $key => $spec) {
            [$value, $unit] = $this->normalizeSpecValue($spec);

            if ($value === null || $value === '') {
                continue;
            }

            $property = [
                '@type' => 'PropertyValue',
                'name' => Str::headline((string) $key),
                'value' => $value,
            ];
            if ($unit !== null && $unit !== '') {
                $property['unitText'] = $unit;
            }
            $additional[] = $property;

            $normalizedKey = strtolower(trim((string) $key));

            if ($normalizedKey === 'material') {
                $fields['material'] = (string) $value;
            } elseif ($normalizedKey === 'weight') {
                $weight = ['@type' => 'QuantitativeValue', 'value' => $value];
                if ($unit !== null && $unit !== '') {
                    $weight['unitText'] = $unit;
                }
                $fields['weight'] = $weight;
            } elseif (in_array($normalizedKey, ['size', 'dimensions'], true)) {
                $fields['size'] = ($unit !== null && $unit !== '')
                    ? trim($value.' '.$unit)
                    : (string) $value;
            }
        }

        if (! empty($additional)) {
            $fields['additionalProperty'] = $additional;
        }

        return $fields;
    }

    /**
     * Splits a spec entry into [value, unit]. Entries are either a scalar
     * (value only) or an array with `value` and optional `unit` keys.
     *
     * @param  mixed  $spec
     * @return array{0: mixed, 1: ?string}
     */
    protected function normalizeSpecValue($spec): array
    {
        if (is_array($spec)) {
            $value = $spec['value'] ?? null;
            $unit = isset($spec['unit']) ? (string) $spec['unit'] : null;

            return [$value, $unit];
        }

        return [$spec, null];
    }

    /**
     * Product JSON-LD identifier fields.
     *
     * Emits `gtin13` when a known GTIN-13 is stored (tumbler-blank case), else
     * emits `identifierExists` => false (a real JSON boolean) for a handmade
     * product with no identifiers. The two never both appear. (A16)
     */
    public function jsonLdIdentifierFields(): array
    {
        $gtin = $this->gtin13 !== null ? trim((string) $this->gtin13) : '';

        if ($gtin !== '') {
            return ['gtin13' => $gtin];
        }

        if ($this->identifier_exists === false) {
            return ['identifierExists' => false];
        }

        return [];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function variationTypes(): HasMany
    {
        return $this->hasMany(ProductVariationType::class)->orderBy('sort_order');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tags');
    }

    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class);
    }

    public function primaryMedia(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->where('is_primary', true);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }
}
