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
    ];

    protected $casts = [
        'price'         => 'integer',
        'regular_price' => 'integer',
        'compare_at_price' => 'integer',
        'discount_percent' => 'integer',
        'is_special_offer' => 'boolean',
        'weight'        => 'integer',
        'is_active'     => 'boolean',
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

    public function category()
    {
        return $this->categories();
    }

    public function url()
    {
        $slug = seo_slug(trim(($this->sku ? $this->sku . '-' : '') . $this->title), (string) $this->id);
        return '/product/' . $this->id . '/' . $slug;
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
        return $this->belongsToMany(User::class, 'product_favorites', 'product_id', 'user_id')->withPivot('pin')->withTimestamps();
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

