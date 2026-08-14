<?php

namespace Tests\Unit;

use App\Models\DiscountCode;
use App\Models\Order;
use PHPUnit\Framework\TestCase;

/**
 * قاعده‌ی کد تخفیف: «X درصد از مبلغ اقلام، ولی حداکثر تا سقف Y».
 *
 * سقف همان چیزی است که فروشگاه را از ضرر نگه می‌دارد؛ بدون آن، یک کد ۲۰
 * درصدی روی فاکتور ۵۰ میلیونی ده میلیون تومان تخفیف می‌داد. این تست‌ها
 * دقیقا همان مرز را می‌بندند.
 */
class DiscountCodeTest extends TestCase
{
    private function code(int $percent, ?int $cap): DiscountCode
    {
        $code = new DiscountCode();
        $code->percent      = $percent;
        $code->max_discount = $cap;

        return $code;
    }

    public function test_percent_applies_below_the_cap(): void
    {
        // ۲۰٪ از ۲۰۰ هزار = ۴۰ هزار؛ هنوز زیر سقف ۵۰ هزار است
        $code = $this->code(20, 50000);

        $this->assertSame(40000, $code->discountFor(200000));
        $this->assertFalse($code->hitsCap(200000));
    }

    public function test_cap_limits_the_discount_on_large_invoices(): void
    {
        // ۲۰٪ از یک میلیون = ۲۰۰ هزار، ولی بیشتر از سقف کم نمی‌شود
        $code = $this->code(20, 50000);

        $this->assertSame(50000, $code->discountFor(1000000));
        $this->assertTrue($code->hitsCap(1000000));

        // فاکتور پنجاه میلیونی هم دقیقا همان سقف را می‌گیرد
        $this->assertSame(50000, $code->discountFor(50000000));
    }

    public function test_exactly_on_the_cap_is_not_reported_as_capped(): void
    {
        // ۲۰٪ از ۲۵۰ هزار دقیقا ۵۰ هزار است؛ چیزی بریده نشده که به کاربر
        // بگوییم «به سقف خورد»
        $code = $this->code(20, 50000);

        $this->assertSame(50000, $code->discountFor(250000));
        $this->assertFalse($code->hitsCap(250000));
    }

    public function test_null_cap_means_unlimited(): void
    {
        $code = $this->code(20, null);

        $this->assertSame(200000, $code->discountFor(1000000));
        $this->assertFalse($code->hitsCap(1000000));
    }

    public function test_discount_never_exceeds_the_invoice(): void
    {
        // سقفِ بزرگ‌تر از خود فاکتور نباید جمع را منفی کند
        $code = $this->code(100, 9999999);

        $this->assertSame(120000, $code->discountFor(120000));
        $this->assertSame(0, $code->discountFor(0));
    }

    public function test_fraction_is_rounded_down(): void
    {
        // ۱۵٪ از ۱۰٬۰۰۱ = ۱۵۰۰.۱۵ ؛ تخفیف نباید یک تومان بیشتر از درصد اعلامی شود
        $this->assertSame(1500, $this->code(15, null)->discountFor(10001));
    }

    public function test_code_is_normalized_before_lookup(): void
    {
        // کاربر با کیبورد فارسی، فاصله و حروف کوچک تایپ می‌کند
        $this->assertSame('OFF-10', DiscountCode::normalizeCode(' off-۱۰ '));
        $this->assertSame('NOROOZ', DiscountCode::normalizeCode('nOrOoZ'));
        $this->assertSame('OFF10', DiscountCode::normalizeCode('off 10'));
        $this->assertSame('', DiscountCode::normalizeCode('؟؟؟'));
    }

    public function test_order_total_subtracts_discount_before_adding_shipping(): void
    {
        // تخفیف روی اقلام می‌نشیند، نه روی کرایه‌ی ارسال
        $order = new Order();
        $order->final_price     = 300000;
        $order->discount_amount = 50000;
        $order->shipping_price  = 20000;

        $order->recalculateTotals();

        $this->assertSame(250000, $order->payableItemsTotal());
        $this->assertSame(270000, $order->total_price);
        $this->assertTrue($order->hasDiscount());
    }

    public function test_order_total_without_discount_is_untouched(): void
    {
        $order = new Order();
        $order->final_price     = 300000;
        $order->discount_amount = 0;
        $order->shipping_price  = 20000;

        $order->recalculateTotals();

        $this->assertSame(320000, $order->total_price);
        $this->assertFalse($order->hasDiscount());
    }
}
