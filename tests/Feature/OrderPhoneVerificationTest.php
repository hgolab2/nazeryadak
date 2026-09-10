<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Services\OrderNotifier;
use App\Services\OtpService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * تأیید شماره با کدی که همراه پیامک ثبت سفارش می‌رود.
 *
 * جدول‌های customers و orders مهاجرت ندارند (از دیتابیس قدیمی آمده‌اند)، پس
 * مثل بقیه‌ی تست‌ها اینجا ساخته می‌شوند تا روی sqlite حافظه‌ای اجرا شود.
 */
class OrderPhoneVerificationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable()->unique();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->string('remember_token')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('address_id')->nullable();
            $table->unsignedBigInteger('shipping_method_id')->nullable();
            $table->unsignedBigInteger('discount_code_id')->nullable();
            $table->string('discount_code')->nullable();
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('shipping_price')->default(0);
            $table->unsignedBigInteger('total_price')->default(0);
            $table->unsignedBigInteger('final_price')->default(0);
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    private function makeOrder(?string $verifiedAt = null): Order
    {
        $customer = Customer::create([
            'phone'  => '09120000009',
            'status' => 1,
        ]);

        if ($verifiedAt) {
            $customer->forceFill(['phone_verified_at' => $verifiedAt])->save();
        }

        return Order::create([
            'customer_id' => $customer->id,
            'status'      => 'awaiting_call',
            'total_price' => 500000,
        ]);
    }

    /** متن پیامکی که برای این رویداد ساخته می‌شود. */
    private function smsFor(Order $order, string $event = 'order_placed'): string
    {
        $notifier = new OrderNotifier();
        $ref      = new \ReflectionClass($notifier);

        $block = $ref->getMethod('verificationBlock');
        $block->setAccessible(true);

        $render = $ref->getMethod('render');
        $render->setAccessible(true);

        return trim($render->invoke(
            $notifier,
            OrderNotifier::template($event),
            $order,
            $block->invoke($notifier, $order, $event)
        ));
    }

    public function test_unverified_customer_gets_a_code_in_the_order_sms(): void
    {
        $order = $this->makeOrder();

        $sms = $this->smsFor($order->fresh('customer'));

        $this->assertStringContainsString('کد تأیید شماره:', $sms);
        // خط WebOTP باید آخر متن باشد وگرنه کروم آن را نمی‌خواند
        $this->assertMatchesRegularExpression('/@\S+ #\d{6}$/u', $sms);
        $this->assertNotNull(Cache::get('otp:' . OtpService::PURPOSE_ORDER . ':09120000009'));
    }

    public function test_verified_customer_gets_no_code_and_no_dangling_label(): void
    {
        $order = $this->makeOrder(verifiedAt: '2026-01-01 00:00:00');

        $sms = $this->smsFor($order->fresh('customer'));

        $this->assertStringNotContainsString('کد تأیید', $sms);
        $this->assertStringNotContainsString('@', $sms);
    }

    public function test_later_status_messages_never_carry_a_code(): void
    {
        $order = $this->makeOrder();

        $this->assertStringNotContainsString('کد تأیید', $this->smsFor($order->fresh('customer'), 'shipped'));
        $this->assertStringNotContainsString('کد تأیید', $this->smsFor($order->fresh('customer'), 'delivered'));
    }

    public function test_order_code_outlives_a_login_code(): void
    {
        // کد ورود دو دقیقه‌ای برای پیامکی که ساعت‌ها بعد خوانده می‌شود بی‌فایده است
        $this->assertSame(120, OtpService::ttlFor(OtpService::PURPOSE_LOGIN));
        $this->assertGreaterThanOrEqual(3600, OtpService::ttlFor(OtpService::PURPOSE_ORDER));
    }

    public function test_correct_code_verifies_the_phone_and_signs_the_guest_in(): void
    {
        $order = $this->makeOrder();
        $this->smsFor($order->fresh('customer'));
        $code = Cache::get('otp:' . OtpService::PURPOSE_ORDER . ':09120000009')['code'];

        // نشستی که سفارش را ثبت کرده، حق تأیید دارد
        $this->withSession(['phone_verify_orders' => [$order->id]])
            ->postJson('/order/verify-phone', ['order_id' => $order->id, 'code' => $code])
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $this->assertNotNull($order->customer->fresh()->phone_verified_at);
        $this->assertTrue(Auth::guard('customer')->check());
    }

    public function test_wrong_code_is_rejected_and_phone_stays_unverified(): void
    {
        $order = $this->makeOrder();
        $this->smsFor($order->fresh('customer'));

        $this->withSession(['phone_verify_orders' => [$order->id]])
            ->postJson('/order/verify-phone', ['order_id' => $order->id, 'code' => '000000'])
            ->assertStatus(422);

        $this->assertNull($order->customer->fresh()->phone_verified_at);
        $this->assertFalse(Auth::guard('customer')->check());
    }

    /**
     * مهم‌ترین تست این فایل.
     *
     * کد ۲۴ ساعته اگر از هر مرورگری پذیرفته شود، یعنی هرکس شماره‌ی سفارش را
     * حدس بزند می‌تواند به حساب دیگری وارد شود.
     */
    public function test_a_browser_that_did_not_place_the_order_cannot_use_the_code(): void
    {
        $order = $this->makeOrder();
        $this->smsFor($order->fresh('customer'));
        $code = Cache::get('otp:' . OtpService::PURPOSE_ORDER . ':09120000009')['code'];

        $this->postJson('/order/verify-phone', ['order_id' => $order->id, 'code' => $code])
            ->assertStatus(422);

        $this->assertNull($order->customer->fresh()->phone_verified_at);
        $this->assertFalse(Auth::guard('customer')->check());
    }

    public function test_code_cannot_be_replayed(): void
    {
        $order = $this->makeOrder();
        $this->smsFor($order->fresh('customer'));
        $code = Cache::get('otp:' . OtpService::PURPOSE_ORDER . ':09120000009')['code'];

        $this->withSession(['phone_verify_orders' => [$order->id]])
            ->postJson('/order/verify-phone', ['order_id' => $order->id, 'code' => $code])
            ->assertOk();

        // بار دوم دیگر نباید کار کند، حتی از همان نشست
        $this->withSession(['phone_verify_orders' => [$order->id]])
            ->postJson('/order/verify-phone', ['order_id' => $order->id, 'code' => $code])
            ->assertOk()
            ->assertJson(['message' => 'شماره‌ی شما از قبل تأیید شده است.']);
    }
}
