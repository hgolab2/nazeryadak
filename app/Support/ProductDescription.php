<?php

namespace App\Support;

use App\Enums\ProductCategory;
use App\Models\Product;

/**
 * بازنویسی سئویی توضیحات صفحه‌ی محصول.
 *
 * دو مشکل، متن قبلیِ ۱۵۹۵ محصولِ توضیحات‌دار را برای گوگل بی‌ارزش کرده بود:
 *
 *   ۱) مقاله‌های ایساکو (۶۶۵ محصول) با products:sync-isaco-content و بر اساس
 *      «کد پایه» دانلود شده بودند، پس هر محصولی که کد پایه‌اش یکی بود متن
 *      یکسان می‌گرفت: ۶۵ صفحه با مقاله‌ی «سپر ماشین»، ۴۲ صفحه با «شیلنگ
 *      رادیاتور». صفحه‌ای که متن اصلی‌اش با ده‌ها صفحه‌ی دیگر مو‌به‌مو یکی
 *      است، محتوای تکراری حساب می‌شود و بیشترشان اصلا رتبه نمی‌گیرند.
 *   ۲) متن‌های تولیدشده‌ی قبلی (۹۳۰ محصول) یک قالب ثابت بودند که فقط نام و
 *      کد فنی را جای‌گذاری می‌کرد: حدود ۴۳۰ کاراکتر، بدون یک کلمه اطلاعاتِ
 *      تازه‌ای که در ستون‌های همان جدول نباشد. یعنی محتوای نازک.
 *
 * راهکار این کلاس: بخش عمده‌ی هر متن از داده‌ی خودِ محصول ساخته می‌شود (نام،
 * کد فنی، کد پایه، مدل خودرو، گروه قطعه) و دانشِ مشترکِ SeoContent فقط
 * به‌صورت زیرمجموعه‌ی چرخشی می‌آید، تا دو محصول از یک دسته هم متن یکسان
 * نگیرند. مقاله‌ی ایساکو حذف نمی‌شود؛ پایین‌تر از متن یکتا و با عنوانی یک
 * پله پایین‌تر (h3) نگه داشته می‌شود تا هم اطلاعاتش بماند و هم دیگر بدنه‌ی
 * اصلی صفحه نباشد.
 *
 * ترتیب بخش‌ها اتفاقی نیست: یکتاترین بخش‌ها (معرفی و مشخصات) اول می‌آیند،
 * چون همان ۱۵۸ کاراکتر اول است که autoSeoDescription به توضیحات متا تبدیل
 * می‌کند و ۳۰۰ کاراکتر اولش در اسکیمای Product می‌نشیند.
 */
class ProductDescription
{
    /**
     * امضای متنِ ساخته‌شده‌ی این کلاس.
     *
     * article() هر متنِ HTML‌داری را «مقاله‌ی ایساکو» فرض می‌کرد و نگهش
     * می‌داشت. از وقتی Product::generateDescription هم خروجی همین کلاس را
     * می‌نویسد، اجرای بعدیِ products:seo-describe روی همان محصول، متنِ
     * ساخته‌شده را به‌عنوان مقاله داخل متنِ تازه لانه می‌داد: کل صفحه دو بار،
     * یک‌بار با h2 و یک‌بار با h3. این ردیفِ ثابتِ جدولِ مشخصات در هر خروجی
     * هست و در هیچ مقاله‌ی ایساکویی نیست، پس تشخیص را قطعی می‌کند.
     */
    private const SIGNATURE = 'فروشنده: فروشگاه اینترنتی ناظر یدک';

    /** دانش دسته و خودرو یک‌بار ساخته می‌شود، نه به ازای هر محصول در حلقه. */
    private static ?array $categoryFacts = null;

    private static ?array $carFacts = null;

    private string $name;

    private string $sku;

    private string $isaco;

    private string $car;

    private string $carUrl = '';

    private ?array $carFact = null;

    private ?ProductCategory $case = null;

    private ?array $catFact = null;

    private string $catLabel = '';

    private string $catSlug = '';

    private string $article = '';

