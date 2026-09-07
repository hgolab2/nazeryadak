<?php

namespace Database\Seeders;

use App\Models\ProductReview;
use Database\Seeders\Support\ReviewVocabulary as V;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * داده‌ی نمایشیِ نظرات محصول — فقط برای محیط توسعه و دموی کارفرما.
 *
 * ⚠️ روی پایگاه‌داده‌ی واقعی اجرا نشود. این نظرها ساختگی‌اند و اگر روی سایت
 * زنده منتشر شوند، هم خریدار را گمراه می‌کنند و هم چون مستقیم وارد
 * aggregateRating می‌شوند، مصداق نقض سیاست structured data گوگل‌اند که
 * جریمه‌اش حذف rich result کل دامنه است. به همین دلیل عمدا در
 * DatabaseSeeder صدا زده نشده و باید دستی اجرا شود:
 *
 *     php artisan db:seed --class=ProductReviewSeeder
 *
 * محصولی که نظر دارد رد می‌شود، پس اجرای دوباره نظرها را دوبرابر نمی‌کند —
 * ولی محصولاتی که در اجرای قبل خالی مانده بودند (سهم COVERAGE) این بار
 * شانس دوباره می‌گیرند و پوشش را بالا می‌برد. برای ساختِ از نو:
 *
 *     TRUNCATE TABLE product_reviews;
 *     php artisan db:seed --class=ProductReviewSeeder
 *
 * ---
 *
 * ایده‌ی کلی: هیچ نظری از یک قالب ثابت پر نمی‌شود. هر نظر از ترکیب چند
 * «تکه»ی مستقل (مقدمه، تناسب، کیفیت، قیمت، ارسال، ایراد، سوال، جمع‌بندی)
 * ساخته می‌شود که تعداد، ترتیب و مخزنشان تصادفی است، و بعد یک لایه‌ی
 * «سبک نوشتاری» روی متن می‌نشیند — نیم‌فاصله، نقطه‌ی آخر، ایموجی، غلط
 * تایپی. دو نظر با محتوای یکسان هم با دو سبک متفاوت چاپ می‌شوند.
 */
class ProductReviewSeeder extends Seeder
{
    /**
     * سهم محصولاتی که دست‌کم یک نظر می‌گیرند.
     *
     * عمدا ۱ نیست: در هیچ فروشگاه واقعی‌ای تک‌تکِ ۴۱۷۲ قطعه — از لنت ترمز تا
     * لولای در موتور — نظر ندارند، و اگر همه داشته باشند اولین چیزی است که
     * به چشم می‌آید. اگر برای دمو لازم است هیچ صفحه‌ای خالی نباشد، این را
     * روی 1.0 بگذارید.
     */
    private const COVERAGE = 0.85;

    /** بازه‌ی تاریخ نظرها (ماه). */
    private const MONTHS_BACK = 16;

    /** اندازه‌ی هر insert دسته‌ای. */
    private const CHUNK = 500;

    /**
     * قطعه‌های مصرفی چند برابر بقیه نظر می‌گیرند.
     *
     * کسی که فیلتر روغن می‌خرد سالی چند بار برمی‌گردد و نظر می‌دهد؛ کسی که
     * یک بار در عمر خودرو «قاب زیرورویی فرمان» می‌خرد، نه. بدون این وزن،
     * توزیع نظرها بین قطعات یکنواخت و در نتیجه غیرواقعی می‌شود.
     */
    private const CONSUMABLES = ['لنت', 'فیلتر', 'شمع', 'تسمه', 'روغن', 'باتری', 'برف پاک', 'دیسک و صفحه', 'کلاچ'];

    public function run(): void
    {
        $done = DB::table('product_reviews')->distinct()->pluck('product_id')->flip();

        $products = DB::table('products')
            ->select('id', 'title', 'car_model')
            ->orderBy('id')
            ->get()
            ->reject(fn ($p) => $done->has($p->id));

        if ($products->isEmpty()) {
            $this->command?->warn('محصولی بدون نظر پیدا نشد؛ کاری انجام نشد.');

            return;
        }

        $bar = $this->command?->getOutput()->createProgressBar($products->count());
        $bar?->start();

        $buffer = [];
        $total  = 0;

        foreach ($products as $product) {
            foreach ($this->reviewsFor($product) as $row) {
                $buffer[] = $row;
                $total++;

                if (count($buffer) >= self::CHUNK) {
                    DB::table('product_reviews')->insert($buffer);
                    $buffer = [];
                }
            }

            $bar?->advance();
        }

        if ($buffer) {
            DB::table('product_reviews')->insert($buffer);
        }

        $bar?->finish();
        $this->command?->newLine(2);

        $this->refreshProductRatings();

        $this->command?->info(sprintf(
            '%s نظر برای %s محصول ساخته شد.',
            number_format($total),
            number_format($products->count())
        ));
    }

