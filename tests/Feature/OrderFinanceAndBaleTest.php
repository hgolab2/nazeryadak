<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\FinanceTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\BaleBot;
use App\Services\FinanceReport;
use App\Services\OrderStatusService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * سود سفارش، دفتر مالی، تغییر وضعیت از یک مسیر مشترک، و ربات بله.
 *
 * جدول‌ها مثل بقیه‌ی تست‌های این پوشه روی sqlite حافظه‌ای ساخته می‌شوند.
 */
class OrderFinanceAndBaleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->timestamp('phone_verified_at')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->increments('user_id');
            $table->string('username')->nullable();
            $table->string('name')->nullable();
            $table->string('family')->nullable();
            $table->string('password')->nullable();
            $table->integer('active')->default(1);
            $table->integer('role_id')->default(0);
            $table->integer('group_id')->default(0);
            $table->integer('site_id')->default(0);
            $table->integer('last_login')->default(0);
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
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('bale_notified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->integer('quantity')->default(1);
            $table->integer('unit_price')->nullable();
            $table->integer('unit_cost')->nullable();
            $table->integer('total_price')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('slug')->nullable();
            $table->string('sku')->nullable();
            $table->integer('price')->default(0);
            $table->unsignedBigInteger('cost_price')->nullable();
            $table->unsignedBigInteger('compare_at_price')->nullable();
            $table->unsignedTinyInteger('import_bonus_percent')->default(0);
            $table->integer('stock')->nullable();
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
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10);
            $table->string('category', 40);
            $table->string('title', 190);
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('order_id')->nullable();
            $table->date('occurred_on');
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('type')->nullable();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('url')->nullable();
            $table->string('icon')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key')->unique();
            $table->text('setting_value')->nullable();
            $table->timestamps();
        });

        config(['bale.enabled' => false]);
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

    /** سفارش ۲ × ۵۰۰٬۰۰۰ با قیمت خرید ۴۰۰٬۰۰۰ و ارسال ۵۰٬۰۰۰ */
    private function makeOrder(array $attributes = []): Order
    {
        $customer = Customer::create(['phone' => '09121234567', 'first_name' => 'رضا', 'last_name' => 'نوری']);

        $order = Order::create(array_merge([
            'customer_id'    => $customer->id,
            'final_price'    => 1_000_000,
            'shipping_price' => 50_000,
            'total_price'    => 1_050_000,
            'status'         => 'awaiting_call',
        ], $attributes));

        $product = Product::create(['title' => 'لنت ترمز جلو', 'sku' => 'NY-1001', 'price' => 500_000, 'cost_price' => 400_000, 'stock' => 10]);

        OrderItem::create([
            'order_id'    => $order->id,
            'product_id'  => $product->id,
            'quantity'    => 2,
            'unit_price'  => 500_000,
            'unit_cost'   => 400_000,
            'total_price' => 1_000_000,
        ]);

        return $order;
    }

    /* ------------------------------------------------------- سود سفارش */

    public function test_order_profit_subtracts_item_cost_and_expenses(): void
    {
        $order = $this->makeOrder();

        // ۱٬۰۵۰٬۰۰۰ دریافتی − ۸۰۰٬۰۰۰ خرید = ۲۵۰٬۰۰۰
        $this->assertSame(800_000, $order->itemsCost());
        $this->assertSame(250_000, $order->netProfit());
        $this->assertTrue($order->hasCompleteCosts());

        FinanceTransaction::create([
            'type' => 'expense', 'category' => 'shipping', 'title' => 'پست',
            'amount' => 60_000, 'order_id' => $order->id, 'occurred_on' => now()->toDateString(),
        ]);

        $this->assertSame(190_000, $order->fresh()->netProfit());
    }

    public function test_purchase_cost_falls_back_to_the_retail_markup(): void
    {
        // قیمت سایت = اکسل × ۱.۲ و پاداش ۴٪ روی قیمتِ پیش از تخفیف
        $product = new Product(['price' => 120_000, 'compare_at_price' => 124_800, 'import_bonus_percent' => 4]);

        $this->assertSame(100_000, $product->purchaseCost());

        $product->cost_price = 95_000;
        $this->assertSame(95_000, $product->purchaseCost());
    }

    /* ------------------------------------------------------- تغییر وضعیت */

    public function test_first_settlement_stamps_paid_at_and_decrements_stock_once(): void
    {
        $order   = $this->makeOrder();
        $service = new OrderStatusService();

        $this->assertTrue($service->change($order, 'paid', 'test'));
        $order->refresh();

        $this->assertNotNull($order->paid_at);
        $this->assertSame(8, (int) Product::first()->stock);

        // رفت و برگشت وضعیت نباید دوباره از انبار کم کند
        $service->change($order, 'processing', 'test');
        $service->change($order, 'paid', 'test');

        $this->assertSame(8, (int) Product::first()->stock);
        $this->assertFalse($service->change($order->fresh(), 'paid', 'test'));
    }

    public function test_invalid_status_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new OrderStatusService())->change($this->makeOrder(), 'flying', 'test');
    }

    public function test_admin_can_change_status_inline(): void
    {
        $this->loginAsAdmin();
        $order = $this->makeOrder();

        $this->put("/admin/order/{$order->id}/status", ['status' => 'processing'])
            ->assertOk()
            ->assertJson(['success' => true, 'changed' => true, 'label' => 'در حال آماده‌سازی']);

        $this->assertSame('processing', $order->fresh()->status);

        $this->put("/admin/order/{$order->id}/status", ['status' => 'nope'])
            ->assertStatus(422);
    }

    public function test_admin_can_fix_item_cost_from_the_order_page(): void
    {
        $this->loginAsAdmin();
        $order = $this->makeOrder();
        $item  = $order->items()->first();

        $this->put("/admin/order/{$order->id}/item/{$item->id}/cost", ['unit_cost' => '۴۵۰,۰۰۰'])
            ->assertRedirect();

        $this->assertSame(450_000, (int) $item->fresh()->unit_cost);
        $this->assertSame(150_000, $order->fresh()->netProfit());
    }

    /* ------------------------------------------------------- حسابداری */

    public function test_finance_report_counts_only_settled_orders(): void
    {
        $settled = $this->makeOrder(['status' => 'paid', 'paid_at' => now()]);
        $this->makeOrder(['status' => 'pending']);

        FinanceTransaction::create([
            'type' => 'expense', 'category' => 'server', 'title' => 'سرور',
            'amount' => 100_000, 'occurred_on' => now()->toDateString(),
        ]);
        FinanceTransaction::create([
            'type' => 'expense', 'category' => 'shipping', 'title' => 'پست',
            'amount' => 30_000, 'order_id' => $settled->id, 'occurred_on' => now()->toDateString(),
        ]);
        FinanceTransaction::create([
            'type' => 'income', 'category' => 'other', 'title' => 'فروش حضوری',
            'amount' => 200_000, 'occurred_on' => now()->toDateString(),
        ]);

        $report = FinanceReport::today();

        $this->assertSame(1, $report['orders']);
        $this->assertSame(2, $report['items']);
        $this->assertSame(1_050_000, $report['sales']);
        $this->assertSame(800_000, $report['cogs']);
        $this->assertSame(30_000, $report['order_expenses']);
        $this->assertSame(100_000, $report['general_expenses']);
        $this->assertSame(200_000, $report['other_income']);
        // ۱٬۰۵۰٬۰۰۰ + ۲۰۰٬۰۰۰ − ۸۰۰٬۰۰۰ − ۳۰٬۰۰۰ − ۱۰۰٬۰۰۰
        $this->assertSame(320_000, $report['net_profit']);
        $this->assertSame(['server' => 100_000, 'shipping' => 30_000], $report['expenses_by_category']);
    }

    public function test_admin_can_record_an_expense_with_a_jalali_date(): void
    {
        $this->loginAsAdmin();
        $order = $this->makeOrder();

        $this->post('/admin/finance', [
            'type'        => 'expense',
            'category'    => 'packaging',
            'title'       => 'کارتن',
            'amount'      => '۱۲,۵۰۰',
            'order_id'    => $order->id,
            'occurred_on' => '۱۴۰۵/۰۶/۲۰',
            'return'      => 'order',
        ])->assertRedirect('/admin/order/show/' . $order->id);

        $transaction = FinanceTransaction::first();

        $this->assertSame(12_500, $transaction->amount);
        $this->assertSame('2026-09-11', $transaction->occurred_on->toDateString());
        $this->assertSame($order->id, $transaction->order_id);

        // دسته‌ای که مال نوع دیگری است پذیرفته نمی‌شود
        $this->post('/admin/finance', [
            'type' => 'income', 'category' => 'server', 'title' => 'x', 'amount' => 1, 'occurred_on' => '1405/06/20',
        ])->assertSessionHasErrors('category');
    }

    public function test_finance_page_renders(): void
    {
        $this->loginAsAdmin();
        $this->makeOrder(['status' => 'paid', 'paid_at' => now()]);

        $this->get('/admin/finance')->assertOk()->assertSee('صورت سود و زیان');
        $this->get('/admin/finance/create?type=expense')->assertOk();
        $this->get('/dashboardAdmin')->assertOk()->assertSee('سود خالص');
    }

    /* ------------------------------------------------------- ربات بله */

    private function enableBale(): void
    {
        config([
            'bale.enabled'   => true,
            'bale.token'     => 'TOKEN',
            'bale.chat_id'   => '111',
            'bale.admin_ids' => '',
            'bale.defer'     => false,
            'bale.api_url'   => 'https://tapi.bale.ai',
        ]);
    }

    public function test_status_button_in_bale_changes_the_order(): void
    {
        $this->enableBale();
        Http::fake(['tapi.bale.ai/*' => Http::response(['ok' => true, 'result' => ['message_id' => 5]])]);

        $order = $this->makeOrder();

        (new BaleBot())->handle([
            'update_id'      => 1,
            'callback_query' => [
                'id'      => 'cb1',
                'from'    => ['id' => 111],
                'message' => ['message_id' => 9, 'chat' => ['id' => 111]],
                'data'    => 'st:' . $order->id . ':processing',
            ],
        ]);

        $this->assertSame('processing', $order->fresh()->status);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'answerCallbackQuery')
            && str_contains($request['text'], 'در حال آماده‌سازی'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'editMessageText'));
    }

    public function test_unknown_user_cannot_use_the_bot(): void
    {
        $this->enableBale();
        Http::fake(['tapi.bale.ai/*' => Http::response(['ok' => true, 'result' => []])]);

        $order = $this->makeOrder();

        (new BaleBot())->handle([
            'update_id'      => 2,
            'callback_query' => [
                'id'      => 'cb2',
                'from'    => ['id' => 999],
                'message' => ['message_id' => 9, 'chat' => ['id' => 999]],
                'data'    => 'st:' . $order->id . ':canceled',
            ],
        ]);

        $this->assertSame('awaiting_call', $order->fresh()->status);
        Http::assertSent(fn ($request) => str_contains($request['text'] ?? '', 'مجاز'));
    }

    public function test_bot_replies_to_report_commands(): void
    {
        $this->makeOrder(['status' => 'paid', 'paid_at' => now()]);
        $bot = new BaleBot();

        $this->assertStringContainsString('سود خالص: 250,000', $bot->reply('آمار امروز')['text']);
        $this->assertStringContainsString('#1', $bot->reply('باز')['text']);
        $this->assertStringContainsString('لنت ترمز جلو', $bot->reply('1')['text']);
        $this->assertArrayHasKey('inline_keyboard', $bot->reply('1')['markup']);
        $this->assertStringContainsString('محصولات: 1', $bot->reply('فروشگاه')['text']);
    }

    public function test_webhook_rejects_wrong_secret_and_accepts_the_right_one(): void
    {
        $this->enableBale();
        Http::fake(['tapi.bale.ai/*' => Http::response(['ok' => true, 'result' => []])]);

        $this->postJson('/bale/webhook/wrong', ['update_id' => 1])->assertNotFound();

        $secret = \App\Http\Controllers\BaleWebhookController::secret();

        $this->postJson('/bale/webhook/' . $secret, [
            'update_id' => 3,
            'message'   => ['chat' => ['id' => 111], 'from' => ['id' => 111], 'text' => '/start'],
        ])->assertOk()->assertJson(['ok' => true]);

        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage') && isset($request['reply_markup']['keyboard']));
    }

    public function test_order_message_marks_bale_notified_at_when_delivered(): void
    {
        $this->enableBale();
        Http::fake(['tapi.bale.ai/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]])]);

        $order = $this->makeOrder();
        $this->assertNull($order->bale_notified_at);

        (new OrderStatusService())->change($order, 'processing', 'test');

        $this->assertNotNull($order->fresh()->bale_notified_at);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'sendMessage')
            && isset($request['reply_markup']['inline_keyboard'])
            && str_contains($request['text'], 'سود خالص'));
    }
}
