<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ستون payments.status از نوع enum('init','success','failed') بود، ولی کد
 * مقدارهای 'pending' و 'paid' می‌نوشت. نتیجه: خطای «Data truncated for column
 * status» و صفحه‌ی ۵۰۰ دقیقاً وقتی کاربر روی «پرداخت امن» کلیک می‌کرد؛
 * یعنی هیچ سفارشی هرگز به درگاه نمی‌رسید.
 *
 * ستون به varchar(20) تغییر می‌کند تا مثل orders.status باشد و دیگر با
 * اضافه شدن وضعیت جدید نشکند.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('payments')) {
            return;
        }

        DB::statement("ALTER TABLE `payments` MODIFY `status` VARCHAR(20) NOT NULL DEFAULT 'pending'");

        // هم‌سان کردن ردیف‌های قدیمی با نام‌گذاری جدید
        DB::table('payments')->where('status', 'init')->update(['status' => 'pending']);
        DB::table('payments')->where('status', 'success')->update(['status' => 'paid']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('payments')) {
            return;
        }

        DB::table('payments')->where('status', 'pending')->update(['status' => 'init']);
        DB::table('payments')->where('status', 'paid')->update(['status' => 'success']);

        DB::statement("ALTER TABLE `payments` MODIFY `status` ENUM('init','success','failed') NOT NULL DEFAULT 'init'");
    }
};
