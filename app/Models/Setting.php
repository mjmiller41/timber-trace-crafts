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
        // Cache the raw DB value (null = not set). Apply $default at read time so
        // callers with different defaults all get the right answer, and the first
        // call's default is never permanently locked into cache.
        $value = Cache::rememberForever('setting.'.$key, function () use ($key) {
            $setting = static::find($key);

            return $setting?->value;
        });

        return $value ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $group = str_contains($key, '.') ? explode('.', $key)[0] : 'general';

        $setting = static::firstOrCreate(
            ['key' => $key],
            ['group' => $group, 'label' => $key]
        );

        $setting->update(['value' => $value, 'updated_at' => now()]);

        Cache::forget('setting.'.$key);
    }
}
