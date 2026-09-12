<?php

namespace App\Console\Commands;

use App\Services\BaleNotifier;
use App\Services\BaleOrderResender;
use Illuminate\Console\Command;

/**
 * سفارش‌هایی را که به بله نرسیده‌اند دوباره می‌فرستد.
 *
 *   php artisan bale:resend-orders          سفارش‌های ارسال‌نشده‌ی ۷ روز اخیر
 *   php artisan bale:resend-orders --days=30
 *
 * «نرسیده» یعنی orders.bale_notified_at خالی است: یا بله در لحظه‌ی ثبت در
 * دسترس نبود، یا توکن/مقصد آن موقع تنظیم نبود. صفحه‌ی «بله» در پنل همین
 * کار را با یک دکمه انجام می‌دهد؛ این دستور برای cron یا خط فرمان است.
 */
class BaleResendOrders extends Command
{
    protected $signature = 'bale:resend-orders {--days=7 : چند روز اخیر بررسی شود}';

    protected $description = 'ارسال دوباره‌ی سفارش‌هایی که به بله نرسیده‌اند';

    public function handle(): int
    {
        if ($problem = BaleNotifier::problem()) {
            $this->error($problem);

            return self::FAILURE;
        }

        $result = (new BaleOrderResender())->resend((int) $this->option('days'));

        $this->info('ارسال‌شده: ' . $result['sent'] . ' — ناموفق: ' . $result['failed'] . ' — بررسی‌شده: ' . $result['total']);

        return $result['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
