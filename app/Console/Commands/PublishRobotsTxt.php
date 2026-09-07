<?php

namespace App\Console\Commands;

use App\Http\Controllers\SitemapController;
use Illuminate\Console\Command;

/**
 * نوشتن نسخه‌ی استاتیک robots.txt در public/.
 *
 * روت /robots.txt منبعِ حقیقت است، اما آپاچی فایل موجود در public/ را
 * زودتر از روت سرو می‌کند؛ یعنی عملا همان فایل استاتیک است که خزنده‌ها
 * می‌بینند. اگر این دو از هم جدا بیفتند، تغییرِ قواعد در کد هیچ اثری روی
 * خزنده‌ها ندارد و این خرابی هیچ نشانه‌ای هم بروز نمی‌دهد. این دستور فایل
 * استاتیک را از روی همان کد بازتولید می‌کند.
 *
 * بعد از هر تغییر در SitemapController::robots() یا AI_CRAWLERS اجرا شود.
 */
class PublishRobotsTxt extends Command
{
    protected $signature = 'seo:robots
        {--check : فقط بگوید فایل استاتیک با کد هم‌خوان است یا نه}';

    protected $description = 'بازتولید public/robots.txt از روی روت داینامیک';

    public function handle(SitemapController $controller): int
    {
        // آدرس‌های داخل فایل از seo_url() می‌آیند و seo_url روی محیط توسعه
        // localhost برمی‌گرداند. بدون این بررسی، یک اجرای ساده روی لپ‌تاپ،
        // Sitemap و آدرس‌های robots.txt پروडاکشن را به 127.0.0.1 تبدیل
        // می‌کند و خزنده‌ها نقشه‌ی سایت را گم می‌کنند.
        $host = (string) parse_url(seo_base_url(), PHP_URL_HOST);

        if ($host === '' || $host === 'localhost' || str_starts_with($host, '127.') || str_ends_with($host, '.test')) {
            $this->error('آدرس پایه «' . $host . '» محلی است؛ این دستور را روی سرور یا با SEO_BASE_URL=https://دامنه اجرا کنید.');

            return self::FAILURE;
        }

        $path = public_path('robots.txt');
        $fresh = $controller->robots()->getContent() . "\n";
        $current = is_file($path) ? file_get_contents($path) : null;

        if ($current === $fresh) {
            $this->info('robots.txt به‌روز است.');

            return self::SUCCESS;
        }

        if ($this->option('check')) {
            $this->error('robots.txt با کد هم‌خوان نیست؛ «php artisan seo:robots» را اجرا کنید.');

            return self::FAILURE;
        }

        file_put_contents($path, $fresh);
        $this->info('robots.txt بازتولید شد (' . number_format(strlen($fresh)) . ' بایت).');

        return self::SUCCESS;
    }
}
