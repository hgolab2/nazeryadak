<?php

namespace App\Console\Commands;

use App\Services\BaleBot;
use App\Services\BaleNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * دریافت پیام‌های ربات با getUpdates، به‌جای وبهوک.
 *
 *   php artisan bale:poll           تا وقتی متوقفش نکنید گوش می‌دهد
 *   php artisan bale:poll --once    یک بار می‌گیرد و پردازش می‌کند (برای cron)
 *
 * برای محیط محلی (که آدرس عمومی ندارد) یا سروری که وبهوک روی آن جواب نمی‌دهد.
 * اگر وبهوک ثبت باشد، بله به getUpdates جواب نمی‌دهد؛ اول bale:webhook --remove.
 *
 * شناسه‌ی آخرین رویداد پردازش‌شده در کش می‌ماند تا بعد از اجرای دوباره،
 * همان پیام‌ها دوباره پردازش نشوند.
 */
class BalePoll extends Command
{
    protected $signature = 'bale:poll
        {--once : یک نوبت دریافت و پایان}
        {--timeout=25 : ثانیه‌ی انتظار هر نوبت (long polling)}';

    protected $description = 'دریافت پیام‌های ربات بله بدون وبهوک';

    private const OFFSET_KEY = 'bale:poll:offset';

    public function handle(): int
    {
        if ($problem = BaleNotifier::problem()) {
            $this->error($problem);

            return self::FAILURE;
        }

        $bot     = new BaleBot();
        $timeout = max(0, (int) $this->option('timeout'));

        $this->info('گوش‌دادن به ربات بله' . ($this->option('once') ? ' (یک نوبت)' : ' — برای توقف Ctrl+C'));

        do {
            $offset  = (int) Cache::get(self::OFFSET_KEY, 0);
            $updates = BaleNotifier::api('getUpdates', [
                'offset'  => $offset,
                'timeout' => $timeout,
                'limit'   => 50,
            ], 'poll', $timeout + 10); // مهلت HTTP باید از مهلت long polling بیشتر باشد

            if ($updates === null) {
                $this->warn('دریافت ناموفق؛ ۵ ثانیه بعد دوباره تلاش می‌شود.');
                sleep(5);
                continue;
            }

            // getUpdates آرایه‌ی خام برمی‌گرداند؛ api() آن را در «result» می‌پیچد
            $list = isset($updates['result']) && is_array($updates['result']) ? $updates['result'] : $updates;

            foreach ($list as $update) {
                if (! is_array($update) || ! isset($update['update_id'])) {
                    continue;
                }

                $kind = isset($update['callback_query']) ? 'دکمه' : 'پیام';
                $this->line('• ' . $kind . ' #' . $update['update_id']);

                $bot->handle($update);

                Cache::forever(self::OFFSET_KEY, (int) $update['update_id'] + 1);
            }
        } while (! $this->option('once'));

        return self::SUCCESS;
    }
}
