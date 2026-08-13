<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = ['setting_key', 'setting_value'];

    private const CACHE_KEY = 'site_settings_all';

    /**
     * همه‌ی تنظیمات یک‌جا خوانده و کش می‌شوند؛ هدر و فوتر چند کلید مختلف
     * می‌خواهند و بدون کش، هر صفحه چند کوئری اضافه می‌زد.
     */
    public static function all_settings(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            // اگر هنوز مایگریشن اجرا نشده باشد، سایت نباید ۵۰۰ بدهد
            try {
                return static::pluck('setting_value', 'setting_key')->all();
            } catch (\Throwable $e) {
                return [];
            }
        });
    }

    public static function get(string $key, $default = null)
    {
        $value = self::all_settings()[$key] ?? null;

        return ($value === null || $value === '') ? $default : $value;
    }

    public static function put(string $key, $value): void
    {
        static::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => (string) $value]
        );

        self::forget();
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
