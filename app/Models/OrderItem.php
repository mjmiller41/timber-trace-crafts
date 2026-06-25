<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'sku_snapshot',
        'name_snapshot',
        'variant_label_snapshot',
        'personalization_text',
        'price_snapshot',
        'qty',
        'subtotal',
        'etsy_transaction_id',
        'etsy_expected_ship_date',
        'etsy_buyer_coupon',
        'etsy_shop_coupon',
    ];

    protected function casts(): array
    {
        return [
            'price_snapshot' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'qty' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(ProductReview::class);
    }
}
