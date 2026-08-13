<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * فیلدهای سئوی دستی محصول — همان چیزی که مقالات با مهاجرت
 * add_rank_math_fields_to_article1 گرفتند.
 *
 * تا پیش از این، عنوان و توضیحات متای صفحه‌ی محصول فقط از روی نام و کد فنی
 * ساخته می‌شد و مدیر هیچ راهی برای بازنویسی آن نداشت. ستون‌ها nullable
 * می‌مانند تا محصولات موجود دقیقا مثل قبل رفتار کنند و تولید خودکار فقط
 * وقتی کنار برود که مدیر مقدار وارد کرده باشد.
 */
return new class extends Migration
{
    private const COLUMNS = [
        'seo_title', 'seo_description', 'focus_keyword',
        'canonical_url', 'robots_index', 'robots_follow',
    ];

    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'seo_title')) {
                $table->string('seo_title')->nullable()->after('car_model');
            }
            if (!Schema::hasColumn('products', 'seo_description')) {
                $table->string('seo_description', 500)->nullable()->after('seo_title');
            }
            if (!Schema::hasColumn('products', 'focus_keyword')) {
                $table->string('focus_keyword')->nullable()->after('seo_description');
            }
            if (!Schema::hasColumn('products', 'canonical_url')) {
                $table->string('canonical_url', 500)->nullable()->after('focus_keyword');
            }
            if (!Schema::hasColumn('products', 'robots_index')) {
                $table->boolean('robots_index')->default(true)->after('canonical_url');
            }
            if (!Schema::hasColumn('products', 'robots_follow')) {
                $table->boolean('robots_follow')->default(true)->after('robots_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach (self::COLUMNS as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
