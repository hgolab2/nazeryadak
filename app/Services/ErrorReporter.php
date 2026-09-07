<?php

namespace App\Services;

use App\Models\ErrorLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * ثبت خطای سرور در جدول errorlog و اطلاع‌رسانی آن در بله.
 *
 * دو قاعده‌ی این کلاس:
 *
 * ۱) هرگز خطای تازه نساز. اگر دیتابیس پایین باشد یا بله جواب ندهد، فقط در
 *    لاگ فایل می‌نشیند؛ وگرنه صفحه‌ی خطا خودش با یک خطای دیگر می‌ترکید و
 *    کاربر به‌جای ۵۰۰ی معمولی، صفحه‌ی سفید می‌دید.
 *
 * ۲) سیل خطا نباید ربات را قفل کند. یک خطای تکراری (همان فایل و همان خط)
 *    فقط یک‌بار در بازه‌ی throttle به بله می‌رود، ولی همه‌ی رخدادها در جدول
 *    ثبت می‌شوند؛ جدول سابقه است، بله هشدار.
 */
class ErrorReporter
{
    /** حداکثر طول متنی که در ستون‌های جدول جا می‌شود. */
    private const MAX_MESSAGE     = 60000;
    private const MAX_TRACE       = 60000;
    private const MAX_REQUEST     = 5000;
    private const MAX_BALE_MESSAGE = 700;

    /** کلیدهایی که نباید در request_data ذخیره شوند. */
    private const SECRETS = [
        'password', 'password_confirmation', 'current_password', 'new_password',
        '_token', 'token', 'api_token', 'access_token', 'secret', 'card_number', 'cvv', 'otp', 'code',
    ];

    /**
     * نقطه‌ی ورود؛ از bootstrap/app.php برای هر استثنای گزارش‌شدنی صدا زده می‌شود.
     */
    public static function capture(Throwable $e, ?Request $request = null): void
    {
        try {
            $request ??= app()->runningInConsole() ? null : request();

            $row = self::row($e, $request);

            self::store($row);
            self::notify($e, $row);
        } catch (Throwable $inner) {
            // گزارش خطا نباید خودش خطا شود
            Log::error('ثبت خطا در errorlog ناموفق بود: ' . $inner->getMessage());
        }
    }

    /** ساخت ردیف جدول از روی استثنا و درخواست. */
    private static function row(Throwable $e, ?Request $request): array
    {
        $console = $request === null;

        return [
            'message'      => self::clip($e->getMessage() !== '' ? $e->getMessage() : get_class($e), self::MAX_MESSAGE),
            'stack_trace'  => self::clip($e->getTraceAsString(), self::MAX_TRACE),
            'level'        => 'error',
            // در کنسول آدرسی وجود ندارد؛ به‌جایش خودِ دستور ثبت می‌شود
            'url'          => self::clip($console ? self::command() : $request->getRequestUri(), 2048),
            'route_name'   => $console ? null : (self::clip((string) optional($request->route())->getName(), 255) ?: null),
            'method'       => $console ? 'CLI' : self::clip($request->method(), 10),
            'request_data' => $console ? null : self::requestData($request),
            'user_id'      => self::userId(),
            'code'         => self::clip((string) $e->getCode(), 100),
            'file'         => self::clip($e->getFile(), 250),
            'line'         => (int) $e->getLine(),
            'fullurl'      => self::clip($console ? self::command() : $request->fullUrl(), 1000),
            'ip'           => self::clip($console ? 'cli' : (string) $request->ip(), 250),
        ];
    }

    /** درج در جدول؛ اگر خود دیتابیس مشکل داشته باشد فقط لاگ می‌شود. */
    private static function store(array $row): void
    {
        try {
            ErrorLog::create($row);
        } catch (Throwable $e) {
            Log::error('درج در errorlog ناموفق بود: ' . $e->getMessage());
        }
    }

    /** ارسال هشدار به بله، با جلوگیری از تکرار خطای یکسان. */
    private static function notify(Throwable $e, array $row): void
    {
        if (! self::shouldNotify($row)) {
            return;
        }

        BaleNotifier::send('site_error', [
            'نوع'   => class_basename($e),
            'پیام'  => self::clip($row['message'], self::MAX_BALE_MESSAGE),
            'فایل'  => $row['file'] . ':' . $row['line'],
            'آدرس'  => $row['fullurl'],
            'روش'   => $row['method'],
            'کاربر' => $row['user_id'] ? '#' . $row['user_id'] : 'مهمان',
            'IP'    => $row['ip'],
        ]);
    }

    /**
     * خطای تکراری در بازه‌ی throttle دوباره فرستاده نمی‌شود.
     * اگر کش در دسترس نباشد، ارسال انجام می‌شود؛ نبودِ هشدار بدتر از تکرار است.
     */
    private static function shouldNotify(array $row): bool
    {
        $seconds = (int) config('bale.error_throttle', 300);

        if ($seconds < 1) {
            return true;
        }

        try {
            $key = 'bale-error:' . md5($row['file'] . '|' . $row['line'] . '|' . $row['message']);

            return Cache::add($key, 1, $seconds);
        } catch (Throwable $e) {
            return true;
        }
    }

    /** ورودی درخواست بدون مقادیر حساس. */
    private static function requestData(Request $request): ?string
    {
        try {
            $data = $request->except(self::SECRETS);

            if ($data === []) {
                return null;
            }

            return self::clip(
                (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR),
                self::MAX_REQUEST
            );
        } catch (Throwable $e) {
            return null;
        }
    }

    /** شناسه‌ی کاربر واردشده (پنل یا مشتری)؛ در کنسول null. */
    private static function userId(): ?int
    {
        try {
            return Auth::check() ? (int) Auth::id() : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /** خط فرمانی که خطا در آن رخ داده؛ مثل «artisan products:normalize». */
    private static function command(): string
    {
        $argv = (array) ($_SERVER['argv'] ?? []);

        return trim('artisan ' . implode(' ', array_slice($argv, 1))) ?: 'cli';
    }

    private static function clip(?string $value, int $length): string
    {
        return mb_substr((string) $value, 0, $length);
    }
}
