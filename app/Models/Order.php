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

    /**
     * آدرس تحویل سفارش. صفحه‌ی جزئیات سفارش تا پیش از این نام تحویل‌گیرنده و
     * تلفن را نداشت و به‌جایش شماره‌ی سفارش و یک شماره‌ی ثابت نمایش می‌داد.
     */
    public function address()
    {
        return $this->belongsTo(CustomerAddress::class, 'address_id');
    }

    /**
     * آیا سفارش قلمی از دسته‌ی «شاسی و بدنه» دارد؟ مبلغ این اقلام روی سایت
     * اعلام نمی‌شود و در جمع فاکتور نمی‌آید؛ کارشناس تلفنی اعلام می‌کند.
     */
    public function hasContactPriceItems(): bool
    {
        $this->loadMissing('items.product.categories');

        return $this->items->contains(fn ($item) => (bool) $item->product?->isContactPrice());
    }

    public function status()
    {
        switch ($this->status) {

            case 'pending':
                return 'در انتظار پرداخت';

            // سفارشی که بدون پرداخت آنلاین ثبت شده و منتظر تماس کارشناس است
            case 'awaiting_call':
                return 'در انتظار تماس کارشناس';

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
