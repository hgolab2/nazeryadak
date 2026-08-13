<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * regular_price از ایمپورتر با «قیمت میانگین/خرید» پر می‌شود و کمتر از قیمت
 * فروش است، ولی رابط کاربری آن را به‌عنوان «قیمت قبل از تخفیف» خط می‌زد و
 * محاسبه‌ی تخفیف هم بر مبنای همان انجام می‌شد؛ نتیجه‌اش سقوط قیمت فروش بود.
 * این ستون، قیمت قبل از تخفیف را جدا نگه می‌دارد تا قیمت خرید دست‌نخورده بماند.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('compare_at_price')->nullable()->after('regular_price');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('compare_at_price');
        });
    }
};
