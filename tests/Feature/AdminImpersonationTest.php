<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * ورود مدیر به حساب مشتری و تغییر رمز مشتری از پنل.
 *
 * جدول‌های users و customers مهاجرت ندارند، پس مثل بقیه‌ی تست‌های این پوشه
 * روی sqlite حافظه‌ای ساخته می‌شوند.
 */
class AdminImpersonationTest extends TestCase
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
    }

    private function admin(): User
    {
        return User::create([
            'username' => 'admin',
            'name'     => 'مدیر',
            'password' => Hash::make('secret123'),
            'active'   => 1,
            'role_id'  => 7,
        ]);
    }

    /** ورود واقعی از فرم پنل، تا نشست مدیر مثل محیط واقعی روی session بنشیند */
    private function loginAsAdmin(): User
    {
        $admin = $this->admin();

        $this->post('/loginAdmin', ['username' => 'admin', 'password' => 'secret123'])
            ->assertRedirect('/dashboardAdmin');

        return $admin;
    }

    public function test_admin_can_enter_and_leave_a_customer_account(): void
    {
        $admin    = $this->loginAsAdmin();
        $customer = Customer::create(['phone' => '09121110001', 'first_name' => 'سارا']);

        $this->get("/admin/customer/{$customer->id}/impersonate")
            ->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($customer, 'customer');
        // نشست مدیر باز می‌ماند تا بازگشت به پنل بدون ورود دوباره ممکن باشد
        $this->assertAuthenticatedAs($admin, 'web');
        $this->assertTrue(session()->has('impersonator_id'));

        $this->get('/impersonate/stop')->assertRedirect('/admin/customer/list');

        $this->assertGuest('customer');
        $this->assertAuthenticatedAs($admin, 'web');
    }

    public function test_guest_cannot_impersonate(): void
    {
        $customer = Customer::create(['phone' => '09121110002']);

        $this->get("/admin/customer/{$customer->id}/impersonate")
            ->assertRedirect('/loginAdmin');

        $this->assertGuest('customer');
    }

    public function test_customer_password_change_is_blocked_while_impersonating(): void
    {
        $admin    = $this->admin();
        $customer = Customer::create(['phone' => '09121110003', 'password' => Hash::make('secret123')]);

        $this->actingAs($admin)->get("/admin/customer/{$customer->id}/impersonate");

        $this->put('/profile/password', [
            'current_password'      => 'secret123',
            'password'              => 'hacked-pass',
            'password_confirmation' => 'hacked-pass',
        ])->assertRedirect('/profile/password');

        $this->assertTrue(Hash::check('secret123', $customer->fresh()->password));
    }

    public function test_admin_can_set_and_remove_a_customer_password(): void
    {
        $admin    = $this->admin();
        $customer = Customer::create(['phone' => '09121110004']);

        $this->actingAs($admin)->post("/admin/customer/{$customer->id}/password", [
            'password'              => 'fresh-secret',
            'password_confirmation' => 'fresh-secret',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('fresh-secret', $customer->fresh()->password));

        $this->actingAs($admin)->post("/admin/customer/{$customer->id}/password", ['action' => 'remove'])
            ->assertRedirect();

        $this->assertNull($customer->fresh()->password);
    }

    public function test_admin_created_customer_has_no_password_when_field_is_left_empty(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/customer/store', [
            'first_name' => 'رضا',
            'last_name'  => 'محمدی',
            'phone'      => '09121110005',
            'status'     => 1,
        ])->assertRedirect('/admin/customer/list');

        // قبلا اینجا bcrypt(null) ذخیره می‌شد و مشتری «رمزدار» به حساب می‌آمد
        $this->assertNull(Customer::where('phone', '09121110005')->first()->password);
    }
}
