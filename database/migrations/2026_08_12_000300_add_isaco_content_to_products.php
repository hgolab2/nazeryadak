<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $columns = collect(DB::select('SHOW COLUMNS FROM products'))->pluck('Field')->all();

        if (!in_array('short_description', $columns, true)) {
            DB::statement('ALTER TABLE products ADD short_description TEXT NULL AFTER description');
        }
        if (!in_array('isaco_code', $columns, true)) {
            DB::statement('ALTER TABLE products ADD isaco_code VARCHAR(32) NULL AFTER sku');
        }
        if (!in_array('isaco_url', $columns, true)) {
            DB::statement('ALTER TABLE products ADD isaco_url VARCHAR(512) NULL AFTER isaco_code');
        }

        if (!DB::select("SHOW TABLES LIKE 'product_images'")) {
            DB::statement("CREATE TABLE product_images (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                product_id BIGINT UNSIGNED NOT NULL,
                path VARCHAR(512) NOT NULL,
                alt VARCHAR(255) NULL,
                is_primary TINYINT(1) NOT NULL DEFAULT 0,
                sort_order INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP NULL DEFAULT NULL,
                updated_at TIMESTAMP NULL DEFAULT NULL,
                PRIMARY KEY (id),
                KEY product_images_product_id_index (product_id),
                KEY product_images_primary_index (product_id, is_primary)
            ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        }
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS product_images');

        $columns = collect(DB::select('SHOW COLUMNS FROM products'))->pluck('Field')->all();
        foreach (['isaco_url', 'isaco_code', 'short_description'] as $column) {
            if (in_array($column, $columns, true)) {
                DB::statement("ALTER TABLE products DROP COLUMN {$column}");
            }
        }
    }
};