    /**
     * نظرهای یک محصول.
     *
     * «حال‌وهوا»ی محصول یک بار قرعه می‌خورد و توزیع ستاره‌ی همه‌ی نظرهای آن
     * محصول را جابه‌جا می‌کند. بدون این کار میانگین امتیازِ هر ۴۱۷۲ محصول
     * حول یک عدد می‌افتد و ستون rating_avg در فهرست محصولات یکنواخت
     * می‌شود؛ در واقعیت بعضی قطعات محبوب‌اند و بعضی دردسرساز.
     *
     * @return array<int,array<string,mixed>>
     */
    private function reviewsFor(object $product): array
    {
        if (mt_rand(1, 1000) > (int) round(self::COVERAGE * 1000)) {
            return [];
        }

        $title  = (string) $product->title;
        $domain = V::domainOf($title);
        $car    = trim((string) ($product->car_model ?? '')) ?: 'خودروم';
        $mood   = $this->weighted(['loved' => 45, 'good' => 33, 'mixed' => 17, 'poor' => 5]);

        /* جمله‌های مصرف‌شده‌ی همین محصول؛ تکرار عین یک جمله در دو نظرِ یک
           صفحه، سریع‌ترین راه لو رفتن داده‌ی ساختگی است. */
        $used = [];
        $rows = [];

        foreach (range(1, $this->reviewCount($title)) as $ignored) {
            $rows[] = $this->makeReview($product->id, $domain, $car, $mood, $used);
        }

        return $rows;
    }

    /** یک ردیف نظر. */
    private function makeReview(int $productId, string $domain, string $car, string $mood, array &$used): array
    {
        $rating = $this->rating($mood);
        $ask    = $this->chance(14);
        $band   = $rating >= 4 ? 'pos' : ($rating === 3 ? 'mid' : 'neg');

        /* سوال پیش از متن انتخاب می‌شود چون پاسخِ فروشگاه به همان سوال
           گره خورده است و نمی‌تواند بعدا مستقل قرعه بخورد. */
        $question = $ask ? $this->pickQuestion($domain, $used) : null;

        $comment = $this->stylize(
            $this->compose($domain, $band, $car, $question, $used),
            $this->persona()
        );

        $createdAt = $this->pastDate();
        $reply     = $this->reply($question, $rating, $car);

        /* نظرِ پاسخ‌داده‌شده حتما منتشرشده است: پاسخ گذاشتن زیر نظری که
           رد شده یا در صف تأیید مانده، در پنل بی‌معنی به نظر می‌رسد. */
        $status = $reply
            ? ProductReview::STATUS_APPROVED
            : $this->weighted([
                ProductReview::STATUS_APPROVED => 92,
                ProductReview::STATUS_PENDING  => 5,
                ProductReview::STATUS_REJECTED => 3,
            ]);

        return [
            'product_id'  => $productId,
            'customer_id' => null,
            'name'        => $this->name(),
            'rating'      => $rating,
            'criteria'    => ($c = $this->criteria($rating, $ask)) ? json_encode($c, JSON_UNESCAPED_UNICODE) : null,
            'title'       => $this->title($band, $ask, $car),
            'comment'     => $comment,
            'reply'       => $reply,
            'replied_at'  => $reply ? $this->replyDate($createdAt) : null,
            'status'      => $status,
            'is_buyer'    => $ask ? $this->chance(32) : $this->chance(62),
            'ip'          => $this->chance(72) ? $this->ip() : null,
            'created_at'  => $createdAt,
            'updated_at'  => $createdAt,
        ];
    }

    /* ------------------------------------------------------ ساخت متن نظر */

