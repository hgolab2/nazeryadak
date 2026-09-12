<?php

namespace App\Console\Commands;

use App\Models\SeoKeyword;
use App\Support\PageAudit;
use Illuminate\Console\Command;

/**
 * ممیزی صفحه‌ی هدفِ هر کلیدواژه: عبارت در title/H1/description هست؟
 * صفحه ۲۰۰ می‌دهد؟ ایندکس‌پذیر است؟
 *
 * هفتگی از زمان‌بند اجرا می‌شود (routes/console.php)؛ مدیر هم می‌تواند
 * از پنل برای یک عبارت یا همه‌شان اجرایش کند.
 */
class SeoKeywordsCheck extends Command
{
    protected $signature = 'seo:keywords-check
                            {--id=* : فقط این شناسه‌ها}
                            {--stale=0 : فقط آن‌هایی که بیش از این تعداد روز بررسی نشده‌اند (۰ = همه)}';

    protected $description = 'بررسی صفحات هدف کلیدواژه‌های سئو (title، H1، description، ایندکس‌پذیری)';

    public function handle(): int
    {
        $query = SeoKeyword::query()->whereNotNull('target_url')->where('target_url', '!=', '');

        if ($ids = array_filter(array_map('intval', (array) $this->option('id')))) {
            $query->whereIn('id', $ids);
        }

        if (($stale = (int) $this->option('stale')) > 0) {
            $query->where(function ($q) use ($stale) {
                $q->whereNull('checked_at')->orWhere('checked_at', '<', now()->subDays($stale));
            });
        }

        $keywords = $query->orderBy('priority')->orderBy('id')->get();
        if ($keywords->isEmpty()) {
            $this->info('کلیدواژه‌ای برای بررسی نیست.');

            return self::SUCCESS;
        }

        $checked = 0;
        $failed  = 0;

        foreach ($keywords as $keyword) {
            $result = static::audit($keyword);
            $checked++;

            if ($result['error'] || $result['http_status'] !== 200) {
                $failed++;
                $this->warn(sprintf('  ✗ %s → %s (%s)', $keyword->keyword, $keyword->target_url, $result['error'] ?: 'HTTP ' . $result['http_status']));
                continue;
            }

            $this->line(sprintf(
                '  %s %s → title:%s h1:%s desc:%s body:%d',
                $keyword->score() >= 80 ? '✓' : '•',
                $keyword->keyword,
                $result['in_title'] ? '✓' : '✗',
                $result['in_h1'] ? '✓' : '✗',
                $result['in_description'] ? '✓' : '✗',
                $result['body_hits'],
            ));
        }

        $this->info("بررسی شد: {$checked}، ناموفق: {$failed}");

        return self::SUCCESS;
    }

    /**
     * ممیزی یک کلیدواژه و ذخیره‌ی نتیجه روی همان ردیف.
     *
     * استاتیک است تا کنترلر پنل هم بدون راه‌اندازی Artisan همین را صدا بزند.
     */
    public static function audit(SeoKeyword $keyword): array
    {
        $result = PageAudit::run((string) $keyword->target_url, $keyword->keyword);

        $keyword->forceFill([
            'http_status'      => $result['http_status'],
            'page_title'       => $result['page_title'],
            'page_h1'          => $result['page_h1'],
            'page_description' => $result['page_description'],
            'in_title'         => $result['in_title'],
            'in_h1'            => $result['in_h1'],
            'in_description'   => $result['in_description'],
            'body_hits'        => $result['body_hits'],
            'is_indexable'     => $result['is_indexable'],
            'checked_at'       => now(),
        ])->save();

        return $result;
    }
}
