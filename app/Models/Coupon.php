<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'type',
        'value',
        'min_order_amount',
        'max_uses',
        'used_count',
        'applies_to',
        'category_id',
        'product_id',
        'starts_at',
        'expires_at',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'min_order_amount' => 'decimal:2',
            'max_uses' => 'integer',
            'used_count' => 'integer',
            'active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function isValid(float $orderAmount): bool
    {
        if (! $this->active) {
            return false;
        }

        $now = Carbon::now();

        if ($this->starts_at !== null && $now->isBefore($this->starts_at)) {
            return false;
        }

        if ($this->expires_at !== null && $now->isAfter($this->expires_at)) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        if ($orderAmount < (float) $this->min_order_amount) {
            return false;
        }

        return true;
    }

    /**
     * Calculate the discount amount for the given cart contents.
     * For 'product' or 'category' coupons, only eligible items contribute.
     *
     * @param  array<string, mixed>  $cart
     */
    public function calculateDiscount(array $cart): float
    {
        $items = collect($cart);

        $eligibleSubtotal = match ($this->applies_to) {
            'product' => $this->product_id
                ? $items->filter(fn ($i) => $i['product_id'] === $this->product_id)
                    ->sum(fn ($i) => ($i['price'] + ($i['personalization_price'] ?? 0)) * $i['qty'])
                : 0.0,

            'category' => $this->category_id
                ? $this->eligibleProductIds($items)
                    ->pipe(fn (Collection $ids) => $items->filter(fn ($i) => $ids->contains($i['product_id'])))
                    ->sum(fn ($i) => ($i['price'] + ($i['personalization_price'] ?? 0)) * $i['qty'])
                : 0.0,

            default => $items->sum(fn ($i) => ($i['price'] + ($i['personalization_price'] ?? 0)) * $i['qty']),
        };

        return match ($this->type) {
            'percent' => round($eligibleSubtotal * ((float) $this->value / 100), 2),
            'fixed' => min((float) $this->value, $eligibleSubtotal),
            default => 0.0,
        };
    }

    private function eligibleProductIds(Collection $items): Collection
    {
        $productIds = $items->pluck('product_id')->unique();

        return Product::where('category_id', $this->category_id)
            ->whereIn('id', $productIds)
            ->pluck('id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
