<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * زیرساخت «مدیریت ریدایرکت» و «مانیتور ۴۰۴».
 *
 * redirects: قواعدی که مدیر تعریف می‌کند تا آدرس‌های قدیمی (تغییر اسلاگ،
 * حذف محصول، آدرس‌های سایت قبلی) با 301 به آدرس درست برسند و اعتبار لینک
 * از دست نرود.
 *
 * not_found_logs: هر مسیر ۴۰۴ فقط یک ردیف دارد و شمارنده‌اش بالا می‌رود؛
 * با یک ردیف به‌ازای هر بازدید، جدول در چند روز از کنترل خارج می‌شد.
 *
 * طول مسیرها ۱۹۱ است چون AppServiceProvider طول پیش‌فرض رشته را ۱۹۱ گذاشته
 * و ایندکس یکتا روی رشته‌ی بلندتر در MySQL ساخته نمی‌شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('source_path', 191)->unique();
            // مقصد خالی فقط برای وضعیت 410 (حذف دائمی) معنی دارد
            $table->string('target_path', 500)->nullable();
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('not_found_logs', function (Blueprint $table) {
            $table->id();
            $table->string('path', 191)->unique();
            $table->string('referer', 500)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->unsignedInteger('hits')->default(1);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            // فهرست ادمین همیشه با «تازه‌ترین» و «پربازدیدترین» مرتب می‌شود
            $table->index('last_seen_at');
            $table->index('hits');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('not_found_logs');
        Schema::dropIfExists('redirects');
    }
};
