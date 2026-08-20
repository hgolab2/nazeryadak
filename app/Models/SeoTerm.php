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
        'type', 'slug', 'name', 'heading', 'intro', 'body', 'faq',
        'seo_title', 'seo_description', 'focus_keyword',
        'robots_index', 'is_active', 'generated',
    ];

    protected $casts = [
        'robots_index' => 'boolean',
        'is_active'    => 'boolean',
        'generated'    => 'boolean',
    ];

    /**
     * پرسش‌های متداول به‌صورت آرایه‌ی [['q' => ..., 'a' => ...], ...].
     *
     * ستون به‌صورت JSON ذخیره می‌شود ولی cast نمی‌شود، چون رکوردهای قدیمی
     * (یا ورودی دستیِ نادرست از پنل) نباید کل صفحه‌ی فروشگاه را با
     * JsonException بخوابانند؛ اینجا هر مقدار خراب به آرایه‌ی خالی می‌رسد.
     */
    public function faqList(): array
    {
        if (blank($this->faq)) {
            return [];
        }

        $decoded = json_decode((string) $this->faq, true);
        if (! is_array($decoded)) {
            return [];
        }

        $faqs = [];
        foreach ($decoded as $row) {
            $question = trim((string) ($row['q'] ?? ''));
            $answer   = trim((string) ($row['a'] ?? ''));
            if ($question !== '' && $answer !== '') {
                $faqs[] = ['q' => $question, 'a' => $answer];
            }
        }

        return $faqs;
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetCache());
        static::deleted(fn () => static::forgetCache());
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);

        // کش تک‌رکوردی سراسری پاک نمی‌شود (بدون tag ممکن نیست)؛ به‌جای آن
        // نسخه‌ی کلید بالا می‌رود و کلیدهای قدیمی خودبه‌خود بی‌استفاده می‌مانند.
        // increment روی کلید نبوده در درایور file کار نمی‌کند، پس صریح می‌نویسیم.
        Cache::forever(self::CACHE_KEY . ':version', static::cacheVersion() + 1);
    }

    private static function cacheVersion(): int
    {
        return (int) Cache::get(self::CACHE_KEY . ':version', 0);
    }

    /**
     * فهرست کلیدهای «type:slug» که ترم فعال دارند.
     *
     * فقط کلیدها کش می‌شوند نه خود رکوردها: با چند صد صفحه‌ی فرود که هرکدام
     * چند کیلوبایت HTML دارند، کش‌کردن کل جدول یعنی مگابایت‌ها دیسریالایز در
     * هر بازدید فروشگاه. این آرایه‌ی رشته‌ای چند ده کیلوبایت بیشتر نیست و
     * صفحاتی که ترم ندارند (اکثر آدرس‌ها) اصلا به دیتابیس نمی‌روند.
     *
     * @return array<int, string>
     */
    public static function activeKeys(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            try {
                return static::where('is_active', 1)
                    ->get(['type', 'slug'])
                    ->map(fn ($term) => $term->type . ':' . $term->slug)
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

        $key = $type . ':' . $slug;

        if (! in_array($key, static::activeKeys(), true)) {
            return null;
        }

        return Cache::remember(self::CACHE_KEY . ':' . static::cacheVersion() . ':' . md5($key), now()->addDay(), function () use ($type, $slug) {
            try {
                return static::where('is_active', 1)
                    ->where('type', $type)
                    ->where('slug', $slug)
                    ->first();
            } catch (\Throwable $e) {
                return null;
            }
        });
    }
}
