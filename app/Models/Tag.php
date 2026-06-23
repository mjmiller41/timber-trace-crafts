<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_tags');
    }

    public function journalPosts(): BelongsToMany
    {
        return $this->belongsToMany(JournalPost::class, 'journal_post_tags', 'tag_id', 'post_id');
    }
}
