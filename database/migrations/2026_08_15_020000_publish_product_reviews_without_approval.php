<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * انتشار نظرها بدون انتظارِ تأیید.
 *
 * تا امروز هر نظر در وضعیت pending می‌ماند و تا وقتی مدیر تأییدش نمی‌کرد
 * روی صفحه‌ی محصول دیده نمی‌شد. حالا نظر بی‌درنگ منتشر می‌شود و مدیر
 * فقط نظرهای نامناسب را رد (reject) می‌کند.
 *
 * دو کار اینجا انجام می‌شود:
 *   ۱) پیش‌فرض ستون status روی approved می‌رود تا هر مسیر درجی که وضعیت را
 *      صریح نمی‌دهد هم نظر را منتشر کند.
 *   ۲) نظرهای در انتظارِ باقی‌مانده تأیید می‌شوند؛ این‌ها نظرهای واقعی
 *      کاربران‌اند که فقط پشت صف تأیید مانده بودند.
 *
 * بعد از تأیید انبوه، rating_count و rating_avg محصولات دوباره حساب می‌شوند
 * چون این دو ستون از روی نظرهای تأییدشده denormalize شده‌اند. به‌روزرسانی با
 * Query Builder انجام می‌شود تا updated_at محصول — که در lastmod نقشه‌ی سایت
 * می‌آید — جابه‌جا نشود.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_reviews')) {
            return;
        }

        $this->setStatusDefault('approved');

        DB::table('product_reviews')->where('status', 'pending')->update(['status' => 'approved']);

        $this->recalculateProductRatings();
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_reviews')) {
            return;
        }

        // نظرهای تأییدشده به pending برنمی‌گردند: تشخیص اینکه کدام‌شان را مدیر
        // دستی تأیید کرده بود و کدام با این مایگریشن منتشر شده‌اند ممکن نیست.
        $this->setStatusDefault('pending');
    }

    /** پیش‌فرض ستون status. فقط روی MySQL معنا دارد؛ روی sqlite تست‌ها رد می‌شود. */
    private function setStatusDefault(string $default): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `product_reviews` MODIFY `status` VARCHAR(20) NOT NULL DEFAULT '{$default}'");
    }

    private function recalculateProductRatings(): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        $stats = DB::table('product_reviews')
            ->select('product_id', DB::raw('COUNT(*) as cnt'), DB::raw('AVG(rating) as avg_rating'))
            ->where('status', 'approved')
            ->groupBy('product_id')
            ->get();

        DB::table('products')->update(['rating_count' => 0, 'rating_avg' => null]);

        foreach ($stats as $row) {
            DB::table('products')->where('id', $row->product_id)->update([
                'rating_count' => (int) $row->cnt,
                'rating_avg'   => round((float) $row->avg_rating, 2),
            ]);
        }
    }
};
