<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'label',
        'material_code',
        'stock_qty',
        'low_stock_threshold',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'stock_qty' => 'integer',
            'low_stock_threshold' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function isInStock(): bool
    {
        return $this->stock_qty > 0;
    }

    public function isLowStock(): bool
    {
        return $this->stock_qty > 0 && $this->stock_qty <= $this->low_stock_threshold;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class, 'variant_id');
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'variant_id');
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class, 'product_variant_id');
    }

    public function restockRequests(): HasMany
    {
        return $this->hasMany(RestockRequest::class, 'product_variant_id');
    }
}
