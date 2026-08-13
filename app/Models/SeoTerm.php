<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * سئوی دستیِ یک صفحه‌ی فرود (دسته‌بندی، مدل خودرو، یا ترکیب این دو).
 *
 * چون این رکوردها روی هر بازدیدِ صفحه‌ی فروشگاه خوانده می‌شوند و تعدادشان
 * چند صد تا بیشتر نمی‌شود، کل جدول یک‌جا کش می‌شود.
 */
class SeoTerm extends Model
{
    public const CACHE_KEY = 'seo:terms:all';

    public const TYPE_CATEGORY = 'category';
    public const TYPE_CAR = 'car';
    public const TYPE_CAR_CATEGORY = 'car_category';

    public const TYPES = [
        self::TYPE_CATEGORY     => 'دسته‌بندی',
        self::TYPE_CAR          => 'مدل خودرو',
        self::TYPE_CAR_CATEGORY => 'دسته × خودرو',
    ];

    protected $table = 'seo_terms';

    protected $fillable = [
        'type', 'slug', 'name', 'heading', 'intro', 'body',
        'seo_title', 'seo_description', 'focus_keyword',
        'robots_index', 'is_active',
    ];

    protected $casts = [
        'robots_index' => 'boolean',
        'is_active'    => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetCache());
        static::deleted(fn () => static::forgetCache());
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** نقشه‌ی «type:slug => رکورد» برای ترم‌های فعال. */
    public static function map(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            try {
                return static::where('is_active', 1)
                    ->get()
                    ->keyBy(fn ($term) => $term->type . ':' . $term->slug)
                    ->all();
            } catch (\Throwable $e) {
                // پیش از اجرای مهاجرت، نبود جدول نباید فروشگاه را بخواباند.
                return [];
            }
        });
    }

    public static function find_(string $type, ?string $slug): ?self
    {
        if ($slug === null || $slug === '') {
            return null;
        }

        return static::map()[$type . ':' . $slug] ?? null;
    }
}
