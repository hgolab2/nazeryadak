<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * کلیدواژه‌های هدف سئو و داده‌ی خامِ سرچ کنسول.
 *
 * تا امروز هیچ‌جا معلوم نبود «برای کدام عبارت‌ها می‌جنگیم» و هر عبارت قرار
 * است به کدام صفحه برسد. نتیجه‌اش این بود که focus_keyword محصول‌ها و
 * صفحات فرود بدون هیچ بازخوردی پر می‌شد: نه کسی می‌دانست عبارت واقعا در
 * title و H1 نشسته، نه اینکه گوگل برایش رتبه‌ای داده یا نه.
 *
 *   - seo_keywords   عبارت هدف ← صفحه‌ی هدف، به‌همراه نتیجه‌ی آخرین ممیزیِ
 *                    خودکار (عبارت در title/H1/description هست؟) و آخرین
 *                    آمار سرچ کنسول برای همان عبارت.
 *   - gsc_queries    داده‌ی خام Search Console (عبارت × صفحه) که یا از API
 *                    می‌آید یا از CSV خروجیِ خودِ سرچ کنسول. از این جدول
 *                    عبارت‌های مهم «کشف» می‌شوند و به seo_keywords می‌روند.
 *
 * نسخه‌ی SQL دستی: database/sql/2026_09_12_seo_keywords.sql
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_keywords', function (Blueprint $table) {
            $table->id();

            // شکل خوانا و شکل یکسان‌سازی‌شده (Product::normalizeTerm)؛ دومی
            // یکتاست تا «فيلتر روغن» و «فیلتر روغن» دو ردیف نسازند.
            $table->string('keyword', 160);
            $table->string('term', 120)->unique();

            // مسیر نسبی صفحه‌ی هدف؛ مثل /part/لنت-ترمز/پژو-206
            $table->string('target_url', 500)->nullable();

            // ۱ = اصلی، ۲ = مهم، ۳ = فرعی. برای مرتب‌سازی فهرست.
            $table->unsignedTinyInteger('priority')->default(2);
            $table->text('note')->nullable();

            /* نتیجه‌ی آخرین ممیزی صفحه‌ی هدف */
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('page_title', 300)->nullable();
            $table->string('page_h1', 300)->nullable();
            $table->string('page_description', 500)->nullable();
            $table->boolean('in_title')->nullable();
            $table->boolean('in_h1')->nullable();
            $table->boolean('in_description')->nullable();
            $table->unsignedSmallInteger('body_hits')->nullable();
            $table->boolean('is_indexable')->nullable();
            $table->timestamp('checked_at')->nullable();

            /* آخرین آمار سرچ کنسول برای همین عبارت (جمع همه‌ی صفحه‌ها) */
            $table->unsignedInteger('gsc_clicks')->nullable();
            $table->unsignedInteger('gsc_impressions')->nullable();
            $table->decimal('gsc_position', 6, 2)->nullable();
            $table->decimal('gsc_ctr', 6, 4)->nullable();
            // صفحه‌ای که گوگل عملا برای این عبارت نشان می‌دهد؛ اگر با
            // target_url فرق داشته باشد یعنی دو صفحه با هم رقابت می‌کنند.
            $table->string('gsc_top_page', 500)->nullable();
            $table->timestamp('gsc_synced_at')->nullable();

            $table->timestamps();

            $table->index(['priority', 'gsc_impressions']);
        });

        Schema::create('gsc_queries', function (Blueprint $table) {
            $table->id();
            $table->string('query', 200);
            $table->string('term', 120);
            // مسیر نسبی؛ خالی یعنی ردیفِ جمعِ همه‌ی صفحه‌ها (خروجی CSV بدون بعد صفحه)
            $table->string('page', 500)->default('');
            $table->unsignedInteger('clicks')->default(0);
            $table->unsignedInteger('impressions')->default(0);
            $table->decimal('ctr', 6, 4)->default(0);
            $table->decimal('position', 6, 2)->default(0);
            // بازه‌ی گزارش (روز) و زمان همگام‌سازی؛ برای اینکه معلوم باشد این
            // عدد مال کدام دوره است.
            $table->unsignedSmallInteger('period_days')->default(28);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            // یک عبارت × یک صفحه فقط یک ردیف؛ همگام‌سازیِ بعدی بازنویسی می‌کند.
            // page طولانی است و در ایندکس یکتا جا نمی‌شود، پس hash آن ایندکس می‌شود.
            $table->char('page_hash', 32);
            $table->unique(['term', 'page_hash']);
            $table->index('impressions');
            $table->index('clicks');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gsc_queries');
        Schema::dropIfExists('seo_keywords');
    }
};
