<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * جدول تنظیمات ارسال. روی سرور فعلی این جدول دستی ساخته شده بود و مهاجرتی
 * نداشت؛ این مهاجرت هم جدول را برای نصب‌های تازه می‌سازد و هم ردیف‌های
 * پیش‌فرضِ جاافتاده را (بدون دست‌زدن به مقادیر موجود) اضافه می‌کند.
 *
 * توجه: مقادیر مبلغی در این جدول به «ریال» ذخیره می‌شوند، ولی کل سایت با
 * «تومان» کار می‌کند. تبدیل در getShippingRules() و فرم مدیریت انجام می‌شود.
 */
return new class extends Migration
{
    private array $defaults = [
        [
            'setting_key'   => 'local_province_id',
            'setting_value' => '19',
            'description'   => 'شناسه استان محلی (قم)',
        ],
        [
            'setting_key'   => 'local_free_threshold',
            'setting_value' => '50000000',
            'description'   => 'حداقل مبلغ فاکتور برای ارسال رایگان در استان محلی (ریال)',
        ],
        [
            'setting_key'   => 'local_shipping_cost',
            'setting_value' => '500000',
            'description'   => 'هزینه ارسال در استان محلی زیر حد آستانه (ریال)',
        ],
        [
            'setting_key'   => 'national_free_threshold',
            'setting_value' => '200000000',
            'description'   => 'حداقل مبلغ فاکتور برای ارسال رایگان سایر شهرها (ریال)',
        ],
        [
            'setting_key'   => 'national_shipping_method',
            'setting_value' => 'tipax',
            'description'   => 'روش ارسال سایر شهرها (تیپاکس - پسکرایه)',
        ],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('shipping_settings')) {
            Schema::create('shipping_settings', function (Blueprint $table) {
                $table->id();
                $table->string('setting_key')->unique();
                $table->string('setting_value')->nullable();
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        $existing = DB::table('shipping_settings')->pluck('setting_key')->all();

        foreach ($this->defaults as $row) {
            if (in_array($row['setting_key'], $existing, true)) {
                continue;
            }
            DB::table('shipping_settings')->insert($row + [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // جدول تنظیمات حذف نمی‌شود تا مقادیر تنظیم‌شده‌ی فروشگاه از بین نرود.
    }
};
