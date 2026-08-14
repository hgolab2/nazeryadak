<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * کد یکبارمصرف پیامکی.
 *
 * کد در کش نگه‌داری می‌شود، نه روی رکورد مشتری. قبلا هر درخواست کد یک ردیف
 * در جدول customers می‌ساخت؛ یعنی هر کسی با تایپ یک شماره‌ی دلخواه می‌توانست
 * حساب خالی بسازد. حالا حساب فقط بعد از تأیید موفق کد ساخته می‌شود.
 *
 * «purpose» دو مسیر کد را از هم جدا می‌کند: کدی که برای بازیابی رمز فرستاده
 * شده نباید بتواند کاربر را وارد حساب کند و برعکس.
 */
class OtpService
{
    /** اعتبار کد */
    public const TTL = 120;

    /** فاصله‌ی لازم تا ارسال مجدد */
    public const RESEND_WAIT = 60;

    /** حداکثر کد اشتباه پیش از باطل شدن کد */
    private const MAX_TRIES = 5;

    public const PURPOSE_LOGIN = 'login';
    public const PURPOSE_RESET = 'reset';

    private static function key(string $mobile, string $purpose): string
    {
        return "otp:{$purpose}:{$mobile}";
    }

    private static function waitKey(string $mobile, string $purpose): string
    {
        return "otp-wait:{$purpose}:{$mobile}";
    }

    /**
     * ثانیه‌های باقی‌مانده تا اجازه‌ی ارسال بعدی؛ صفر یعنی همین حالا می‌شود فرستاد.
     */
    public static function cooldown(string $mobile, string $purpose): int
    {
        $until = Cache::get(self::waitKey($mobile, $purpose));

        return $until ? max(0, $until - time()) : 0;
    }

    /**
     * ارسال کد. خروجی: ['ok' => bool, 'wait' => int, 'message' => ?string, 'code' => ?string]
     * فیلد code فقط در محیط توسعه پر می‌شود تا بدون درگاه پیامک هم بتوان تست کرد.
     */
    public static function send(string $mobile, string $purpose, string $ip = ''): array
    {
        if ($wait = self::cooldown($mobile, $purpose)) {
            return ['ok' => false, 'wait' => $wait, 'message' => "تا {$wait} ثانیه دیگر می‌توانید کد جدید بگیرید."];
        }

        // سقف روزانه‌ی هر شماره و هر آی‌پی، جدا از فاصله‌ی ارسال مجدد؛
        // بدون آن یک نفر می‌تواند با صبر ۶۰ ثانیه‌ای شماره‌ای را بمباران کند.
        foreach ([["otp-send-{$mobile}", 8], ["otp-ip-" . ($ip ?: 'unknown'), 30]] as [$key, $max]) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                return [
                    'ok'      => false,
                    'wait'    => RateLimiter::availableIn($key),
                    'message' => 'تعداد درخواست کد زیاد است. لطفا بعدا دوباره تلاش کنید.',
                ];
            }
            RateLimiter::hit($key, 3600);
        }

        $code = (string) random_int(100000, 999999);

        Cache::put(
            self::key($mobile, $purpose),
            ['code' => $code, 'tries' => 0, 'expires' => time() + self::TTL],
            self::TTL
        );
        Cache::put(self::waitKey($mobile, $purpose), time() + self::RESEND_WAIT, self::RESEND_WAIT);

        $text = $purpose === self::PURPOSE_RESET
            ? "کد بازیابی رمز عبور شما: {$code}"
            : "کد ورود به ناظر یدک: {$code}";

        sendSms($mobile, $text);

        $result = ['ok' => true, 'wait' => self::RESEND_WAIT, 'message' => null];

        // فقط با SMS_SHOW_CODE=true و آن هم روی لوکال؛ برای وقتی که درگاه
        // پیامک در دسترس نیست و باید فلوی ورود تست شود.
        if (config('sms.show_code') && app()->isLocal()) {
            Log::info("OTP for {$mobile} ({$purpose}): {$code}");
            $result['code'] = $code;
        }

        return $result;
    }

    /**
     * بررسی کد. با درست بودن، کد بلافاصله سوزانده می‌شود تا دوباره به کار نرود.
     */
    public static function verify(string $mobile, string $purpose, string $code): bool
    {
        $key   = self::key($mobile, $purpose);
        $entry = Cache::get($key);

        if (! $entry) {
            return false;
        }

        if (! hash_equals($entry['code'], $code)) {
            $entry['tries']++;
            // شمردن تلاش‌های اشتباه، وگرنه ۶ رقم را می‌شود در دو دقیقه حدس زد.
            // مهلت با هر تلاش تمدید نمی‌شود؛ همان انقضای اولیه باقی می‌ماند.
            $remaining = $entry['expires'] - time();

            if ($entry['tries'] >= self::MAX_TRIES || $remaining < 1) {
                Cache::forget($key);
            } else {
                Cache::put($key, $entry, $remaining);
            }

            return false;
        }

        Cache::forget($key);
        Cache::forget(self::waitKey($mobile, $purpose));

        return true;
    }
}
