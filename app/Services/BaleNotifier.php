<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * ارسال رویدادهای سایت به پیام‌رسان بله.
 *
 * قرارداد این کلاس یک چیز است: «هیچ‌وقت جلوی کار کاربر را نگیر».
 * هر خطایی (توکن غلط، قطعی شبکه، بالا نیامدن بله) فقط در لاگ می‌نشیند و
 * پاسخ صفحه دست‌نخورده می‌ماند؛ اطلاع‌رسانی بخشِ حیاتیِ ثبت سفارش نیست.
 *
 * ارسال هم پیش‌فرض بعد از تحویل پاسخ به مرورگر انجام می‌شود (terminating)،
 * پس تاخیر شبکه‌ی بله به زمان بارگذاری صفحه اضافه نمی‌شود.
 */
class BaleNotifier
{
    /**
     * رویدادهای قابل ارسال؛ کلید همان چیزی است که در BALE_EVENTS نوشته می‌شود.
     * ایموجی جلوی پیام است تا در فهرست چت، نوع رویداد از یک نگاه معلوم باشد.
     */
    public const EVENTS = [
        // سفارش و پرداخت
        'order_placed'    => ['🧾', 'سفارش جدید (پرداخت آنلاین)'],
        'awaiting_call'   => ['📞', 'سفارش جدید (در انتظار تماس)'],
        'paid'            => ['✅', 'پرداخت سفارش تأیید شد'],
        'processing'      => ['📦', 'سفارش در حال آماده‌سازی'],
        'shipped'         => ['🚚', 'سفارش ارسال شد'],
        'delivered'       => ['🏁', 'سفارش تحویل داده شد'],
        'canceled'        => ['❌', 'سفارش لغو شد'],
        'payment_failed'  => ['⚠️', 'پرداخت ناموفق'],
        'payment_receipt' => ['🧷', 'رسید پرداخت ثبت شد'],

        // ورود و ثبت‌نام
        'customer_register'  => ['🎉', 'ثبت‌نام مشتری جدید'],
        'customer_login'     => ['🔐', 'ورود مشتری'],
        'admin_login'        => ['🛡️', 'ورود مدیر'],
        'admin_login_failed' => ['🚨', 'تلاش ناموفق ورود مدیر'],

        // سایر رویدادهای فروشگاه
        'product_review' => ['⭐', 'نظر جدید روی محصول'],
        'product_search' => ['🔎', 'جستجوی کاربر'],
        'discount_used'  => ['🎟️', 'کد تخفیف اعمال شد'],
        'site_error'     => ['💥', 'خطای سرور'],
        'test'           => ['🔔', 'پیام آزمایشی'],
    ];

    /** آیا توکن و مقصد تنظیم شده‌اند؟ */
    public static function configured(): bool
    {
        return (bool) config('bale.token') && self::chatIds() !== [];
    }

    /** آیا این رویداد باید ارسال شود؟ */
    public static function enabled(string $event): bool
    {
        if (! config('bale.enabled') || ! self::configured()) {
            return false;
        }

        if (in_array($event, self::asList(config('bale.events_except')), true)) {
            return false;
        }

        $only = self::asList(config('bale.events', '*'));

        return $only === [] || in_array('*', $only, true) || in_array($event, $only, true);
    }

