<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * فیلدهای فروش عمده.
 *
 * هر سه ستون nullable/پیش‌فرض‌دارند و خالی‌بودنشان یعنی «خودکار حساب کن»:
 * آستانه‌ی تعداد و قیمت عمده از روی قیمت خود محصول و آستانه‌ی ارسال رایگان
 * کشوری به‌دست می‌آید (Product::wholesaleMinQty و Product::wholesalePrice).
 * پس برای محصولات فعلی لازم نیست چیزی پر شود.
 */
return new class extends Migration
{
    public function up(): void
    {
        $columns = collect(DB::select('SHOW COLUMNS FROM products'))->pluck('Field')->all();

        if (!in_array('wholesale_min_qty', $columns, true)) {
            DB::statement('ALTER TABLE products ADD wholesale_min_qty INT UNSIGNED NULL AFTER compare_at_price');
        }

        if (!in_array('wholesale_price', $columns, true)) {
            DB::statement('ALTER TABLE products ADD wholesale_price INT UNSIGNED NULL AFTER wholesale_min_qty');
        }

        if (!in_array('wholesale_enabled', $columns, true)) {
            DB::statement('ALTER TABLE products ADD wholesale_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER wholesale_price');
        }
    }

    public function down(): void
    {
        $columns = collect(DB::select('SHOW COLUMNS FROM products'))->pluck('Field')->all();

        foreach (['wholesale_enabled', 'wholesale_price', 'wholesale_min_qty'] as $column) {
            if (in_array($column, $columns, true)) {
                DB::statement("ALTER TABLE products DROP COLUMN {$column}");
            }
        }
    }
};
