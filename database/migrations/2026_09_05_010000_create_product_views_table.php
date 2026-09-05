<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * شمارش بازدید محصول‌ها.
 *
 * دو جا ذخیره می‌شود چون دو سوال متفاوت پرسیده می‌شود:
 *
 * ۱) products.views_count — مجموع بازدید از ابتدا. یک عدد روی خودِ محصول،
 *    تا نمایش «چند نفر این قطعه را دیده‌اند» هیچ کوئری اضافه‌ای نخواهد.
 *
 * ۲) product_views — یک ردیف به‌ازای هر «محصول × روز» (نه هر بازدید).
 *    ریل «پیشنهاد ویژه» صفحه‌ی اصلی باید پربازدیدهای دو روز اخیر را پیدا کند
 *    و با یک ردیف برای هر بازدید، جدول در چند هفته از کنترل خارج می‌شد؛
 *    همان الگویی که not_found_logs دارد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('views_count')->default(0)->after('rating_count');
        });

        Schema::create('product_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');

            // تاریخ میلادی روزِ بازدید؛ پنجره‌ی «دو روز اخیر» روی همین ستون
            // مقایسه می‌شود، پس date است نه timestamp.
            $table->date('viewed_on');

            $table->unsignedBigInteger('hits')->default(0);

            $table->timestamps();

            // شمارنده با update-then-insert بالا می‌رود؛ این ایندکس یکتا
            // تضمین می‌کند دو درخواست هم‌زمان دو ردیف برای یک روز نسازند.
            $table->unique(['product_id', 'viewed_on'], 'product_views_product_day_unique');

            // کوئری صفحه‌ی اصلی: جمع hits روی بازه‌ی تاریخ، مرتب‌شده نزولی.
            $table->index(['viewed_on', 'hits'], 'product_views_day_hits_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_views');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('views_count');
        });
    }
};
