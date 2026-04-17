<?php

namespace ShhStore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Throwable;

class StoreSetting extends Model
{
    protected $table = 'shh_store_settings';

    protected $fillable = [
        'key',
        'value',
        'is_encrypted',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
    ];

    protected static array $runtimeCache = [];
    protected static bool $cacheWarmed = false;

    /**
     * Pre-load all non-encrypted settings into memory once per request.
     */
    protected static function warmCache(): void
    {
        if (static::$cacheWarmed) {
            return;
        }

        if (!SchemaFacade::hasTable('shh_store_settings')) {
            static::$cacheWarmed = true;
            return;
        }

        static::query()->get()->each(function (self $setting) {
            static::$runtimeCache[$setting->key] = $setting;
        });

        static::$cacheWarmed = true;
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        static::warmCache();

        if (!array_key_exists($key, static::$runtimeCache)) {
            return $default;
        }

        $setting = static::$runtimeCache[$key];
        if (!$setting) {
            return $default;
        }

        if ($setting->is_encrypted) {
            if ($setting->value === null || $setting->value === '') {
                return $default;
            }

            try {
                return Crypt::decryptString((string) $setting->value);
            } catch (Throwable) {
                return $default;
            }
        }

        return $setting->value ?? $default;
    }

    public static function setValue(string $key, mixed $value, bool $encrypted = false): void
    {
        $storedValue = $value;

        if ($encrypted) {
            $storedValue = ($value === null || $value === '')
                ? null
                : Crypt::encryptString((string) $value);
        } else {
            $storedValue = $value === null ? null : (string) $value;
        }

        $setting = static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'is_encrypted' => $encrypted,
            ]
        );

        // Update runtime cache
        static::$runtimeCache[$key] = $setting;
    }
}
