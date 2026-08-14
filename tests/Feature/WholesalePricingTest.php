<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * قیمت‌گذاری عمده: آستانه‌ی تعداد، قیمت واحد، و اعمال‌شدنش در سبد.
 *
 * جدول‌های محصول مهاجرت کامل ندارند (از دیتابیس قدیمی آمده‌اند)، پس نسخه‌ی
 * کوچکی از آن‌ها روی sqlite حافظه‌ای ساخته می‌شود.
 */
class WholesalePricingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // کش‌های درون‌درخواستی بین تست‌های همین پروسه نشت می‌کنند
        forgetShippingSettings();
        Product::flushContactPriceCache();

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->integer('category_id')->nullable();
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->string('sku')->nullable();
            $table->string('file_path')->nullable();
            $table->integer('price')->default(0);
            $table->integer('regular_price')->default(0);
            $table->integer('compare_at_price')->nullable();
            $table->integer('discount_percent')->default(0);
            $table->boolean('is_special_offer')->default(false);
            $table->integer('wholesale_min_qty')->nullable();
            $table->integer('wholesale_price')->nullable();
            $table->boolean('wholesale_enabled')->default(true);
            $table->integer('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_in_category', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id');
            $table->integer('category_id');
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id');
            $table->string('path')->nullable();
            $table->string('alt')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
        });

        Schema::create('shipping_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key')->unique();
            $table->string('setting_value')->nullable();
        });

        // ۲۰۰٬۰۰۰٬۰۰۰ ریال = ۲۰٬۰۰۰٬۰۰۰ تومان
        DB::table('shipping_settings')->insert([
            ['setting_key' => 'national_free_threshold', 'setting_value' => '200000000'],
        ]);
    }

    protected function tearDown(): void
    {
        forgetShippingSettings();
        parent::tearDown();
    }

    private function product(array $attributes = []): Product
    {
        return Product::create($attributes + [
            'title'  => 'قطعه آزمایشی',
            'price'  => 1000000,
            'stock'  => 500,
            'is_active' => true,
        ]);
    }

    public function test_threshold_is_the_count_that_reaches_the_free_shipping_amount(): void
    {
        // ۲۰ میلیون ÷ ۱ میلیون = ۲۰
        $this->assertSame(20, $this->product(['price' => 1000000])->wholesaleMinQty());
    }

    public function test_expensive_product_falls_back_to_the_floor_of_six(): void
    {
        // ۲۰ میلیون ÷ ۱۰ میلیون = ۲ که زیر کف است
        $this->assertSame(6, $this->product(['price' => 10000000])->wholesaleMinQty());
    }

    public function test_wholesale_price_is_ten_percent_over_the_excel_price(): void
    {
        // قیمت سایت = قیمت اکسل × ۱.۲ → اکسل ۱٬۰۰۰٬۰۰۰ / قیمت عمده ۱٬۱۰۰٬۰۰۰
        $product = $this->product(['price' => 1200000]);

        $this->assertSame(1100000, $product->wholesalePrice());
    }

    public function test_manual_values_win_over_the_calculated_ones(): void
    {
        $product = $this->product([
            'price' => 1000000,
            'wholesale_min_qty' => 3,
            'wholesale_price'   => 800000,
        ]);

        $this->assertSame(3, $product->wholesaleMinQty());
        $this->assertSame(800000, $product->wholesalePrice());
        $this->assertSame(800000, $product->unitPriceFor(3));
        $this->assertSame(1000000, $product->unitPriceFor(2));
    }

    public function test_unit_price_switches_at_the_threshold(): void
    {
        $product = $this->product(['price' => 1000000]);
        $min = $product->wholesaleMinQty();

        $this->assertSame(1000000, $product->unitPriceFor($min - 1));
        $this->assertSame($product->wholesalePrice(), $product->unitPriceFor($min));
        $this->assertSame($product->wholesalePrice(), $product->unitPriceFor($min + 5));
    }

    /**
     * منطقِ آستانه دقیقا برای همین است: هر خرید عمده‌ای باید ارزشی برابر
     * آستانه‌ی ارسال رایگان داشته باشد، وگرنه وعده‌ی «پست رایگان» روی صفحه‌ی
     * محصول برای بعضی قیمت‌ها نادرست می‌شود.
     */
    public function test_every_wholesale_quantity_reaches_the_free_shipping_amount(): void
    {
        $threshold = getShippingRules()['national_free_threshold'];

        foreach ([120000, 500000, 999999, 1000000, 3000000, 3333333, 10000000] as $price) {
            $product = $this->product(['price' => $price]);
            $gross   = $price * $product->wholesaleMinQty();

            $this->assertGreaterThanOrEqual(
                $threshold,
                $gross,
                "قیمت {$price}: ارزش سفارش عمده به آستانه‌ی ارسال رایگان نمی‌رسد"
            );
        }
    }

    public function test_disabled_flag_turns_wholesale_off(): void
    {
        $product = $this->product(['wholesale_enabled' => false]);

        $this->assertFalse($product->hasWholesale());
        $this->assertSame(1000000, $product->unitPriceFor(100));
    }

    public function test_contact_price_product_has_no_wholesale(): void
    {
        $product = $this->product();
        DB::table('product_in_category')->insert([
            'product_id'  => $product->id,
            'category_id' => Product::CONTACT_PRICE_CATEGORY_ID,
        ]);

        $this->assertFalse($product->hasWholesale());
        $this->assertSame(0, $product->unitPriceFor(100));
    }

    public function test_retail_discount_better_than_wholesale_hides_wholesale(): void
    {
        // ۳۰٪ تخفیف خرده‌فروشی از ۸٪ عمده بهتر است؛ نمایش «قیمت عمده» گمراه‌کننده می‌شد
        $product = $this->product(['price' => 1000000]);
        $product->applyDiscountPercent(30);
        $product->save();

        $this->assertFalse($product->hasWholesale());
    }

    public function test_cart_applies_and_removes_the_wholesale_price(): void
    {
        $product = $this->product(['price' => 1000000]);
        $min = $product->wholesaleMinQty();

        $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => $min])
            ->assertOk()
            ->assertJson(['is_wholesale' => true]);

        $this->assertSame($product->wholesalePrice(), session('cart')[$product->id]['price']);

        // یک عدد کمتر: قیمت باید به تکی برگردد
        $this->post('/cart/decrease', ['id' => $product->id])
            ->assertOk()
            ->assertJson(['item_is_wholesale' => false, 'item_unit_price' => 1000000]);

        $this->assertSame(1000000, session('cart')[$product->id]['price']);

        // و با یک عدد بیشتر دوباره عمده شود
        $this->post('/cart/increase', ['id' => $product->id])
            ->assertOk()
            ->assertJson(['item_is_wholesale' => true, 'item_unit_price' => $product->wholesalePrice()]);
    }

    /**
     * قیمت عمده حدود ۸٪ زیر قیمت تکی است، پس مبلغ پرداختیِ یک سفارش عمده
     * کمی زیر آستانه می‌ماند. ارسال باید همچنان رایگان باشد، وگرنه همان
     * سفارشی که رویش «ارسال رایگان» نوشته بودیم هزینه‌ی پست می‌گیرد.
     */
    public function test_wholesale_order_gets_free_national_shipping(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('customer_id')->nullable();
            $table->integer('address_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id');
            $table->integer('product_id');
            $table->integer('quantity');
            $table->integer('unit_price');
            $table->integer('total_price');
            $table->timestamps();
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->integer('customer_id')->nullable();
            $table->integer('province_id')->nullable();
            $table->boolean('is_default')->default(true);
            $table->timestamps();
        });

        $product = $this->product(['price' => 1000000]);
        $qty     = $product->wholesaleMinQty();

        $address = \App\Models\CustomerAddress::create(['customer_id' => 1, 'province_id' => 99]);
        $order   = \App\Models\Order::create(['customer_id' => 1, 'address_id' => $address->id, 'status' => 'pending']);
        $order->items()->create([
            'product_id'  => $product->id,
            'quantity'    => $qty,
            'unit_price'  => $product->wholesalePrice(),
            'total_price' => $product->wholesalePrice() * $qty,
        ]);

        // مبلغ پرداختی زیر آستانه است ولی ارزش سفارش به آن می‌رسد
        $this->assertLessThan(
            getShippingRules()['national_free_threshold'],
            $product->wholesalePrice() * $qty
        );

        $this->assertSame('free', getShippingInfo($order->fresh())['type']);
    }

    /**
     * قطعه‌ی استعلامی مبلغ ندارد؛ نباید با قیمت فهرستش سفارش را به آستانه‌ی
     * ارسال رایگان برساند، وگرنه سفارشی با مبلغ پرداختی صفر پست رایگان می‌گرفت.
     */
    public function test_contact_price_item_does_not_earn_free_shipping(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('customer_id')->nullable();
            $table->integer('address_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->integer('order_id');
            $table->integer('product_id');
            $table->integer('quantity');
            $table->integer('unit_price');
            $table->integer('total_price');
            $table->timestamps();
        });

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->integer('customer_id')->nullable();
            $table->integer('province_id')->nullable();
            $table->boolean('is_default')->default(true);
            $table->timestamps();
        });

        $product = $this->product(['price' => 30000000]);
        DB::table('product_in_category')->insert([
            'product_id'  => $product->id,
            'category_id' => Product::CONTACT_PRICE_CATEGORY_ID,
        ]);

        $address = \App\Models\CustomerAddress::create(['customer_id' => 1, 'province_id' => 99]);
        $order   = \App\Models\Order::create(['customer_id' => 1, 'address_id' => $address->id, 'status' => 'pending']);
        $order->items()->create([
            'product_id'  => $product->id,
            'quantity'    => 1,
            'unit_price'  => 0,
            'total_price' => 0,
        ]);

        $this->assertNotSame('free', getShippingInfo($order->fresh())['type']);
    }

    public function test_cart_total_uses_the_wholesale_price(): void
    {
        $product = $this->product(['price' => 1000000]);
        $min = $product->wholesaleMinQty();

        $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => $min]);

        $this->get('/cart/data')
            ->assertOk()
            ->assertJson(['total' => number_format($product->wholesalePrice() * $min)]);
    }
}
