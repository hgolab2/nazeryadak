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
        'short_description',
        'price',
        'regular_price',
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
        'discount_percent' => 'integer',
        'is_special_offer' => 'boolean',
        'weight'        => 'integer',
        'is_active'     => 'boolean',
    ];

    /**
     */
    private const LETTER_MAP = [
        "\u{200C}" => ' ',
        "\u{0640}" => '',
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

    public function discountPercent()
    {
        if ($this->discount_percent > 0) {
            return min(100, max(0, (int) $this->discount_percent));
        }

        if (!$this->regular_price || $this->regular_price == 0) {
            return 0;
        }

        if ($this->price < $this->regular_price) {
            $percent = (($this->regular_price - $this->price) / $this->regular_price) * 100;
            return round($percent, 2);
        }

        return 0;
    }
    public function applyDiscountPercent(?int $percent): void
    {
        $percent = min(100, max(0, (int) $percent));
        $this->discount_percent = $percent;
        $this->is_special_offer = $percent > 0;

        if ($percent > 0 && $this->regular_price > 0) {
            $this->price = (int) round($this->regular_price * (100 - $percent) / 100);
        }
    }
}

