<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

/**
 * فهرست مدل‌های خودرو، مستقیم از ستون products.car_model.
 *
 * این مدل‌ها جدول جدا ندارند و به‌صورت متن آزاد وارد شده‌اند. تا پیش از این
 * فهرستشان در SitemapController هاردکد بود (۱۲ مورد، در حالی که ۲۴ مدل در
 * دیتابیس هست) و هر مدل تازه‌ای که وارد انبار می‌شد، تا اصلاح دستی کد در
 * نقشه‌ی سایت دیده نمی‌شد.
 *
 * اسلاگ‌ها با seo_slug ساخته می‌شوند که «ي/ك» عربی را به فارسی نگاشت می‌کند،
 * پس دو املای مختلفِ یک خودرو به یک صفحه می‌رسند.
 */
class CarModels
{
    public const CACHE_KEY = 'seo:car-models';

    /**
     * مدلی که فقط یکی دو قطعه دارد، صفحه‌ی فرودِ تقریبا خالی می‌سازد؛
     * صفحه‌ی کم‌محتوا به‌جای کمک، کیفیت کل دامنه را پایین می‌آورد.
     */
    private const MIN_PRODUCTS = 3;

    /** @return array<string, array{name: string, count: int}> کلید = اسلاگ */
    public static function all(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addHours(6), function () {
            try {
                $rows = Product::query()
                    ->where('is_active', 1)
                    ->whereNotNull('car_model')
                    ->where('car_model', '!=', '')
                    ->selectRaw('car_model, COUNT(*) as cnt')
                    ->groupBy('car_model')
                    ->get();
            } catch (\Throwable $e) {
                return [];
            }

            $models = [];
            foreach ($rows as $row) {
                $name = trim((string) $row->car_model);
                $slug = seo_slug($name, '');
                if ($slug === '') {
                    continue;
                }

                if (! isset($models[$slug])) {
                    $models[$slug] = ['name' => $name, 'count' => 0];
                }

                // چند املای مختلف به یک اسلاگ می‌رسند؛ پرتکرارترین املا
                // به‌عنوان نام نمایشی انتخاب می‌شود.
                if ($row->cnt > ($models[$slug]['top'] ?? 0)) {
                    $models[$slug]['name'] = $name;
                    $models[$slug]['top'] = (int) $row->cnt;
                }

                $models[$slug]['count'] += (int) $row->cnt;
            }

            $models = array_filter($models, fn ($m) => $m['count'] >= self::MIN_PRODUCTS);
            uasort($models, fn ($a, $b) => $b['count'] <=> $a['count']);

            foreach ($models as &$model) {
                unset($model['top']);
            }

            return $models;
        });
    }

    /** نام اصلی خودرو از روی اسلاگ؛ null یعنی چنین صفحه‌ای وجود ندارد. */
    public static function fromSlug(?string $slug): ?string
    {
        $slug = seo_slug((string) $slug, '');

        return self::all()[$slug]['name'] ?? null;
    }

    public static function slugFor(?string $name): string
    {
        return seo_slug((string) $name, '');
    }

    public static function countFor(?string $slug): int
    {
        return (int) (self::all()[seo_slug((string) $slug, '')]['count'] ?? 0);
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
