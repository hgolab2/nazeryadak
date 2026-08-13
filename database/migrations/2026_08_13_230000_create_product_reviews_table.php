<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * نظر و امتیاز محصول.
 *
 * انگیزه‌ی سئویی: اسکیمای Product سایت کامل است ولی aggregateRating ندارد،
 * و بدون آن ستاره‌های نتیجه‌ی گوگل نمایش داده نمی‌شوند. کنارش، نظر کاربران
 * محتوای یکتا برای صفحه‌ی محصول می‌سازد — چیزی که ۶۲٪ محصولات کم دارند.
 *
 * میانگین و تعداد روی جدول products ذخیره (denormalize) می‌شوند تا فهرست
 * محصولات برای نمایش ستاره، به ازای هر کارت یک کوئری جدا نزند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('name', 100);
            $table->unsignedTinyInteger('rating');
            $table->string('title')->nullable();
            $table->text('comment');
            // نظر تازه منتشر نمی‌شود؛ اسپم روی صفحه‌ی محصول به سئو آسیب می‌زند
            $table->string('status', 20)->default('pending');
            $table->boolean('is_buyer')->default(false);
            $table->string('ip', 45)->nullable();
            $table->timestamps();

            $table->index(['product_id', 'status']);
            $table->index('status');
        });

        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'rating_avg')) {
                $table->decimal('rating_avg', 3, 2)->nullable()->after('robots_follow');
            }
            if (! Schema::hasColumn('products', 'rating_count')) {
                $table->unsignedInteger('rating_count')->default(0)->after('rating_avg');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach (['rating_avg', 'rating_count'] as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('product_reviews');
    }
};
