<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        // قیمت خرید واحد در لحظه‌ی سفارش؛ مبنای محاسبه‌ی سود همین است
        'unit_cost',
        'total_price',
    ];

    /* Relations */

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** بهای تمام‌شده‌ی این قلم؛ بدون قیمت خرید، صفر (و سفارش «برآوردی» می‌شود). */
    public function totalCost(): int
    {
        return (int) $this->unit_cost * (int) $this->quantity;
    }

    /** فروش این قلم منهای بهای خریدش. */
    public function profit(): int
    {
        $sale = (int) ($this->total_price ?: (int) $this->unit_price * (int) $this->quantity);

        return $sale - $this->totalCost();
    }
}
