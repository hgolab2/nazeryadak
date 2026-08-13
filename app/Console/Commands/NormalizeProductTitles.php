<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * پاک‌سازی و یکتاسازی عنوان محصولات.
 *
 * دو کار انجام می‌دهد:
 *  ۱. حروف عربی (ك، ي، ة، …) و ارقام و فاصله‌های نامتعارف را به معادل
 *     فارسی تبدیل می‌کند. داده‌ها از فایل ایساکو وارد شده‌اند و با حروف
 *     عربی نوشته شده‌اند؛ نتیجه این بود که اسلاگِ آدرس فارسی بود ولی
 *     عنوان و H1 صفحه عربی — ناهماهنگی‌ای که هم برای کاربر بد است و هم
 *     تطابق عبارت جستجو را برای گوگل نامطمئن می‌کند.
 *  ۲. عنوان‌های تکراری را با افزودن مدل خودرو یا کد فنی یکتا می‌کند تا
 *     چند صفحه بر سر یک کوئری با هم رقابت نکنند (هم‌نوع‌خواری سئو).
 *
 * تغییر عنوان، اسلاگِ آدرس محصول را هم عوض می‌کند؛ آدرس قدیمی به‌صورت
 * خودکار با 301 به آدرس جدید هدایت می‌شود (ProductController::show).
 */
class NormalizeProductTitles extends Command
{
    protected $signature = 'products:normalize-titles
        {--dry-run : فقط گزارش تغییرات، بدون نوشتن در دیتابیس}
        {--skip-dedup : فقط اصلاح حروف، بدون یکتاسازی عنوان‌های تکراری}
        {--limit=0 : حداکثر تعداد رکورد برای پردازش (۰ یعنی همه)}';

    protected $description = 'اصلاح حروف عربی و یکتاسازی عنوان‌های تکراری محصولات';

    /** حروف عربی و نویسه‌های مشکل‌ساز → معادل استاندارد فارسی. */
    private const CHAR_MAP = [
        'ك' => 'ک',
        'ي' => 'ی',
        'ى' => 'ی',
        'ﻯ' => 'ی',
        'ئ' => 'ئ',
        'ة' => 'ه',
        'ۀ' => 'ه',
        'إ' => 'ا',
        'أ' => 'ا',
        'ٱ' => 'ا',
        'ؤ' => 'و',
        '٠' => '۰', '١' => '۱', '٢' => '۲', '٣' => '۳', '٤' => '۴',
        '٥' => '۵', '٦' => '۶', '٧' => '۷', '٨' => '۸', '٩' => '۹',
        "\u{0640}" => '',        // کشیده (ـ)
        "\u{200F}" => '',        // علامت راست‌به‌چپ
        "\u{200E}" => '',        // علامت چپ‌به‌راست
        "\u{00A0}" => ' ',       // فاصله‌ی سخت
        'ـ' => '',
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $this->info($dry ? '=== حالت آزمایشی: هیچ تغییری ذخیره نمی‌شود ===' : '=== اعمال تغییرات ===');

        $charFixed = $this->fixCharacters($dry, $limit);

        $deduped = 0;
        if (! $this->option('skip-dedup')) {
            $deduped = $this->deduplicate($dry);
        }

        $this->newLine();
        $this->table(['شرح', 'تعداد'], [
            ['عنوان اصلاح‌شده (حروف)', number_format($charFixed)],
            ['عنوان یکتاشده', number_format($deduped)],
        ]);

        if (! $dry) {
            $this->newLine();
            $this->warn('کش نقشه‌ی سایت را پاک کنید: php artisan cache:clear');
        }

        return self::SUCCESS;
    }

