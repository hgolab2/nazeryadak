<?php

namespace App\Support;

/**
 * یکسان‌سازی شماره‌ی موبایل.
 *
 * ورودی کاربر با کیبورد فارسی، کپی از دفترچه‌تلفن (+98) یا با خط تیره می‌آید؛
 * همه باید به یک شکل «09xxxxxxxxx» برسند تا جست‌وجو در دیتابیس و محدودیت
 * تعداد تلاش روی یک کلید ثابت انجام شود.
 */
class Mobile
{
    /** قاعده‌ی اعتبارسنجی مشترک همه‌ی فرم‌هایی که شماره می‌گیرند */
    public const RULES = ['mobile' => 'required|regex:/^09\d{9}$/'];

    public const MESSAGES = [
        'mobile.required' => 'لطفا شماره موبایل خود را وارد کنید.',
        'mobile.regex'    => 'شماره موبایل باید ۱۱ رقم و به شکل ۰۹۱۲۳۴۵۶۷۸۹ باشد.',
    ];

    public static function digits(?string $value): string
    {
        return strtr((string) $value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
    }

    public static function normalize(?string $value): string
    {
        $value = preg_replace('/[^0-9+]/', '', self::digits($value));

        if (str_starts_with($value, '+98')) {
            $value = '0' . substr($value, 3);
        } elseif (str_starts_with($value, '0098')) {
            $value = '0' . substr($value, 4);
        } elseif (str_starts_with($value, '98') && strlen($value) === 12) {
            $value = '0' . substr($value, 2);
        } elseif (strlen($value) === 10 && str_starts_with($value, '9')) {
            $value = '0' . $value;
        }

        return $value;
    }

    /** «۰۹۱۲***۴۵۶۷» برای نمایش در صفحه، بدون لو دادن کل شماره */
    public static function mask(string $mobile): string
    {
        return strlen($mobile) === 11
            ? substr($mobile, 0, 4) . '***' . substr($mobile, -4)
            : $mobile;
    }
}
