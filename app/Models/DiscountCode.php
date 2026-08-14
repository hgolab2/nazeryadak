<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * کد تخفیف درصدی با سقف مبلغ.
 *
 * قاعده‌ی محاسبه یک خط است: «درصدی از مبلغ اقلام، ولی حداکثر تا سقف».
 * هزینه‌ی ارسال هیچ‌وقت مشمول تخفیف نمی‌شود.
 *
 * همه‌ی شرط‌های اعتبار (فعال بودن، بازه‌ی زمانی، حداقل مبلغ، سقف دفعات)
 * در reasonUnusableFor() جمع شده‌اند تا فرم مشتری، صفحه‌ی پرداخت و لحظه‌ی
 * ثبت نهایی همگی دقیقا یک قضاوت داشته باشند؛ اگر هر کدام قاعده‌ی خودش را
 * داشت، کدِ منقضی‌شده می‌توانست بین دو مرحله از لای انگشت‌ها رد شود.
 */
class DiscountCode extends Model
{
    /**
     * وضعیت‌هایی که یعنی «سفارش واقعا ثبت شده و کد مصرف شده است».
     *
     * سفارش pending هنوز در سبد است و کاربر ممکن است رهایش کند؛ اگر آن را
     * مصرف حساب کنیم، ظرفیت یک کد صد نفره با صد سبد رهاشده تمام می‌شود.
     * سفارش مرجوعی اما مصرف حساب می‌شود: کد یک‌بار خرج شده است.
     */
    public const CONSUMED_ORDER_STATUSES = [
        'awaiting_call', 'paid', 'processing', 'shipped', 'delivered', 'returned',
    ];

    protected $table = 'discount_codes';

    protected $fillable = [
        'code', 'title', 'percent', 'max_discount', 'min_order_amount',
        'usage_limit', 'per_customer_limit', 'starts_at', 'expires_at', 'is_active',
    ];

    protected $casts = [
        'percent'            => 'integer',
        'max_discount'       => 'integer',
        'min_order_amount'   => 'integer',
        'usage_limit'        => 'integer',
        'per_customer_limit' => 'integer',
        'starts_at'          => 'datetime',
        'expires_at'         => 'datetime',
        'is_active'          => 'boolean',
    ];

    /* ------------------------------------------------------------ روابط */

    public function orders()
    {
        return $this->hasMany(Order::class, 'discount_code_id');
    }

    /* ------------------------------------------------------------ جست‌وجو */

    /**
     * یکسان‌سازی کدی که کاربر تایپ کرده است.
     *
     * کاربر ایرانی ممکن است کد را با کیبورد فارسی، با فاصله‌ی اضافه یا با
     * حروف کوچک بنویسد؛ بدون این نرمال‌سازی، «off ۱۰» با «OFF10» یکی
     * شناخته نمی‌شد و پشتیبانی باید دستی جواب می‌داد.
     */
    public static function normalizeCode($value): string
    {
        $value = toLatinDigits((string) $value);
        $value = preg_replace('/\s+/u', '', $value);
        $value = preg_replace('/[^A-Za-z0-9_\-]/u', '', (string) $value);

        return mb_strtoupper((string) $value);
    }

    public static function findByCode($value): ?self
    {
        $code = static::normalizeCode($value);

        return $code === '' ? null : static::where('code', $code)->first();
    }

    /* ------------------------------------------------------------ محاسبه */

    /**
     * مبلغ تخفیف روی یک جمعِ اقلام (تومان).
     *
     * floor به کار رفته تا تخفیف هیچ‌وقت یک تومان بیشتر از درصد اعلامی
     * نشود، و خروجی از خود جمع اقلام بالاتر نمی‌رود تا فاکتور منفی نشود.
     */
    public function discountFor(int $subtotal): int
    {
        if ($subtotal <= 0 || $this->percent <= 0) {
            return 0;
        }

        $discount = (int) floor($subtotal * $this->percent / 100);

        if ($this->max_discount !== null && $this->max_discount > 0) {
            $discount = min($discount, (int) $this->max_discount);
        }

        return max(0, min($discount, $subtotal));
    }

