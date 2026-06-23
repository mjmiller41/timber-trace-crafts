<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
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

        return asset('storage/'.$primary->media->path);
    }

    public function isOnSale(): bool
    {
        return $this->sale_price !== null && (float) $this->sale_price < (float) $this->price;
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
