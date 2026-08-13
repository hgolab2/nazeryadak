<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تنظیمات عمومی سایت به‌صورت کلید-مقدار.
 *
 * جدول shipping_settings عمداً استفاده نشد؛ آن مخصوص قواعد ارسال است و
 * ریختن مشخصات تماس و متن پیامک‌ها داخلش، معنی جدول را مبهم می‌کرد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 100)->unique();
            $table->text('setting_value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
