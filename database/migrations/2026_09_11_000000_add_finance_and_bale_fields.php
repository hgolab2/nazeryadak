<?php

use App\Models\Product;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * حسابداری فروشگاه و ردگیری ارسال سفارش به بله.
 *
 * تا حالا هیچ‌جا معلوم نبود یک سفارش چقدر سود داشته: قیمت خرید قطعه ذخیره
 * نمی‌شد، هزینه‌های جانبی (پست، بسته‌بندی) جایی ثبت نمی‌شد و هزینه‌های
 * ثابت (سرور، پیامک) اصلا در سیستم نبودند. این مهاجرت چهار چیز می‌سازد:
 *
 *   - products.cost_price       قیمت خرید قطعه (اکسل، بدون ضریب فروش)
 *   - order_items.unit_cost     قیمت خرید در لحظه‌ی سفارش (بعدا عوض نمی‌شود)
 *   - orders.paid_at            لحظه‌ی تسویه؛ مبنای گزارش درآمد ماهانه
 *   - orders.bale_notified_at   آیا این سفارش به بله رسیده؟ (برای ارسال دوباره)
 *   - finance_transactions      دفتر درآمد/هزینه، با یا بی‌ارتباط با سفارش
 *
 * نسخه‌ی SQL دستی: database/sql/2026_09_11_finance_and_bale.sql
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('cost_price')->nullable()->after('price');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->integer('unit_cost')->nullable()->after('unit_price');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('paid_at')->nullable()->after('status');
            $table->timestamp('bale_notified_at')->nullable()->after('paid_at');
        });

        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10);            // income | expense
            $table->string('category', 40);
            $table->string('title', 190);
            $table->unsignedBigInteger('amount');  // تومان
            $table->unsignedBigInteger('order_id')->nullable();
            $table->date('occurred_on');
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['type', 'occurred_on']);
            $table->index('order_id');
        });

        /*
        | قیمت خرید محصولات فعلی از روی قیمت فروش بازسازی می‌شود: قیمت سایت
        | همان قیمت اکسل × ۱.۲ است و پاداش تصادفی ایمپورت هم روی قیمتِ پیش از
        | تخفیف نشسته. همان فرمولی است که Product::autoWholesalePrice به کار
        | می‌برد. از این به بعد ایمپورت خودش این ستون را پر می‌کند.
        */
        $markup = Product::RETAIL_MARKUP;
        DB::statement("
            UPDATE products
            SET cost_price = ROUND(
                COALESCE(NULLIF(compare_at_price, 0), price) * 100 / (100 + COALESCE(import_bonus_percent, 0)) / {$markup}
            )
            WHERE cost_price IS NULL AND price > 0
        ");

        // اقلام سفارش‌های قدیمی: قیمت خرید امروزِ همان محصول؛ اگر محصول
        // حذف شده، از قیمت فروش همان قلم برآورد می‌شود
        DB::statement("
            UPDATE order_items oi
            LEFT JOIN products p ON p.id = oi.product_id
            SET oi.unit_cost = COALESCE(p.cost_price, ROUND(oi.unit_price / {$markup}))
            WHERE oi.unit_cost IS NULL AND oi.unit_price > 0
        ");

        // سفارش‌های تسویه‌شده‌ی فعلی: تاریخ پرداخت از رکورد پرداخت، وگرنه
        // آخرین تغییر سفارش
        DB::statement("
            UPDATE orders o
            LEFT JOIN (
                SELECT order_id, MIN(paid_at) AS paid_at
                FROM payments
                WHERE status = 'paid' AND paid_at IS NOT NULL
                GROUP BY order_id
            ) pay ON pay.order_id = o.id
            SET o.paid_at = COALESCE(pay.paid_at, o.updated_at)
            WHERE o.paid_at IS NULL
              AND o.status IN ('paid', 'processing', 'shipped', 'delivered')
        ");

        // سفارش‌های قدیمی «ارسال‌شده» فرض می‌شوند؛ وگرنه اولین اجرای ارسال
        // دوباره، همه‌ی تاریخچه را در بله می‌ریخت
        DB::table('orders')->whereNull('bale_notified_at')->update(['bale_notified_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_transactions');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['paid_at', 'bale_notified_at']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }
};