    /**
     * رویداد را می‌فرستد؛ نقطه‌ای که کنترلرها صدا می‌زنند.
     *
     * @param  string  $event   یکی از کلیدهای EVENTS
     * @param  array   $fields  «برچسب => مقدار»؛ مقدار خالی خودش حذف می‌شود
     */
    public static function send(string $event, array $fields = []): void
    {
        try {
            if (! self::enabled($event)) {
                return;
            }

            $text = self::compose($event, $fields);

            if (config('bale.defer')) {
                // پاسخ اول به کاربر می‌رسد، بعد پیام بله فرستاده می‌شود.
                app()->terminating(fn () => self::deliver($text, $event));

                return;
            }

            self::deliver($text, $event);
        } catch (\Throwable $e) {
            Log::warning('ارسال رویداد به بله ناموفق بود', [
                'event'   => $event,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * ارسال فوری و بدون تعویق؛ برای دستور تست که باید نتیجه را همان لحظه بگوید.
     *
     * @return array{ok: bool, message: string}
     */
    public static function sendNow(string $event, array $fields = []): array
    {
        if (! config('bale.enabled')) {
            return ['ok' => false, 'message' => 'اطلاع‌رسانی بله خاموش است (BALE_ENABLED=false).'];
        }

        if (! self::configured()) {
            return ['ok' => false, 'message' => 'BALE_BOT_TOKEN یا BALE_CHAT_ID در فایل .env تنظیم نشده است.'];
        }

        $sent = self::deliver(self::compose($event, $fields), $event);

        return $sent
            ? ['ok' => true, 'message' => 'پیام برای ' . count(self::chatIds()) . ' مقصد فرستاده شد.']
            : ['ok' => false, 'message' => 'ارسال ناموفق بود؛ جزئیات در storage/logs/laravel.log است.'];
    }

    /**
     * متن نهایی پیام.
     *
     * کلید رشته‌ای «برچسب: مقدار» می‌شود؛ کلید عددی یک بلوک چندخطی است که
     * دست‌نخورده چاپ می‌شود، تا فرستنده بتواند خط خالیِ بین بخش‌ها را خودش
     * تعیین کند (خلاصه‌ی سفارش از همین استفاده می‌کند).
     */
    public static function compose(string $event, array $fields = []): string
    {
        [$icon, $label] = self::EVENTS[$event] ?? ['🔔', $event];

        $lines = [$icon . ' ' . $label];

        foreach ($fields as $key => $value) {
            $value = is_array($value) ? implode('، ', array_filter($value)) : (string) $value;

            if (trim($value) === '') {
                continue;
            }

            $lines[] = is_int($key) ? rtrim($value) : $key . ': ' . trim($value);
        }

        $lines[] = '—';
        $lines[] = self::footer();

        return self::fit(implode("\n", $lines));
    }

    /**
     * پیام بلندتر از سقف بله بریده می‌شود.
     *
     * سفارش عمده با ده‌ها قلم می‌توانست از سقف رد شود؛ آن‌وقت بله کل پیام را
     * رد می‌کرد و مدیر به‌جای پیامِ ناقص، هیچ خبری نمی‌گرفت.
     */
    private static function fit(string $text): string
    {
        $max = (int) config('bale.max_length', 3500);

        if ($max <= 0 || mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, $max - 20) . "\n… (پیام بریده شد)";
    }

    /** خط پایانی: نام فروشگاه و زمان. */
    private static function footer(): string
    {
        $shop = (string) config('app.name');
        $time = date('Y/m/d H:i');

        try {
            if (function_exists('seo_site_name')) {
                $shop = seo_site_name();
            }

            if (function_exists('toPersianDate')) {
                $time = toPersianDate(now(), false);
            }
        } catch (\Throwable $e) {
            // تاریخ شمسی نشد، همان میلادی برود؛ پیام نباید به‌خاطر فرمت تاریخ نرود
        }

        return trim($shop . ' • ' . $time, ' •');
    }

    /** ارسال واقعی به همه‌ی مقصدها. true یعنی دست‌کم یک مقصد پیام را گرفت. */
    private static function deliver(string $text, string $event): bool
    {
        $url = rtrim((string) config('bale.api_url'), '/')
            . '/bot' . config('bale.token') . '/sendMessage';

        $delivered = false;

        foreach (self::chatIds() as $chatId) {
            try {
                $response = Http::timeout((int) config('bale.timeout', 8))
                    ->asJson()
                    ->post($url, [
                        'chat_id' => $chatId,
                        'text'    => $text,
                    ]);

                if ($response->successful() && $response->json('ok')) {
                    $delivered = true;
                    continue;
                }

                Log::warning('بله پیام را نپذیرفت', [
                    'event'   => $event,
                    'chat_id' => $chatId,
                    'status'  => $response->status(),
                    // توکن داخل URL است، پس فقط بدنه‌ی پاسخ لاگ می‌شود
                    'body'    => mb_substr($response->body(), 0, 500),
                ]);
            } catch (\Throwable $e) {
                Log::warning('ارتباط با بله برقرار نشد', [
                    'event'   => $event,
                    'chat_id' => $chatId,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $delivered;
    }

    /** @return string[] */
    public static function chatIds(): array
    {
        return self::asList(config('bale.chat_id'));
    }

    /**
     * رشته‌ی جداشده با کاما را به آرایه‌ی تمیز تبدیل می‌کند.
     *
     * @return string[]
     */
    private static function asList($value): array
    {
        if (is_array($value)) {
            $value = implode(',', $value);
        }

        return array_values(array_filter(
            array_map('trim', explode(',', (string) $value)),
            fn ($item) => $item !== ''
        ));
    }
}
