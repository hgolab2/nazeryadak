<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    /**
     * دسته‌ی «شاسی و بدنه» (ProductCategory::CHASSIS_BODY). قیمت این قطعات
     * به کاربر نمایش داده نمی‌شود و به‌جای آن «لطفا تماس بگیرید» می‌آید،
     * چون قیمتشان بسته به رنگ، کیفیت و موجودی روز تعیین می‌شود.
     */
    public const CONTACT_PRICE_CATEGORY_ID = 3;

    /** کش درون‌درخواستی نتیجه‌ی isContactPrice برای محصولاتی که رابطه‌شان لود نشده. */
    private static array $contactPriceCache = [];

    protected $table = 'products';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'description',
        'short_description',
        'price',
        'regular_price',
        'compare_at_price',
        'discount_percent',
        'is_special_offer',
        'file_path',
        'weight',
        'is_active',
        'sku',
        'isaco_code',
        'isaco_url',
        'stock',
        'car_model',
        // سئوی دستی؛ خالی بودنشان یعنی مقدار خودکار صفحه‌ی محصول استفاده شود
        'seo_title',
        'seo_description',
        'focus_keyword',
        'canonical_url',
        'robots_index',
        'robots_follow',
    ];

    protected $casts = [
        'price'         => 'integer',
        'regular_price' => 'integer',
        'compare_at_price' => 'integer',
        'discount_percent' => 'integer',
        'is_special_offer' => 'boolean',
        'weight'        => 'integer',
        'is_active'     => 'boolean',
        'robots_index'  => 'boolean',
        'robots_follow' => 'boolean',
    ];

    /**
     */
    /**
     * حدود ۹۳٪ عنوان محصولات با «ي» و «ك» عربی وارد شده‌اند، در حالی که کاربر
     * «ی» و «ک» فارسی تایپ می‌کند. این نگاشت روی هر دو طرفِ مقایسه (عبارت
     * جستجو و ستون دیتابیس) اعمال می‌شود، پس هر دو شکل به یک صورت درمی‌آیند.
     */
    private const LETTER_MAP = [
        "\u{200C}" => ' ',  // نیم‌فاصله
        "\u{0640}" => '',   // کشیده
        "\u{064A}" => "\u{06CC}", // ي عربی → ی فارسی
        "\u{0649}" => "\u{06CC}", // ى مقصوره → ی فارسی
        "\u{0643}" => "\u{06A9}", // ك عربی → ک فارسی
        "\u{0629}" => "\u{0647}", // ة → ه
        "\u{0623}" => "\u{0627}", // أ → ا
        "\u{0625}" => "\u{0627}", // إ → ا
        "\u{0622}" => "\u{0627}", // آ → ا
        "\u{0624}" => "\u{0648}", // ؤ → و
    ];

    private const DIGIT_MAP = [
        'Û°' => '0', 'Û±' => '1', 'Û²' => '2', 'Û³' => '3', 'Û´' => '4',
        'Ûµ' => '5', 'Û¶' => '6', 'Û·' => '7', 'Û¸' => '8', 'Û¹' => '9',
        '-' => '', '_' => '', '/' => '', '.' => '', ' ' => '',
    ];

    public static function normalizeTerm(?string $value): string
    {
        $value = (string) $value;
        $value = strtr($value, self::LETTER_MAP);
        $value = strtr($value, [
            'Û°' => '0', 'Û±' => '1', 'Û²' => '2', 'Û³' => '3', 'Û´' => '4',
            'Ûµ' => '5', 'Û¶' => '6', 'Û·' => '7', 'Û¸' => '8', 'Û¹' => '9',
            "\u{200F}" => '', "\u{200E}" => '',
        ]);
        $value = preg_replace('/\s+/u', ' ', $value);

        return mb_strtolower(trim($value));
    }

    private static function normalizeCode(string $value): string
    {
        return strtr($value, self::DIGIT_MAP);
    }

    private static function normalizedColumn(string $column, array $map): string
    {
        $expr = "LOWER($column)";
        foreach ($map as $from => $to) {
            $expr = "REPLACE($expr, " . self::quote($from) . ', ' . self::quote($to) . ')';
        }

        return $expr;
    }

    private static function quote(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }

    /**
     */
    public function scopeSearchText($query, ?string $term)
    {
        $term = self::normalizeTerm($term);
        if ($term === '') {
            return $query;
        }

        // Normalize the search path without changing behavior.
        $code = self::normalizeCode($term);
        if ($code !== '' && preg_match('/^[0-9a-z]{4,}$/', $code)) {
            $skuExpr = self::normalizedColumn('sku', self::DIGIT_MAP);
            $query->where(function ($q) use ($skuExpr, $code, $term) {
                $q->whereRaw($skuExpr . ' LIKE ?', ['%' . $code . '%'])
                  ->orWhereRaw(self::normalizedColumn('title', self::LETTER_MAP) . ' LIKE ?', ['%' . $term . '%']);
            });

            return $query;
        }

        $titleExpr = self::normalizedColumn('title', self::LETTER_MAP);
        $carExpr   = self::normalizedColumn('car_model', self::LETTER_MAP);

        // Normalize the search path without changing behavior.
        foreach (explode(' ', $term) as $word) {
            if ($word === '') {
                continue;
            }
            $like = '%' . $word . '%';
            $query->where(function ($q) use ($titleExpr, $carExpr, $like) {
                $q->whereRaw($titleExpr . ' LIKE ?', [$like])
                  ->orWhereRaw($carExpr . ' LIKE ?', [$like]);
            });
        }

        return $query;
    }

    /** Search helper for normalized product text. */
    public function scopeSearchCarModel($query, ?string $term)
    {
        $term = self::normalizeTerm($term);
        if ($term === '') {
            return $query;
        }

        return $query->whereRaw(
            self::normalizedColumn('car_model', self::LETTER_MAP) . ' LIKE ?',
            ['%' . $term . '%']
        );
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderByDesc('is_primary')->orderBy('sort_order')->orderBy('id');
    }

    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', 1)->orderBy('sort_order');
    }

    public function categories()
    {
        return $this->hasMany(ProductInCategory::class, 'product_id');
    }

    public function reviews()
    {
        return $this->hasMany(ProductReview::class)->orderByDesc('id');
    }

    /** فقط نظرهای تأییدشده روی صفحه‌ی محصول و در اسکیما می‌آیند. */
    public function approvedReviews()
    {
        return $this->hasMany(ProductReview::class)
            ->where('status', ProductReview::STATUS_APPROVED)
            ->orderByDesc('id');
    }

    public function category()
    {
        return $this->categories();
    }

    public function url()
    {
        $slug = seo_slug(trim(($this->sku ? $this->sku . '-' : '') . $this->title), (string) $this->id);
        return '/product/' . $this->id . '/' . $slug;
    }

    /* --------------------------------------------------------------- سئو ---
     | عنوان و توضیحات متا تا پیش از این فقط داخل صفحه‌ی محصول ساخته می‌شد.
     | حالا مقدار خودکار اینجاست تا هم صفحه‌ی محصول و هم پیش‌نمایشِ فرم
     | مدیریت دقیقا یک متن را نشان بدهند، و مقدار دستیِ مدیر (در صورت وجود)
     | جای آن را بگیرد.
     */

    /**
     * عنوان پیش‌فرض: «خرید {نام قطعه} کد {کد ایساکو}».
     * کد فنی عمدا داخل عنوان می‌آید چون بخش بزرگی از جستجوها با همان کد است.
     */
    public function autoSeoTitle(): string
    {
        return seo_title('خرید ' . $this->title . ($this->isaco_code ? ' کد ' . $this->isaco_code : ''));
    }

    /**
     * توضیحات پیش‌فرض متا. توضیحات کوتاه‌تر از ۸۰ کاراکتر معمولا توسط گوگل
     * کنار گذاشته و با متن دلخواه جایگزین می‌شود؛ در آن حالت یک جمله‌ی کامل
     * از روی نام، کد فنی و مدل خودرو ساخته می‌شود.
     */
    public function autoSeoDescription(): string
    {
        $description = seo_description($this->description ?: ($this->short_description ?: ''));

        if (mb_strlen($description) >= 80) {
            return $description;
        }

        return seo_description(
            'خرید ' . $this->title
            . ($this->sku ? ' با کد فنی ' . $this->sku : '')
            . ($this->car_model ? ' مناسب ' . $this->car_model : '')
            . ' اصل و دارای ضمانت اصالت کالا، قیمت روز و ارسال سریع به سراسر ایران از فروشگاه ناظر یدک.'
        );
    }

    /**
     * پیش‌نویس توضیحات قطعه، از روی داده‌هایی که قبلا در دیتابیس هست.
     *
     * ۶۲٪ محصولات توضیحات ندارند و نوشتن دستی برای ۲۵۰۰ قطعه عملی نیست.
     * این متن جای نویسنده را نمی‌گیرد — یک نقطه‌ی شروع است که مدیر ویرایشش
     * می‌کند. عمدا از جمله‌بندی متنوع استفاده می‌شود تا هزاران صفحه با متن
     * کاملا یکسان تولید نشوند؛ متن تکراری در مقیاس، خودش مشکل سئویی است.
     */
    public function generateDescription(): string
    {
        $name = trim((string) $this->title);
        if ($name === '') {
            return '';
        }

        $car = trim((string) $this->car_model);
        $category = $this->categoryLabelForSeo();

        $paragraphs = [];

        // بند اول: معرفی، کد فنی و تناسب با خودرو
        $intro = $name . ($car ? ' مناسب ' . $car : '') . ' یکی از قطعات'
            . ($category ? ' گروه ' . $category : '') . ' موجود در فروشگاه ناظر یدک است.';
        if ($this->isaco_code) {
            $intro .= ' کد فنی این قطعه ' . $this->isaco_code . ' است؛ پیش از خرید، کد روی قطعه‌ی فعلی خودرو را با همین کد مقایسه کنید.';
        } elseif ($this->sku) {
            $intro .= ' کد انبار این قطعه ' . $this->sku . ' است.';
        }
        $paragraphs[] = $intro;

        // بند دوم: اصالت و ضمانت — متن بر اساس id چرخش می‌کند تا یکسان نشود
        $assurance = [
            'همه‌ی قطعات ناظر یدک با ضمانت اصالت کالا عرضه می‌شوند و در صورت مغایرت، امکان مرجوعی تا ۷ روز وجود دارد.',
            'این قطعه با تضمین اصالت ارائه می‌شود؛ در صورت ناسازگاری با خودرو، طبق شرایط بازگشت کالا قابل مرجوع کردن است.',
            'اصالت این کالا تضمین شده است و در صورت عدم تطابق با کد فنی سفارش، هزینه بازگردانده می‌شود.',
        ];
        $paragraphs[] = $assurance[$this->id % count($assurance)];

        // بند سوم: راهنمای خرید
        $guide = $car
            ? 'اگر از تناسب این قطعه با ' . $car . ' خود مطمئن نیستید، شماره‌ی شاسی یا کد فنی قطعه‌ی قبلی را برای کارشناسان ما بفرستید تا پیش از ثبت سفارش بررسی شود.'
            : 'اگر از تناسب این قطعه با خودروی خود مطمئن نیستید، کد فنی قطعه‌ی قبلی را برای کارشناسان ما بفرستید تا پیش از ثبت سفارش بررسی شود.';
        $paragraphs[] = $guide;

        return '<p>' . implode('</p><p>', $paragraphs) . '</p>';
    }

    /** برچسب دسته‌بندی برای متن‌های سئویی؛ از جدول واسط یا ستون محصول. */
    public function categoryLabelForSeo(): ?string
    {
        $categoryId = $this->category_id;

        if (! $categoryId && $this->relationLoaded('categories')) {
            $categoryId = optional($this->categories->firstWhere(
                fn ($row) => $row->category_id >= 1 && $row->category_id <= 11
            ))->category_id;
        }

        return $categoryId
            ? (\App\Enums\ProductCategory::tryFrom((int) $categoryId)?->label())
            : null;
    }

    public function seoTitle(): string
    {
        return $this->seo_title ?: $this->autoSeoTitle();
    }

    public function seoDescription(): string
    {
        return $this->seo_description
            ? seo_description($this->seo_description)
            : $this->autoSeoDescription();
    }

    /** آدرس کانونیکال دستی فقط وقتی استفاده می‌شود که مدیر صراحتا واردش کرده باشد. */
    public function seoCanonical(): string
    {
        return $this->canonical_url ?: seo_url($this->url());
    }

    public function seoRobotsTag(): string
    {
        return seo_robots_tag(
            (bool) ($this->robots_index ?? true),
            (bool) ($this->robots_follow ?? true)
        );
    }

    public function image()
    {
        $primary = $this->relationLoaded('images')
            ? $this->images->firstWhere('is_primary', true)
            : $this->primaryImage()->first();

        if ($primary) {
            return $primary->path;
        }

        if (empty($this->file_path)) {
            return '/images/no-image.svg';
        }

        if (str_starts_with($this->file_path, 'http')) {
            return $this->file_path;
        }

        return $this->file_path;
    }

    public function hasImage(): bool
    {
        return !empty($this->file_path) && $this->file_path !== '/images/no-image.svg';
    }

    public function favorites()
    {
        /*
        | ستون user_id در product_favorites شناسه‌ی مشتری است نه کاربر ادمین.
        | جدول users کلید اصلی‌اش user_id است و اصلاً به این رابطه ربطی ندارد؛
        | اگر به User وصل شود، join بی‌نتیجه می‌ماند و لیست علاقه‌مندی‌ها خالی درمی‌آید.
        */
        return $this->belongsToMany(Customer::class, 'product_favorites', 'product_id', 'user_id')->withPivot('pin')->withTimestamps();
    }

    /**
     * آیا این محصول از دسته‌ی «شاسی و بدنه» است و باید به‌جای قیمت،
     * «لطفا تماس بگیرید» نمایش داده شود؟
     *
     * اگر رابطه‌ی categories از قبل لود شده باشد (فهرست‌ها و صفحه‌ی اصلی این
     * کار را می‌کنند) هیچ کوئری‌ای نمی‌زند؛ در غیر این صورت نتیجه‌ی همان
     * محصول را برای بقیه‌ی درخواست کش می‌کند.
     */
    public function isContactPrice(): bool
    {
        if ($this->relationLoaded('categories')) {
            return $this->categories->contains(
                fn ($row) => (int) $row->category_id === self::CONTACT_PRICE_CATEGORY_ID
            );
        }

        $id = (int) $this->id;
        if ($id <= 0) {
            return false;
        }

        return self::$contactPriceCache[$id] ??= ProductInCategory::where('product_id', $id)
            ->where('category_id', self::CONTACT_PRICE_CATEGORY_ID)
            ->exists();
    }

    /**
     * قیمتی که در سبد و سفارش ثبت می‌شود؛ برای قطعات استعلامی صفر است تا
     * نه در جمع کل بیاید و نه از طریق API سبد به کاربر نشت کند.
     */
    public function sellablePrice(): int
    {
        return $this->isContactPrice() ? 0 : (int) $this->price;
    }

    /**
     * قیمتی که باید خط‌خورده نمایش داده شود؛ اگر تخفیفی در کار نباشد null است.
     * توجه: regular_price قیمت خرید است و هرگز نباید اینجا استفاده شود.
     */
    public function compareAtPrice(): ?int
    {
        $compare = (int) $this->compare_at_price;

        return $compare > 0 && $compare > (int) $this->price ? $compare : null;
    }

    public function discountPercent()
    {
        if ($this->discount_percent > 0) {
            return min(100, max(0, (int) $this->discount_percent));
        }

        $compare = $this->compareAtPrice();
        if ($compare === null) {
            return 0;
        }

        return round((($compare - (int) $this->price) / $compare) * 100, 2);
    }

    /**
     * درصد تخفیف را روی قیمت فروش فعلی اعمال می‌کند و قیمت پیش از تخفیف را در
     * compare_at_price نگه می‌دارد. اجرای چندباره تخفیف را روی هم انباشته
     * نمی‌کند، چون مبنا همیشه قیمت پیش از تخفیف است.
     */
    public function applyDiscountPercent(?int $percent): void
    {
        $percent = min(100, max(0, (int) $percent));
        $base = (int) ($this->compare_at_price ?: $this->price);

        $this->discount_percent = $percent;
        // با درصد صفر، تیک «پیشنهاد ویژه» دست‌نخورده می‌ماند تا ادمین بتواند
        // محصولی را بدون تخفیف هم ویژه نگه دارد.
        if ($percent > 0) {
            $this->is_special_offer = true;
        }

        if ($base <= 0) {
            return;
        }

        if ($percent > 0) {
            $this->compare_at_price = $base;
            $this->price = (int) round($base * (100 - $percent) / 100);

            return;
        }

        // برداشتن تخفیف: قیمت به مقدار پیش از تخفیف برمی‌گردد
        $this->price = $base;
        $this->compare_at_price = null;
    }
}

