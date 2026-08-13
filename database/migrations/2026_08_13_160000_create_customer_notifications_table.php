<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * اعلان‌های داخل سایت برای مشتری. جدول notifications خود لاراول عمدا استفاده
 * نشده چون کلید آن morph است و این پروژه فقط یک نوع مخاطب (مشتری) دارد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->string('type', 40)->default('order');
            $table->string('title');
            $table->text('body')->nullable();
            $table->string('url')->nullable();
            $table->string('icon', 40)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // شمارش خوانده‌نشده‌ها در هر بارگذاری صفحه اجرا می‌شود
            $table->index(['customer_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_notifications');
    }
};
