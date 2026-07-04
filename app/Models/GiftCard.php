<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class GiftCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'initial_balance',
        'balance',
        'currency',
        'recipient_email',
        'recipient_name',
        'purchaser_email',
        'purchase_payment_intent_id',
        'message',
        'active',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'initial_balance' => 'decimal:2',
            'balance' => 'decimal:2',
            'active' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Whether the card can currently be used as tender.
     */
    public function isRedeemable(): bool
    {
        if (! $this->active) {
            return false;
        }

        if ($this->expires_at !== null && Carbon::now()->isAfter($this->expires_at)) {
            return false;
        }

        return (float) $this->balance > 0;
    }

    /**
     * The amount of this card that can be applied to an order of $orderTotal,
     * i.e. never more than the remaining balance and never more than the total.
     */
    public function redeemableAmount(float $orderTotal): float
    {
        if (! $this->isRedeemable() || $orderTotal <= 0) {
            return 0.0;
        }

        return round(min((float) $this->balance, $orderTotal), 2);
    }

    /**
     * Generate a unique, human-legible gift-card code (e.g. GC-3F9K-7Q2M).
     */
    public static function generateCode(): string
    {
        do {
            $code = 'GC-'.strtoupper(Str::random(4)).'-'.strtoupper(Str::random(4));
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(GiftCardTransaction::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