    /** آیا تخفیف این فاکتور به سقف کد خورده است؟ برای پیام «تا سقف ... تومان». */
    public function hitsCap(int $subtotal): bool
    {
        if ($this->max_discount === null || $this->max_discount <= 0) {
            return false;
        }

        return (int) floor($subtotal * $this->percent / 100) > (int) $this->max_discount;
    }

    /* ------------------------------------------------------------ اعتبار */

    /**
     * دلیل غیرقابل‌استفاده بودن کد، یا null اگر مشکلی نیست.
     *
     * متن خروجی مستقیم به مشتری نشان داده می‌شود، پس عمدا می‌گوید مشکل
     * دقیقا چیست (مثلا «برای خریدهای بالای ...») تا کاربر بداند چه کند.
     *
     * @param Order|null $order       سفارشی که کد رویش می‌نشیند
     * @param int|null   $customerId  برای سقف «هر مشتری چند بار»
     */
    public function reasonUnusableFor(?Order $order = null, $customerId = null): ?string
    {
        if (! $this->is_active) {
            return 'این کد تخفیف فعال نیست.';
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return 'زمان استفاده از این کد تخفیف هنوز نرسیده است.';
        }

        if ($this->expires_at && $now->gt($this->expires_at)) {
            return 'اعتبار این کد تخفیف به پایان رسیده است.';
        }

        $subtotal = $order ? $order->itemsSubtotal() : 0;

        if ($this->min_order_amount > 0 && $subtotal < $this->min_order_amount) {
            return 'این کد فقط برای خریدهای بالای '
                . toPersianNumbers(number_format($this->min_order_amount)) . ' تومان است.';
        }

        // سفارش جاری از شمارش کنار گذاشته می‌شود؛ وگرنه کدِ «یک‌بار مصرف»
        // بعد از ثبت سفارش، در همان صفحه‌ی پرداخت «تکمیل‌شده» اعلام می‌شد.
        $exceptOrderId = $order?->id;

        if ($this->usage_limit !== null && $this->usedCount($exceptOrderId) >= $this->usage_limit) {
            return 'ظرفیت استفاده از این کد تخفیف تکمیل شده است.';
        }

        if ($this->per_customer_limit !== null && $customerId
            && $this->usedCount($exceptOrderId, $customerId) >= $this->per_customer_limit) {
            return 'شما پیش از این از این کد تخفیف استفاده کرده‌اید.';
        }

        return null;
    }

    /** آیا کد بدون در نظر گرفتن سفارش خاصی، الان قابل استفاده است؟ (فهرست پنل) */
    public function isUsable(): bool
    {
        return $this->reasonUnusableFor() === null;
    }

    /**
     * تعداد سفارش‌هایی که این کد را واقعا مصرف کرده‌اند.
     *
     * @param int|null $exceptOrderId سفارشی که نباید شمرده شود (سفارش جاری)
     * @param int|null $customerId    محدود به یک مشتری
     */
    public function usedCount($exceptOrderId = null, $customerId = null): int
    {
        $query = Order::where('discount_code_id', $this->id)
            ->whereIn('status', self::CONSUMED_ORDER_STATUSES);

        if ($exceptOrderId) {
            $query->where('id', '!=', $exceptOrderId);
        }

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        return (int) $query->count();
    }

    /* ------------------------------------------------------------ نمایش */

    /** خلاصه‌ی قاعده‌ی کد برای فهرست پنل و پیام‌های مشتری. */
    public function ruleLabel(): string
    {
        $label = toPersianNumbers($this->percent, false) . '٪ تخفیف';

        if ($this->max_discount !== null && $this->max_discount > 0) {
            $label .= ' تا سقف ' . toPersianNumbers(number_format($this->max_discount)) . ' تومان';
        }

        return $label;
    }

    /** وضعیت خوانا برای فهرست پنل: فعال / غیرفعال / منقضی / تکمیل. */
    public function stateLabel(): string
    {
        if (! $this->is_active) {
            return 'غیرفعال';
        }

        if ($this->expires_at && now()->gt($this->expires_at)) {
            return 'منقضی';
        }

        if ($this->starts_at && now()->lt($this->starts_at)) {
            return 'زمان‌بندی‌شده';
        }

        if ($this->usage_limit !== null && $this->usedCount() >= $this->usage_limit) {
            return 'ظرفیت تکمیل';
        }

        return 'فعال';
    }
}