    /**
     * متن نظر را از چند تکه‌ی مستقل می‌سازد.
     *
     * تعداد تکه‌ها، نوعشان و ترتیبشان تصادفی است؛ خروجی از «عالی بود» تا یک
     * پاراگراف پنج‌جمله‌ای متغیر است. همین پراکندگیِ طول، بیش از خودِ
     * کلمه‌ها، متن را طبیعی نشان می‌دهد.
     */
    private function compose(string $domain, string $band, string $car, ?array $question, array &$used): string
    {
        /* سوال‌ها اغلب کوتاه‌اند و مستقل از تجربه‌ی خرید مطرح می‌شوند. */
        if ($question && $this->chance(55)) {
            return $this->join($this->fillAll($this->chance(45)
                ? [$this->pickUnused(V::OPENERS, $used), $question['q']]
                : [$question['q']], $car));
        }

        if (! $question && $this->chance(24)) {
            return $this->pickUnused(V::ONE_LINERS[$band], $used);
        }

        $beats = [];

        if ($this->chance(42)) {
            $beats[] = $this->fill($this->pickUnused(V::OPENERS, $used), $car);
        }

        foreach ($this->pickTypes($band) as $type) {
            $beats[] = $this->pickUnused($this->poolFor($type, $domain), $used);
        }

        if ($question) {
            $beats[] = $question['q'];
        } elseif ($this->chance($band === 'neg' ? 32 : 42)) {
            $beats[] = $this->pickUnused(V::CLOSERS[$band === 'neg' ? 'neg' : 'pos'], $used);
        }

        return $this->join($this->fillAll($beats, $car));
    }

    /**
     * انتخاب نوعِ تکه‌ها بر اساس سطح رضایت، بدون تکرار نوع.
     *
     * سهم کوچکی از نظرهای مثبت یک ایراد جزئی هم دارند («عالی بود فقط
     * ارسالش طول کشید») — نظرِ پنج‌ستاره‌ی بی‌عیب‌ونقص، تبلیغ به نظر می‌رسد.
     *
     * @return string[]
     */
    private function pickTypes(string $band): array
    {
        $weights = match ($band) {
            'pos' => ['fit' => 70, 'quality_pos' => 62, 'price_pos' => 34, 'ship_pos' => 30, 'durability' => 24, 'nitpick' => 14],
            'mid' => ['quality_mid' => 66, 'issue' => 52, 'fit' => 34, 'price_pos' => 22, 'price_neg' => 22, 'ship_pos' => 18],
            default => ['issue' => 80, 'quality_neg' => 58, 'price_neg' => 30, 'ship_neg' => 24, 'fit' => 12],
        };

        $count = min(
            $this->weighted([1 => 22, 2 => 34, 3 => 27, 4 => 17]),
            count($weights)
        );

        $picked = [];
        for ($i = 0; $i < $count; $i++) {
            $type = $this->weighted($weights);
            $picked[] = $type;
            unset($weights[$type]);
        }

        return $picked;
    }

    /** @return string[] */
    private function poolFor(string $type, string $domain): array
    {
        return match ($type) {
            'fit'         => V::merged(V::FIT, $domain),
            'issue'       => V::merged(V::ISSUES, $domain),
            'nitpick'     => V::NITPICKS,
            'quality_pos' => V::QUALITY['pos'],
            'quality_mid' => V::QUALITY['mid'],
            'quality_neg' => V::QUALITY['neg'],
            'price_pos'   => V::PRICE['pos'],
            'price_neg'   => V::PRICE['neg'],
            'ship_pos'    => V::SHIPPING['pos'],
            'ship_neg'    => V::SHIPPING['neg'],
            'durability'  => V::DURABILITY,
        };
    }

    /**
     * چسباندن تکه‌ها با علائم نگارشیِ متفاوت.
     *
     * بعد از تکه‌ای که با «؟» تمام شده نقطه گذاشته نمی‌شود؛ بقیه‌ی حالت‌ها
     * بین نقطه، ویرگول، فاصله‌ی خالی و خط جدید تقسیم می‌شوند — دقیقا همان
     * بی‌نظمی‌ای که در نظرهای واقعی هست.
     *
     * @param  string[]  $beats
     */
    private function join(array $beats): string
    {
        $out = '';

        foreach (array_values($beats) as $i => $beat) {
            if ($i > 0) {
                $last = mb_substr($out, -1);
                $out .= in_array($last, ['؟', '?', '!'], true)
                    ? ' '
                    : $this->weighted(['. ' => 48, '، ' => 22, ' ' => 14, ".\n" => 16]);
            }

            $out .= $beat;
        }

        return $out;
    }

