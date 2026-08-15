<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * امتیازِ مجموعه‌بندی‌شده روی نظر محصول.
 *
 * تا امروز هر نظر فقط یک عدد ۱ تا ۵ داشت. «۳ ستاره» به خریدار بعدی نمی‌گوید
 * مشکل از کیفیت ساخت بوده یا از قیمت؛ برای قطعه‌ی یدکی که خریدار دنبال
 * تناسبش با خودرو و دوامش می‌گردد، همین تفکیک تصمیم را عوض می‌کند.
 *
 * ستون‌ها به‌ازای هر معیار ساخته نشدند: فهرست معیارها در ProductReview::CRITERIA
 * است و با اضافه‌شدن یک معیار نباید مایگریشن تازه لازم شود. مقدارها فقط
 * خوانده و میانگین‌گیری می‌شوند و هیچ‌وقت شرط کوئری نیستند، پس یک ستون JSON
 * دقیقا همان چیزی است که لازم داریم.
 *
 * میانگینِ هر معیار روی products ذخیره (denormalize) نمی‌شود چون برخلاف
 * rating_avg، فقط در صفحه‌ی خودِ محصول لازم می‌شود — جایی که نظرهای
 * تأییدشده همین حالا با محصول لود شده‌اند و میانگین بدون کوئری اضافه
 * از روی همان مجموعه حساب می‌شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('product_reviews', 'criteria')) {
                $table->json('criteria')->nullable()->after('rating');
            }
        });
    }

    public function down(): void
    {
        Schema::table('product_reviews', function (Blueprint $table) {
            if (Schema::hasColumn('product_reviews', 'criteria')) {
                $table->dropColumn('criteria');
            }
        });
    }
};
