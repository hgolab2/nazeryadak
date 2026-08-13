<?php

namespace Tests\Unit;

use App\Http\Controllers\UserController;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * صفحه‌ی ورود در placeholder خودش «۰۹۱۲۰۰۰۰۰۰۰» با ارقام فارسی نشان می‌داد،
 * ولی اعتبارسنجی فقط ^09\d{9}$ لاتین را می‌پذیرفت — یعنی دقیقا همان چیزی که
 * به کاربر پیشنهاد می‌شد رد می‌شد.
 */
class MobileNormalizationTest extends TestCase
{
    public static function mobileProvider(): array
    {
        return [
            'ارقام فارسی'        => ['۰۹۱۲۳۴۵۶۷۸۹', '09123456789'],
            'ارقام عربی'         => ['٠٩١٢٣٤٥٦٧٨٩', '09123456789'],
            'با فاصله'           => ['0912 345 6789', '09123456789'],
            'با خط تیره'         => ['0912-345-6789', '09123456789'],
            'با +98'             => ['+989123456789', '09123456789'],
            'با 0098'            => ['00989123456789', '09123456789'],
            'با 98'              => ['989123456789',  '09123456789'],
            'بدون صفر ابتدایی'   => ['9123456789',    '09123456789'],
            'از قبل درست'        => ['09123456789',   '09123456789'],
            'ترکیبی'             => ['+۹۸ ۹۱۲-۳۴۵-۶۷۸۹', '09123456789'],
        ];
    }

    #[DataProvider('mobileProvider')]
    public function test_mobile_numbers_normalize_to_local_format(string $input, string $expected): void
    {
        $this->assertSame($expected, UserController::normalizeMobile($input));
    }

    public function test_null_and_empty_are_safe(): void
    {
        $this->assertSame('', UserController::normalizeMobile(null));
        $this->assertSame('', UserController::normalizeMobile(''));
    }

    public function test_normalized_number_passes_the_validation_pattern(): void
    {
        foreach (self::mobileProvider() as $case) {
            $this->assertMatchesRegularExpression(
                '/^09\d{9}$/',
                UserController::normalizeMobile($case[0]),
                'شماره‌ی «' . $case[0] . '» باید بعد از یکسان‌سازی معتبر باشد'
            );
        }
    }
}
