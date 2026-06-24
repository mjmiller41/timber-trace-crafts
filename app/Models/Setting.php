<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    protected $fillable = [
        'key',
        'value',
        'group',
        'label',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'updated_at' => 'datetime',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever('setting.'.$key, function () use ($key, $default) {
            $setting = static::find($key);

            return $setting ? $setting->value : $default;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        $group = str_contains($key, '.') ? explode('.', $key)[0] : 'general';

        static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group, 'label' => $key, 'updated_at' => now()]
        );

        Cache::forget('setting.'.$key);
    }
}
