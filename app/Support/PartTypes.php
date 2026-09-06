<?php

namespace App\Support;

use App\Enums\ProductCategory;
use App\Models\Product;
use Illuminate\Support\Facades\Cache;

/**
 * انواع قطعه — محورِ سومِ صفحات فرود، کنار دسته‌بندی و مدل خودرو.
 *
 * دسته‌بندی‌های سایت یازده گروه کلان‌اند («چرخ، ترمز و جلوبندی»). هیچ کاربری
 * این عبارت را جستجو نمی‌کند؛ او «لنت ترمز پژو ۲۰۶» را جستجو می‌کند. فاصله‌ی
 * بین این دو، همان صفحاتی است که این کلاس می‌سازد.
 *
 * چرا فهرست دستی و نه استخراج خودکار از عنوان محصولات؟ عنوان‌ها آزاد وارد
 * شده‌اند («کمک فنرعقب»، «ترمز درب جلو») و خوشه‌بندی خودکارشان صفحاتی با
 * نام‌های بی‌معنی می‌سازد. فهرست دستی هم نامِ درست را تضمین می‌کند و هم اینکه
 * فقط قطعاتی صفحه بگیرند که واقعا جستجو می‌شوند.
 *
 * کلید تطبیق، الگوهای `match` است که روی عنوان محصول اعمال می‌شوند. الگوی
 * کوتاه خطرناک است — «فن» با LIKE به «کمک فنر» هم می‌خورد — برای همین هرجا
 * لازم بوده `exclude` گذاشته شده.
 */
class PartTypes
{
    public const CACHE_KEY = 'seo:part-types';
    public const COMBO_CACHE_KEY = 'seo:part-types:cars';

    /** کمتر از این تعداد، صفحه‌ی نوع قطعه ساخته می‌شود ولی ایندکس نمی‌شود. */
    public const INDEX_MIN_PRODUCTS = 8;

    /** آستانه‌ی ایندکس برای ترکیب «قطعه × خودرو». */
    public const COMBO_MIN_INDEXABLE = 3;

