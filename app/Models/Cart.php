<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cart extends Model
{
    protected $fillable = [
        'token',
        'user_id',
        'email',
        'contents',
        'item_count',
        'subtotal',
        'unsubscribe_token',
        'last_activity_at',
        'reminder_stage',
        'reminder_sent_at',
        'converted_at',
    ];

    protected function casts(): array
    {
        return [
            'contents' => 'array',
            'subtotal' => 'decimal:2',
            'last_activity_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * A cart is a candidate for an abandoned-cart reminder when it has items,
     * a known email, has not converted to an order, and the email has not
     * opted out. Stage/idle filtering is applied by the sweep command.
     */
    public function isRemindable(): bool
    {
        return $this->item_count > 0
            && ! empty($this->email)
            && $this->converted_at === null;
    }
}
