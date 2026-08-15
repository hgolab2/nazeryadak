<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * واحد شمارش، تعداد در بسته و تاریخچه‌ی قیمت.
 *
 * ستون «واحد شمارش» در فایل stkstock از اول در اکسل بود ولی ایمپورتر آن را
 * دور می‌ریخت. حالا ذخیره می‌شود تا در صفحه‌ی محصول بیاید؛ برای قطعه‌ای که
 * واحدش «دست ۴ عددي» است، کاربر باید بداند با یک بار خرید چهار عدد می‌گیرد.
 *
 * تاریخچه‌ی قیمت جدولِ فقط-افزودنی است: هر بار که قیمت محصول عوض می‌شود یک
 * ردیف اضافه می‌شود. تا امروز ایمپورت قیمت را در جا بازنویسی می‌کرد و هیچ
 * ردی از قیمت قبلی نمی‌ماند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // واحد شمارش اکسل: «عدد»، «دست ۴ عددي»، «گالن ۴ليتري» و مانند آن.
            $table->string('unit', 50)->nullable()->after('stock');

            // تعداد قطعه در هر بسته، وقتی از روی متنِ واحد قابل استخراج باشد.
            // برای واحدهای حجمی/وزنی (گالن، کيلوگرم) null می‌ماند چون «تعداد»
            // برایشان معنا ندارد.
            $table->unsignedInteger('pack_qty')->nullable()->after('unit');
        });

        Schema::create('product_price_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');

            // قیمت تومانیِ سایت در آن لحظه (همان چیزی که به کاربر نشان داده می‌شد).
            $table->bigInteger('price');

            // منشأ تغییر: import (فایل اکسل) یا admin (ویرایش دستی).
            $table->string('source', 20)->default('import');

            $table->timestamp('created_at')->nullable();

            // نمودار صفحه‌ی محصول دقیقا همین ترتیب را می‌خواهد.
            $table->index(['product_id', 'created_at'], 'pph_product_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_history');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['unit', 'pack_qty']);
        });
    }
};