    /* ---------------------------------------------------- سبک نوشتاری کاربر */

    /**
     * «عادت‌های تایپی» یک نویسنده.
     *
     * این لایه جداست چون مستقل از محتواست: همان جمله را یکی با نیم‌فاصله و
     * نقطه‌ی پایانی می‌نویسد و دیگری بدون هیچ‌کدام. یکدست‌بودنِ نگارش در یک
     * صفحه، واضح‌ترین نشانه‌ی تولید ماشینی است.
     *
     * @return array<string,mixed>
     */
    private function persona(): array
    {
        return [
            'zwnj'  => $this->weighted(['keep' => 45, 'space' => 38, 'strip' => 17]),
            'arabic' => $this->chance(9),
            'dot'   => $this->chance(46),
            'excl'  => $this->chance(12),
            'emoji' => $this->chance(9),
            'typo'  => $this->chance(11),
        ];
    }

    /** @param  array<string,mixed>  $p */
    private function stylize(string $text, array $p): string
    {
        if ($p['zwnj'] !== 'keep') {
            $text = str_replace("\u{200c}", $p['zwnj'] === 'space' ? ' ' : '', $text);
        }

        /* کیبورد عربی هنوز روی گوشی خیلی‌ها فعال است. */
        if ($p['arabic']) {
            $text = strtr($text, ['ی' => 'ي', 'ک' => 'ك']);
        }

        if ($p['typo']) {
            $text = $this->typo($text);
        }

        $last = mb_substr($text, -1);
        if (! in_array($last, ['؟', '?', '!', '.'], true)) {
            if ($p['excl']) {
                $text .= str_repeat('!', mt_rand(1, 2));
            } elseif ($p['dot']) {
                $text .= '.';
            }
        }

        if ($p['emoji']) {
            $text .= ' ' . $this->pick(['🙏', '👍', '🌹', '😊']);
        }

        return $text;
    }

    /**
     * یک غلط تایپی کوچک: جاافتادن فاصله، تکرار حرف یا جابه‌جایی دو حرف.
     *
     * فقط یک بار و در یک نقطه اعمال می‌شود؛ متنِ پر از غلط هم به اندازه‌ی
     * متنِ بی‌نقص مصنوعی به نظر می‌رسد.
     */
    private function typo(string $text): string
    {
        $chars = mb_str_split($text);
        $len   = count($chars);

        if ($len < 8) {
            return $text;
        }

        $i = mt_rand(2, $len - 3);

        switch (mt_rand(1, 3)) {
            case 1:
                $spaces = array_keys($chars, ' ', true);
                if ($spaces) {
                    unset($chars[$spaces[array_rand($spaces)]]);
                }
                break;
            case 2:
                $chars[$i] .= $chars[$i];
                break;
            default:
                [$chars[$i], $chars[$i + 1]] = [$chars[$i + 1], $chars[$i]];
        }

        return implode('', $chars);
    }

    /* ------------------------------------------------------------ فیلدها */

    /**
     * امتیاز، بر اساس حال‌وهوای محصول.
     *
     * توزیع‌ها عمدا J شکل‌اند (تجمع روی ۵ و یک دم کوچک روی ۱) چون توزیع
     * واقعی امتیاز در فروشگاه‌های آنلاین همین شکل است، نه زنگوله‌ای.
     */
    private function rating(string $mood): int
    {
        return $this->weighted(match ($mood) {
            'loved' => [5 => 62, 4 => 26, 3 => 8, 2 => 3, 1 => 1],
            'good'  => [5 => 42, 4 => 32, 3 => 15, 2 => 7, 1 => 4],
            'mixed' => [5 => 22, 4 => 25, 3 => 27, 2 => 16, 1 => 10],
            default => [5 => 8, 4 => 14, 3 => 22, 2 => 28, 1 => 28],
        });
    }

