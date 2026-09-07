<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * نگه‌داشتن متن اصلیِ توضیحات پیش از بازنویسی سئویی.
 *
 * products:seo-describe متن ۱۵۹۵ محصول را بازنویسی می‌کند. بدون این ستون،
 * مقاله‌هایی که products:sync-isaco-content از isaco.ir گرفته بود برای همیشه
 * از بین می‌رفتند و اجرای دوباره‌ی دستور، خروجیِ خودش را دوباره پردازش
 * می‌کرد و بخش‌ها روی هم انباشته می‌شدند.
 *
 * با این ستون، منبع همیشه ثابت می‌ماند: هر بار متن از description_source
 * ساخته می‌شود، پس دستور idempotent است و --revert می‌تواند دقیقا وضعیت
 * قبل را برگرداند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'description_source')) {
                $table->text('description_source')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'description_source')) {
                $table->dropColumn('description_source');
            }
        });
    }
};