    private function __construct(private readonly Product $product, string $source)
    {
        $this->name  = trim((string) $product->title);
        $this->sku   = trim((string) $product->sku);
        $this->isaco = trim((string) $product->isaco_code);

        $car = trim((string) $product->car_model);
        // «نامشخص» یک مدل خودرو نیست؛ ایمپورت آن را جای خالی گذاشته است.
        $this->car = $car === 'نامشخص' ? '' : $car;

        if ($this->car !== '') {
            static::$carFacts ??= SeoContent::carFacts();
            $this->carFact = static::$carFacts[CarModels::slugFor($this->car)] ?? null;
            $this->carUrl  = car_landing_url($this->car);
        }

        $this->case = $product->categoryForSeo();

        if ($this->case) {
            $this->catLabel = $this->case->label();
            $this->catSlug  = $this->case->slug();
            static::$categoryFacts ??= SeoContent::categoryFacts();
            $this->catFact = static::$categoryFacts[$this->catSlug] ?? null;
        }

        $this->article = static::article($source);
    }

    /**
     * متن نهایی محصول.
     *
     * @param  string|null  $source  متن اصلیِ پیش از بازنویسی (description_source).
     *                               فقط برای بیرون کشیدن مقاله‌ی ایساکو به کار
     *                               می‌رود؛ اگر متن اصلی همان قالبِ تولیدشده‌ی
     *                               قدیمی بوده باشد، چیزی از آن نگه داشته نمی‌شود.
     */
    public static function build(Product $product, ?string $source = null): string
    {
        return (new self($product, (string) ($source ?? $product->description)))->render();
    }

    /**
     * بیرون کشیدن بخشِ ارزشمندِ متن اصلی.
     *
     * متن‌های قالبیِ قدیمی HTML نداشتند و چیزی جز نام و کد فنی نمی‌گفتند، پس
     * دور ریخته می‌شوند. مقاله‌های ایساکو HTML هستند و نگه داشته می‌شوند، ولی
     * عنوان‌هایشان از h2 به h3 می‌آید تا زیر عنوان‌های این کلاس بنشینند و
     * سلسله‌مراتب عنوان‌های صفحه نشکند.
     */
    public static function article(?string $source): string
    {
        $source = trim((string) $source);

        if ($source === '' || ! str_contains($source, '<')) {
            return '';
        }

        // خروجیِ خودِ این کلاس مقاله نیست؛ نگه‌داشتنش یعنی تکرار کل متن.
        if (static::isGenerated($source)) {
            return '';
        }

        $html = strip_tags($source, '<p><h2><h3><h4><ul><ol><li><strong><b><em><i><br><a>');
        $html = preg_replace('#<(/?)h2(\s[^>]*)?>#iu', '<$1h3>', $html);
        $html = preg_replace('#<p>(\s|&nbsp;)*</p>#iu', '', $html);

        return trim(preg_replace('/\s+/u', ' ', $html));
    }

    /**
     * توضیح کوتاهِ یک‌جمله‌ای برای کارت محصول و بالای صفحه.
     *
     * ۳۹۵۹ محصول این ستون را خالی داشتند و کارتشان در فهرست دسته و صفحه‌ی
     * فرود خودرو فقط نام و قیمت نشان می‌داد؛ صفحه‌ی فهرستی که چهل کارتِ
     * بی‌متن دارد، خودش محتوای نازک است. متن عمدا از description جدا ساخته
     * می‌شود، نه بریده‌ی آن، وگرنه همان جمله دو بار پشت سر هم در صفحه‌ی
     * محصول می‌آمد (یک‌بار در dk-short-desc و یک‌بار در ابتدای توضیحات).
     *
     * خروجی متنِ ساده است، نه HTML: هر دو مصرف‌کننده آن را داخل یک <p>
     * می‌گذارند و کارت محصول با Str::limit کوتاهش می‌کند.
     */
    public static function summary(Product $product): string
    {
        $self = new self($product, '');

        if ($self->name === '') {
            return '';
        }

        $for  = $self->car !== '' ? ' مناسب ' . $self->car : '';
        $code = $self->sku !== '' ? ' با کد فنی ' . $self->sku : '';
        $cat  = $self->catLabel !== '' ? ' از گروه ' . $self->catLabel : '';

        return $self->pick([
            $self->name . $for . $code . '؛ قطعه‌ی اصلی با ضمانت اصالت کالا، قیمت روز و ارسال سریع از انبار ناظر یدک.',
            $self->name . $code . $for . ' — عرضه‌ی مستقیم از ناظر یدک با تضمین اصالت، موجودی لحظه‌ای و ارسال به سراسر ایران.',
            'خرید ' . $self->name . $for . $cat . $code . '، اصل و دارای ضمانت اصالت کالا با ارسال سراسری.',
            $self->name . $for . ' با تضمین اصالت کالا در ناظر یدک؛ '
                . ($self->sku !== '' ? 'کد فنی ' . $self->sku . ' را پیش از خرید با قطعه‌ی فعلی خودرو مقایسه کنید.' : 'قیمت روز و ارسال سریع به سراسر ایران.'),
        ]);
    }
    /** واژه‌هایی که یک عبارت جستجو با آن‌ها تمام نمی‌شود. */
    private const KEYWORD_STOP_TAIL = ['و', 'با', 'یا', 'در', 'از', 'به', 'بدون', 'برای', 'طرح'];

