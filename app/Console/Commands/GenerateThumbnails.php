<?php

namespace App\Console\Commands;

use App\Services\ThumbnailService;
use Illuminate\Console\Command;

/**
 * پیش‌ساخت بندانگشتی‌ها.
 *
 * لازم نیست: هر بندانگشتی در اولین درخواستش خودکار ساخته می‌شود. ولی
 * «اولین بازدیدکننده» هزینه‌اش را می‌دهد، پس بعد از آپلود انبوه یا اولین
 * راه‌اندازی بهتر است یک‌بار این دستور اجرا شود تا همه از پیش آماده باشند.
 *
 * اجرای دوباره بی‌خطر است: فایل‌های موجود و به‌روز دوباره ساخته نمی‌شوند.
 */
class GenerateThumbnails extends Command
{
    protected $signature = 'images:thumbs
        {--dir=* : پوشه‌های هدف نسبت به public (پیش‌فرض: upload و assets/images)}
        {--size=* : عرض‌های هدف؛ پیش‌فرض همه‌ی اندازه‌های تعریف‌شده}
        {--force : ساخت دوباره حتی اگر نسخه‌ی به‌روز موجود باشد}';

    protected $description = 'ساخت نسخه‌ی بندانگشتی تصاویر در اندازه‌ی واقعی نمایش';

    public function handle(): int
    {
        if (! function_exists('imagewebp')) {
            $this->error('افزونه‌ی GD بدون پشتیبانی WebP کامپایل شده است.');
            return self::FAILURE;
        }

        // assets/images هم لازم است: آیکون خودروهای صفحه‌ی اصلی آنجاست.
        $dirs = $this->option('dir') ?: ['upload', 'assets/images'];

        $sizes = array_map('intval', $this->option('size')) ?: ThumbnailService::SIZES;
        $invalid = array_diff($sizes, ThumbnailService::SIZES);
        if ($invalid) {
            $this->error('عرض نامعتبر: ' . implode(', ', $invalid)
                . ' — مقادیر مجاز: ' . implode(', ', ThumbnailService::SIZES));
            return self::FAILURE;
        }

        $made = 0;
        $skipped = 0;
        $failed = 0;
        $sourceBytes = 0;
        $thumbBytes = 0;

        foreach ($dirs as $dir) {
            $root = public_path(trim($dir, '/\\'));

            if (! is_dir($root)) {
                $this->warn("پوشه پیدا نشد: {$dir}");
                continue;
            }

            $this->info("در حال پردازش: {$dir}");

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                if (! in_array(strtolower($file->getExtension()), ['jpg', 'jpeg', 'png'], true)) {
                    continue;
                }

                $relative = trim($dir, '/\\') . '/' . str_replace(
                    '\\', '/', substr($file->getPathname(), strlen($root) + 1)
                );

                foreach ($sizes as $size) {
                    $target = public_path(ThumbnailService::CACHE_DIR . "/{$size}/{$relative}.webp");

                    if (! $this->option('force')
                        && is_file($target)
                        && filemtime($target) >= $file->getMTime()) {
                        $skipped++;
                        continue;
                    }

                    if ($this->option('force') && is_file($target)) {
                        @unlink($target);
                    }

                    $built = ThumbnailService::generate($relative, $size);

                    if (! $built) {
                        $failed++;
                        continue;
                    }

                    $sourceBytes += $file->getSize();
                    $thumbBytes += filesize($built);
                    $made++;

                    if ($made % 500 === 0) {
                        $this->line("  {$made} بندانگشتی ساخته شد...");
                    }
                }
            }
        }

        $this->newLine();
        $this->info('ساخت بندانگشتی‌ها انجام شد');
        $this->table(['شرح', 'مقدار'], [
            ['ساخته‌شده', number_format($made)],
            ['از قبل موجود', number_format($skipped)],
            ['ناموفق', number_format($failed)],
            ['حجم منبع', $this->human($sourceBytes)],
            ['حجم بندانگشتی', $this->human($thumbBytes)],
            ['نسبت', $sourceBytes > 0 ? sprintf('%.0f%% کوچک‌تر', (1 - $thumbBytes / $sourceBytes) * 100) : '-'],
        ]);

        return self::SUCCESS;
    }

    private function human(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return sprintf('%.1f %s', $bytes / (1024 ** $i), $units[$i]);
    }
}
