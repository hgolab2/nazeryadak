<?php

namespace App\Console\Commands;

use App\Http\Controllers\BaleWebhookController;
use App\Services\BaleNotifier;
use Illuminate\Console\Command;

/**
 * ثبت یا حذف وبهوک ربات بله.
 *
 *   php artisan bale:webhook            آدرس سایت را به بله معرفی می‌کند
 *   php artisan bale:webhook --show     وضعیت فعلی وبهوک
 *   php artisan bale:webhook --remove   حذف وبهوک (مثلا برای برگشتن به bale:poll)
 *
 * بعد از ثبت، بله هر پیام و لمس دکمه را به /bale/webhook/{secret} می‌فرستد
 * و BaleBot آن را پردازش می‌کند. APP_URL باید آدرس عمومی سایت با https باشد.
 */
class BaleWebhook extends Command
{
    protected $signature = 'bale:webhook
        {--remove : حذف وبهوک}
        {--show : فقط نمایش وضعیت فعلی}';

    protected $description = 'ثبت وبهوک ربات بله روی آدرس سایت';

    public function handle(): int
    {
        if ($problem = BaleNotifier::problem()) {
            $this->error($problem);

            return self::FAILURE;
        }

        if ($this->option('show')) {
            return $this->show();
        }

        if ($this->option('remove')) {
            $result = BaleNotifier::api('deleteWebhook');
            $this->line($result !== null ? 'وبهوک حذف شد.' : 'حذف وبهوک ناموفق بود؛ لاگ را ببینید.');

            return $result !== null ? self::SUCCESS : self::FAILURE;
        }

        $url = BaleWebhookController::url();

        if (! str_starts_with($url, 'https://')) {
            $this->warn('APP_URL با https شروع نمی‌شود: ' . config('app.url'));
            $this->warn('بله فقط به آدرس عمومی https پیام می‌فرستد. در محیط محلی از «php artisan bale:poll» استفاده کنید.');
        }

        $result = BaleNotifier::api('setWebhook', ['url' => $url]);

        if ($result === null) {
            $this->error('ثبت وبهوک ناموفق بود؛ جزئیات در storage/logs/laravel.log است.');

            return self::FAILURE;
        }

        $this->info('وبهوک ثبت شد: ' . $url);
        $this->line('حالا از داخل بله به ربات /start بفرستید.');

        return self::SUCCESS;
    }

    private function show(): int
    {
        $info = BaleNotifier::api('getWebhookInfo');

        if ($info === null) {
            $this->error('دریافت وضعیت وبهوک ناموفق بود.');

            return self::FAILURE;
        }

        $this->line('آدرس ثبت‌شده: ' . (($info['url'] ?? '') !== '' ? $info['url'] : '— (وبهوک ندارد)'));
        $this->line('آدرس مورد انتظار: ' . BaleWebhookController::url());

        if (! empty($info['last_error_message'])) {
            $this->warn('آخرین خطا: ' . $info['last_error_message']);
        }

        if (isset($info['pending_update_count'])) {
            $this->line('رویدادهای در صف: ' . $info['pending_update_count']);
        }

        return self::SUCCESS;
    }
}
