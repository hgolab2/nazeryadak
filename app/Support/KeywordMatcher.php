<?php

namespace App\Support;

use App\Enums\ProductCategory;
use App\Models\Product;

/**
 * از یک عبارت جستجو (چه داخلی، چه گوگل) می‌گوید کدام صفحه‌ی سایت باید
 * هدفش باشد.
 *
 * منطقش همان قالب‌هایی است که جستجوی لوازم یدکی تقریبا همیشه یکی از آن‌هاست:
 *
 *   قطعه + خودرو     «لنت ترمز ۲۰۶»          → /part/لنت-ترمز/پژو-206
 *   قطعه             «لنت ترمز»              → /part/لنت-ترمز
 *   خودرو            «لوازم یدکی دنا»         → /car/دنا
 *   دسته + خودرو     «قطعات موتوری سمند»     → /car/سمند/موتوری
 *   کد فنی           «1233650000»            → /product/…
 *
 * تشخیص خودرو با «همه‌ی کلمات نام» یا «کلمه‌ی متمایز مدل» است؛ کاربر «۲۰۶»
 * می‌نویسد نه «پژو ۲۰۶»، و «پارس» می‌نویسد نه «پژو پارس».
 */
class KeywordMatcher
{
    /**
     * کلمه‌هایی که به‌تنهایی مدل خودرو نیستند؛ اگر نام خودرو فقط از این‌ها
     * ساخته شده باشد، فقط با تطبیق کامل شناخته می‌شود.
     */
    private const BRAND_WORDS = ['پژو', 'سایپا', 'ایران', 'خودرو', 'ایران خودرو', 'رنو', 'کیا', 'هیوندای', 'نیسان', 'مزدا', 'تویوتا', 'ام وی ام', 'چری', 'جک', 'لیفان', 'برلیانس', 'دانگ فنگ', 'هایما'];

    /**
     * @return array{url:string,label:string,type:string}|null
     */
    public static function match(string $phrase): ?array
    {
        $term = Product::normalizeTerm($phrase);
        if ($term === '') {
            return null;
        }

        /* کد فنی: فقط رقم و دست‌کم ۶ تا */
        if (preg_match('/^[0-9]{6,}$/', $term)) {
            $product = Product::where('is_active', 1)->where('sku', $term)->first();
            if ($product) {
                return ['url' => $product->url(), 'label' => 'محصول: ' . $product->title, 'type' => 'product'];
            }
        }

        $padded = ' ' . static::spaced($term) . ' ';

        $part = static::matchPart($padded);
        $car  = static::matchCar($padded);

        if ($part && $car) {
            $partCount = PartTypes::carCount($part['slug'], $car['slug']);
            if ($partCount > 0) {
                return [
                    'url'   => '/part/' . $part['slug'] . '/' . $car['slug'],
                    'label' => $part['name'] . ' ' . $car['name'],
                    'type'  => 'part_car',
                ];
            }
        }

        if ($part) {
            return [
                'url'   => '/part/' . $part['slug'],
                'label' => $part['name'],
                'type'  => 'part',
            ];
        }

        $category = static::matchCategory($padded);

        if ($category && $car) {
            return [
                'url'   => '/car/' . $car['slug'] . '/' . $category->slug(),
                'label' => $category->label() . ' ' . $car['name'],
                'type'  => 'car_category',
            ];
        }

        if ($car) {
            return [
                'url'   => '/car/' . $car['slug'],
                'label' => 'لوازم یدکی ' . $car['name'],
                'type'  => 'car',
            ];
        }

        if ($category) {
            return [
                'url'   => '/shop/' . $category->slug(),
                'label' => $category->label(),
                'type'  => 'category',
            ];
        }

        return null;
    }