    private function reviewCount(string $title): int
    {
        $roll = mt_rand(1, 100);

        $count = match (true) {
            $roll <= 34 => 1,
            $roll <= 58 => 2,
            $roll <= 74 => 3,
            $roll <= 84 => 4,
            $roll <= 91 => mt_rand(5, 7),
            $roll <= 97 => mt_rand(8, 14),
            default     => mt_rand(15, 26),
        };

        foreach (self::CONSUMABLES as $keyword) {
            if (mb_strpos($title, $keyword) !== false) {
                return (int) ceil($count * 1.9);
            }
        }

        return $count;
    }

    private function name(): string
    {
        $first = $this->pick(V::FIRST_NAMES);
        $last  = $this->pick(V::LAST_NAMES);
        $roll  = mt_rand(1, 100);

        return match (true) {
            $roll <= 33 => $first,
            $roll <= 61 => $first . ' ' . $last,
            $roll <= 69 => mb_substr($first, 0, 1) . '. ' . $last,
            $roll <= 76 => $first . ' ' . mb_substr($last, 0, 1) . '.',
            $roll <= 82 => $first . mt_rand(60, 99),
            $roll <= 87 => $this->pick(V::NICKNAMES),
            $roll <= 92 => 'خانم ' . $last,
            $roll <= 96 => 'آقای ' . $last,
            default     => $first . ' (' . $this->pick(['تعمیرکار', 'مکانیک', 'راننده تاکسی', 'خریدار عمده']) . ')',
        };
    }

    private function title(string $band, bool $ask, string $car): ?string
    {
        if ($ask) {
            return $this->chance(35) ? $this->pick(V::TITLES['ask']) : null;
        }

        /* بیشتر کاربرها فیلد اختیاری عنوان را رد می‌کنند. */
        if (! $this->chance(38)) {
            return null;
        }

        return $this->fill($this->pick(V::TITLES[$band]), $car);
    }

    /**
     * ریزامتیازها.
     *
     * حول امتیاز کلی نوسان می‌کنند نه مستقل از آن؛ کاربری که ۵ داده معمولا
     * به همه‌ی معیارها ۴ و ۵ می‌دهد. همه‌ی معیارها هم پر نمی‌شوند چون فرم
     * اختیاری است و کاربر وسطش رها می‌کند.
     *
     * @return array<string,int>|null
     */
    private function criteria(int $rating, bool $ask): ?array
    {
        /* کسی که فقط سوال دارد، معمولا حوصله‌ی امتیاز تفکیکی ندارد. */
        if ($ask || ! $this->chance(46)) {
            return null;
        }

        $keys = array_keys(ProductReview::CRITERIA);
        shuffle($keys);

        $out = [];
        foreach (array_slice($keys, 0, $this->weighted([1 => 16, 2 => 24, 3 => 24, 4 => 36])) as $key) {
            $out[$key] = max(1, min(5, $rating + $this->weighted([-1 => 18, 0 => 58, 1 => 24])));
        }

        return $out;
    }

    /**
     * پاسخ فروشگاه.
     *
     * سوال تقریبا همیشه جواب می‌گیرد، شکایت اغلب، و تعریف به‌ندرت — همان
     * الگوی فروشنده‌ای که وقت محدودی برای پاسخ‌دادن دارد. اگر همه‌ی نظرها
     * پاسخ داشته باشند، بخش نظرات شبیه صفحه‌ی از پیش چیده‌شده می‌شود.
     *
     * @param  array{q:string,a:string}|null  $question
     */
    private function reply(?array $question, int $rating, string $car): ?string
    {
        $chance = match (true) {
            (bool) $question => 92,
            $rating <= 2     => 62,
            $rating === 3    => 26,
            default          => 10,
        };

        if (! $this->chance($chance)) {
            return null;
        }

        if ($question) {
            return $this->fill($question['a'], $car);
        }

        return $this->pick(V::REPLIES[match (true) {
            $rating <= 2  => 'complaint',
            $rating === 3 => 'neutral',
            default       => 'praise',
        }]);
    }

    /**
     * یک سوالِ به‌کارنرفته از مخزن عمومی و تخصصیِ همان نوع قطعه.
     *
     * @param  array<string,true>  $used
     * @return array{q:string,a:string}
     */
    private function pickQuestion(string $domain, array &$used): array
    {
        $pool = V::merged(V::QUESTIONS, $domain);

        for ($try = 0; $try < 8; $try++) {
            $choice = $pool[array_rand($pool)];
            if (! isset($used[$choice['q']])) {
                $used[$choice['q']] = true;

                return $choice;
            }
        }

        return $pool[array_rand($pool)];
    }

