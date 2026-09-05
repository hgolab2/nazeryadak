<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * درصد پاداش تصادفی ایمپورت.
 *
 * ایمپورت روی قیمت اکسل ۲۰٪ قطعی می‌گذارد، بعد بین ۰ تا ۵ درصد به‌صورت
 * تصادفی به همان مبلغ اضافه می‌کند و دقیقا همان مبلغ را به‌عنوان تخفیف کم
 * می‌کند؛ پس قیمت پرداختی همان ۲۰٪ می‌ماند و روی کارت محصول تخفیف دیده
 * می‌شود. این ستون درصدِ به‌کاررفته را نگه می‌دارد تا قیمت عمده (که از روی
 * قیمت پیش از تخفیف بازسازی می‌شود) بتواند آن را از مبنا بردارد.
 */
return new class extends Migration
{
    public function up(): void
    {
        $columns = collect(DB::select('SHOW COLUMNS FROM products'))->pluck('Field')->all();

        if (!in_array('import_bonus_percent', $columns, true)) {
            DB::statement('ALTER TABLE products ADD import_bonus_percent TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER compare_at_price');
        }
    }

    public function down(): void
    {
        $columns = collect(DB::select('SHOW COLUMNS FROM products'))->pluck('Field')->all();

        if (in_array('import_bonus_percent', $columns, true)) {
            DB::statement('ALTER TABLE products DROP COLUMN import_bonus_percent');
        }
    }
};
