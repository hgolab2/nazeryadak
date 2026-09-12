<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| زمان‌بندی — روی سرور یک cron دقیقه‌ای لازم است:
|   * * * * * cd /path/to/site && php artisan schedule:run >> /dev/null 2>&1
|
| بدون این cron هیچ‌کدام اجرا نمی‌شوند ولی از پنل سئو → کلیدواژه‌ها به‌صورت
| دستی قابل اجرا هستند.
*/
Schedule::command('seo:gsc-sync')->dailyAt('04:30')->withoutOverlapping();
Schedule::command('seo:keywords-check --stale=6')->weeklyOn(6, '05:00')->withoutOverlapping();
