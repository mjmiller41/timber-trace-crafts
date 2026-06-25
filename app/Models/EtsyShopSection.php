<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EtsyShopSection extends Model
{
    protected $fillable = [
        'etsy_section_id',
        'title',
        'rank',
        'active_listing_count',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'etsy_shop_section_id', 'etsy_section_id');
    }
}