    /**
     * کلیدواژه‌ی اصلی محصول: همان عبارتی که کاربر واقعا تایپ می‌کند.
     *
     * ستون focus_keyword برای هر ۴۶۲۴ محصول خالی بود، در حالی که صفحه‌ی
     * محصول آن را اولین قلم فهرست keywords می‌گذارد و فرم «سئوی محصول» در
     * پنل هم رویش تکیه دارد.
     *
     * عنوانِ خام به‌درد کلیدواژه نمی‌خورد: عنوان‌های این انبار مشخصات فنی،
     * پرانتز و گاهی کد فنی را هم دارند («ترموستات بادمای اسمی بازشدن: 83
     * درجه-موتور XU7JP4») و کسی این را جستجو نمی‌کند. پس تا اولین جداکننده
     * بریده می‌شود، پرانتز و کد حذف می‌شود، و حاصل به چند واژه‌ی اول محدود
     * می‌ماند — بعد مدل خودرو می‌آید، چون جستجوی واقعی «نام قطعه + خودرو»
     * است نه نام قطعه به‌تنهایی.
     */
    public static function focusKeyword(Product $product): string
    {
        $name = trim((string) $product->title);
        if ($name === '') {
            return '';
        }

        // پرانتزِ توضیحی، سپس هر چه بعد از اولین جداکننده‌ی مشخصات آمده.
        $name = preg_replace('/\([^)]*\)/u', ' ', $name);
        $name = preg_split('/[:،,؛\-–]/u', $name)[0];

        // کد فنی و کد پایه جای کلیدواژه نیستند؛ در عنوان متا و متن صفحه هستند.
        $name = preg_replace('/\b\d{5,}\b/u', ' ', $name);
        $name = trim(preg_replace('/\s+/u', ' ', $name));

        $words = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY);

        /*
        | واژه‌های به‌هم‌چسبیده‌ی فایل مبدأ («متالیک9995باضربه») کلیدواژه نیستند.
        | ملاک، رشته‌ی چهاررقمی یا بلندتر است تا نام موتور و مدل خودرو —
        | XU7، TU5، S5، 206، 405 — سالم بمانند.
        */
        $words = array_values(array_filter($words, fn ($w) => ! preg_match('/\d{4,}/u', $w)));
        $words = array_slice($words, 0, 5);

        while ($words !== [] && in_array(end($words), self::KEYWORD_STOP_TAIL, true)) {
            array_pop($words);
        }

        if ($words === []) {
            return '';
        }

        $car = trim((string) $product->car_model);
        if ($car === 'نامشخص') {
            $car = '';
        }

        /*
        | برشِ پنج‌واژه‌ای می‌تواند نام خودرو را از وسط نصف کند: عنوانِ
        | «… قرمز پژو پارس» به «… قرمز پژو» می‌رسید و افزودن مدل خودرو
        | «… قرمز پژو پژو پارس» می‌ساخت. واژه‌های انتهاییِ نصفه پیش از
        | چسباندن نام کامل حذف می‌شوند.
        */
        if ($car !== '') {
            $carWords = preg_split('/\s+/u', $car, -1, PREG_SPLIT_NO_EMPTY);
            while ($words !== [] && in_array(end($words), $carWords, true)) {
                array_pop($words);
            }
        }

        if ($words === []) {
            return '';
        }

        $keyword = implode(' ', $words);

        $flatten = fn (string $text) => preg_replace('/[\s\x{200C}]+/u', '', $text);

        if ($car !== '' && ! str_contains($flatten($keyword), $flatten($car))) {
            $keyword .= ' ' . $car;
        }

