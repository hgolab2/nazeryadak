<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * قاعده‌ی ریدایرکت که مدیر از پنل تعریف می‌کند.
 *
 * تطبیق روی «مسیر» انجام می‌شود نه آدرس کامل، چون یک قاعده باید هم روی
 * دامنه‌ی اصلی و هم روی www و هم روی محیط توسعه کار کند. کوئری‌استرینگِ
 * درخواست در تطبیق نقشی ندارد ولی در مقصد حفظ می‌شود.
 */
class Redirect extends Model
{
    /** نقشه‌ی قواعد در هر درخواست خوانده می‌شود؛ بدون کش یعنی یک کوئری روی کل سایت. */
    public const CACHE_KEY = 'seo:redirects:map';

    public const STATUS_CODES = [
        301 => 'انتقال دائمی (301)',
        302 => 'انتقال موقت (302)',
        307 => 'انتقال موقت با حفظ متد (307)',
        410 => 'حذف دائمی (410)',
    ];

    protected $table = 'redirects';

    protected $fillable = [
        'source_path', 'target_path', 'status_code',
        'is_active', 'hits', 'last_hit_at', 'note',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'is_active'   => 'boolean',
        'hits'        => 'integer',
        'last_hit_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetCache());
        static::deleted(fn () => static::forgetCache());
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * مسیر را به شکل یکتا در می‌آورد تا «/shop/» و «shop» و
     * «https://nazeryadak.ir/shop?x=1» همگی به یک کلید برسند.
     *
     * دیکد کردن لازم است چون مسیرهای فارسی سایت در مرورگر به شکل
     * درصدی (%D8%..) می‌آیند ولی مدیر در فرم، فارسی خوانا وارد می‌کند.
     */
    public static function normalize(?string $path): string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return '/';
        }

        if (preg_match('~^https?://~i', $path)) {
            $path = (string) parse_url($path, PHP_URL_PATH);
        }

        $path = explode('?', $path, 2)[0];
        $path = explode('#', $path, 2)[0];
        $path = rawurldecode($path);
        $path = '/' . ltrim($path, '/');
        $path = rtrim($path, '/');

        return $path === '' ? '/' : $path;
    }

    /** نقشه‌ی «مسیر مبدا => قاعده» برای قواعد فعال. */
    public static function map(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return static::query()
                ->where('is_active', 1)
                ->get(['id', 'source_path', 'target_path', 'status_code'])
                ->keyBy('source_path')
                ->map(fn ($rule) => [
                    'id'     => (int) $rule->id,
                    'target' => $rule->target_path,
                    'status' => (int) $rule->status_code,
                ])
                ->all();
        });
    }
}
