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

    public static function getValue(string $key, mixed $default = null): mixed
    {
        if (!SchemaFacade::hasTable('shh_store_settings')) {
            return $default;
        }

        $setting = static::query()->where('key', $key)->first();
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

        static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'is_encrypted' => $encrypted,
            ]
        );
    }
}
