<?php

namespace Tests\Unit;

use App\Models\Product;
use PHPUnit\Framework\TestCase;

/**
 * صفحه‌ی فروشگاه وقتی تخفیفی در کار نبود، به‌جای price مقدار regular_price را
 * نشان می‌داد. چون در ۸۷۱۸ محصول از ۹۱۱۷ محصول، regular_price کمتر از price
 * است، قیمت‌های کاملا غلط نمایش داده می‌شد — مثلا «۱ تومان» برای کالای
 * ۸۰٬۰۰۰ تومانی.
 */
class ProductPriceTest extends TestCase
{
    private function product(int $price, ?int $regular): Product
    {
        $p = new Product();
        $p->price = $price;
        $p->regular_price = $regular;

        return $p;
    }

    public function test_no_discount_when_regular_price_is_lower_than_price(): void
    {
        // رایج‌ترین حالت در داده‌ی واقعی
        $this->assertSame(0, $this->product(80000, 1)->discountPercent());
        $this->assertSame(0, $this->product(1500000, 1167)->discountPercent());
        $this->assertSame(0, $this->product(300000000, 89993817)->discountPercent());
    }

    public function test_no_discount_when_prices_are_equal(): void
    {
        $this->assertSame(0, $this->product(1500000, 1500000)->discountPercent());
    }

    public function test_no_discount_when_regular_price_is_missing(): void
    {
        $this->assertSame(0, $this->product(50000, 0)->discountPercent());
        $this->assertSame(0, $this->product(50000, null)->discountPercent());
    }

    public function test_real_discount_is_calculated(): void
    {
        // ۵۰۰٬۰۰۰ از ۱٬۵۰۰٬۰۰۰ یعنی ۶۶.۶۷٪
        $this->assertEqualsWithDelta(66.67, $this->product(500000, 1500000)->discountPercent(), 0.01);
        $this->assertEqualsWithDelta(16.67, $this->product(1250000, 1500000)->discountPercent(), 0.01);
        $this->assertEqualsWithDelta(50.0,  $this->product(500000, 1000000)->discountPercent(), 0.01);
    }

    public function test_placeholder_is_used_when_no_image(): void
    {
        $p = new Product();
        $p->file_path = '';
        $this->assertSame('/images/no-image.svg', $p->image());
        $this->assertFalse($p->hasImage());

        $p->file_path = '/storage/products/1.jpg';
        $this->assertTrue($p->hasImage());
    }
}
