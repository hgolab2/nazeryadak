<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * «این شماره واقعا مال همین آدم است؟»
 *
 * تا حالا جواب این سؤال از روی «حساب دارد یا نه» حدس زده می‌شد، ولی این دو
 * یکی نیستند: حسابی که ادمین از پنل ساخته یا از فایل وارد شده، شماره‌اش
 * هیچ‌وقت تأیید نشده است.
 *
 * جداکردن این پرچم همان چیزی است که اجازه می‌دهد سفارش بدون کد پیامکی ثبت
 * شود ولی ورود به حساب همچنان کد بخواهد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->timestamp('phone_verified_at')->nullable()->after('phone');
        });

        /*
        | مشتری‌های فعلی نباید یک‌شبه «تأییدنشده» شوند و کد اضافه در پیامک
        | سفارششان ببینند.
        |
        | ملاک، last_login_at است: تنها راهی که یک مشتری خودش حساب می‌سازد،
        | همان مسیر کد پیامکی است، پس هرکس یک بار وارد شده شماره‌اش را هم
        | تأیید کرده. حساب‌های ساخته‌شده توسط ادمین این ستون را ندارند و
        | درست هم همین است که تأییدنشده بمانند.
        |
        | اشتباه در هر دو جهت کم‌هزینه است: تأییدشده‌ی اشتباهی فقط کد نمی‌گیرد،
        | تأییدنشده‌ی اشتباهی فقط یک خط اضافه در پیامک می‌بیند.
        */
        DB::table('customers')
            ->whereNull('phone_verified_at')
            ->whereNotNull('last_login_at')
            ->update(['phone_verified_at' => DB::raw('last_login_at')]);
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('phone_verified_at');
        });
    }
};
