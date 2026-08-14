<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DiscountCode;
use App\Models\Order;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * ثبت و برداشتن کد تخفیف از مسیر واقعی HTTP.
 *
 * جدول‌های customers و orders مهاجرت ندارند (از دیتابیس قدیمی آمده‌اند)، پس
 * مثل CustomerAuthTest همین‌جا ساخته می‌شوند تا تست روی sqlite حافظه‌ای اجرا
 * شود و به دیتابیس واقعی دست نزند.
 */
class DiscountCheckoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('family')->nullable();
            $table->string('phone')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('address_id')->nullable();
            $table->unsignedBigInteger('shipping_method_id')->nullable();
            $table->integer('shipping_price')->default(0);
            $table->integer('total_price')->default(0);
            $table->integer('final_price')->default(0);
            $table->unsignedBigInteger('discount_code_id')->nullable();
            $table->string('discount_code', 40)->nullable();
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->string('status', 20)->default('pending');
            $table->timestamps();
        });

        Schema::create('discount_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('title', 150)->nullable();
            $table->unsignedTinyInteger('percent');
            $table->unsignedBigInteger('max_discount')->nullable();
            $table->unsignedBigInteger('min_order_amount')->default(0);
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('per_customer_limit')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('discount_codes');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('customers');

        parent::tearDown();
    }

    private function customer(): Customer
    {
        return Customer::create(['name' => 'تست', 'family' => 'تستی', 'phone' => '09120000000']);
    }

    private function order(Customer $customer, int $subtotal = 500000, int $shipping = 30000): Order
    {
        return Order::create([
            'customer_id'    => $customer->id,
            'status'         => 'pending',
            'final_price'    => $subtotal,
            'shipping_price' => $shipping,
            'total_price'    => $subtotal + $shipping,
        ]);
    }

    /** «۲۰٪ تا سقف ۵۰٬۰۰۰ تومان» */
    private function code(array $overrides = []): DiscountCode
    {
        return DiscountCode::create(array_merge([
            'code'         => 'NOROOZ',
            'percent'      => 20,
            'max_discount' => 50000,
            'is_active'    => true,
        ], $overrides));
    }

    public function test_cap_is_enforced_through_the_endpoint(): void
    {
        $customer = $this->customer();
        // ۲۰٪ از ۵۰۰٬۰۰۰ = ۱۰۰٬۰۰۰ ولی سقف ۵۰٬۰۰۰ است
        $order = $this->order($customer, 500000, 30000);
        $this->code();

        $response = $this->actingAs($customer, 'customer')
            ->postJson('/order/discount/apply', ['order_id' => $order->id, 'code' => 'NOROOZ']);

        $response->assertOk()
            ->assertJson([
                'status'          => 'success',
                'discount_amount' => 50000,
                'discount_code'   => 'NOROOZ',
                // ۵۰۰٬۰۰۰ − ۵۰٬۰۰۰ + ۳۰٬۰۰۰ ارسال
                'total_price'     => 480000,
            ]);

        $this->assertSame(50000, (int) $order->fresh()->discount_amount);
    }

    public function test_code_is_matched_case_insensitively(): void
    {
        $customer = $this->customer();
        $order    = $this->order($customer, 100000, 0);
        $this->code();

        $this->actingAs($customer, 'customer')
            ->postJson('/order/discount/apply', ['order_id' => $order->id, 'code' => ' noRooz '])
            ->assertOk()
            ->assertJson(['discount_amount' => 20000, 'total_price' => 80000]);
    }

    public function test_unknown_code_is_rejected(): void
    {
        $customer = $this->customer();
        $order    = $this->order($customer);

        $this->actingAs($customer, 'customer')
            ->postJson('/order/discount/apply', ['order_id' => $order->id, 'code' => 'NOPE'])
            ->assertStatus(422)
            ->assertJson(['message' => 'کد تخفیف واردشده معتبر نیست.']);

        $this->assertSame(0, (int) $order->fresh()->discount_amount);
    }

    public function test_expired_code_is_rejected(): void
    {
        $customer = $this->customer();
        $order    = $this->order($customer);
        $this->code(['expires_at' => now()->subDay()]);

        $this->actingAs($customer, 'customer')
            ->postJson('/order/discount/apply', ['order_id' => $order->id, 'code' => 'NOROOZ'])
            ->assertStatus(422)
            ->assertJson(['message' => 'اعتبار این کد تخفیف به پایان رسیده است.']);
    }

    public function test_inactive_code_is_rejected(): void
    {
        $customer = $this->customer();
        $order    = $this->order($customer);
        $this->code(['is_active' => false]);

        $this->actingAs($customer, 'customer')
            ->postJson('/order/discount/apply', ['order_id' => $order->id, 'code' => 'NOROOZ'])
            ->assertStatus(422);
    }

    public function test_invoice_below_minimum_is_rejected(): void
    {
        $customer = $this->customer();
        $order    = $this->order($customer, 90000, 0);
        $this->code(['min_order_amount' => 100000]);

        $this->actingAs($customer, 'customer')
            ->postJson('/order/discount/apply', ['order_id' => $order->id, 'code' => 'NOROOZ'])
            ->assertStatus(422);

        $this->assertSame(0, (int) $order->fresh()->discount_amount);
    }

    public function test_per_customer_limit_blocks_a_second_order(): void
    {
        $customer = $this->customer();
        $code     = $this->code(['per_customer_limit' => 1]);

        // سفارش قبلیِ همین مشتری که با این کد ثبت شده است
        Order::create([
            'customer_id'      => $customer->id,
            'status'           => 'awaiting_call',
            'final_price'      => 500000,
            'shipping_price'   => 0,
            'total_price'      => 450000,
            'discount_code_id' => $code->id,
            'discount_code'    => 'NOROOZ',
            'discount_amount'  => 50000,
        ]);

        $order = $this->order($customer);

        $this->actingAs($customer, 'customer')
            ->postJson('/order/discount/apply', ['order_id' => $order->id, 'code' => 'NOROOZ'])
            ->assertStatus(422)
            ->assertJson(['message' => 'شما پیش از این از این کد تخفیف استفاده کرده‌اید.']);
    }

    public function test_abandoned_cart_does_not_consume_the_usage_limit(): void
    {
        $customer = $this->customer();
        $code     = $this->code(['usage_limit' => 1]);

        // سبد رهاشده (pending) نباید ظرفیت کد را بخورد
        Order::create([
            'customer_id'      => $customer->id,
            'status'           => 'pending',
            'final_price'      => 500000,
            'shipping_price'   => 0,
            'total_price'      => 450000,
            'discount_code_id' => $code->id,
            'discount_code'    => 'NOROOZ',
            'discount_amount'  => 50000,
        ]);

        $order = $this->order($customer);

        $this->actingAs($customer, 'customer')
            ->postJson('/order/discount/apply', ['order_id' => $order->id, 'code' => 'NOROOZ'])
            ->assertOk();
    }

    public function test_removing_the_code_restores_the_full_amount(): void
    {
        $customer = $this->customer();
        $order    = $this->order($customer, 500000, 30000);
        $this->code();

        $this->actingAs($customer, 'customer')
            ->postJson('/order/discount/apply', ['order_id' => $order->id, 'code' => 'NOROOZ'])
            ->assertOk();

        $this->actingAs($customer, 'customer')
            ->postJson('/order/discount/remove', ['order_id' => $order->id])
            ->assertOk()
            ->assertJson(['discount_amount' => 0, 'total_price' => 530000]);

        $fresh = $order->fresh();
        $this->assertNull($fresh->discount_code_id);
        $this->assertNull($fresh->discount_code);
        $this->assertSame(0, (int) $fresh->discount_amount);
    }

    public function test_a_customer_cannot_discount_someone_elses_order(): void
    {
        $owner     = $this->customer();
        $intruder  = Customer::create(['name' => 'مزاحم', 'phone' => '09120000001']);
        $order     = $this->order($owner);
        $this->code();

        $this->actingAs($intruder, 'customer')
            ->postJson('/order/discount/apply', ['order_id' => $order->id, 'code' => 'NOROOZ'])
            ->assertStatus(404);

        $this->assertSame(0, (int) $order->fresh()->discount_amount);
    }

    public function test_a_placed_order_can_no_longer_be_discounted(): void
    {
        $customer = $this->customer();
        $order    = $this->order($customer);
        $order->update(['status' => 'awaiting_call']);
        $this->code();

        $this->actingAs($customer, 'customer')
            ->postJson('/order/discount/apply', ['order_id' => $order->id, 'code' => 'NOROOZ'])
            ->assertStatus(404);
    }

    public function test_guest_cannot_apply_a_code(): void
    {
        $customer = $this->customer();
        $order    = $this->order($customer);
        $this->code();

        $this->postJson('/order/discount/apply', ['order_id' => $order->id, 'code' => 'NOROOZ'])
            ->assertStatus(401);
    }
}
