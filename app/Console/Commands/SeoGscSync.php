<?php

namespace App\Console\Commands;

use App\Models\GscQuery;
use App\Models\SeoKeyword;
use App\Support\SearchConsole;
use Illuminate\Console\Command;

/**
 * همگام‌سازی گزارش Performance سرچ کنسول با جدول gsc_queries و به‌روزکردن
 * آمار کلیدواژه‌های هدف.
 *
 * روزانه از زمان‌بند اجرا می‌شود؛ اگر سرچ کنسول پیکربندی نشده باشد بی‌صدا
 * رد می‌شود تا لاگ زمان‌بند پر از خطا نشود. مدیر می‌تواند به‌جای API از پنل
 * CSV آپلود کند؛ آن مسیر هم در پایان همین applyToKeywords را صدا می‌زند.
 */
class SeoGscSync extends Command
{
    protected $signature = 'seo:gsc-sync {--days= : بازه‌ی گزارش به روز (پیش‌فرض از config)}';

    protected $description = 'دریافت عبارت‌های جستجو از Google Search Console';

    public function handle(): int
    {
        if (! SearchConsole::isConfigured()) {
            $this->line('سرچ کنسول پیکربندی نشده (SEO_GSC_CREDENTIALS / SEO_GSC_SITE_URL)؛ رد شد.');

            return self::SUCCESS;
        }

        $days = (int) ($this->option('days') ?: config('seo.search_console.days', 28));

        try {
            $rows = SearchConsole::fetchQueries($days);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $saved = static::store($rows, $days);
        $this->info("ذخیره شد: {$saved} ردیف (عبارت × صفحه) برای {$days} روز اخیر.");

        $updated = static::applyToKeywords();
        $this->info("آمار {$updated} کلیدواژه‌ی هدف به‌روز شد.");

        return self::SUCCESS;
    }

    /**
     * ذخیره‌ی ردیف‌های خام. جدول قبل از ذخیره خالی می‌شود، چون هر همگام‌سازی
     * عکسِ کاملِ بازه است و ردیف‌هایی که دیگر در گزارش نیستند (عبارت‌های
     * مرده) نباید بمانند. آپلود CSV این کار را نمی‌کند (پارامتر truncate)،
     * چون فایل CSV سرچ کنسول به ۱۰۰۰ ردیف محدود است و ممکن است چند فایل
     * پشت هم آپلود شود.
     *
     * @param  array<int, array{query:string,page:string,clicks:int,impressions:int,ctr:float,position:float}>  $rows
     */
    public static function store(array $rows, int $days, bool $truncate = true): int
    {
        if ($truncate) {
            GscQuery::query()->delete();
        }

        $now   = now();
        $saved = 0;
        foreach ($rows as $row) {
            if (GscQuery::upsertRow($row, $days, $now)) {
                $saved++;
            }
        }

        return $saved;
    }

    /** آمار سرچ کنسول را روی هر کلیدواژه‌ی هدف می‌نشاند. */
    public static function applyToKeywords(): int
    {
        $updated = 0;
        $now = now();

        SeoKeyword::query()->chunkById(200, function ($keywords) use (&$updated, $now) {
            foreach ($keywords as $keyword) {
                $summary = GscQuery::summaryFor($keyword->term);

                $keyword->forceFill([
                    'gsc_clicks'      => $summary['clicks'] ?? null,
                    'gsc_impressions' => $summary['impressions'] ?? null,
                    'gsc_position'    => $summary['position'] ?? null,
                    'gsc_ctr'         => $summary['ctr'] ?? null,
                    'gsc_top_page'    => $summary['top_page'] ?? null,
                    'gsc_synced_at'   => $now,
                ])->save();

                $updated++;
            }
        });

        return $updated;
    }
}
