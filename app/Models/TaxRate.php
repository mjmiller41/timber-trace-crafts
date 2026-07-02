<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $rate_percent Stored as a fraction (e.g. 0.0600 for 6%), not a whole percentage — do not multiply by 100.
 */
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
