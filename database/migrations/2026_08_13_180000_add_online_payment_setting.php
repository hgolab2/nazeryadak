<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * کلید روشن/خاموش‌کردن پرداخت آنلاین در همان جدول تنظیمات فروشگاه.
 *
 * پیش‌فرض «خاموش» است، چون فعلا درگاه پرداخت در دسترس نیست و سفارش‌ها با
 * پیش‌فاکتور و تماس تلفنی کارشناسان نهایی می‌شوند. مدیر هر وقت خواست از
 * /admin/settings آن را روشن می‌کند.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('shipping_settings')) {
            return;
        }

        $exists = DB::table('shipping_settings')
            ->where('setting_key', 'online_payment_enabled')
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('shipping_settings')->insert([
            'setting_key'   => 'online_payment_enabled',
            'setting_value' => '0',
            'description'   => 'فعال بودن پرداخت آنلاین (۱ فعال / ۰ غیرفعال)',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    public function down(): void
    {
        if (Schema::hasTable('shipping_settings')) {
            DB::table('shipping_settings')->where('setting_key', 'online_payment_enabled')->delete();
        }
    }
};
