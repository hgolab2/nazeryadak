<?php

namespace App\Console\Commands;

use App\Services\BaleNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * راه‌اندازی و عیب‌یابی اطلاع‌رسانی بله.
 *
 *   php artisan bale:test           یک پیام آزمایشی می‌فرستد
 *   php artisan bale:test --chats   شناسه‌ی چت‌هایی که به ربات پیام داده‌اند
 *
 * گام اول راه‌اندازی: ربات را در بله بسازید، توکن را در BALE_BOT_TOKEN
 * بگذارید، از داخل بله به ربات یک پیام (مثلا /start) بدهید و بعد
 * «--chats» را اجرا کنید تا شناسه‌ی چت را برای BALE_CHAT_ID بردارید.
 */
class BaleTest extends Command
{
    protected $signature = 'bale:test
        {--chats : نمایش شناسه‌ی چت‌هایی که اخیرا به ربات پیام داده‌اند}';

    protected $description = 'آزمودن اطلاع‌رسانی بله و یافتن شناسه‌ی چت';

    public function handle(): int
    {
        if (! config('bale.token')) {
            $this->error('BALE_BOT_TOKEN در فایل .env تنظیم نشده است.');

            return self::FAILURE;
        }

        return $this->option('chats') ? $this->showChats() : $this->sendTest();
    }

    private function sendTest(): int
    {
        $this->line('مقصدها: ' . (implode('، ', BaleNotifier::chatIds()) ?: '—'));

        $result = BaleNotifier::sendNow('test', [
            'وضعیت' => 'اتصال سایت به بله برقرار است.',
            'محیط'  => config('app.env'),
        ]);

        if (! $result['ok']) {
            $this->error($result['message']);

            return self::FAILURE;
        }

        $this->info($result['message']);

        return self::SUCCESS;
    }

    /** getUpdates تنها راه دیدن شناسه‌ی چت است؛ ربات آن را خودش اعلام نمی‌کند. */
    private function showChats(): int
    {
        $url = rtrim((string) config('bale.api_url'), '/')
            . '/bot' . config('bale.token') . '/getUpdates';

        try {
            $response = Http::timeout((int) config('bale.timeout', 8))->get($url);
        } catch (\Throwable $e) {
            $this->error('ارتباط با بله برقرار نشد: ' . $e->getMessage());

            return self::FAILURE;
        }

        if (! $response->successful() || ! $response->json('ok')) {
            $this->error('پاسخ بله معتبر نبود: ' . mb_substr($response->body(), 0, 300));

            return self::FAILURE;
        }

        $chats = [];
        foreach ((array) $response->json('result', []) as $update) {
            $chat = $update['message']['chat'] ?? null;

            if (! $chat || ! isset($chat['id'])) {
                continue;
            }

            $chats[$chat['id']] = [
                $chat['id'],
                $chat['type'] ?? '',
                trim(($chat['title'] ?? '') . ' ' . ($chat['first_name'] ?? '') . ' ' . ($chat['last_name'] ?? '')),
            ];
        }

        if ($chats === []) {
            $this->warn('هنوز پیامی به ربات نرسیده است. از داخل بله به ربات /start بفرستید و دوباره اجرا کنید.');

            return self::SUCCESS;
        }

        $this->table(['شناسه چت', 'نوع', 'نام'], array_values($chats));
        $this->line('شناسه‌ی دلخواه را در BALE_CHAT_ID بگذارید (چند مقصد را با کاما جدا کنید).');

        return self::SUCCESS;
    }
}
