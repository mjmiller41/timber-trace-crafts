<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    protected $fillable = [
        'order_id',
        'carrier',
        'service',
        'tracking_number',
        'shipped_at',
        'estimated_delivery',
        'label_url',
    ];

    protected function casts(): array
    {
        return [
            'shipped_at' => 'datetime',
            'estimated_delivery' => 'date',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
