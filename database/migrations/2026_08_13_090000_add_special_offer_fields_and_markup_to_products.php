<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $columns = collect(DB::select('SHOW COLUMNS FROM products'))->pluck('Field')->all();

        if (!in_array('discount_percent', $columns, true)) {
            DB::statement('ALTER TABLE products ADD discount_percent TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER regular_price');
        }

        if (!in_array('is_special_offer', $columns, true)) {
            DB::statement('ALTER TABLE products ADD is_special_offer TINYINT(1) NOT NULL DEFAULT 0 AFTER discount_percent');
        }

        DB::table('products')
            ->where(function ($query) {
                $query->where('price', '>', 0)
                    ->orWhere('regular_price', '>', 0);
            })
            ->update([
                'price' => DB::raw('CASE WHEN price > 0 THEN ROUND(price * 1.2) ELSE price END'),
                'regular_price' => DB::raw('CASE WHEN regular_price > 0 THEN ROUND(regular_price * 1.2) ELSE regular_price END'),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        $columns = collect(DB::select('SHOW COLUMNS FROM products'))->pluck('Field')->all();

        foreach (['is_special_offer', 'discount_percent'] as $column) {
            if (in_array($column, $columns, true)) {
                DB::statement("ALTER TABLE products DROP COLUMN {$column}");
            }
        }
    }
};
