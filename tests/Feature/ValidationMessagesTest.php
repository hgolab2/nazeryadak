<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * پیام‌های اعتبارسنجی باید فارسی باشند.
 *
 * پوشه‌ی ترجمه اصلا وجود نداشت و هر فرمی که پیام سفارشی نداشت، متن پیش‌فرض
 * انگلیسی لاراول را نشان می‌داد: «The address line field is required» وسط
 * یک صفحه‌ی فارسی. این تست جلوی برگشتن آن را می‌گیرد.
 */
class ValidationMessagesTest extends TestCase
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
    }

    public function test_address_form_errors_are_in_persian(): void
    {
        $customer = Customer::create(['phone' => '09120000011', 'status' => 1]);

        $response = $this->actingAs($customer, 'customer')
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

    public function test_pagination_labels_are_in_persian(): void
    {
        $this->assertStringContainsString('قبلی', trans('pagination.previous'));
        $this->assertStringContainsString('بعدی', trans('pagination.next'));
        $this->assertSame('نمایش', __('Showing'));
    }
}
