<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->withoutStrictZeroDateMode(function () {
            Schema::table('article1', function (Blueprint $table) {
                if (!Schema::hasColumn('article1', 'seo_title')) {
                    $table->string('seo_title')->nullable()->after('keywords');
                }
                if (!Schema::hasColumn('article1', 'seo_description')) {
                    $table->string('seo_description', 500)->nullable()->after('seo_title');
                }
                if (!Schema::hasColumn('article1', 'focus_keyword')) {
                    $table->string('focus_keyword')->nullable()->after('seo_description');
                }
                if (!Schema::hasColumn('article1', 'canonical_url')) {
                    $table->string('canonical_url', 500)->nullable()->after('focus_keyword');
                }
                if (!Schema::hasColumn('article1', 'robots_index')) {
                    $table->boolean('robots_index')->default(true)->after('canonical_url');
                }
                if (!Schema::hasColumn('article1', 'robots_follow')) {
                    $table->boolean('robots_follow')->default(true)->after('robots_index');
                }
            });
        });
    }

    public function down(): void
    {
        $this->withoutStrictZeroDateMode(function () {
            Schema::table('article1', function (Blueprint $table) {
                foreach (['seo_title', 'seo_description', 'focus_keyword', 'canonical_url', 'robots_index', 'robots_follow'] as $column) {
                    if (Schema::hasColumn('article1', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        });
    }

    private function withoutStrictZeroDateMode(callable $callback): void
    {
        $original = DB::selectOne('SELECT @@SESSION.sql_mode AS mode')->mode ?? '';
        $relaxed = collect(explode(',', $original))
            ->reject(fn ($mode) => in_array($mode, ['STRICT_TRANS_TABLES', 'STRICT_ALL_TABLES', 'NO_ZERO_DATE', 'NO_ZERO_IN_DATE'], true))
            ->implode(',');

        DB::statement('SET SESSION sql_mode = ?', [$relaxed]);
        try {
            $callback();
        } finally {
            DB::statement('SET SESSION sql_mode = ?', [$original]);
        }
    }
};