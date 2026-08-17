<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * تولید نسخه‌ی WebP کنار هر تصویر JPEG/PNG.
 *
 * فایل اصلی دست‌نخورده می‌ماند و فقط یک فایل هم‌نام با پسوند .webp ساخته
 * می‌شود؛ قالب‌ها با تگ <picture> ابتدا نسخه‌ی WebP را به مرورگر می‌دهند و
 * اگر پشتیبانی نشد، به فایل اصلی برمی‌گردند. چون چیزی حذف نمی‌شود،
 * اجرای دوباره‌ی دستور بی‌خطر است.
 */
class ConvertImagesToWebp extends Command
{
    protected $signature = 'images:webp
        {--dir=* : پوشه‌های هدف نسبت به public (پیش‌فرض: upload و assets/images)}
        {--file=* : فایل‌های مشخص نسبت به public؛ برای تصاویری که اندازه‌ی هدفشان با بقیه فرق دارد}
        {--quality=82 : کیفیت خروجی WebP بین ۱ تا ۱۰۰}
        {--min-size=20 : فایل‌های کوچک‌تر از این مقدار (کیلوبایت) نادیده گرفته شوند}
        {--max-dim=900 : بزرگ‌ترین ضلع خروجی به پیکسل؛ صفر یعنی بدون تغییر اندازه}
        {--force : ساخت دوباره حتی اگر نسخه‌ی WebP از قبل وجود داشته باشد}
        {--dry-run : فقط گزارش بدهد و چیزی ننویسد}';

    protected $description = 'ساخت نسخه‌ی WebP برای تصاویر سایت جهت کاهش حجم و بهبود سرعت';

    public function handle(): int
    {
        if (! function_exists('imagewebp')) {
            $this->error('افزونه‌ی GD بدون پشتیبانی WebP کامپایل شده است.');
            return self::FAILURE;
        }

        $dirs = $this->option('dir');
        $only = $this->option('file');

        // اگر کاربر فقط --file داده، نباید کل پوشه‌های پیش‌فرض هم پردازش شوند.
        if (! $dirs && ! $only) {
            $dirs = ['upload', 'assets/images'];
        }

        $quality = max(1, min(100, (int) $this->option('quality')));
        $minBytes = max(0, (int) $this->option('min-size')) * 1024;
        $maxDim = max(0, (int) $this->option('max-dim'));
        $force = (bool) $this->option('force');
        $dry = (bool) $this->option('dry-run');

        $converted = 0;
        $skipped = 0;
        $failed = 0;
        $originalBytes = 0;
        $webpBytes = 0;

        foreach ($this->sources($dirs, $only) as $file) {
            $ext = strtolower($file->getExtension());
            if (! in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                continue;
            }

            $source = $file->getPathname();
            $target = preg_replace('/\.(jpe?g|png)$/i', '.webp', $source);

            if ($file->getSize() < $minBytes) {
                $skipped++;
                continue;
            }

            // نسخه‌ی موجود فقط وقتی بازسازی می‌شود که اصل فایل جدیدتر باشد.
            if (! $force && is_file($target) && filemtime($target) >= $file->getMTime()) {
                $skipped++;
                continue;
            }

            if ($dry) {
                $converted++;
                $originalBytes += $file->getSize();
                continue;
            }

            $image = $this->load($source, $ext);
            if (! $image) {
                $failed++;
                continue;
            }

            // PNG ممکن است شفافیت داشته باشد؛ بدون این دو خط، پس‌زمینه سیاه می‌شود.
            imagepalettetotruecolor($image);
            imagealphablending($image, false);
            imagesavealpha($image, true);

            // تصاویر منبع ۱۰۲۴ پیکسل‌اند ولی در کارت‌ها حدود ۳۰۰ پیکسل دیده
            // می‌شوند؛ کوچک‌سازی، بخش عمده‌ی صرفه‌جویی حجم را می‌دهد.
            $image = $this->downscale($image, $maxDim);

            $ok = @imagewebp($image, $target, $quality);
            imagedestroy($image);

            if (! $ok || ! is_file($target)) {
                $failed++;
                continue;
            }

            $newSize = filesize($target);

            // اگر WebP بزرگ‌تر از اصل درآمد (روی بعضی PNGهای ساده رخ می‌دهد)
            // نگهش نمی‌داریم تا مرورگر فایل سنگین‌تر دانلود نکند.
            if ($newSize >= $file->getSize()) {
                @unlink($target);
                $skipped++;
                continue;
            }

            $originalBytes += $file->getSize();
            $webpBytes += $newSize;
            $converted++;

            if ($converted % 250 === 0) {
                $this->line("  {$converted} فایل تبدیل شد...");
            }
        }

        $this->newLine();
        $this->info($dry ? 'گزارش آزمایشی (چیزی نوشته نشد)' : 'تبدیل انجام شد');
        $this->table(['شرح', 'مقدار'], [
            ['تبدیل‌شده', number_format($converted)],
            ['رد شده', number_format($skipped)],
            ['ناموفق', number_format($failed)],
            ['حجم اصلی', $this->human($originalBytes)],
            ['حجم WebP', $dry ? '-' : $this->human($webpBytes)],
            ['صرفه‌جویی', $dry || $originalBytes === 0 ? '-' :
                $this->human($originalBytes - $webpBytes) . sprintf(' (%.0f%%)', (1 - $webpBytes / $originalBytes) * 100)],
        ]);

        return self::SUCCESS;
    }

    /**
     * فهرست فایل‌های ورودی از ترکیب --dir و --file.
     *
     * @return \Generator<\SplFileInfo>
     */
    private function sources(array $dirs, array $files): \Generator
    {
        foreach ($dirs as $dir) {
            $path = public_path(trim($dir, '/\\'));

            if (! is_dir($path)) {
                $this->warn("پوشه پیدا نشد: {$dir}");
                continue;
            }

            $this->info("در حال پردازش: {$dir}");

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    yield $file;
                }
            }
        }

        foreach ($files as $relative) {
            $path = public_path(ltrim($relative, '/\\'));

            if (! is_file($path)) {
                $this->warn("فایل پیدا نشد: {$relative}");
                continue;
            }

            yield new \SplFileInfo($path);
        }
    }

    /**
     * کوچک‌سازی با حفظ نسبت ابعاد. اگر تصویر از حد کوچک‌تر باشد دست‌نخورده
     * برمی‌گردد؛ شفافیت هم روی بوم مقصد حفظ می‌شود.
     */
    private function downscale($image, int $maxDim)
    {
        if ($maxDim <= 0) {
            return $image;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $longest = max($width, $height);

        if ($longest <= $maxDim) {
            return $image;
        }

        $ratio = $maxDim / $longest;
        $newWidth = max(1, (int) round($width * $ratio));
        $newHeight = max(1, (int) round($height * $ratio));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $canvas;
    }

    private function load(string $path, string $ext)
    {
        try {
            return match ($ext) {
                'png' => @imagecreatefrompng($path),
                default => @imagecreatefromjpeg($path),
            } ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function human(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);

        return sprintf('%.1f %s', $bytes / (1024 ** $i), $units[$i]);
    }
}
