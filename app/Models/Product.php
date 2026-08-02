<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'price'        => 'integer',
        'regular_price'=> 'integer',
        'weight'       => 'integer',
        'is_active'    => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

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
        return '/product/'.$this->id;
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
        return $this->belongsToMany(User::class,'product_favorites','product_id','user_id')->withPivot('pin')->withTimestamps();
    }

    public function discountPercent()
    {
        if (!$this->regular_price || $this->regular_price == 0) {
            return 0;
        }

        // اگر قیمت کمتر از قیمت اصلی باشد، درصد تخفیف محاسبه می‌شود
        if ($this->price < $this->regular_price) {
            $percent = (($this->regular_price - $this->price) / $this->regular_price) * 100;
            return round($percent, 2); // گرد کردن تا دو رقم اعشار
        }

        return 0;
    }
}
