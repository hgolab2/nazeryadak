<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\Product;
use App\Models\Province;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * خرید بدون حساب.
 *
 * قاعده‌ای که این فایل نگهبانش است: بدون کد پیامکی می‌شود سفارش داد، ولی
 * هیچ‌وقت نباید نشستِ ورود ساخته شود و هیچ‌وقت نباید اطلاعات یک مشتری دیگر
 * دیده شود.
 */
class GuestCheckoutTest extends TestCase
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

        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('province_id')->nullable();
            $table->string('city')->nullable();
            $table->string('receiver_name')->nullable();
            $table->string('receiver_phone')->nullable();
            $table->string('postal_code')->nullable();
            $table->text('address_line')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
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

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->integer('quantity')->default(1);
            $table->integer('unit_price')->nullable();
            $table->integer('total_price')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->string('sku')->nullable();
            $table->integer('price')->default(0);
            $table->integer('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('wholesale_enabled')->default(true);
            $table->timestamps();
        });

        Province::create(['name' => 'قم']);
    }

    private function addressPayload(array $overrides = []): array
    {
        return array_merge([
            'receiver_name'  => 'علی رضایی',
            'receiver_phone' => '09120000011',
            'province_id'    => 1,
            'city'           => 'قم',
            'address_line'   => 'خیابان انقلاب، پلاک ۱۵',
        ], $overrides);
    }

    public function test_guest_reaches_checkout_without_being_sent_to_login(): void
    {
        // سبد خالی به /cart می‌رود؛ آن مسیر ربطی به ورود ندارد
        $this->withSession(['cart' => []])
            ->get('/order/shopping')
            ->assertRedirect('/cart');

        $this->assertFalse(Auth::guard('customer')->check());
    }

    public function test_saving_an_address_creates_the_customer_without_logging_them_in(): void
    {
        $order = Order::create(['status' => 'pending']);

        $this->withSession(['guest_orders' => [$order->id]])
            ->postJson('/address/save', $this->addressPayload())
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $customer = Customer::where('phone', '09120000011')->first();
        $this->assertNotNull($customer, 'مشتری باید از روی شماره‌ی گیرنده ساخته شود');
        $this->assertSame('علی', $customer->first_name);

        $order->refresh();
        $this->assertSame($customer->id, $order->customer_id);
        $this->assertNotNull($order->address_id);

        // مهم‌ترین بخش: سفارش ثبت شد ولی هیچ نشستی ساخته نشد
        $this->assertFalse(Auth::guard('customer')->check());
        $this->assertNull($customer->phone_verified_at);
    }

    public function test_persian_digits_in_the_receiver_phone_are_normalized(): void
    {
        $order = Order::create(['status' => 'pending']);

        $this->withSession(['guest_orders' => [$order->id]])
            ->postJson('/address/save', $this->addressPayload(['receiver_phone' => '۰۹۱۲۰۰۰۰۰۱۱']))
            ->assertOk();

        // بدون یکسان‌سازی، هر شکل از یک شماره یک مشتری تکراری می‌ساخت
        $this->assertSame(1, Customer::where('phone', '09120000011')->count());
    }

    public function test_a_malformed_phone_is_rejected(): void
    {
        $order = Order::create(['status' => 'pending']);

        $this->withSession(['guest_orders' => [$order->id]])
            ->postJson('/address/save', $this->addressPayload(['receiver_phone' => '0945123']))
            ->assertStatus(422);

        $this->assertSame(0, Customer::count());
    }

    /**
     * مهم‌ترین تست امنیتی این فایل.
     *
     * اگر مهمانی شماره‌ی یک مشتری دیگر را وارد کند، سفارش به همان حساب
     * می‌چسبد (که درست است، چون شماره کلید هویت است) — ولی نباید آدرس
     * ذخیره‌شده‌ی آن آدم بازنویسی یا دیده شود.
     */
    public function test_guest_cannot_overwrite_an_existing_customers_saved_address(): void
    {
        $owner = Customer::create(['phone' => '09120000011', 'status' => 1]);
        $owner->forceFill(['phone_verified_at' => now()])->save();

        $ownerAddress = CustomerAddress::create([
            'customer_id'    => $owner->id,
            'province_id'    => 1,
            'city'           => 'تهران',
            'receiver_name'  => 'صاحب حساب',
            'receiver_phone' => '09120000011',
            'address_line'   => 'آدرس خصوصی صاحب حساب',
        ]);

        $order = Order::create(['status' => 'pending']);

        $this->withSession(['guest_orders' => [$order->id]])
            ->postJson('/address/save', $this->addressPayload(['receiver_name' => 'مهمان']))
            ->assertOk();

        // آدرس صاحب حساب باید دست‌نخورده مانده باشد
        $ownerAddress->refresh();
        $this->assertSame('آدرس خصوصی صاحب حساب', $ownerAddress->address_line);
        $this->assertSame('صاحب حساب', $ownerAddress->receiver_name);
        $this->assertSame(2, CustomerAddress::count(), 'آدرس تازه باید جدا ساخته شود');

        $this->assertFalse(Auth::guard('customer')->check());
    }

    public function test_a_browser_cannot_touch_an_order_it_did_not_create(): void
    {
        $order = Order::create(['status' => 'pending']);

        // بدون guest_orders در نشست، این سفارش مال این مرورگر نیست
        $this->postJson('/address/save', $this->addressPayload())
            ->assertStatus(422);

        $this->get('/order/payment/' . $order->id)->assertRedirect('/cart');
        $this->get('/order/invoice/' . $order->id)->assertRedirect('/cart');

        $order->refresh();
        $this->assertNull($order->customer_id);
    }

    /**
     * پوشه‌ی lang اصلا وجود نداشت و هر فرمی که پیام سفارشی نداشت، متن
     * پیش‌فرض انگلیسی لاراول را نشان می‌داد: «The address line field is
     * required». مشتری وسط یک صفحه‌ی فارسی، جمله‌ی انگلیسی می‌دید.
     */
    public function test_validation_errors_are_in_persian(): void
    {
        $order = Order::create(['status' => 'pending']);

        $response = $this->withSession(['guest_orders' => [$order->id]])
            ->postJson('/address/save', ['receiver_name' => '', 'address_line' => ''])
            ->assertStatus(422);

        $errors = $response->json('errors');

        $this->assertSame('وارد کردن آدرس کامل الزامی است.', $errors['address_line'][0]);
        $this->assertSame('وارد کردن نام و نام خانوادگی گیرنده الزامی است.', $errors['receiver_name'][0]);

        // نه کلید خام ترجمه، نه متن انگلیسی
        foreach ($errors as $messages) {
            $this->assertStringNotContainsString('validation.', $messages[0]);
            $this->assertDoesNotMatchRegularExpression('/[A-Za-z]{4,}/', $messages[0]);
        }
    }

    public function test_placing_an_order_as_guest_needs_no_login(): void
    {
        $order = Order::create(['status' => 'pending']);
        $session = ['guest_orders' => [$order->id]];

        $this->withSession($session)->postJson('/address/save', $this->addressPayload())->assertOk();

        $product = Product::create(['title' => 'لنت ترمز', 'price' => 250000, 'stock' => 5]);
        $order->items()->create([
            'product_id'  => $product->id,
            'quantity'    => 1,
            'unit_price'  => 250000,
            'total_price' => 250000,
        ]);

        $this->withSession($session)
            ->post('/order/place/' . $order->id)
            ->assertRedirect('/order/invoice/' . $order->id);

        $order->refresh();
        $this->assertSame('awaiting_call', $order->status);
        $this->assertNotNull($order->customer_id);
        $this->assertFalse(Auth::guard('customer')->check());
    }
}
