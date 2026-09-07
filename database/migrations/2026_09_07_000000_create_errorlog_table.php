<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * جدول خطاهای سرور.
 *
 * جدول روی سرور فعلی دست‌ساز ساخته شده بود و مهاجرتی نداشت؛ یعنی روی هر
 * نصب تازه (یا بعد از migrate:fresh) وجود نداشت و ثبت خطا بی‌سروصدا از کار
 * می‌افتاد. این مهاجرت همان ساختار موجود را تثبیت می‌کند و اگر جدول از قبل
 * باشد کاری نمی‌کند، پس روی دیتابیس فعلی چیزی از دست نمی‌رود.
 *
 * ستون‌های url و fullurl عمدا هر دو هستند: url مسیر درخواست است (چیزی که در
 * فهرست ادمین خوانده می‌شود) و fullurl آدرس کامل با query برای بازتولید خطا.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('errorlog')) {
            return;
        }

        Schema::create('errorlog', function (Blueprint $table) {
            $table->id();
            $table->text('message');
            $table->longText('stack_trace')->nullable();
            $table->string('level', 50)->nullable()->default('error');
            $table->string('url', 2048)->nullable();
            $table->string('route_name', 255)->nullable();
            $table->string('method', 10)->default('GET');
            $table->text('request_data')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('code', 100);
            $table->string('file', 250);
            $table->integer('line')->nullable();
            $table->string('fullurl', 1000);
            $table->string('ip', 250);
            $table->timestamps();

            // فهرست ادمین همیشه «تازه‌ترین اول» است
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('errorlog');
    }
};
