<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * ادغام فایل‌های CSS و JS، هرکدام در یک فایل.
 *
 * سرور HTTP/1.1 است و مرورگر همزمان بیش از شش اتصال به یک دامنه باز
 * نمی‌کند؛ هر <link> یا <script> اضافه یک رفت‌وبرگشت کامل است که برای CSS
 * رندر را هم متوقف می‌کند. فهرست و ترتیب از config/assets.php می‌آید تا
 * قالب و این دستور هرگز از هم جدا نیفتند.
 *
 * فایل‌های اصلی حذف نمی‌شوند؛ اگر باندل روی سرور ساخته نشده یا قدیمی
 * باشد، قالب خودکار به همان تگ‌های جداگانه برمی‌گردد.
 */
class BundleStyles extends Command
{
    protected $signature = 'assets:bundle
        {--group=* : فقط css یا js؛ پیش‌فرض هر دو}
        {--check : فقط بگوید باندل‌ها به‌روز هستند یا نه}';

    protected $description = 'ادغام فایل‌های CSS و JS برای کاهش تعداد درخواست‌ها';

    public function handle(): int
    {
        $groups = $this->option('group') ?: ['css', 'js'];
        $status = self::SUCCESS;

        foreach ($groups as $group) {
            if (! in_array($group, ['css', 'js'], true)) {
                $this->error("گروه ناشناخته: {$group}");
                $status = self::FAILURE;
                continue;
            }

            if ($this->build($group) !== self::SUCCESS) {
                $status = self::FAILURE;
            }
        }

        return $status;
    }

    private function build(string $group): int
    {
        $sources = (array) config("assets.{$group}.sources", []);
        $bundle = (string) config("assets.{$group}.bundle");

        if (! $sources || $bundle === '') {
            $this->error("بخش «{$group}» در config/assets.php تنظیم نشده است.");

            return self::FAILURE;
        }

        /*
        | ریشه‌ی پوشه‌ی عمومی از public_roots() می‌آید، نه مستقیم از
        | public_path(): روی هاست اشتراکی که فایل‌های عمومی در public_html
        | هستند، public_path() به مسیری اشاره می‌کند که وجود ندارد و باندل
        | جایی ساخته می‌شد که هیچ‌وقت سرو نمی‌شد.
        */
        $root = public_roots()[0] ?? public_path();
        foreach (public_roots() as $candidate) {
            if (is_file($candidate . '/' . ltrim($sources[0], '/'))) {
                $root = $candidate;
                break;
            }
        }

        $target = $root . '/' . ltrim($bundle, '/');

        if ($this->option('check')) {
            $fresh = asset_bundle_is_fresh($group);
            $this->line($fresh
                ? "باندل {$group} به‌روز است."
                : "باندل {$group} وجود ندارد یا قدیمی است؛ `php artisan assets:bundle` را اجرا کنید.");

            return $fresh ? self::SUCCESS : self::FAILURE;
        }

        $parts = [];
        $totalBytes = 0;

        foreach ($sources as $source) {
            $path = public_file_path($source);

            if (! $path) {
                $this->warn("رد شد (پیدا نشد): {$source}");
                continue;
            }

            $code = file_get_contents($path);
            $totalBytes += strlen($code);

            $parts[] = '/* ' . $source . ' */';

            /*
            | مسیرهای نسبی داخل url() فقط در CSS معنا دارند و باید نسبت به
            | جای فایل اصلی مطلق شوند؛ فونت‌آوسام فونت‌هایش را با
            | «../webfonts/» صدا می‌زند و باندل در پوشه‌ی دیگری می‌نشیند.
            |
            | برای JS یک «;» جدا اضافه می‌شود: فایل مینی‌فایدی که بدون
            | نقطه‌ویرگول پایانی تمام شده، وگرنه با شروع فایل بعدی یکی
            | می‌شود و کل باندل خطای نحوی می‌گیرد.
            */
            $parts[] = $group === 'css'
                ? $this->absolutizeUrls($code, $source)
                : $code . "\n;";
        }

        if (! $parts) {
            $this->error('هیچ فایلی برای ادغام پیدا نشد.');

            return self::FAILURE;
        }

        $output = implode("\n", $parts) . "\n";

        if (! is_dir(dirname($target))) {
            mkdir(dirname($target), 0755, true);
        }

        /*
        | محتوای یکسان دوباره نوشته نمی‌شود تا ?v= عوض نشود و کش مرورگر
        | کاربرها بی‌دلیل باطل نگردد. ولی mtime باید جلو بیاید، وگرنه فایلی
        | که فقط دست‌کاری تاریخ خورده (مثلا بعد از آپلود) باندل را برای همیشه
        | «قدیمی» نگه می‌دارد و قالب هرگز از حالت پشتیبان بیرون نمی‌آید.
        */
        if (is_file($target) && file_get_contents($target) === $output) {
            touch($target);
            clearstatcache(true, $target);
            $this->info("محتوای باندل {$group} تغییری نکرده بود؛ فقط تاریخ آن تازه شد.");

            return self::SUCCESS;
        }

        file_put_contents($target, $output);

        $this->info('باندل ساخته شد: ' . $bundle);
        $this->table(['شرح', 'مقدار'], [
            ['فایل‌های ادغام‌شده', count($sources)],
            ['حجم ورودی', $this->human($totalBytes)],
            ['حجم خروجی', $this->human(strlen($output))],
            ['درخواست‌های حذف‌شده', max(0, count($sources) - 1)],
        ]);

        return self::SUCCESS;
    }

    /**
     * مطلق‌کردن مسیرهای نسبی داخل url().
     *
     * فونت‌آوسام فونت‌هایش را با «../webfonts/» صدا می‌زند؛ چون باندل در
     * پوشه‌ی دیگری می‌نشیند، این مسیر بدون بازنویسی به جای اشتباه می‌رود و
     * همه‌ی آیکون‌ها مربع خالی می‌شوند.
     */
    private function absolutizeUrls(string $css, string $source): string
    {
        $baseDir = rtrim(str_replace('\\', '/', dirname($source)), '/');

        return preg_replace_callback(
            '/url\(\s*([\'"]?)(?!data:|https?:|\/\/|#|\/)([^\'")]+)\1\s*\)/i',
            function (array $m) use ($baseDir) {
                $resolved = $this->resolvePath($baseDir . '/' . $m[2]);

                return 'url(' . $m[1] . $resolved . $m[1] . ')';
            },
            $css
        );
    }

    /** حذف «..» و «.» از مسیر، بدون مراجعه به دیسک (فایل ممکن است نباشد). */
    private function resolvePath(string $path): string
    {
        // بخش کوئری و لنگر (مثل ‎?#iefix‎) نباید وارد محاسبه‌ی مسیر شود.
        $suffix = '';
        if (preg_match('/^([^?#]*)([?#].*)$/', $path, $m)) {
            $path = $m[1];
            $suffix = $m[2];
        }

        $out = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($out);
                continue;
            }
            $out[] = $segment;
        }

        return '/' . implode('/', $out) . $suffix;
    }

    private function human(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0';
        }
        $units = ['B', 'KB', 'MB'];
        $i = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return sprintf('%.1f %s', $bytes / (1024 ** $i), $units[$i]);
    }
}