    /**
     * @return array<string, array{
     *     name: string, category: int, match: string[], exclude: string[], keyword: string
     * }>  کلید = اسلاگ
     */
    public static function catalog(): array
    {
        return [
            /* --- چرخ، ترمز و جلوبندی --- */
            'لنت-ترمز' => [
                'name' => 'لنت ترمز', 'category' => ProductCategory::BRAKE_SUSPENSION->value,
                'match' => ['لنت'], 'exclude' => [], 'keyword' => 'لنت ترمز',
            ],
            'دیسک-ترمز' => [
                'name' => 'دیسک ترمز', 'category' => ProductCategory::BRAKE_SUSPENSION->value,
                'match' => ['دیسک ترمز', 'دیسک چرخ'], 'exclude' => ['دیسک کلاچ'], 'keyword' => 'دیسک ترمز',
            ],
            'کمک-فنر' => [
                'name' => 'کمک فنر', 'category' => ProductCategory::BRAKE_SUSPENSION->value,
                'match' => ['کمک فنر', 'کمک‌فنر', 'کمکفنر'], 'exclude' => [], 'keyword' => 'کمک فنر',
            ],
            'سیبک-و-طبق' => [
                'name' => 'سیبک و طبق', 'category' => ProductCategory::BRAKE_SUSPENSION->value,
                'match' => ['سیبک', 'طبق چرخ', 'طبق جلو', 'بوش طبق'], 'exclude' => [], 'keyword' => 'سیبک و طبق',
            ],
            'بلبرینگ-چرخ' => [
                'name' => 'بلبرینگ چرخ', 'category' => ProductCategory::BRAKE_SUSPENSION->value,
                'match' => ['بلبرینگ چرخ', 'توپی چرخ'], 'exclude' => [], 'keyword' => 'بلبرینگ چرخ',
            ],
            'کالیپر-ترمز' => [
                'name' => 'کالیپر ترمز', 'category' => ProductCategory::BRAKE_SUSPENSION->value,
                'match' => ['کالیپر'], 'exclude' => [], 'keyword' => 'کالیپر ترمز',
            ],
            'جعبه-فرمان' => [
                'name' => 'جعبه فرمان', 'category' => ProductCategory::BRAKE_SUSPENSION->value,
                'match' => ['جعبه فرمان', 'میل فرمان', 'پمپ هیدرولیک فرمان'], 'exclude' => [], 'keyword' => 'جعبه فرمان',
            ],

            /* --- قطعات مصرفی --- */
            'فیلتر-هوا' => [
                'name' => 'فیلتر هوا', 'category' => ProductCategory::CONSUMABLES->value,
                'match' => ['فیلتر هوا', 'صافی هوا'], 'exclude' => [], 'keyword' => 'فیلتر هوا',
            ],
            'فیلتر-روغن' => [
                'name' => 'فیلتر روغن', 'category' => ProductCategory::CONSUMABLES->value,
                'match' => ['فیلتر روغن', 'صافی روغن'], 'exclude' => [], 'keyword' => 'فیلتر روغن',
            ],
            'فیلتر-بنزین' => [
                'name' => 'فیلتر بنزین', 'category' => ProductCategory::CONSUMABLES->value,
                'match' => ['فیلتر بنزین', 'فیلتر سوخت', 'صافی بنزین'], 'exclude' => [], 'keyword' => 'فیلتر بنزین',
            ],
            'شمع-خودرو' => [
                'name' => 'شمع خودرو', 'category' => ProductCategory::CONSUMABLES->value,
                'match' => ['شمع'], 'exclude' => ['شمعک'], 'keyword' => 'شمع خودرو',
            ],
            'تسمه-تایم' => [
                'name' => 'تسمه تایم', 'category' => ProductCategory::ENGINE->value,
                'match' => ['تسمه تایم', 'تسمه سفت کن', 'تسمه‌سفت‌کن', 'غلتک تسمه'], 'exclude' => [], 'keyword' => 'تسمه تایم',
            ],
            'تسمه-دینام' => [
                'name' => 'تسمه دینام', 'category' => ProductCategory::CONSUMABLES->value,
                'match' => ['تسمه دینام', 'تسمه لوازم جانبی'], 'exclude' => [], 'keyword' => 'تسمه دینام',
            ],
            'تیغه-برف-پاک-کن' => [
                'name' => 'تیغه برف‌پاک‌کن', 'category' => ProductCategory::CONSUMABLES->value,
                'match' => ['تیغه برف', 'برف پاک کن', 'برف‌پاک‌کن'], 'exclude' => [], 'keyword' => 'تیغه برف پاک کن',
            ],

            /* --- موتور --- */
            'دسته-موتور' => [
                'name' => 'دسته موتور', 'category' => ProductCategory::ENGINE->value,
                'match' => ['دسته موتور', 'دسته‌موتور', 'پایه موتور'], 'exclude' => [], 'keyword' => 'دسته موتور',
            ],
            'سرسیلندر' => [
                'name' => 'سرسیلندر', 'category' => ProductCategory::ENGINE->value,
                'match' => ['سرسیلندر', 'سر سیلندر'], 'exclude' => ['واشر سرسیلندر', 'واشر سر سیلندر'], 'keyword' => 'سرسیلندر',
            ],
            'واشر-سرسیلندر' => [
                'name' => 'واشر سرسیلندر', 'category' => ProductCategory::ENGINE->value,
                'match' => ['واشر سرسیلندر', 'واشر سر سیلندر'], 'exclude' => [], 'keyword' => 'واشر سرسیلندر',
            ],
            'پیستون-و-رینگ' => [
                'name' => 'پیستون و رینگ', 'category' => ProductCategory::ENGINE->value,
                'match' => ['پیستون', 'رینگ موتور', 'رینگ پیستون'], 'exclude' => [], 'keyword' => 'پیستون و رینگ موتور',
            ],
            'میل-لنگ' => [
                'name' => 'میل لنگ', 'category' => ProductCategory::ENGINE->value,
                'match' => ['میل لنگ', 'میل‌لنگ', 'یاتاقان'], 'exclude' => [], 'keyword' => 'میل لنگ',
            ],
            'سوپاپ' => [
                'name' => 'سوپاپ', 'category' => ProductCategory::ENGINE->value,
                'match' => ['سوپاپ'], 'exclude' => [], 'keyword' => 'سوپاپ',
            ],
            'اویل-پمپ' => [
                'name' => 'اویل پمپ', 'category' => ProductCategory::ENGINE->value,
                'match' => ['اویل پمپ', 'پمپ روغن', 'اویل‌پمپ'], 'exclude' => [], 'keyword' => 'اویل پمپ',
            ],
            'کاسه-نمد' => [
                'name' => 'کاسه نمد', 'category' => ProductCategory::ENGINE->value,
                'match' => ['کاسه نمد', 'کاسه‌نمد'], 'exclude' => [], 'keyword' => 'کاسه نمد',
            ],

            /* --- خنک‌کننده --- */
            'رادیاتور' => [
                'name' => 'رادیاتور', 'category' => ProductCategory::COOLING->value,
                'match' => ['رادیاتور'], 'exclude' => ['رادیاتور بخاری'], 'keyword' => 'رادیاتور',
            ],
            'واترپمپ' => [
                'name' => 'واترپمپ', 'category' => ProductCategory::COOLING->value,
                'match' => ['واترپمپ', 'واتر پمپ', 'پمپ آب'], 'exclude' => [], 'keyword' => 'واترپمپ',
            ],
            'ترموستات' => [
                'name' => 'ترموستات', 'category' => ProductCategory::COOLING->value,
                'match' => ['ترموستات', 'هوزینگ ترموستات'], 'exclude' => [], 'keyword' => 'ترموستات',
            ],
            'منبع-انبساط' => [
                'name' => 'منبع انبساط', 'category' => ProductCategory::COOLING->value,
                'match' => ['منبع انبساط', 'مخزن انبساط'], 'exclude' => [], 'keyword' => 'منبع انبساط',
            ],
            'شیلنگ-رادیاتور' => [
                'name' => 'شیلنگ رادیاتور', 'category' => ProductCategory::COOLING->value,
                'match' => ['شیلنگ رادیاتور', 'شیلنگ آب'], 'exclude' => [], 'keyword' => 'شیلنگ رادیاتور',
            ],

            /* --- سوخت‌رسانی و جرقه --- */
            'پمپ-بنزین' => [
                'name' => 'پمپ بنزین', 'category' => ProductCategory::FUEL_SYSTEM->value,
                'match' => ['پمپ بنزین', 'پمپ سوخت'], 'exclude' => [], 'keyword' => 'پمپ بنزین',
            ],
            'انژکتور' => [
                'name' => 'انژکتور', 'category' => ProductCategory::FUEL_SYSTEM->value,
                'match' => ['انژکتور', 'ریل سوخت'], 'exclude' => [], 'keyword' => 'انژکتور',
            ],
            'سنسور-اکسیژن' => [
                'name' => 'سنسور اکسیژن', 'category' => ProductCategory::FUEL_SYSTEM->value,
                'match' => ['سنسور اکسیژن', 'اکسیژن سنسور'], 'exclude' => [], 'keyword' => 'سنسور اکسیژن',
            ],
            'دریچه-گاز' => [
                'name' => 'دریچه گاز', 'category' => ProductCategory::FUEL_SYSTEM->value,
                'match' => ['دریچه گاز', 'استپر موتور'], 'exclude' => [], 'keyword' => 'دریچه گاز',
            ],
            'کوئل-و-وایر' => [
                'name' => 'کوئل و وایر شمع', 'category' => ProductCategory::FUEL_SYSTEM->value,
                'match' => ['کوئل', 'کویل', 'وایر شمع'], 'exclude' => [], 'keyword' => 'کوئل و وایر شمع',
            ],

            /* --- برق و سنسور --- */
            'دینام-و-استارت' => [
                'name' => 'دینام و استارت', 'category' => ProductCategory::ELECTRICAL->value,
                'match' => ['دینام', 'استارت', 'آلترناتور'], 'exclude' => ['سوییچ استارت'], 'keyword' => 'دینام و استارت',
            ],
            'دسته-سیم' => [
                'name' => 'دسته سیم', 'category' => ProductCategory::ELECTRICAL->value,
                'match' => ['دسته سیم', 'دسته‌سیم', 'سیم کشی'], 'exclude' => [], 'keyword' => 'دسته سیم',
            ],
            'سنسور-دما' => [
                'name' => 'سنسور دما', 'category' => ProductCategory::ELECTRICAL->value,
                'match' => ['سنسور دما', 'فشنگی آب', 'سنسور حرارت'], 'exclude' => [], 'keyword' => 'سنسور دمای آب',
            ],
            'رله-و-فیوز' => [
                'name' => 'رله و فیوز', 'category' => ProductCategory::ELECTRICAL->value,
                'match' => ['رله', 'فیوز'], 'exclude' => [], 'keyword' => 'رله و فیوز',
            ],
            'واحد-کنترل-الکترونیکی' => [
                'name' => 'واحد کنترل الکترونیکی (ECU)', 'category' => ProductCategory::ELECTRICAL->value,
                'match' => ['واحد کنترل', 'کنترل یونیت', 'ای سی یو'], 'exclude' => [], 'keyword' => 'ای سی یو خودرو',
            ],

            /* --- گیربکس --- */
            'دیسک-و-صفحه-کلاچ' => [
                'name' => 'دیسک و صفحه کلاچ', 'category' => ProductCategory::GEARBOX->value,
                'match' => ['دیسک کلاچ', 'صفحه کلاچ', 'بلبرینگ کلاچ', 'کیت کلاچ'], 'exclude' => [], 'keyword' => 'دیسک و صفحه کلاچ',
            ],
            'پلوس-و-گردگیر' => [
                'name' => 'پلوس و گردگیر', 'category' => ProductCategory::GEARBOX->value,
                'match' => ['پلوس', 'گردگیر'], 'exclude' => [], 'keyword' => 'پلوس خودرو',
            ],

            /* --- بدنه --- */
            'سپر' => [
                'name' => 'سپر', 'category' => ProductCategory::CHASSIS_BODY->value,
                'match' => ['سپر'], 'exclude' => [], 'keyword' => 'سپر خودرو',
            ],
            'گلگیر' => [
                'name' => 'گلگیر', 'category' => ProductCategory::CHASSIS_BODY->value,
                'match' => ['گلگیر', 'گل گیر'], 'exclude' => [], 'keyword' => 'گلگیر',
            ],
            'چراغ-جلو' => [
                'name' => 'چراغ جلو', 'category' => ProductCategory::CHASSIS_BODY->value,
                'match' => ['چراغ جلو', 'چراغ مه شکن', 'چراغ مه‌شکن'], 'exclude' => [], 'keyword' => 'چراغ جلو',
            ],
            'چراغ-عقب' => [
                'name' => 'چراغ عقب', 'category' => ProductCategory::CHASSIS_BODY->value,
                'match' => ['چراغ عقب', 'چراغ خطر'], 'exclude' => [], 'keyword' => 'چراغ عقب',
            ],
            'شیشه-بالابر' => [
                'name' => 'شیشه بالابر', 'category' => ProductCategory::CHASSIS_BODY->value,
                'match' => ['شیشه بالابر', 'شیشه‌بالابر', 'مکانیزم شیشه'], 'exclude' => [], 'keyword' => 'شیشه بالابر',
            ],
            'قفل-و-دستگیره-درب' => [
                'name' => 'قفل و دستگیره درب', 'category' => ProductCategory::CHASSIS_BODY->value,
                'match' => ['قفل درب', 'دستگیره درب', 'مجموعه قفل', 'قفل برقی'], 'exclude' => [], 'keyword' => 'قفل و دستگیره درب',
            ],
            'آینه-بغل' => [
                'name' => 'آینه بغل', 'category' => ProductCategory::CHASSIS_BODY->value,
                'match' => ['آینه بغل', 'آینه جانبی', 'آینه برقی'], 'exclude' => [], 'keyword' => 'آینه بغل',
            ],

            /* --- اگزوز و تهویه --- */
            'منیفولد' => [
                'name' => 'منیفولد', 'category' => ProductCategory::EXHAUST->value,
                'match' => ['منیفولد', 'مانیفولد'], 'exclude' => [], 'keyword' => 'منیفولد',
            ],
            'کاتالیست' => [
                'name' => 'کاتالیست', 'category' => ProductCategory::EXHAUST->value,
                'match' => ['کاتالیست', 'کاتالیزور'], 'exclude' => [], 'keyword' => 'کاتالیست',
            ],
            'کمپرسور-کولر' => [
                'name' => 'کمپرسور کولر', 'category' => ProductCategory::EXHAUST->value,
                'match' => ['کمپرسور'], 'exclude' => [], 'keyword' => 'کمپرسور کولر',
            ],
            'رادیاتور-بخاری' => [
                'name' => 'رادیاتور بخاری', 'category' => ProductCategory::EXHAUST->value,
                'match' => ['رادیاتور بخاری', 'موتور فن بخاری'], 'exclude' => [], 'keyword' => 'رادیاتور بخاری',
            ],

            /* --- تزئینات --- */
            'داشبورد' => [
                'name' => 'داشبورد', 'category' => ProductCategory::INTERIOR->value,
                'match' => ['داشبورد', 'داش بورد'], 'exclude' => [], 'keyword' => 'داشبورد',
            ],
            'قالپاق-چرخ' => [
                'name' => 'قالپاق چرخ', 'category' => ProductCategory::INTERIOR->value,
                'match' => ['قالپاق'], 'exclude' => [], 'keyword' => 'قالپاق چرخ',
            ],
        ];
    }

