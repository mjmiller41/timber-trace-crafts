<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $fillable = [
        'filename',
        'original_name',
        'disk',
        'path',
        'mime_type',
        'size_bytes',
        'alt_text',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'image_id');
    }

    public function productMedia(): HasMany
    {
        return $this->hasMany(ProductMedia::class, 'media_id');
    }

    public function journalPosts(): HasMany
    {
        return $this->hasMany(JournalPost::class, 'featured_image_id');
    }
}
