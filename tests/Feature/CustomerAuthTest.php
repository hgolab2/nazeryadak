<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Services\OtpService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

/**
 * ورود و ثبت‌نام مشتری.
 *
 * جدول customers مهاجرت ندارد (از دیتابیس قدیمی آمده)، پس اینجا ساخته می‌شود
 * تا تست روی sqlite حافظه‌ای اجرا شود و به دیتابیس واقعی دست نزند.
 */
class CustomerAuthTest extends TestCase
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
            $table->string('otp_code', 6)->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('remember_token')->nullable();
            $table->timestamps();
        });
    }

    private function code(string $mobile, string $purpose = OtpService::PURPOSE_LOGIN): string
    {
        return Cache::get("otp:{$purpose}:{$mobile}")['code'];
    }

    public function test_unknown_mobile_goes_to_otp_and_creates_no_account(): void
    {
        $this->post('/auth/check', ['mobile' => '09120000001'])
            ->assertOk()
            ->assertJson(['step' => 'otp', 'is_new' => true]);

        // درخواست کد نباید حساب بسازد؛ حساب فقط بعد از تأیید کد ساخته می‌شود
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_mobile_with_password_is_offered_password_first(): void
    {
        Customer::create(['phone' => '09120000002', 'password' => Hash::make('secret123')]);

        $this->post('/auth/check', ['mobile' => '09120000002'])
            ->assertOk()
            ->assertJson(['step' => 'password']);

        // برای این شماره نباید کدی فرستاده شده باشد
        $this->assertNull(Cache::get('otp:login:09120000002'));
    }

    public function test_mobile_without_password_never_sees_password_step(): void
    {
        Customer::create(['phone' => '09120000003', 'first_name' => 'علی', 'last_name' => 'رضایی']);

        $this->post('/auth/check', ['mobile' => '09120000003'])
            ->assertOk()
            ->assertJson(['step' => 'otp', 'is_new' => false]);
    }

    public function test_otp_verification_registers_and_asks_to_complete_profile(): void
    {
        $this->post('/auth/check', ['mobile' => '09120000004']);

        $this->post('/auth/verify-otp', [
            'mobile' => '09120000004',
            'otp'    => $this->code('09120000004'),
        ])->assertOk()->assertJson(['is_new' => true, 'step' => 'profile']);

        $this->assertAuthenticated('customer');
        $this->assertDatabaseHas('customers', ['phone' => '09120000004']);

        $this->post('/auth/complete-profile', [
            'first_name' => 'مریم',
            'last_name'  => 'کریمی',
            'password'   => 'secret123',
            'password_confirmation' => 'secret123',
        ])->assertOk()->assertJson(['redirect' => '/dashboard']);

        $customer = Customer::where('phone', '09120000004')->first();
        $this->assertSame('مریم کریمی', $customer->fullName());
        $this->assertTrue(Hash::check('secret123', $customer->password));
    }

    public function test_otp_code_is_single_use(): void
    {
        $this->post('/auth/check', ['mobile' => '09120000005']);
        $code = $this->code('09120000005');

        $this->post('/auth/verify-otp', ['mobile' => '09120000005', 'otp' => $code])->assertOk();

        $this->get('/logout');

        $this->post('/auth/verify-otp', ['mobile' => '09120000005', 'otp' => $code])
            ->assertStatus(422);
    }

    public function test_password_login(): void
    {
        Customer::create(['phone' => '09120000006', 'first_name' => 'حسن', 'password' => Hash::make('secret123')]);

        $this->post('/auth/login-password', ['mobile' => '09120000006', 'password' => 'wrong-one'])
            ->assertStatus(422);

        $this->post('/auth/login-password', ['mobile' => '09120000006', 'password' => 'secret123'])
            ->assertOk()
            ->assertJson(['redirect' => '/dashboard']);

        $this->assertAuthenticated('customer');
    }

    public function test_inactive_customer_cannot_log_in(): void
    {
        Customer::create(['phone' => '09120000007', 'password' => Hash::make('secret123'), 'status' => false]);

        $this->post('/auth/login-password', ['mobile' => '09120000007', 'password' => 'secret123'])
            ->assertStatus(403);

        $this->assertGuest('customer');
    }

    public function test_persian_digits_and_country_code_are_accepted(): void
    {
        Customer::create(['phone' => '09120000008', 'password' => Hash::make('secret123')]);

        $this->post('/auth/check', ['mobile' => '۰۹۱۲۰۰۰۰۰۰۸'])
            ->assertOk()->assertJson(['step' => 'password']);

        $this->post('/auth/check', ['mobile' => '+989120000008'])
            ->assertOk()->assertJson(['step' => 'password']);
    }

    public function test_forgot_password_flow(): void
    {
        Customer::create(['phone' => '09120000009', 'password' => Hash::make('old-secret')]);

        $this->get('/password/forgot')->assertOk();

        $this->post('/password/forgot/send-otp', ['mobile' => '09120000009'])
            ->assertOk()->assertJson(['step' => 'otp']);

        $this->post('/password/forgot/verify-otp', [
            'mobile' => '09120000009',
            'otp'    => $this->code('09120000009', OtpService::PURPOSE_RESET),
        ])->assertOk()->assertJson(['step' => 'reset']);

        $this->post('/password/forgot/reset', [
            'password' => 'new-secret',
            'password_confirmation' => 'new-secret',
        ])->assertOk();

        $this->assertAuthenticated('customer');
        $this->assertTrue(Hash::check('new-secret', Customer::first()->password));
    }

    public function test_password_cannot_be_reset_without_verifying_the_code(): void
    {
        Customer::create(['phone' => '09120000010', 'password' => Hash::make('old-secret')]);

        $this->post('/password/forgot/reset', [
            'password' => 'new-secret',
            'password_confirmation' => 'new-secret',
        ])->assertStatus(419);

        $this->assertTrue(Hash::check('old-secret', Customer::first()->password));
    }

    public function test_reset_code_cannot_be_used_to_log_in(): void
    {
        Customer::create(['phone' => '09120000011']);

        $this->post('/password/forgot/send-otp', ['mobile' => '09120000011']);
        $code = $this->code('09120000011', OtpService::PURPOSE_RESET);

        $this->post('/auth/verify-otp', ['mobile' => '09120000011', 'otp' => $code])
            ->assertStatus(422);

        $this->assertGuest('customer');
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('ورود به ناظر یدک');
    }

    public function test_internal_redirect_is_kept_and_external_one_is_dropped(): void
    {
        Customer::create(['phone' => '09120000012', 'first_name' => 'نگار', 'password' => Hash::make('secret123')]);

        $this->get('/login?redirect=https://evil.example.com');
        $this->post('/auth/login-password', ['mobile' => '09120000012', 'password' => 'secret123'])
            ->assertJson(['redirect' => '/dashboard']);

        $this->get('/logout');

        $this->get('/login?redirect=/cart');
        $this->post('/auth/login-password', ['mobile' => '09120000012', 'password' => 'secret123'])
            ->assertJson(['redirect' => '/cart']);
    }
}
