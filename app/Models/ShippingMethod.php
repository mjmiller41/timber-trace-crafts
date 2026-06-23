<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    protected $fillable = [
        'name',
        'carrier',
        'service_code',
        'description',
        'price_override',
        'is_free_base',
        'sort_order',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'price_override' => 'decimal:2',
            'is_free_base' => 'boolean',
            'sort_order' => 'integer',
            'active' => 'boolean',
        ];
    }
}