    /**
     * طولانی‌ترین match برنده است: «دیسک ترمز» باید بر «دیسک» غلبه کند.
     *
     * @return array{slug:string,name:string}|null
     */
    private static function matchPart(string $padded): ?array
    {
        $best = null;
        $bestLen = 0;

        foreach (PartTypes::catalog() as $slug => $part) {
            foreach ($part['exclude'] ?? [] as $exclude) {
                if (mb_strpos($padded, static::spaced(Product::normalizeTerm($exclude))) !== false) {
                    continue 2;
                }
            }

            $needles = array_merge([$part['name']], $part['match'] ?? []);
            foreach ($needles as $needle) {
                $needle = static::spaced(Product::normalizeTerm($needle));
                if ($needle === '' || mb_strpos($padded, $needle) === false) {
                    continue;
                }
                if (mb_strlen($needle) > $bestLen) {
                    $bestLen = mb_strlen($needle);
                    $best = ['slug' => $slug, 'name' => $part['name']];
                }
            }
        }

        return $best;
    }

    /**
     * @return array{slug:string,name:string}|null
     */
    private static function matchCar(string $padded): ?array
    {
        $best = null;
        $bestLen = 0;

        foreach (CarModels::all() as $slug => $car) {
            $name   = static::spaced(Product::normalizeTerm($car['name']));
            $tokens = array_values(array_filter(explode(' ', $name)));
            if (! $tokens) {
                continue;
            }

            // تطبیق کامل نام
            if (mb_strpos($padded, ' ' . $name . ' ') !== false) {
                if (mb_strlen($name) > $bestLen) {
                    $bestLen = mb_strlen($name);
                    $best = ['slug' => $slug, 'name' => $car['name']];
                }
                continue;
            }

            // تطبیق با کلمه‌ی متمایز مدل («206»، «پارس»، «دنا»)
            $distinct = array_values(array_filter($tokens, fn ($t) => ! in_array($t, self::BRAND_WORDS, true) && mb_strlen($t) >= 2));
            if (count($distinct) === 0) {
                continue;
            }

            $all = true;
            foreach ($distinct as $token) {
                if (mb_strpos($padded, ' ' . $token . ' ') === false) {
                    $all = false;
                    break;
                }
            }
            if ($all) {
                $len = mb_strlen(implode(' ', $distinct));
                if ($len > $bestLen) {
                    $bestLen = $len;
                    $best = ['slug' => $slug, 'name' => $car['name']];
                }
            }
        }

        return $best;
    }

    private static function matchCategory(string $padded): ?ProductCategory
    {
        foreach (ProductCategory::cases() as $category) {
            $label = Product::normalizeTerm($category->label());
            if ($label !== '' && mb_strpos($padded, $label) !== false) {
                return $category;
            }

            // «قطعات موتوری» در برابر برچسب «موتور و اجزای متعلقه»؛ هر کلمه‌ی
            // سه‌حرفی به بالای برچسب که عبارت با آن شروع‌کلمه داشته باشد کافی
            // است («موتور» ← «موتوری»)، مگر کلمه‌های عمومی.
            // (trim بایتی است و حرف فارسی را می‌شکند؛ جداکننده‌ها با regex حذف می‌شوند)
            foreach (preg_split('/[\s،,]+/u', $label) as $word) {
                if (mb_strlen($word) >= 3 && ! in_array($word, ['قطعات', 'لوازم', 'سیستم', 'سایر', 'اجزای', 'متعلقه', 'خودرو'], true)
                    && mb_strpos($padded, ' ' . $word) !== false) {
                    return $category;
                }
            }
        }

        return null;
    }

    /**
     * فاصله بین حرف و رقم: «پژو207» → «پژو 207»، «s5» → «s 5».
     *
     * نام خودروها در دیتابیس با هر دو املا آمده و کاربر هم هر دو را می‌نویسد؛
     * بعد از این تبدیل، هر دو طرف یک شکل دارند و تطبیق کلمه‌ای کار می‌کند.
     */
    private static function spaced(string $text): string
    {
        $text = preg_replace('/(\p{L})(\p{N})/u', '$1 $2', $text);
        $text = preg_replace('/(\p{N})(\p{L})/u', '$1 $2', (string) $text);

        return trim(preg_replace('/\s+/u', ' ', (string) $text));
    }
}
