<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    protected $fillable = [
        'state_code',
        'rate_percent',
        'label',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'rate_percent' => 'decimal:4',
            'active' => 'boolean',
        ];
    }
}
