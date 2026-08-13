<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * سئوی دستیِ صفحات فرود.
 *
 * دسته‌بندی‌ها یک enum در کد هستند و مدل‌های خودرو از ستون products.car_model
 * می‌آیند؛ هیچ‌کدام ردیف دیتابیس ندارند که بشود سئویشان را رویش ذخیره کرد.
 * این جدول همان لایه‌ی گم‌شده است: با کلیدِ (نوع، اسلاگ) به هر صفحه‌ی فرود
 * وصل می‌شود، بدون اینکه لازم باشد enum یا محصولات تغییر کنند.
 *
 * type = category      → /shop/{slug}
 * type = car           → /car/{slug}
 * type = car_category  → /car/{car}/{category}  (اسلاگ: "{car}/{category}")
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_terms', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('slug', 160);
            $table->string('name')->nullable();

            // محتوای صفحه‌ی فرود؛ بدون متن یکتا، صفحه فقط یک فهرست فیلترشده
            // است و گوگل آن را نسخه‌ی تکراری فروشگاه می‌بیند.
            $table->string('heading')->nullable();
            $table->text('intro')->nullable();
            $table->text('body')->nullable();

            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->string('focus_keyword')->nullable();
            $table->boolean('robots_index')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['type', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_terms');
    }
};
