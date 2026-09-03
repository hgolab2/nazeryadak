<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * مدیریت سفارشات در پنل: مشاهده، ویرایش و حذف.
 *
 * جدول‌های users/customers/orders مهاجرت ندارند (از دیتابیس قدیمی آمده‌اند)،
 * پس مثل بقیه‌ی تست‌های این پوشه روی sqlite حافظه‌ای ساخته می‌شوند.
 */
class AdminOrderPanelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable()->unique();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->increments('user_id');
            $table->string('name')->nullable();
            $table->string('family')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->integer('group_id')->default(0);
            $table->integer('last_login')->default(0);
            $table->tinyInteger('active')->default(1);
            $table->integer('role_id')->default(7);
            $table->integer('site_id')->default(1);
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

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->integer('quantity')->default(1);
            $table->integer('unit_price')->nullable();
            $table->integer('total_price')->nullable();
            $table->timestamps();
        });

        // صفحه‌ی مشاهده، عنوان و کد فنی قطعه را از روی همین جدول می‌خواند
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->string('sku')->nullable();
            $table->integer('price')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('wholesale_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('gateway');
            $table->string('method', 30)->nullable();
            $table->bigInteger('amount');
            $table->string('status', 20)->default('pending');
            $table->string('ref_id')->nullable();
            $table->string('reference', 100)->nullable();
            $table->string('card_last4', 4)->nullable();
            $table->string('payer_name', 100)->nullable();
            $table->string('receipt_image')->nullable();
            $table->text('customer_note')->nullable();
            $table->text('admin_note')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    private function loginAsAdmin(): User
    {
        $admin = User::create([
            'username' => 'admin',
            'name'     => 'مدیر',
            'password' => Hash::make('secret123'),
            'active'   => 1,
            'role_id'  => 7,
        ]);

        $this->post('/loginAdmin', ['username' => 'admin', 'password' => 'secret123'])
            ->assertRedirect('/dashboardAdmin');

        return $admin;
    }

    private function makeOrder(array $attributes = []): Order
    {
        $customer = Customer::create([
            'phone'      => '0912' . str_pad((string) random_int(0, 9999999), 7, '0', STR_PAD_LEFT),
            'first_name' => 'رضا',
            'last_name'  => 'نوری',
        ]);

        $order = Order::create(array_merge([
            'customer_id'    => $customer->id,
            'final_price'    => 1_000_000,
            'shipping_price' => 50_000,
            'total_price'    => 1_050_000,
            'status'         => 'pending',
        ], $attributes));

        $product = \App\Models\Product::create([
            'title' => 'لنت ترمز جلو',
            'sku'   => 'NY-1001',
            'price' => 500_000,
        ]);

        OrderItem::create([
            'order_id'    => $order->id,
            'product_id'  => $product->id,
            'quantity'    => 2,
            'unit_price'  => 500_000,
            'total_price' => 1_000_000,
        ]);

        return $order;
    }

    /** لینک «مشاهده» در لیست به مسیری می‌رفت که وجود نداشت و ۴۰۴ می‌گرفت. */
    public function test_admin_can_open_the_order_show_page(): void
    {
        $this->loginAsAdmin();
        $order = $this->makeOrder();

        $this->get("/admin/order/show/{$order->id}")
            ->assertOk()
            ->assertSee('رضا نوری')
            ->assertSee('در انتظار پرداخت');
    }

    public function test_show_page_redirects_when_the_order_is_gone(): void
    {
        $this->loginAsAdmin();

        $this->get('/admin/order/show/999999')
            ->assertRedirect('/admin/order/list');
    }

    /**
     * ویرایش سفارش با shipping_method_id اجباری همیشه رد می‌شد، چون فرم چنین
     * فیلدی ندارد و جدول shipping_methods هم خالی است.
     */
    public function test_admin_can_update_an_order_without_a_shipping_method(): void
    {
        $this->loginAsAdmin();
        $order = $this->makeOrder();

        $this->put("/admin/order/update/{$order->id}", [
            'customer_id'    => $order->customer_id,
            'shipping_price' => 90_000,
            'final_price'    => 2_000_000,
            'status'         => 'processing',
        ])->assertRedirect('/admin/order/list');

        $order->refresh();

        $this->assertSame('processing', $order->status);
        $this->assertSame(2_000_000, (int) $order->final_price);
        // مبلغ پرداختی از فرم نمی‌آید؛ خود مدل از اقلام و ارسال می‌سازدش
        $this->assertSame(2_090_000, (int) $order->total_price);
    }

    /** تخفیف ثبت‌شده نباید با ویرایش پنل از مبلغ پرداختی حذف شود. */
    public function test_update_keeps_the_discount_inside_the_payable_amount(): void
    {
        $this->loginAsAdmin();
        $order = $this->makeOrder([
            'discount_code'   => 'OFF100',
            'discount_amount' => 100_000,
        ]);

        $this->put("/admin/order/update/{$order->id}", [
            'customer_id'    => $order->customer_id,
            'shipping_price' => 50_000,
            'final_price'    => 1_000_000,
            'status'         => 'pending',
        ])->assertRedirect('/admin/order/list');

        $this->assertSame(950_000, (int) $order->refresh()->total_price);
    }

    public function test_update_rejects_an_unknown_status(): void
    {
        $this->loginAsAdmin();
        $order = $this->makeOrder();

        $this->put("/admin/order/update/{$order->id}", [
            'customer_id'    => $order->customer_id,
            'shipping_price' => 0,
            'final_price'    => 1_000_000,
            'status'         => 'whatever',
        ])->assertSessionHasErrors('status');

        $this->assertSame('pending', $order->refresh()->status);
    }

    /** مسیر حذف به متدی اشاره می‌کرد که در کنترلر وجود نداشت. */
    public function test_admin_can_delete_an_order_with_its_items_and_payments(): void
    {
        $this->loginAsAdmin();
        $order = $this->makeOrder();

        Payment::create([
            'order_id' => $order->id,
            'gateway'  => Payment::GATEWAY_MANUAL,
            'method'   => 'card_to_card',
            'amount'   => 1_050_000,
            'status'   => 'pending',
        ]);

        $this->delete("/admin/order/{$order->id}")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        // بدون کلید خارجیِ آبشاری، این سطرها یتیم می‌ماندند
        $this->assertDatabaseMissing('order_items', ['order_id' => $order->id]);
        $this->assertDatabaseMissing('payments', ['order_id' => $order->id]);
    }

    /** کد سفارش با ارقام فارسی هم تایپ می‌شود. */
    public function test_order_list_search_accepts_persian_digits(): void
    {
        $this->loginAsAdmin();
        $order = $this->makeOrder();
        $other = $this->makeOrder();

        $response = $this->get('/admin/order/list?order_id=' . toPersianNumbers($order->id, false));

        $response->assertOk();
        $response->assertViewHas('totalCount', 1);
    }

    /** نام ستون مرتب‌سازی مستقیم وارد SQL می‌شد. */
    public function test_order_list_ignores_an_unknown_sort_column(): void
    {
        $this->loginAsAdmin();
        $this->makeOrder();

        $this->get('/admin/order/list?order=' . urlencode('id) from orders --'))
            ->assertOk();
    }
}
