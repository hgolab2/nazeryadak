<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * جدول products (۹۱۱۷ ردیف) و articleincategory (۲۳۶٬۵۲۱ ردیف) هیچ ایندکسی
 * جز کلید اصلی نداشتند.
 *
 * نتیجه: هر جستجو در فروشگاه کل جدول را اسکن می‌کرد، و صفحه‌ی اول برای
 * نمایش ۴ مطلب وبلاگ حدود ۱.۷ ثانیه صرف join روی جدول ۲۳۶ هزارتایی می‌کرد.
 */
return new class extends Migration
{
    /** @var array<string,array<string,string[]>> */
    private array $indexes = [
        'products' => [
            'products_is_active_index' => ['is_active'],
            'products_sku_index'       => ['sku'],
            'products_price_index'     => ['price'],
            'products_active_id_index' => ['is_active', 'id'],
        ],
        'articleincategory' => [
            'aic_articleid_index'  => ['articleid'],
            'aic_categoryid_index' => ['categoryid'],
            'aic_cat_site_index'   => ['categoryid', 'siteid'],
        ],
        'category' => [
            'category_parent_index' => ['parent_id', 'deleted'],
        ],
        'article1' => [
            'article1_showdate_index' => ['showdate'],
        ],
        'product_in_category' => [
            'pic_product_index'  => ['product_id'],
            'pic_category_index' => ['category_id'],
        ],
        'order_items' => [
            'order_items_order_index' => ['order_id'],
        ],
        'orders' => [
            'orders_customer_status_index' => ['customer_id', 'status'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $definitions) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach ($definitions as $name => $columns) {
                foreach ($columns as $column) {
                    if (!Schema::hasColumn($table, $column)) {
                        continue 2;
                    }
                }

                if ($this->indexExists($table, $name)) {
                    continue;
                }

                $cols = implode('`, `', $columns);

                try {
                    DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$name}` (`{$cols}`)");
                } catch (\Throwable $e) {
                    // بعضی جدول‌های قدیمی مقدار '0000-00-00' در ستون تاریخ دارند و
                    // MySQL 8 در حالت strict اجازه‌ی بازسازی جدول را نمی‌دهد.
                    // sql_mode فقط برای همین اتصال شل می‌شود؛ داده‌ای تغییر نمی‌کند.
                    $mode = DB::selectOne('SELECT @@SESSION.sql_mode AS m')->m;

                    try {
                        DB::statement("SET SESSION sql_mode = ''");
                        DB::statement("ALTER TABLE `{$table}` ADD INDEX `{$name}` (`{$cols}`)");
                    } catch (\Throwable $inner) {
                        echo "  ! ایندکس {$name} روی {$table} ساخته نشد: " . $inner->getMessage() . "\n";
                    } finally {
                        DB::statement('SET SESSION sql_mode = ?', [$mode]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $definitions) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            foreach (array_keys($definitions) as $name) {
                if ($this->indexExists($table, $name)) {
                    DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$name}`");
                }
            }
        }
    }

    private function indexExists(string $table, string $name): bool
    {
        return count(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$name])) > 0;
    }
};
