<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * سیاهه‌ی «چه محصولی چه روزی در ریل پیشنهاد ویژه نشست».
 *
 * بدون این جدول، ریل عملا هیچ‌وقت عوض نمی‌شد: ترتیبش فقط از مجموع بازدید
 * و درصد تخفیف می‌آمد و همان چند محصولِ همیشگی هفته‌ها بالای صفحه می‌ماندند.
 * با داشتنِ ترکیبِ دیروز می‌شود پرسید «کدام محصولِ پربازدیدِ امروز دیروز
 * اینجا نبود» و جای تازه‌ها را باز کرد.
 *
 * یک ردیف به‌ازای «محصول × روز» — همان الگوی product_views، به همان دلیل:
 * جدول نباید به‌ازای هر بار رندر صفحه‌ی اصلی رشد کند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('special_offer_features', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');

            // روزی که این محصول در ریل دیده شد؛ مقایسه‌ی «دیروز» روی همین
            // ستون انجام می‌شود، پس date است نه timestamp.
            $table->date('featured_on');

            $table->timestamps();

            // ثبت با insertOrIgnore انجام می‌شود؛ این ایندکس یکتا تضمین
            // می‌کند رندرهای هم‌زمانِ صفحه‌ی اصلی ردیف تکراری نسازند.
            $table->unique(['product_id', 'featured_on'], 'special_offer_features_day_unique');

            $table->index('featured_on', 'special_offer_features_day_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('special_offer_features');
    }
};
