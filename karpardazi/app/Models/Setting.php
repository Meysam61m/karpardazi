<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = ['key', 'value'];

    protected static array $cache = [];

    public static function value(string $key, ?string $default = null): ?string
    {
        if (! array_key_exists($key, static::$cache)) {
            static::$cache[$key] = static::query()->find($key)?->value ?? $default;
        }

        return static::$cache[$key];
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        static::$cache[$key] = $value;
    }

    public static function flushCache(): void
    {
        static::$cache = [];
    }
}
