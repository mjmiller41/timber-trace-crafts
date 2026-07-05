<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartEmailSuppression extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'email',
        'reason',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public static function suppresses(?string $email): bool
    {
        if (empty($email)) {
            return false;
        }

        return static::where('email', $email)->exists();
    }
}
