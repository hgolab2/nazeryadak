<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'address_id',
        'shipping_method_id',
        'shipping_price',
        'total_price',
        'final_price',
        'status',
    ];

    /* Relations */

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function shippingMethod()
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function status()
    {
        switch ($this->status) {

            case 'pending':
                return 'در انتظار پرداخت';

            case 'paid':
                return 'پرداخت شده';

            case 'processing':
                return 'در حال آماده‌سازی';

            case 'shipped':
                return 'ارسال شده';

            case 'delivered':
                return 'تحویل داده شده';

            case 'canceled':
                return 'لغو شده';

            case 'returned':
                return 'مرجوع شده';

            case 'failed':
                return 'پرداخت ناموفق';

            default:
                return 'نامشخص';
        }
    }

}
