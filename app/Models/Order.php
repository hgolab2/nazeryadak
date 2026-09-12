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
        'discount_code_id',
        'discount_code',
        'discount_amount',
        'status',
        'paid_at',
        'bale_notified_at',
    ];

    protected $casts = [
        'paid_at'          => 'datetime',
        'bale_notified_at' => 'datetime',
    ];

    /* Relations */

    public function discountCode()
    {
        return $this->belongsTo(DiscountCode::class, 'discount_code_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /** هزینه‌هایی که بابت همین سفارش در دفتر مالی ثبت شده (پست، بسته‌بندی، ...). */
    public function expenses()
    {
        return $this->hasMany(FinanceTransaction::class)->where('type', FinanceTransaction::TYPE_EXPENSE);
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

    /* ------------------------------------------------------- کد تخفیف */

    /**
     * جمع اقلام فاکتور، پیش از تخفیف و بدون هزینه‌ی ارسال.
     *
     * final_price در این پروژه همین معنی را دارد (نه «مبلغ نهایی»)؛ نامش
     * قدیمی است و چون در کل پروژه و پنل جا افتاده عوض نشده، ولی هر جا
     * منظور «مبنای تخفیف» است این متد خوانده می‌شود تا ابهام نماند.
     */
    public function itemsSubtotal(): int
    {
        return (int) $this->final_price;
    }

    /** آیا روی این سفارش تخفیفی نشسته است؟ */
    public function hasDiscount(): bool
    {
        return (int) $this->discount_amount > 0;
    }

    /** مبلغ اقلام پس از کسر تخفیف، بدون هزینه‌ی ارسال. */
    public function payableItemsTotal(): int
    {
        return max(0, $this->itemsSubtotal() - (int) $this->discount_amount);
    }

    /**
     * total_price را از روی اقلام، تخفیف و ارسال از نو می‌نویسد.
     *
     * تنها جایی که مبلغ قابل پرداخت ساخته می‌شود همین است؛ هر جای دیگری
     * که «final_price + shipping_price» نوشته شود، تخفیف را جا می‌اندازد.
     */
    public function recalculateTotals(): void
    {
        $this->total_price = $this->payableItemsTotal() + (int) $this->shipping_price;
    }

    /**
     * تخفیف را با وضعیت فعلی سفارش هماهنگ می‌کند.
     *
     * لازم است چون سبد بین دو بازدید عوض می‌شود: کاربر کدِ «بالای ۵۰۰ هزار
     * تومان» را می‌گیرد، بعد نصف سبد را حذف می‌کند و برمی‌گردد. اگر مبلغ
     * دوباره حساب نشود، تخفیفِ فاکتور قبلی روی فاکتور کوچک‌تر می‌ماند. کد
     * منقضی‌شده یا غیرفعال‌شده هم همین‌جا برداشته می‌شود.
     *
     * جمع‌ها را هم به‌روز می‌کند ولی ذخیره نمی‌کند؛ ذخیره با فراخواننده است.
     *
     * @return string|null دلیل برداشته‌شدن کد، برای اطلاع به مشتری
     */
    public function syncDiscount(): ?string
    {
        if (! $this->discount_code_id) {
            $this->discount_code   = null;
            $this->discount_amount = 0;
            $this->recalculateTotals();

            return null;
        }

        $code   = $this->discountCode()->first();
        $reason = $code
            ? $code->reasonUnusableFor($this, $this->customer_id)
            : 'کد تخفیف این سفارش دیگر در دسترس نیست.';

        if ($reason) {
            $this->discount_code_id = null;
            $this->discount_code    = null;
            $this->discount_amount  = 0;
            $this->recalculateTotals();

            return $reason;
        }

        $this->discount_code   = $code->code;
        $this->discount_amount = $code->discountFor($this->itemsSubtotal());
        $this->recalculateTotals();

        return null;
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

    /**
     * وضعیت‌های مجاز سفارش و برچسب فارسی هرکدام.
     *
     * فهرست پیش از این سه جا تکرار شده بود (این متد، فیلتر لیست پنل و فرم
     * ویرایش) و از هم عقب افتاده بودند؛ فیلترِ لیست دو وضعیت را نداشت.
     */
    public const STATUSES = [
        'pending'       => 'در انتظار پرداخت',
        // سفارشی که بدون پرداخت آنلاین ثبت شده و منتظر تماس کارشناس است
        'awaiting_call' => 'در انتظار تماس کارشناس',
        'paid'          => 'پرداخت شده',
        'processing'    => 'در حال آماده‌سازی',
        'shipped'       => 'ارسال شده',
        'delivered'     => 'تحویل داده شده',
        'canceled'      => 'لغو شده',
        'returned'      => 'مرجوع شده',
        'failed'        => 'پرداخت ناموفق',
    ];

    public function status()
    {
        return self::STATUSES[$this->status] ?? 'نامشخص';
    }

    /** کلاس رنگ نشان وضعیت در پنل مدیریت. */
    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'paid', 'delivered'      => 'bg-success',
            'processing', 'shipped'  => 'bg-primary',
            'canceled', 'failed'     => 'bg-danger',
            'returned'               => 'bg-dark',
            default                  => 'bg-warning text-dark',
        };
    }


    /* ------------------------------------------------------- وضعیت‌ها */

    /**
     * وضعیت‌هایی که یعنی پول این سفارش گرفته شده و فروش به حساب می‌آید.
     * گزارش درآمد و سود فقط همین‌ها را می‌شمارد.
     */
    public const SETTLED_STATUSES = ['paid', 'processing', 'shipped', 'delivered'];

    /**
     * وضعیت‌هایی که هنوز کسی باید روی سفارش کاری بکند (تماس، آماده‌سازی،
     * ارسال). فهرست «سفارش‌های باز» بله و داشبورد از همین استفاده می‌کند.
     */
    public const OPEN_STATUSES = ['awaiting_call', 'paid', 'processing'];

    /**
     * از هر وضعیت، کدام وضعیت‌ها قدم بعدیِ طبیعی‌اند.
     *
     * دکمه‌های زیر پیام بله از همین ساخته می‌شوند تا مدیر با یک لمس، بدون
     * دیدن نُه گزینه، سفارش را جلو ببرد. پنل همچنان همه‌ی وضعیت‌ها را دارد.
     */
    public const NEXT_STATUSES = [
        'pending'       => ['paid', 'awaiting_call', 'canceled'],
        'awaiting_call' => ['paid', 'processing', 'canceled'],
        'failed'        => ['paid', 'awaiting_call', 'canceled'],
        'paid'          => ['processing', 'shipped', 'canceled'],
        'processing'    => ['shipped', 'canceled'],
        'shipped'       => ['delivered', 'returned'],
        'delivered'     => ['returned'],
        'canceled'      => ['awaiting_call', 'paid'],
        'returned'      => [],
    ];

    /** @return string[] */
    public function nextStatuses(): array
    {
        return self::NEXT_STATUSES[(string) $this->status] ?? [];
    }

    public function isSettled(): bool
    {
        return in_array((string) $this->status, self::SETTLED_STATUSES, true);
    }

    /** ایموجی هر وضعیت؛ در بله که رنگ نداریم، همین جای نشان رنگی را می‌گیرد. */
    public static function statusIcon(string $status): string
    {
        return match ($status) {
            'pending'       => '⏳',
            'awaiting_call' => '📞',
            'paid'          => '✅',
            'processing'    => '📦',
            'shipped'       => '🚚',
            'delivered'     => '🏁',
            'canceled'      => '❌',
            'returned'      => '↩️',
            'failed'        => '⚠️',
            default         => '•',
        };
    }

    /* ------------------------------------------------------- سود و زیان */

    /**
     * قیمت خرید کل اقلام (بهای تمام‌شده‌ی کالا).
     *
     * از unit_cost ثبت‌شده روی هر قلم خوانده می‌شود، نه از قیمت امروز محصول؛
     * سود سفارشِ ماه پیش نباید با تغییر قیمت این هفته عوض شود.
     */
    public function itemsCost(): int
    {
        $this->loadMissing('items');

        return (int) $this->items->sum(fn ($item) => $item->totalCost());
    }

    /** جمع هزینه‌های جانبی ثبت‌شده برای همین سفارش. */
    public function expensesTotal(): int
    {
        if ($this->relationLoaded('expenses')) {
            return (int) $this->expenses->sum('amount');
        }

        return (int) $this->expenses()->sum('amount');
    }

    /**
     * سود خالص: هرچه از مشتری گرفته‌ایم (اقلام − تخفیف + ارسال) منهای قیمت
     * خرید اقلام و هزینه‌های جانبی. هزینه‌ی ارسالی که از مشتری می‌گیریم
     * درآمد است و پولی که به پست می‌دهیم، هزینه‌ی سفارش؛ این دو یکی نیستند.
     */
    public function netProfit(): int
    {
        return (int) $this->total_price - $this->itemsCost() - $this->expensesTotal();
    }

    /** درصد سود نسبت به مبلغ دریافتی؛ صفر وقتی مبلغی در کار نیست. */
    public function profitMargin(): float
    {
        $total = (int) $this->total_price;

        return $total > 0 ? round($this->netProfit() * 100 / $total, 1) : 0.0;
    }

    /** آیا برای همه‌ی اقلام قیمت خرید داریم؟ اگر نه، سود فقط برآورد است. */
    public function hasCompleteCosts(): bool
    {
        $this->loadMissing('items');

        return $this->items->every(fn ($item) => $item->unit_cost !== null || (int) $item->unit_price === 0);
    }
}
