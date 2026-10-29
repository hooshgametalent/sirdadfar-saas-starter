<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Cached key/value access to the settings table. Known keys used by the
 * public site: site_name, support_phone, support_email, address, hero_text.
 */
class Settings
{
    public const CACHE_KEY = 'freight.settings';

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = self::all()[$key] ?? null;

        return filled($value) ? $value : $default;
    }

    public static function set(string $key, mixed $value): void
    {
        Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        self::forget();
    }

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $settings = [];

            foreach (Setting::query()->get(['key', 'value']) as $setting) {
                $settings[$setting->key] = $setting->value;
            }

            return $settings;
        });
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
