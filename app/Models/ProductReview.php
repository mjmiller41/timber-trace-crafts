<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductReview extends Model
{
    protected $fillable = [
        'product_id',
        'order_item_id',
        'user_id',
        'etsy_transaction_id',
        'source',
        'etsy_image_url',
        'language',
        'guest_name',
        'guest_email',
        'rating',
        'title',
        'body',
        'status',
        'admin_note',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Display name for the reviewer, preferring an explicit guest name, then the
     * linked account's name, falling back to a neutral label. Never exposes an
     * email address.
     */
    public function getReviewerNameAttribute(): string
    {
        if (filled($this->guest_name)) {
            return $this->guest_name;
        }

        if ($this->relationLoaded('user') && $this->user && filled($this->user->name)) {
            return $this->user->name;
        }

        return 'Verified Buyer';
    }

    /**
     * Public URL of the customer/curated review photo, if any. Today this is the
     * imported Etsy review image; a future direct-upload column can extend this
     * accessor without touching the views.
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->etsy_image_url ?: null;
    }

    public function hasImage(): bool
    {
        return filled($this->image_url);
    }
}
