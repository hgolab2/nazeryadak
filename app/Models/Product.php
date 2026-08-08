<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $table = 'products';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'description',
        'price',
        'regular_price',
        'file_path',
        'weight',
        'is_active',
        'sku',
        'stock',
        'car_model',
    ];

    protected $casts = [
        'price'         => 'integer',
        'regular_price' => 'integer',
        'weight'        => 'integer',
        'is_active'     => 'boolean',
    ];

    /**
     * نگاشت حرف‌های عربی به معادل فارسی و ارقام به لاتین.
     *
     * ۹۳٪ عنوان محصولات با «ي» و «ك» عربی ذخیره شده‌اند، ولی کیبورد فارسی
     * «ی» و «ک» تولید می‌کند. بدون یکسان‌سازی، جستجوی «فیلتر» هیچ نتیجه‌ای
     * نمی‌داد در حالی که ۱۹۱ محصول موجود بود.
     */
    /** حرف‌های عربی → فارسی (فقط روی ستون‌های متنی اعمال می‌شود) */
    private const LETTER_MAP = [
        'ي' => 'ی', 'ك' => 'ک', 'ة' => 'ه', 'ۀ' => 'ه',
        'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
        'ؤ' => 'و', 'ئ' => 'ی',
        "\u{200C}" => ' ',  // نیم‌فاصله
        "\u{0640}" => '',   // کشیده
    ];

    /** ارقام فارسی/عربی → لاتین و حذف جداکننده‌ها (فقط روی کد فنی) */
    private const DIGIT_MAP = [
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '-' => '', '_' => '', '/' => '', '.' => '', ' ' => '',
    ];

    /** یکسان‌سازی عبارت ورودی کاربر (حرف‌ها؛ ارقام جداگانه) */
    public static function normalizeTerm(?string $value): string
    {
        $value = (string) $value;
        $value = strtr($value, self::LETTER_MAP);
        $value = strtr($value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            "\u{200F}" => '', "\u{200E}" => '',
        ]);
        $value = preg_replace('/\s+/u', ' ', $value);

        return mb_strtolower(trim($value));
    }

    /** شکل فشرده‌ی یک کد فنی برای مقایسه (بدون خط تیره و فاصله) */
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
     * جستجوی متنی روی نام قطعه، کد فنی و خودرو مناسب.
     * هر کلمه جداگانه بررسی می‌شود، پس ترتیب کلمات مهم نیست.
     */
    public function scopeSearchText($query, ?string $term)
    {
        $term = self::normalizeTerm($term);
        if ($term === '') {
            return $query;
        }

        // اگر کل عبارت یک کد فنی است، مستقیم روی sku جستجو کن — سریع‌تر و دقیق‌تر
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

        // هر کلمه باید پیدا شود، ولی ترتیبشان مهم نیست
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

    /** جستجو فقط روی خودرو مناسب */
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
        return '/product/' . $this->id . '/' . ($this->slug ?: Str::slug($this->title, '-'));
    }

    public function image()
    {
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

    public function discountPercent()
    {
        if (!$this->regular_price || $this->regular_price == 0) {
            return 0;
        }

        if ($this->price < $this->regular_price) {
            $percent = (($this->regular_price - $this->price) / $this->regular_price) * 100;
            return round($percent, 2);
        }

        return 0;
    }
}