    /**
     * تاریخ نظر؛ متمایل به ماه‌های اخیر.
     *
     * توان ۱٫۷ روی عدد تصادفی، تراکم را به «امروز» نزدیک می‌کند — همان
     * الگویی که در فروشگاهِ روبه‌رشد دیده می‌شود و باعث می‌شود بخش نظرها
     * مرده به نظر نرسد.
     */
    private function pastDate(): Carbon
    {
        $days = (int) round(self::MONTHS_BACK * 30 * pow(mt_rand(0, 10000) / 10000, 1.7));

        return Carbon::now()
            ->subDays($days)
            ->subHours(mt_rand(0, 23))
            ->subMinutes(mt_rand(0, 59));
    }

    /** پاسخ فروشگاه چند ساعت تا چند روز بعدِ نظر ثبت می‌شود. */
    private function replyDate(Carbon $createdAt): Carbon
    {
        $repliedAt = $createdAt->copy()->addHours(mt_rand(1, 96))->addMinutes(mt_rand(0, 59));

        return $repliedAt->isFuture() ? Carbon::now()->subHours(mt_rand(1, 6)) : $repliedAt;
    }

    private function ip(): string
    {
        return mt_rand(2, 223) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(1, 254);
    }

    /* ------------------------------------------------- به‌روزرسانی میانگین‌ها */

    /**
     * rating_avg و rating_count همه‌ی محصولات، با دو کوئری.
     *
     * رویداد saved مدل این کار را تک‌تک انجام می‌دهد که برای هزاران ردیف
     * یعنی هزاران کوئری؛ به همین دلیل درج به‌صورت Query Builder انجام شد و
     * جمع‌بندی یک بار در پایان.
     *
     * updated_at محصولات دست نمی‌خورد: تاریخ آخرین تغییر محصول در lastmod
     * نقشه‌ی سایت می‌آید و ثبت نظر نباید به گوگل بگوید مشخصات قطعه عوض شده.
     */
    private function refreshProductRatings(): void
    {
        $approved = ProductReview::STATUS_APPROVED;

        DB::statement("
            UPDATE products SET
                rating_count = (
                    SELECT COUNT(*) FROM product_reviews r
                    WHERE r.product_id = products.id AND r.status = ?
                ),
                rating_avg = (
                    SELECT ROUND(AVG(r.rating), 2) FROM product_reviews r
                    WHERE r.product_id = products.id AND r.status = ?
                )
        ", [$approved, $approved]);
    }

    /* ------------------------------------------------------------ ابزارها */

    /** @param  string[]  $pool */
    private function pick(array $pool): string
    {
        return $pool[array_rand($pool)];
    }

    /**
     * انتخاب جمله‌ای که در نظرهای قبلیِ همین محصول به کار نرفته است.
     *
     * @param  string[]  $pool
     * @param  array<string,true>  $used
     */
    private function pickUnused(array $pool, array &$used): string
    {
        for ($try = 0; $try < 8; $try++) {
            $choice = $this->pick($pool);
            if (! isset($used[$choice])) {
                $used[$choice] = true;

                return $choice;
            }
        }

        /* مخزن ته کشیده (محصولی با ۲۰ نظر)؛ تکرار بهتر از نظرِ خالی است. */
        return $this->pick($pool);
    }

    /** @param  array<array-key,int>  $weights */
    private function weighted(array $weights)
    {
        $roll = mt_rand(1, max(1, array_sum($weights)));

        foreach ($weights as $key => $weight) {
            $roll -= $weight;
            if ($roll <= 0) {
                return $key;
            }
        }

        return array_key_first($weights);
    }

    private function chance(int $percent): bool
    {
        return mt_rand(1, 100) <= $percent;
    }

    private function fill(string $text, string $car): string
    {
        return str_replace('{car}', $car, $text);
    }

    /**
     * @param  string[]  $beats
     * @return string[]
     */
    private function fillAll(array $beats, string $car): array
    {
        return array_map(fn (string $b) => $this->fill($b, $car), $beats);
    }
}