    /** تبدیل حروف عربی در عنوان، مدل خودرو و توضیح کوتاه. */
    private function fixCharacters(bool $dry, int $limit): int
    {
        $fixed = 0;
        $samples = [];

        $query = Product::query()->select(['id', 'title', 'car_model', 'short_description']);
        if ($limit > 0) {
            $query->limit($limit);
        }

        $query->chunkById(500, function ($products) use (&$fixed, &$samples, $dry) {
            foreach ($products as $product) {
                $updates = [];

                foreach (['title', 'car_model', 'short_description'] as $field) {
                    $original = (string) $product->{$field};
                    if ($original === '') {
                        continue;
                    }

                    $clean = $this->normalize($original);
                    if ($clean !== $original) {
                        $updates[$field] = $clean;
                    }
                }

                if (! $updates) {
                    continue;
                }

                if (count($samples) < 5 && isset($updates['title'])) {
                    $samples[] = [$product->title, $updates['title']];
                }

                if (! $dry) {
                    DB::table('products')->where('id', $product->id)->update($updates);
                }

                $fixed++;
            }
        });

        if ($samples) {
            $this->newLine();
            $this->line('نمونه‌ی تغییرات حروف:');
            $this->table(['قبل', 'بعد'], $samples);
        }

        return $fixed;
    }

    /**
     * یکتاسازی عنوان‌های تکراری.
     *
     * اولین محصول هر گروه عنوان خود را نگه می‌دارد (معمولا قدیمی‌ترین و
     * دارای بیشترین اعتبار در گوگل) و بقیه با مدل خودرو یا کد فنی متمایز
     * می‌شوند؛ اگر باز هم تکراری بماند، کد فنی هم اضافه می‌شود.
     */
    private function deduplicate(bool $dry): int
    {
        $duplicates = DB::table('products')
            ->select('title', DB::raw('COUNT(*) as total'))
            ->whereNotNull('title')
            ->where('title', '!=', '')
            ->groupBy('title')
            ->having('total', '>', 1)
            ->pluck('total', 'title');

        if ($duplicates->isEmpty()) {
            $this->info('عنوان تکراری‌ای باقی نمانده است.');
            return 0;
        }

        $this->newLine();
        $this->line('یکتاسازی ' . number_format($duplicates->count()) . ' گروه عنوان تکراری...');

        // عنوان‌هایی که همین حالا یکتا هستند تا تغییر جدید با آن‌ها برخورد نکند.
        $taken = DB::table('products')->pluck('title')->map(fn($t) => (string) $t)->flip();

        $changed = 0;
        $samples = [];

        foreach ($duplicates->keys() as $title) {
            $rows = DB::table('products')
                ->where('title', $title)
                ->orderBy('id')
                ->get(['id', 'title', 'car_model', 'sku', 'isaco_code']);

            // اولی دست‌نخورده می‌ماند.
            foreach ($rows->skip(1) as $row) {
                $candidate = $this->uniqueTitle($row, $title, $taken);
                if ($candidate === null) {
                    continue;
                }

                if (count($samples) < 5) {
                    $samples[] = [$title, $candidate];
                }

                if (! $dry) {
                    DB::table('products')->where('id', $row->id)->update(['title' => $candidate]);
                }

                $taken[$candidate] = true;
                $changed++;
            }
        }

        if ($samples) {
            $this->newLine();
            $this->line('نمونه‌ی یکتاسازی:');
            $this->table(['عنوان تکراری', 'عنوان جدید'], $samples);
        }

        return $changed;
    }

    /** ساخت عنوان یکتا با افزودن مدل خودرو، سپس کد فنی. */
    private function uniqueTitle(object $row, string $base, $taken): ?string
    {
        $suffixes = [];

        if (! empty($row->car_model)) {
            $carModel = $this->normalize((string) $row->car_model);
            // اگر مدل خودرو از قبل داخل عنوان هست، تکرارش کمکی نمی‌کند.
            if ($carModel !== '' && ! str_contains($base, $carModel)) {
                $suffixes[] = $carModel;
            }
        }

        if (! empty($row->sku)) {
            $suffixes[] = (string) $row->sku;
        } elseif (! empty($row->isaco_code)) {
            $suffixes[] = (string) $row->isaco_code;
        }

        $accumulated = $base;
        foreach ($suffixes as $suffix) {
            $accumulated = trim($accumulated . ' ' . $suffix);
            if (! isset($taken[$accumulated])) {
                return $accumulated;
            }
        }

        // آخرین راه‌حل: شناسه‌ی محصول، که همیشه یکتاست.
        $fallback = trim($base . ' ' . $row->id);

        return isset($taken[$fallback]) ? null : $fallback;
    }

    private function normalize(string $value): string
    {
        $value = strtr($value, self::CHAR_MAP);
        $value = preg_replace('/[ \t]+/u', ' ', $value);

        return trim($value);
    }
}
