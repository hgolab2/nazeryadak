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

    public const COMBO_CACHE_KEY = 'seo:car-models:combos';

    /**
     * مدلی که فقط یکی دو قطعه دارد، صفحه‌ی فرودِ تقریبا خالی می‌سازد؛
     * صفحه‌ی کم‌محتوا به‌جای کمک، کیفیت کل دامنه را پایین می‌آورد.
     */
    private const MIN_PRODUCTS = 3;

    /**
     * آستانه‌ی ایندکس‌شدن.
     *
     * بین MIN_PRODUCTS و این عدد، صفحه ساخته می‌شود و از داخل سایت در دسترس
     * است (لینک‌های موجود ۴۰۴ نمی‌گیرند) ولی noindex می‌خورد و به نقشه‌ی سایت
     * نمی‌رود. با زیاد شدن موجودی، همان صفحه خودبه‌خود ایندکس‌پذیر می‌شود.
     */
    public const INDEX_MIN_PRODUCTS = 10;

    /**
     * مقادیری که در ستون car_model «نام خودرو» نیستند.
     *
     * «نامشخص» یعنی وارد‌کننده مدل را نمی‌دانسته؛ ساختن صفحه‌ی فرودِ
     * «قطعات نامشخص» هم برای کاربر بی‌معنی است و هم یک صفحه‌ی بی‌کیفیت
     * به ایندکس اضافه می‌کند.
     */
    private const EXCLUDED = ['نامشخص', 'نا مشخص', 'متفرقه', 'سایر', '-'];

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
                if ($slug === '' || in_array($name, self::EXCLUDED, true)) {
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

    /**
     * آیا صفحه‌ی این خودرو آن‌قدر محتوا دارد که ارزش ایندکس‌شدن داشته باشد؟
     *
     * صفحه‌ی غیرقابل‌ایندکس همچنان کار می‌کند (۴۰۴ نمی‌دهد) اما noindex
     * می‌گیرد و در نقشه‌ی سایت نمی‌آید.
     */
    public static function isIndexable(?string $slug): bool
    {
        return self::countFor($slug) >= self::INDEX_MIN_PRODUCTS;
    }

    /**
     * تعداد محصول به ازای هر ترکیب «خودرو × دسته‌بندی».
     *
     * نقشه‌ی سایت تا پیش از این، برای هر خودروی پرمحصول هر ۱۱ دسته را فهرست
     * می‌کرد، حتی ترکیب‌هایی که یک محصول هم نداشتند؛ آن آدرس‌ها در صفحه
     * noindex می‌گیرند و آدرس noindex در نقشه‌ی سایت، خطای Search Console
     * می‌سازد. با این نقشه، فقط ترکیب‌های واقعاً موجود اعلام می‌شوند.
     *
     * @return array<string, array<int, int>> [اسلاگ خودرو => [شناسه‌ی دسته => تعداد]]
     */
    public static function comboCounts(): array
    {
        return Cache::remember(self::COMBO_CACHE_KEY, now()->addHours(6), function () {
            try {
                $rows = \DB::table('product_in_category')
                    ->join('products', 'products.id', '=', 'product_in_category.product_id')
                    ->where('products.is_active', 1)
                    ->whereNotNull('products.car_model')
                    ->where('products.car_model', '!=', '')
                    ->whereIn('product_in_category.category_id', array_map(
                        fn (\App\Enums\ProductCategory $case) => $case->value,
                        \App\Enums\ProductCategory::cases()
                    ))
                    ->groupBy('products.car_model', 'product_in_category.category_id')
                    ->selectRaw('products.car_model as car_model, product_in_category.category_id as category_id, COUNT(*) as cnt')
                    ->get();
            } catch (\Throwable $e) {
                return [];
            }

            $known = self::all();
            $combos = [];

            foreach ($rows as $row) {
                // چند املای مختلفِ یک خودرو به یک اسلاگ می‌رسند و باید جمع شوند.
                $slug = seo_slug((string) $row->car_model, '');
                if ($slug === '' || ! isset($known[$slug])) {
                    continue;
                }

                $categoryId = (int) $row->category_id;
                $combos[$slug][$categoryId] = ($combos[$slug][$categoryId] ?? 0) + (int) $row->cnt;
            }

            return $combos;
        });
    }

    /** تعداد محصول یک ترکیب مشخص. */
    public static function comboCount(?string $carSlug, int $categoryId): int
    {
        return (int) (self::comboCounts()[seo_slug((string) $carSlug, '')][$categoryId] ?? 0);
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::COMBO_CACHE_KEY);
    }
}