    public static function name(?string $slug): ?string
    {
        return static::catalog()[(string) $slug]['name'] ?? null;
    }

    public static function has(?string $slug): bool
    {
        return isset(static::catalog()[(string) $slug]);
    }

    /** دسته‌بندی‌ای که این نوع قطعه زیر آن می‌نشیند (برای مسیر راهنما). */
    public static function categoryOf(?string $slug): ?ProductCategory
    {
        $id = static::catalog()[(string) $slug]['category'] ?? null;

        return $id === null ? null : ProductCategory::tryFrom((int) $id);
    }

    /**
     * تعداد محصول هر نوع قطعه.
     *
     * @return array<string, int>
     */
    public static function counts(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addHours(6), fn () => static::scan()['parts']);
    }

    public static function countFor(?string $slug): int
    {
        return (int) (static::counts()[(string) $slug] ?? 0);
    }

    public static function isIndexable(?string $slug): bool
    {
        return static::countFor($slug) >= self::INDEX_MIN_PRODUCTS;
    }

    /**
     * تعداد محصول هر ترکیب «نوع قطعه × خودرو».
     *
     * ۵۰ نوع قطعه × ۲۰ خودرو یعنی ۱۰۰۰ ترکیب؛ اگر هرکدام یک کوئری می‌گرفت،
     * ساختن لینک‌های داخلیِ یک صفحه‌ی فروشگاه ده‌ها کوئری می‌شد. اینجا به ازای
     * هر نوع قطعه یک کوئریِ گروه‌بندی‌شده زده و نتیجه شش ساعت کش می‌شود.
     *
     * @return array<string, array<string, int>> [اسلاگ قطعه => [اسلاگ خودرو => تعداد]]
     */
    public static function carCounts(): array
    {
        return Cache::remember(self::COMBO_CACHE_KEY, now()->addHours(6), fn () => static::scan()['combos']);
    }

    /**
     * یک‌بار خواندن کل عنوان‌ها و شمردن همه‌ی انواع قطعه و ترکیب‌ها با هم.
     *
     * نسخه‌ی قبلی به ازای هر نوع قطعه یک کوئری LIKE روی کل جدول می‌زد؛ ۵۲
     * اسکن کاملِ جدول با REPLACE تودرتو، که اولین بازدید پس از خالی شدن کش را
     * شش ثانیه می‌کرد — هم برای کاربر بد بود و هم خزنده همان کندی را می‌دید.
     *
     * تطبیق با همان نگاشتی انجام می‌شود که scopePartType در SQL استفاده
     * می‌کند (Product::normalizeTitleForMatch)، پس شمارش و فهرست واقعیِ صفحه
     * از هم جدا نمی‌افتند.
     *
     * @return array{parts: array<string, int>, combos: array<string, array<string, int>>}
     */
    private static function scan(): array
    {
        $catalog = static::catalog();
        $parts   = array_fill_keys(array_keys($catalog), 0);
        $combos  = [];

        try {
            $rows  = Product::where('is_active', 1)->get(['title', 'car_model']);
            $known = CarModels::all();
        } catch (\Throwable $e) {
            return ['parts' => $parts, 'combos' => []];
        }

        // الگوها یک‌بار نرمال می‌شوند، نه به ازای هر محصول
        $patterns = [];
        foreach ($catalog as $slug => $part) {
            $patterns[$slug] = [
                'match'   => array_map(fn ($p) => Product::normalizeTerm($p), $part['match']),
                'exclude' => array_map(fn ($p) => Product::normalizeTerm($p), $part['exclude']),
            ];
        }

        foreach ($rows as $row) {
            $title = Product::normalizeTitleForMatch($row->title);
            if ($title === '') {
                continue;
            }

            // چند املای مختلفِ یک خودرو به یک اسلاگ می‌رسند و باید جمع شوند
            $carSlug = seo_slug((string) $row->car_model, '');
            $carSlug = ($carSlug !== '' && isset($known[$carSlug])) ? $carSlug : null;

            foreach ($patterns as $slug => $pattern) {
                $hit = false;
                foreach ($pattern['match'] as $needle) {
                    if ($needle !== '' && str_contains($title, $needle)) {
                        $hit = true;
                        break;
                    }
                }
                if (! $hit) {
                    continue;
                }

                foreach ($pattern['exclude'] as $needle) {
                    if ($needle !== '' && str_contains($title, $needle)) {
                        $hit = false;
                        break;
                    }
                }
                if (! $hit) {
                    continue;
                }

                $parts[$slug]++;
                if ($carSlug !== null) {
                    $combos[$slug][$carSlug] = ($combos[$slug][$carSlug] ?? 0) + 1;
                }
            }
        }

        arsort($parts);

        return ['parts' => $parts, 'combos' => $combos];
    }

    public static function carCount(?string $partSlug, ?string $carSlug): int
    {
        return (int) (static::carCounts()[(string) $partSlug][(string) $carSlug] ?? 0);
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::COMBO_CACHE_KEY);
    }
}
