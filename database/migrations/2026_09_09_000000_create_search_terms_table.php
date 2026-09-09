<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * شمارش عبارت‌هایی که کاربرها جستجو می‌کنند.
 *
 * دو مصرف دارد و هر دو در یک جدول جا می‌شوند:
 *
 * ۱) پیشنهاد «جستجوهای پرتکرار» به بقیه‌ی کاربرها — تا امروز شش عبارت
 *    دستیِ ثابت در قالب نوشته شده بود که هیچ ربطی به آنچه واقعا دنبالش
 *    می‌گردند نداشت.
 *
 * ۲) دیدنِ عبارت‌های بی‌نتیجه، که مستقیم‌ترین فهرستِ «چه قطعه‌ای را باید
 *    بیاوریم» است.
 *
 * یک ردیف به‌ازای هر عبارت، نه هر جستجو: عبارتِ یکسان فقط شمارنده‌اش بالا
 * می‌رود. همان الگوی product_views و not_found_logs، به همان دلیل.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_terms', function (Blueprint $table) {
            $table->id();

            // شکل یکسان‌سازی‌شده (Product::normalizeTerm)؛ «فيلتر روغن» عربی و
            // «فیلتر روغن» فارسی نباید دو ردیف جدا بسازند. طولش کوتاه است تا
            // ایندکس یکتا در utf8mb4 از سقف ۳۰۷۲ بایتی InnoDB رد نشود.
            $table->string('term', 120)->unique();

            // همان چیزی که کاربر تایپ کرده؛ چیزی که به بقیه نشان می‌دهیم باید
            // خوانا باشد نه شکلِ یکسان‌سازی‌شده.
            $table->string('display_term', 160);

            $table->unsignedBigInteger('hits')->default(0);

            // آخرین تعداد نتیجه‌ی همین عبارت. عبارتِ بی‌نتیجه هرگز به بقیه
            // پیشنهاد نمی‌شود؛ همین ستون، فیلترِ طبیعیِ عبارت‌های بی‌معنا هم هست.
            $table->unsignedInteger('results_count')->default(0);

            $table->timestamp('last_searched_at')->nullable();
            $table->timestamps();

            // کوئری پیشنهاد: پرتکرارترین‌های نتیجه‌دار، مرتب نزولی.
            $table->index(['results_count', 'hits'], 'search_terms_results_hits_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_terms');
    }
};
