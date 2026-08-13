<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::select("SHOW INDEX FROM products WHERE Key_name = 'products_sku_unique'");
        if (!$exists) {
            DB::statement('ALTER TABLE products ADD UNIQUE KEY products_sku_unique (sku)');
        }
    }

    public function down(): void
    {
        $exists = DB::select("SHOW INDEX FROM products WHERE Key_name = 'products_sku_unique'");
        if ($exists) {
            DB::statement('ALTER TABLE products DROP INDEX products_sku_unique');
        }
    }
};
