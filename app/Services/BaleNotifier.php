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
 *
 * ربات دوطرفه (دکمه‌ی تغییر وضعیت، دستورهای آمار) در BaleBot است؛ این‌جا
 * فقط لایه‌ی ارسال و صدا زدن API است.
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
        'returned'        => ['↩️', 'سفارش مرجوع شد'],
        'payment_failed'  => ['⚠️', 'پرداخت ناموفق'],
        'payment_receipt' => ['🧷', 'رسید پرداخت ثبت شد'],
        'order_resend'    => ['🔁', 'سفارش (ارسال دوباره)'],

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

    /**
     * چرا الان پیامی نمی‌رود؟ null یعنی همه‌چیز آماده است.
     *
     * صفحه‌ی «بله» در پنل همین را نشان می‌دهد تا وقتی سفارشی به بله نرسید،
     * مدیر لازم نباشد لاگ سرور را باز کند.
     */
    public static function problem(): ?string
    {
        if (! config('bale.enabled')) {
            return 'اطلاع‌رسانی بله خاموش است (BALE_ENABLED=false).';
        }

        if (! config('bale.token')) {
            return 'توکن ربات (BALE_BOT_TOKEN) در تنظیمات سرور خالی است.';
        }

        if (self::chatIds() === []) {
            return 'مقصد پیام (BALE_CHAT_ID) در تنظیمات سرور خالی است.';
        }

        return null;
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
     * @param  string  $event    یکی از کلیدهای EVENTS
     * @param  array   $fields   «برچسب => مقدار»؛ مقدار خالی خودش حذف می‌شود
     * @param  array   $options  keyboard: دکمه‌های شیشه‌ای زیر پیام (inline_keyboard)
     *                           then: تابعی که بعد از تحویل موفق صدا زده می‌شود
     */
    public static function send(string $event, array $fields = [], array $options = []): void
    {
        try {
            if (! self::enabled($event)) {
                // سفارش‌ها مهم‌اند: اگر نرفت، دلیلش باید در لاگ باشد نه سکوت
                if (self::isOrderEvent($event)) {
                    Log::info('رویداد سفارش به بله فرستاده نشد', ['event' => $event, 'reason' => self::problem() ?: 'رویداد در BALE_EVENTS نیست']);
                }

                return;
            }

            $text   = self::compose($event, $fields);
            $markup = ! empty($options['keyboard']) ? ['inline_keyboard' => $options['keyboard']] : null;
            $then   = $options['then'] ?? null;

            $job = function () use ($text, $event, $markup, $then) {
                if (self::deliver($text, $event, $markup) && is_callable($then)) {
                    try {
                        $then();
                    } catch (\Throwable $e) {
                        Log::warning('کار پس از ارسال بله ناموفق بود', ['event' => $event, 'message' => $e->getMessage()]);
                    }
                }
            };

            if (config('bale.defer') && ! app()->runningInConsole()) {
                // پاسخ اول به کاربر می‌رسد، بعد پیام بله فرستاده می‌شود.
                app()->terminating($job);

                return;
            }

            $job();
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
    public static function sendNow(string $event, array $fields = [], array $options = []): array
    {
        if ($problem = self::problem()) {
            return ['ok' => false, 'message' => $problem];
        }

        $markup = ! empty($options['keyboard']) ? ['inline_keyboard' => $options['keyboard']] : null;
        $sent   = self::deliver(self::compose($event, $fields), $event, $markup);

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
    public static function fit(string $text): string
    {
        $max = (int) config('bale.max_length', 3500);

        if ($max <= 0 || mb_strlen($text) <= $max) {
            return $text;
        }

        return mb_substr($text, 0, $max - 20) . "\n… (پیام بریده شد)";
    }

    /** خط پایانی: نام فروشگاه و زمان. */
    public static function footer(): string
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
    private static function deliver(string $text, string $event, ?array $replyMarkup = null): bool
    {
        $delivered = false;

        foreach (self::chatIds() as $chatId) {
            if (self::sendTo($chatId, $text, $replyMarkup, $event) !== null) {
                $delivered = true;
            }
        }

        return $delivered;
    }

    /**
     * یک پیام به یک چت مشخص. خروجی، پیامِ ساخته‌شده (result) یا null.
     *
     * ربات برای پاسخ به دستورها هم از همین استفاده می‌کند.
     */
    public static function sendTo(string|int $chatId, string $text, ?array $replyMarkup = null, string $event = 'message'): ?array
    {
        $params = ['chat_id' => $chatId, 'text' => self::fit($text)];

        if ($replyMarkup) {
            $params['reply_markup'] = $replyMarkup;
        }

        return self::api('sendMessage', $params, $event);
    }

    /** ویرایش متن (و دکمه‌های) پیامی که قبلا فرستاده شده. */
    public static function editMessage(string|int $chatId, int $messageId, string $text, ?array $replyMarkup = null): ?array
    {
        $params = ['chat_id' => $chatId, 'message_id' => $messageId, 'text' => self::fit($text)];

        if ($replyMarkup !== null) {
            $params['reply_markup'] = $replyMarkup;
        }

        return self::api('editMessageText', $params, 'edit');
    }

    /** پاسخ کوتاهی که بعد از لمس دکمه بالای صفحه‌ی بله ظاهر می‌شود. */
    public static function answerCallback(string $callbackId, string $text = '', bool $alert = false): void
    {
        self::api('answerCallbackQuery', [
            'callback_query_id' => $callbackId,
            'text'              => mb_substr($text, 0, 200),
            'show_alert'        => $alert,
        ], 'callback');
    }

    /**
     * صدا زدن هر متد API بله. null یعنی نشد (جزئیات در لاگ).
     *
     * API بله با تلگرام سازگار است: /bot{token}/{method} با بدنه‌ی JSON.
     */
    public static function api(string $method, array $params = [], string $event = 'api', ?int $timeout = null): ?array
    {
        if (! config('bale.token')) {
            return null;
        }

        $url = rtrim((string) config('bale.api_url'), '/') . '/bot' . config('bale.token') . '/' . $method;

        try {
            $response = Http::timeout($timeout ?? (int) config('bale.timeout', 8))
                ->asJson()
                ->post($url, $params);

            if ($response->successful() && $response->json('ok')) {
                $result = $response->json('result');

                return is_array($result) ? $result : ['result' => $result];
            }

            Log::warning('بله درخواست را نپذیرفت', [
                'method'  => $method,
                'event'   => $event,
                'chat_id' => $params['chat_id'] ?? null,
                'status'  => $response->status(),
                // توکن داخل URL است، پس فقط بدنه‌ی پاسخ لاگ می‌شود
                'body'    => mb_substr($response->body(), 0, 500),
            ]);
        } catch (\Throwable $e) {
            Log::warning('ارتباط با بله برقرار نشد', [
                'method'  => $method,
                'event'   => $event,
                'chat_id' => $params['chat_id'] ?? null,
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /** @return string[] */
    public static function chatIds(): array
    {
        return self::asList(config('bale.chat_id'));
    }

    /**
     * شناسه‌ی کاربرانی که حق دارند از داخل بله وضعیت سفارش را عوض کنند یا
     * گزارش بگیرند: BALE_ADMIN_IDS به‌علاوه‌ی چت‌های خصوصی مقصد.
     *
     * @return string[]
     */
    public static function adminIds(): array
    {
        return array_values(array_unique(array_merge(
            self::asList(config('bale.admin_ids')),
            // چت خصوصی: شناسه‌ی چت همان شناسه‌ی کاربر است
            array_filter(self::chatIds(), fn ($id) => ! str_starts_with($id, '-'))
        )));
    }

    /** آیا این رویداد درباره‌ی یک سفارش است؟ (برای لاگِ «چرا نرفت») */
    private static function isOrderEvent(string $event): bool
    {
        return in_array($event, [
            'order_placed', 'awaiting_call', 'paid', 'processing', 'shipped',
            'delivered', 'canceled', 'returned', 'payment_failed', 'payment_receipt', 'order_resend',
        ], true);
    }

    /**
     * رشته‌ی جداشده با کاما را به آرایه‌ی تمیز تبدیل می‌کند.
     *
     * @return string[]
     */
    public static function asList($value): array
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
