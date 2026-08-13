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

    /**
     * آخرین پرداخت سفارش. یک سفارش می‌تواند چند تلاش پرداخت داشته باشد
     * (تلاش ناموفق درگاه، رسید ردشده و رسید جدید)، پس تازه‌ترین ملاک است.
     */
    public function payment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /** رسید دستی‌ای که مشتری فرستاده و مدیر هنوز بررسی‌اش نکرده است. */
    public function pendingReceipt()
    {
        // شرط باید داخل ofMany بیاید تا وارد ساب‌کوئریِ «آخرین رکورد» شود؛
        // اگر بیرون بنویسیم، اول آخرین پرداخت انتخاب می‌شود و بعد فیلتر
        return $this->hasOne(Payment::class)->ofMany(
            ['id' => 'max'],
            fn ($query) => $query->awaitingReview()
        );
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

    /**
     * مراحل صفحه‌ی «پیگیری سفارش»؛ ترتیب همان مسیری است که سفارش از ثبت تا
     * تحویل طی می‌کند و trackStep() می‌گوید سفارش روی کدام مرحله ایستاده.
     */
    public const TRACK_STEPS = [
        ['title' => 'ثبت سفارش',      'icon' => 'fa-file-alt'],
        ['title' => 'تأیید و پرداخت', 'icon' => 'fa-check-circle'],
        ['title' => 'آماده‌سازی',      'icon' => 'fa-boxes'],
        ['title' => 'ارسال',          'icon' => 'fa-truck'],
        ['title' => 'تحویل',          'icon' => 'fa-clipboard-check'],
    ];

    /** شماره‌ی مرحله‌ی فعلی (از ۱)؛ صفر یعنی سفارش از مسیر عادی خارج شده است. */
    public function trackStep(): int
    {
        return match ($this->status) {
            'pending', 'awaiting_call', 'failed' => 1,
            'paid'       => 2,
            'processing' => 3,
            'shipped'    => 4,
            'delivered'  => 5,
            default      => 0,
        };
    }

    /** سفارشی که دیگر در مسیر تحویل نیست و نوار مراحل برایش معنا ندارد. */
    public function isTrackingHalted(): bool
    {
        return in_array($this->status, ['canceled', 'returned'], true);
    }

    /** یک جمله توضیح برای مشتری که وضعیت فعلی را می‌بیند. */
    public function trackingNote(): string
    {
        return match ($this->status) {
            'pending'       => 'این سفارش هنوز پرداخت نشده است. تا زمانی که پرداخت انجام نشود، سفارش وارد مرحله‌ی آماده‌سازی نمی‌شود.',
            'awaiting_call' => 'سفارش ثبت شده و کارشناس ما برای تأیید نهایی و هماهنگی پرداخت با شما تماس می‌گیرد.',
            'paid'          => 'پرداخت شما تأیید شد و سفارش در نوبت آماده‌سازی قرار گرفت.',
            'processing'    => 'قطعات سفارش شما در انبار در حال آماده‌سازی و بسته‌بندی است.',
            'shipped'       => 'سفارش تحویل شرکت حمل شده است. کد رهگیری پستی از طریق پیامک برای شما ارسال می‌شود.',
            'delivered'     => 'سفارش تحویل داده شده است. اگر مشکلی در قطعات دیدید با کارشناس ما تماس بگیرید.',
            'canceled'      => 'این سفارش لغو شده است. در صورت پرداخت وجه، مبلغ طبق رویه‌ی درگاه بانکی برمی‌گردد.',
            'returned'      => 'این سفارش مرجوع شده است. برای پیگیری بازگشت وجه با کارشناس ما تماس بگیرید.',
            'failed'        => 'پرداخت این سفارش ناموفق بوده است. می‌توانید دوباره پرداخت را انجام دهید یا با کارشناس ما تماس بگیرید.',
            default         => '',
        };
    }

    /**
     * آیا مشتری می‌تواند برای این سفارش رسید پرداخت ثبت کند؟
     *
     * سفارشی که تسویه شده یا لغو/مرجوع شده دیگر رسید نمی‌خواهد؛ بقیه‌ی
     * وضعیت‌ها (در انتظار پرداخت، در انتظار تماس، پرداخت ناموفق) می‌توانند.
     */
    public function canReceiveReceipt(): bool
    {
        return ! in_array((string) $this->status, ['paid', 'shipped', 'delivered', 'canceled', 'returned'], true);
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
