<?php

namespace App\Support;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Schema;

class SettingStore
{
    private static ?array $cache = null;

    public static function get(string $key, ?string $default = null): ?string
    {
        if (self::$cache === null) {
            self::$cache = self::load();
        }

        return self::$cache[$key] ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        AppSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        self::$cache = null;
    }

    public static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = self::load();
        }

        return self::$cache;
    }

    private static function load(): array
    {
        try {
            if (! Schema::hasTable('app_settings')) {
                return [];
            }

            return AppSetting::query()->pluck('value', 'key')->toArray();
        } catch (\Throwable) {
            return [];
        }
    }
}
