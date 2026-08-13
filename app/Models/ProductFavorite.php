<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductFavorite extends Model
{
    /**
     * لیست ملک های علاقه مند شده
     *
     * @return string
     */
    protected $table = 'product_favorites';
    protected $fillable = ['user_id', 'product_id', 'pin'];
    protected $hidden = ['created_at', 'updated_at'];

    /**
     * مشتری‌ای که محصول را به لیست علاقه‌مندی‌ها اضافه کرده است
     * (ستون user_id به جدول customers اشاره می‌کند، نه users)
     *
     * @return object
     */
    public function user()
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }

    /**
     * ملکی که به لیست علاقه مندی ها اضافه شده است
     *
     * @return object
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
