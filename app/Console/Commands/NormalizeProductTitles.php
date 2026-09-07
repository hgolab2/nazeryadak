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

    /**
     * واژه‌های چسبیده‌ی فایل ایساکو → شکل جدا.
     *
     * در فایل مبدأ، فاصله‌ی بین اجزای نام قطعه جا افتاده است: «جلوراست»،
     * «درصندوق عقب»، «فیلترهوا». کاربر هیچ‌وقت این شکل را تایپ نمی‌کند —
     * جستجو «جلو راست» و «در صندوق عقب» است — و چون تطابق عبارت در گوگل
     * روی مرز کلمه انجام می‌شود، این عنوان‌ها برای کوئری واقعی امتیاز
     * نمی‌گیرند. همین رشته‌ها به H1، اسلاگ آدرس، عنوان متا و متن توضیحات
     * هم راه پیدا کرده‌اند.
     *
     * نقشه عمدا دستی و محدود است، نه یک واژه‌شکنِ عمومی: شکستنِ خودکار،
     * ترکیب‌های درستِ فارسی را هم قربانی می‌کند («دستگیره» → «دست گیره»).
     * جایگزینی زیررشته‌ای است تا دنباله‌ی طولانیِ ترکیب‌ها هم پوشش پیدا کند
     * («بالابرجلوراست» با دو قاعده به «بالابر جلو راست» می‌رسد)، پس ترتیب
     * اهمیت دارد: قاعده‌های طولانی‌تر باید پیش از کوتاه‌ترها بیایند.
     */
    private const SPACING_MAP = [
        // موقعیت — پرتکرارترین گروه
        'درجلوراست' => 'در جلو راست',
        'درجلوچپ'   => 'در جلو چپ',
        'درعقبراست' => 'در عقب راست',
        'درعقبچپ'   => 'در عقب چپ',
        'جلوراست'   => 'جلو راست',
        'جلوچپ'     => 'جلو چپ',
        'عقبراست'   => 'عقب راست',
        'عقبچپ'     => 'عقب چپ',
        'بغلراست'   => 'بغل راست',
        'بغلچپ'     => 'بغل چپ',
        'بالاراست'  => 'بالا راست',
        'بالاچپ'    => 'بالا چپ',

        // «در» به‌عنوان قطعه، نه حرف اضافه
        'درصندوق' => 'در صندوق',
        'درموتور' => 'در موتور',
        'درجعبه'  => 'در جعبه',
        'درباک'   => 'در باک',
        'دراتاق'  => 'در اتاق',
        'دراطاق'  => 'در اطاق',
        'دربازکن' => 'در بازکن',
        'درمحافظ' => 'در محافظ',
        'درجلو'   => 'در جلو',
        'درعقب'   => 'در عقب',

        // «زیر» و «رو»
        'زیرشیشه' => 'زیر شیشه',
        'زیرموتور' => 'زیر موتور',
        'زیرچراغ' => 'زیر چراغ',
        'زیرسپر'  => 'زیر سپر',
        'زیرسینی' => 'زیر سینی',
        'رودری'   => 'رو دری',

        // نام قطعه‌ها
        'بالابربرقی' => 'بالابر برقی',
        'بالابردرب'  => 'بالابر درب',
        'بالابرجلو'  => 'بالابر جلو',
        'بالابرعقب'  => 'بالابر عقب',
        'نوارلاستیکی' => 'نوار لاستیکی',
        'واشرلاستیکی' => 'واشر لاستیکی',
        'کمربندایمنی' => 'کمربند ایمنی',
        'سنسورهشدارموانع' => 'سنسور هشدار موانع',
        'کمپرسورکولر' => 'کمپرسور کولر',
        'منیفولدهوا'  => 'منیفولد هوا',
        'فیلترهوای'   => 'فیلتر هوای',
        'فیلترهوا'    => 'فیلتر هوا',
        'فیلترروغن'   => 'فیلتر روغن',
        'ترمزدیسکی'   => 'ترمز دیسکی',
        'ترمزدستی'    => 'ترمز دستی',
        'ترمزضدقفل'   => 'ترمز ضدقفل',
        'ترمزچرخ'     => 'ترمز چرخ',
        'کلیدشیشه'    => 'کلید شیشه',
        'گلگیرجلو'    => 'گلگیر جلو',
        'گلگیرعقب'    => 'گلگیر عقب',
        'سپرجلو'      => 'سپر جلو',
        'سپرعقب'      => 'سپر عقب',
        'فنرجلو'      => 'فنر جلو',
        'فنرعقب'      => 'فنر عقب',
        'گازکولر'     => 'گاز کولر',
        'ورودهوا'     => 'ورود هوا',

        // نام خودرو
        'دناپلاس' => 'دنا پلاس',

        'موتورراست' => 'موتور راست',
        'موتورچپ'   => 'موتور چپ',
        'کولرساندن' => 'کولر ساندن',
        'گیرسپر'    => 'گیر سپر',
        'جلوبدون'   => 'جلو بدون',
        'جلوداشبورد' => 'جلو داشبورد',
        'جلوخاکستری' => 'جلو خاکستری',
        'جلومشکی'   => 'جلو مشکی',
        'ترمزبیرونی' => 'ترمز بیرونی',
        'ترمزداخل'  => 'ترمز داخل',
        'ترمزجدید'  => 'ترمز جدید',
        'ترمزکامل'  => 'ترمز کامل',
        'ترمزسوم'   => 'ترمز سوم',
        'ترمزدرب'   => 'ترمز درب',
        'ترمزروی'   => 'ترمز روی',
        'ترمزجلو'   => 'ترمز جلو',
        'ترمزعقب'   => 'ترمز عقب',

        // حرف ربطِ چسبیده
        'وبرگشت' => 'و برگشت',
        'وراست'  => 'و راست',
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

        $metaDeduped = $this->option('skip-dedup') ? 0 : $this->dedupeMetaTitles($dry);

        $this->newLine();
        $this->table(['شرح', 'تعداد'], [
            ['عنوان اصلاح‌شده (حروف)', number_format($charFixed)],
            ['عنوان یکتاشده', number_format($deduped)],
            ['عنوان متای یکتاشده', number_format($metaDeduped)],
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

    /**
     * یکتاسازی «عنوان متا»، که با یکتا بودنِ ستون title یکی نیست.
     *
     * Product::autoSeoTitle عنوان را در ۶۰ کاراکتر جا می‌دهد و کد پایه‌ی
     * ایساکو را ته آن می‌گذارد. دو مشکل از همین‌جا درمی‌آید:
     *
     *  ۱. کد پایه بین ده‌ها قطعه مشترک است، پس دو نامِ بلندِ متفاوت که سر یک
     *     نقطه بریده می‌شوند، دقیقا یک عنوان متا می‌گیرند:
     *     «خرید مجموعه براکت دستگیره در بازکن عقب… کد 18169» برای هر دو.
     *  ۲. گام یکتاسازیِ بالا، مدل خودرو را به عنوانِ تکراری اضافه می‌کند؛
     *     autoSeoTitle هم همان مدل را به عنوانِ دیگرِ همان گروه می‌چسباند و
     *     باز هر دو یکی می‌شوند.
     *
     * اندازه‌گیری روی همین انبار: ۴۰۱ گروه، ۸۸۰ صفحه. صفحه‌هایی با عنوان
     * یکسان در نتایج گوگل با هم رقابت می‌کنند و معمولا فقط یکی‌شان می‌ماند.
     *
     * راه‌حل: برای اعضای گروه‌های متصادم، ستون seo_title (همان بازنویسیِ
     * دستیِ مدیر) با کد فنیِ کامل — که برخلاف کد پایه یکتاست — پر می‌شود.
     * بقیه‌ی محصولات دست‌نخورده می‌مانند و همچنان عنوان خودکار می‌گیرند؛
     * برای برگرداندن هم کافی است ستون seo_title خالی شود.
     */
    private function dedupeMetaTitles(bool $dry): int
    {
        $groups = [];

        Product::query()
            ->with('categories')
            ->select(['id', 'title', 'car_model', 'sku', 'isaco_code', 'seo_title', 'category_id'])
            ->chunk(500, function ($products) use (&$groups) {
                foreach ($products as $product) {
                    // بازنویسیِ دستیِ مدیر ملاک خودش است و دست نمی‌خورد.
                    if (trim((string) $product->seo_title) !== '') {
                        continue;
                    }
                    $groups[$product->autoSeoTitle()][] = $product;
                }
            });

        $groups = array_filter($groups, fn ($rows) => count($rows) > 1);

        if ($groups === []) {
            $this->info('عنوان متای تکراری‌ای پیدا نشد.');

            return 0;
        }

        $this->newLine();
        $this->line('یکتاسازی ' . number_format(count($groups)) . ' گروه عنوان متای تکراری...');

        $changed = 0;
        $samples = [];

        foreach ($groups as $rows) {
            foreach ($rows as $product) {
                $unique = $this->metaTitleWithSku($product);

                if ($unique === null) {
                    continue;
                }

                if (count($samples) < 5) {
                    $samples[] = [$product->autoSeoTitle(), $unique];
                }

                if (! $dry) {
                    DB::table('products')->where('id', $product->id)->update(['seo_title' => $unique]);
                }

                $changed++;
            }
        }

        if ($samples) {
            $this->newLine();
            $this->line('نمونه‌ی یکتاسازی عنوان متا:');
            $this->table(['قبل', 'بعد'], $samples);
        }

        return $changed;
    }

    /**
     * عنوان متا با کد فنیِ کامل به‌جای کد پایه.
     *
     * بودجه‌ی کاراکتر همان چیزی است که seo_title() اعمال می‌کند؛ اینجا از
     * قبل رعایت می‌شود تا برش، وسطِ کد فنی نیفتد — همان اشتباهی که
     * autoSeoTitle در سربرگش توضیحش داده است.
     */
    private function metaTitleWithSku(Product $product): ?string
    {
        $sku = trim((string) $product->sku);
        if ($sku === '') {
            return null;
        }

        $prefix = 'خرید ';
        $code   = ' کد ' . $sku;
        $budget = 60 - mb_strlen(seo_site_name()) - 3 - mb_strlen($prefix) - mb_strlen($code);

        $name = trim((string) $product->title);
        if ($budget < 8) {
            return null;
        }

        if (mb_strlen($name) > $budget) {
            $name = preg_replace('/[\s،,.\-&+]+$/u', '', mb_substr($name, 0, $budget - 1)) . '…';
        }

        return seo_title($prefix . $name . $code);
    }
    private function normalize(string $value): string
    {
        $value = strtr($value, self::CHAR_MAP);

        /*
        | جداسازی پس از یکسان‌سازی حروف انجام می‌شود، وگرنه «جلوراست» با «ي»
        | عربی از قاعده جا می‌ماند.
        |
        | strtr پس از هر جایگزینی از انتهای همان تکه ادامه می‌دهد، پس در
        | «سپرجلوچپ» فقط «سپرجلو» را می‌بیند و «چپ» چسبیده می‌ماند. تکرار تا
        | رسیدن به وضعیت پایدار این را حل می‌کند؛ سقف سه دور می‌گذاریم تا اگر
        | روزی دو قاعده یکدیگر را خنثی کردند، حلقه بی‌پایان نشود.
        */
        for ($pass = 0; $pass < 3; $pass++) {
            $next = strtr($value, self::SPACING_MAP);
            if ($next === $value) {
                break;
            }
            $value = $next;
        }

        $value = preg_replace('/[ \t]+/u', ' ', $value);

        return trim($value);
    }
}