        return mb_substr($keyword, 0, 255);
    }
    /** آیا این متن، خروجیِ پیشینِ همین سازنده است؟ */
    public static function isGenerated(?string $html): bool
    {
        return $html !== null && str_contains($html, self::SIGNATURE);
    }

    private function render(): string
    {
        if ($this->name === '') {
            return '';
        }

        return static::html(array_merge(
            $this->lead(),
            $this->specs(),
            $this->articleBlock(),
            $this->symptoms(),
            $this->fitment(),
            $this->buyingGuide(),
            $this->faq(),
            $this->closing(),
        ));
    }

    /* ------------------------------------------------------------- بخش‌ها --- */

    /**
     * سه بند معرفی. جمله‌ی اول عمدا کوتاه و پرکلیدواژه است، چون همین جمله پس
     * از برش ۱۵۸ کاراکتری، توضیحات متای صفحه می‌شود.
     *
     * @return string[]
     */
    private function lead(): array
    {
        $name = $this->e($this->name);
        $for  = $this->car !== '' ? ' مناسب ' . $this->e($this->car) : '';
        $code = $this->sku !== '' ? ' با کد فنی ' . $this->e($this->sku) : '';

        $blocks = [static::p($this->pick([
            'خرید ' . $name . $for . $code . ' از فروشگاه ناظر یدک؛ قطعه‌ی اصلی با ضمانت اصالت کالا، قیمت روز و ارسال سریع به سراسر ایران.',
            $name . $for . $code . ' با تضمین اصالت کالا در ناظر یدک عرضه می‌شود؛ قیمت روز، موجودی لحظه‌ای و ارسال به همه‌ی شهرهای ایران.',
            'خرید ' . $name . $code . $for . ' با قیمت روز و ضمانت اصالت کالا از ناظر یدک؛ ارسال سراسری و امکان مرجوع کردن در صورت مغایرت کد فنی.',
        ]))];

        if ($this->sku !== '' && $this->isaco !== '' && $this->isaco !== $this->sku) {
            $codes = 'کد فنی این قطعه ' . $this->e($this->sku)
                . ' و کد پایه‌ی آن در کاتالوگ ایساکو ' . $this->e($this->isaco) . ' است. ';
        } elseif ($this->sku !== '') {
            $codes = 'کد فنی این قطعه ' . $this->e($this->sku) . ' است. ';
        } else {
            $codes = '';
        }

        $blocks[] = static::p($codes . $this->pick([
            'پیش از ثبت سفارش، این کد را با کد حک‌شده روی قطعه‌ی فعلی خودرو مقایسه کنید؛ یک قطعه در سال‌های تولید مختلفِ یک خودرو می‌تواند دو کد فنی متفاوت داشته باشد.',
            'کد فنی دقیق‌ترین راه انتخاب قطعه است؛ نام یک قطعه در بازار چند تلفظ دارد، اما کد فنی برای هر خودرو و هر تیپ یکتاست.',
            'اگر کد قطعه‌ی فعلی خودرو با این کد یکی نبود، پیش از خرید با کارشناسان ما تماس بگیرید؛ اختلاف یک رقم می‌تواند به معنای قطعه‌ای برای تیپ دیگری از همان خودرو باشد.',
        ], 1));

        if ($this->catLabel !== '') {
            $categoryLink = '<a href="/shop/' . rawurlencode($this->catSlug) . '">' . $this->e($this->catLabel) . '</a>';

            $blocks[] = static::p(
                $this->carFact
                    ? $name . ' در گروه ' . $categoryLink . ' جای می‌گیرد و در فهرست '
                        . '<a href="' . $this->e(car_landing_url($this->car, $this->catSlug)) . '">'
                        . $this->e($this->catLabel . ' ' . $this->car) . '</a> هم قابل مشاهده است.'
                    : $name . ' در گروه ' . $categoryLink . ' جای می‌گیرد.'
            );
        }

        return $blocks;
    }

    /**
     * فهرست مشخصات. ستون‌های هر محصول یکتا هستند، پس این بخش هیچ‌وقت بین دو
     * صفحه تکرار نمی‌شود — دقیقا همان چیزی که متن‌های قبلی کم داشتند.
     *
     * @return string[]
     */
    private function specs(): array
    {
        $rows = ['نام قطعه: ' . $this->e($this->name)];

        if ($this->sku !== '') {
            $rows[] = 'کد فنی: ' . $this->e($this->sku);
        }
        if ($this->isaco !== '' && $this->isaco !== $this->sku) {
            $rows[] = 'کد پایه در کاتالوگ ایساکو: ' . $this->e($this->isaco);
        }
        if ($this->catLabel !== '') {
            $rows[] = 'گروه قطعه: ' . $this->e($this->catLabel);
        }
        if ($this->car !== '') {
            $rows[] = 'خودروی سازگار: ' . $this->e($this->car);
        }

        $rows[] = 'اصالت: قطعه‌ی اصلی، همراه با ضمانت اصالت کالا';
        $rows[] = self::SIGNATURE;

        return [
            static::h2('مشخصات ' . $this->e($this->name)),
            static::ul($rows),
        ];
    }

    /** @return string[] */
    private function articleBlock(): array
    {
        if ($this->article === '') {
            return [];
        }

        return [
            static::h2($this->pick([
                $this->e($this->name) . ' چه کاری در خودرو انجام می‌دهد؟',
                'آشنایی بیشتر با ' . $this->e($this->name),
                'کارکرد و اهمیت ' . $this->e($this->name),
            ], 2)),
            $this->article,
        ];
    }

    /**
     * نشانه‌های خرابی و بازه‌ی تعویض. فهرست نشانه‌ها مشترکِ کل دسته است، پس
     * هر محصول فقط سه موردِ متفاوت از آن را می‌گیرد.
     *
     * @return string[]
     */
    private function symptoms(): array
    {
        if (! $this->catFact) {
            return [];
        }

        $name = $this->e($this->name);
        $on   = $this->car !== '' ? $this->e($this->car) : 'خودرو';

        $blocks = [
            static::h2('چه زمانی باید ' . $name . ' را بررسی یا تعویض کرد؟'),
            // فهرست نشانه‌ها مربوط به کل دسته است، نه فقط همین قطعه. بدون این
            // تصریح، صفحه‌ی «فیلتر هوا» زیر عنوان خودش نشانه‌ی خرابی ترمز را
            // فهرست می‌کرد و متن، غلط از آب درمی‌آمد.
            static::p('نشانه‌های زیر مربوط به کل گروه ' . $this->e($this->catLabel)
                . ' است. اگر یکی از آن‌ها را در ' . $on . ' می‌بینید، بررسی قطعات این گروه و در صورت ارتباط تعویض '
                . $name . ' را در اولویت بگذارید:'),
            static::ul($this->slice($this->catFact['symptoms'] ?? [], 3)),
        ];

        if (! empty($this->catFact['interval'])) {
            $blocks[] = static::p($this->pick([
                'درباره‌ی زمان تعویض قطعات این گروه: ',
                'برنامه‌ی تعویض قطعات هم‌گروهِ این محصول: ',
                'بازه‌ی تعویض در گروه ' . $this->e($this->catLabel) . ': ',
            ], 3) . $this->catFact['interval']);
        }

        return $blocks;
    }

    /**
     * تناسب با خودرو. اینجاست که متن دو محصولِ هم‌دسته از هم جدا می‌شود:
     * دانش خودرو در دانش دسته ضرب می‌شود و ترکیبشان تکرار نمی‌شود.
     *
     * @return string[]
     */
    private function fitment(): array
    {
        $name   = $this->e($this->name);
        $blocks = [static::h2($name . ' مناسب چه خودرویی است؟')];

        if (! $this->carFact) {
            $blocks[] = static::p(
                $name . ($this->car !== '' ? ' برای ' . $this->e($this->car) : '')
                . ' عرضه می‌شود. مطمئن‌ترین راه اطمینان از تناسب، مقایسه‌ی '
                . ($this->sku !== '' ? 'کد فنی ' . $this->e($this->sku) : 'کد فنی این قطعه')
                . ' با کد قطعه‌ی فعلی خودرو یا اعلام شماره‌ی شاسی به کارشناسان ماست.'
            );

            return $blocks;
        }

        $car = $this->e($this->car);

        $blocks[] = static::p(
            '<a href="' . $this->e($this->carUrl) . '">' . $car . '</a> محصول '
            . $this->carFact['brand'] . ' است، ' . $this->carFact['engine'] . ' دارد و '
            . $this->carFact['years'] . ' روی ' . $this->carFact['platform'] . ' تولید شده است. '
            . $name . ($this->sku !== '' ? ' با کد فنی ' . $this->e($this->sku) : '')
            . ' برای همین خودرو عرضه می‌شود.'
        );

        if (! empty($this->carFact['siblings'])) {
            $blocks[] = static::p(
                'بخشی از قطعات ' . $car . ' با ' . static::join($this->carFact['siblings'])
                . ' مشترک است، اما اشتراکِ پلتفرم به معنای یکی‌بودن کد فنی نیست؛ ملاک نهایی همان '
                . ($this->sku !== '' ? 'کد ' . $this->e($this->sku) . ' است' : 'کد فنی قطعه است') . '.'
            );
        }

        if (! empty($this->carFact['notes'])) {
            $blocks[] = static::p($this->pick($this->carFact['notes'], 4));
        }

        if (! empty($this->carFact['weak'])) {
            $blocks[] = static::p('در ' . $car . '، ' . $this->pick($this->carFact['weak'], 5)
                . ' زودتر از بقیه‌ی قطعات به تعویض می‌رسد؛ بررسی همزمان این موارد در مراجعه‌ی بعدی به تعمیرگاه، در هزینه و زمان صرفه‌جویی می‌کند.');
        }

        return $blocks;
    }

    /** @return string[] */
    private function buyingGuide(): array
    {
        $name = $this->e($this->name);

        $blocks = [
            static::h2('راهنمای خرید ' . $name),
            static::p($this->pick([
                'برای خرید ' . $name . ' سه چیز را پیش از پرداخت بررسی کنید: تطابق کد فنی، محل نصب روی خودرو و تعداد موردنیاز. تصویر صفحه بر اساس کد پایه‌ی کاتالوگ انتخاب شده و ممکن است با نسخه‌ی نهایی تفاوت جزئی داشته باشد.',
                'در انتخاب ' . $name . ' کد فنی را مبنا بگذارید نه نام قطعه؛ پس از آن محل نصب و تعداد لازم را بررسی کنید. در صورت تردید، پیش از ثبت سفارش با کارشناسان ما هماهنگ کنید.',
                'پیش از سفارش ' . $name . ' کد فنی، محل نصب و تعداد موردنیاز را کنترل کنید. اگر قطعه به‌صورت ست تعویض می‌شود، خرید تک‌قطعه در کنار قطعات فرسوده عمر مجموعه را کوتاه می‌کند.',
            ], 6)),
        ];

        if ($this->catFact && ! empty($this->catFact['tips'])) {
            $blocks[] = static::ul($this->slice($this->catFact['tips'], 2, 1));
        }

        return $blocks;
    }

    /**
     * پرسش‌های پرتکرار. هر پرسش نام و کد فنیِ همین محصول را دارد، پس این بخش
     * هم عبارت‌های دنباله‌بلند («قیمت فلان قطعه») را هدف می‌گیرد و هم بیشترین
     * سهم را در یکتاییِ متن دارد.
     *
     * @return string[]
     */
    private function faq(): array
    {
        $name   = $this->e($this->name);
        $blocks = [static::h2('پرسش‌های پرتکرار درباره ' . $name)];

        $blocks[] = static::h3('قیمت ' . $name . ' چقدر است؟');
        $blocks[] = static::p(
            $this->product->isContactPrice()
                ? 'قطعات گروه شاسی و بدنه قیمت ثابتی ندارند؛ نرخ آن‌ها بسته به رنگ، کیفیت و موجودی روز تعیین می‌شود. برای گرفتن قیمت '
                    . $name . ($this->sku !== '' ? ' با کد فنی ' . $this->e($this->sku) : '')
                    . ' با کارشناسان فروش تماس بگیرید.'
                : 'قیمت روزِ ' . $name . ' در جعبه‌ی خرید همین صفحه درج شده است و با تغییر نرخ بازار به‌روزرسانی می‌شود. نمودار تغییرات قیمت این محصول هم در همین صفحه در دسترس است.'
        );

        if ($this->sku !== '') {
            $blocks[] = static::h3('کد فنی ' . $name . ' چیست؟');
            $blocks[] = static::p(
                'کد فنی این قطعه ' . $this->e($this->sku) . ' است'
                . ($this->isaco !== '' && $this->isaco !== $this->sku
                    ? ' و کد پایه‌ی آن در کاتالوگ ایساکو ' . $this->e($this->isaco) . ' ثبت شده است'
                    : '')
                . '. همین کد را با کد حک‌شده روی قطعه‌ی فعلی خودرو مقایسه کنید تا از انتخاب درست مطمئن شوید.'
            );
        }

        if ($this->car !== '') {
            $blocks[] = static::h3($name . ' روی چه خودرویی نصب می‌شود؟');
            $blocks[] = static::p(
                'این قطعه برای ' . $this->e($this->car) . ' عرضه می‌شود'
                . ($this->carFact && ! empty($this->carFact['siblings'])
                    ? ' و بخشی از قطعات این خودرو با ' . static::join($this->carFact['siblings']) . ' مشترک است'
                    : '')
                . '. تطابق نهایی را با کد فنی بررسی کنید، چون یک خودرو در تیپ‌ها و سال‌های تولید مختلف می‌تواند کد قطعه‌ی متفاوتی داشته باشد.'
            );
        }

        $blocks[] = static::h3('آیا ' . $name . ' اصل است؟');
        $blocks[] = static::p(
            'بله. ' . $name . ' با ضمانت اصالت کالا عرضه می‌شود و در صورت مغایرت با کد فنیِ ثبت‌شده در سفارش، طبق شرایط بازگشت کالا تا ۷ روز قابل مرجوع کردن است.'
        );

        $blocks[] = static::h3('ارسال ' . $name . ' چگونه انجام می‌شود؟');
        $blocks[] = static::p(
            'سفارش پس از ثبت، از انبار ناظر یدک بسته‌بندی و به سراسر ایران ارسال می‌شود. روش و هزینه‌ی ارسال در مرحله‌ی تسویه‌حساب و بر اساس شهر مقصد محاسبه می‌گردد.'
        );

        return $blocks;
    }

    /** @return string[] */
    private function closing(): array
    {
        return [static::p(
            'اگر در انتخاب کد فنی تردید دارید یا قطعه‌ی موردنظرتان را پیدا نکردید، کارشناسان ناظر یدک از طریق '
            . '<a href="/contact-us">صفحه‌ی تماس با ما</a> راهنمایی‌تان می‌کنند.'
        )];
    }

    /* ------------------------------------------------------------ ابزارها --- */

    /**
     * انتخاب چرخشی از میان چند جمله‌ی هم‌معنا.
     *
     * شناسه‌ی محصول ثابت است، پس متنِ یک محصول بین دو اجرا عوض نمی‌شود؛ ولی
     * دو محصولِ کنار هم یک جمله نمی‌گیرند.
     *
     * @param  string[]  $options
     */
    private function pick(array $options, int $offset = 0): string
    {
        $options = array_values(array_filter($options));

        return $options === [] ? '' : $options[((int) $this->product->id + $offset) % count($options)];
    }

    /**
     * برشِ چرخشی از یک فهرست مشترک: هر محصول چند موردِ متفاوت از دانشِ دسته
     * را نشان می‌دهد تا کل فهرست، عینا در صدها صفحه تکرار نشود.
     *
     * @param  string[]  $items
     * @return string[]
     */
    private function slice(array $items, int $take, int $offset = 0): array
    {
        $items = array_values(array_filter($items));
        $count = count($items);

        if ($count === 0 || $take >= $count) {
            return $items;
        }

        $start = ((int) $this->product->id + $offset) % $count;
        $out   = [];

        for ($i = 0; $i < $take; $i++) {
            $out[] = $items[($start + $i) % $count];
        }

        return $out;
    }

    /** نام محصول از ایمپورت می‌آید و می‌تواند «&» داشته باشد؛ خام چاپ نشود. */
    private function e(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /** @param string[] $items */
    private static function join(array $items): string
    {
        $items = array_values(array_filter($items));

        if (count($items) <= 1) {
            return (string) ($items[0] ?? '');
        }

        $last = array_pop($items);

        return implode('، ', $items) . ' و ' . $last;
    }

    /** @param string[] $blocks */
    private static function html(array $blocks): string
    {
        return implode("\n", array_filter($blocks));
    }

    private static function p(string $text): string
    {
        return '<p>' . $text . '</p>';
    }

    private static function h2(string $text): string
    {
        return '<h2>' . $text . '</h2>';
    }

    private static function h3(string $text): string
    {
        return '<h3>' . $text . '</h3>';
    }

    /** @param string[] $items */
    private static function ul(array $items): string
    {
        return $items ? '<ul><li>' . implode('</li><li>', $items) . '</li></ul>' : '';
    }
}
