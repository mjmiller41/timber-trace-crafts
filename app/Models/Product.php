<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

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
