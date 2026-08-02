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
     * کاربری که ملک را به لیست علاقه مندی ها اضافه کرده است
     *
     * @return object
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
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
