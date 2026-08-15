<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * یک نقطه از تاریخچه‌ی قیمت یک محصول.
 *
 * جدول فقط-افزودنی است؛ ردیف‌ها هیچ‌وقت ویرایش نمی‌شوند چون معنایشان
 * «قیمت در آن لحظه» است. فقط created_at دارد و updated_at ندارد.
 */
class ProductPriceHistory extends Model
{
    protected $table = 'product_price_history';

    public const UPDATED_AT = null;

    protected $fillable = [
        'product_id',
        'price',
        'source',
        'created_at',
    ];

    protected $casts = [
        'price'      => 'integer',
        'created_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
