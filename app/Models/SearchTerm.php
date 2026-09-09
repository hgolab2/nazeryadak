<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * عبارت‌هایی که کاربرها جستجو کرده‌اند، با شمارنده.
 *
 * مثل شمارنده‌ی بازدید، هیچ‌کدام از این مسیرها نباید صفحه‌ی نتیجه‌ی جستجو را
 * بشکند؛ پس همه داخل try/catch و روی Query Builder هستند تا رویدادهای مدل
 * راه نیفتند.
 */
class SearchTerm extends Model
{
    protected $table = 'search_terms';

    protected $fillable = ['term', 'display_term', 'hits', 'results_count', 'last_searched_at'];

    protected $casts = [
        'hits'             => 'integer',
        'results_count'    => 'integer',
        'last_searched_at' => 'datetime',
    ];

    /**
     * کمینه‌ی تکرار برای پیشنهادشدن به بقیه.
     *
     * جستجوی یک نفر هنوز «پرتکرار» نیست و نباید بالای جعبه‌ی جستجوی همه
     * بنشیند؛ این عدد همان مرزی است که یک کنجکاوی را از یک تقاضای واقعی
     * جدا می‌کند.
     */
    public const POPULAR_MIN_HITS = 3;

    /** سقف طول عبارت؛ اندازه‌ی ستون است و جمله‌ی بلند هم چیپِ خوانا نمی‌شود. */
    private const MAX_LENGTH = 120;

    /**
     * ثبت یک جستجو.
     *
     * update-then-insert، مثل ProductView: ایندکس یکتای term تضمین می‌کند
     * دو درخواست هم‌زمان دو ردیف برای یک عبارت نسازند.
     *
     * results_count هر بار بازنویسی می‌شود نه جمع: سوالی که از این ستون
     * می‌پرسیم «آیا این عبارت الان نتیجه دارد» است، نه «تا حالا چند تا
     * نتیجه دیده». قطعه‌ای که دیروز موجود نبود و امروز اضافه شده، باید از
     * همین امروز قابل پیشنهاد باشد.
     */
    public static function record(?string $raw, int $resultsCount): void
    {
        try {
            $display = trim(preg_replace('/\s+/u', ' ', (string) $raw));
            $term    = Product::normalizeTerm($display);

            if (mb_strlen($term) < 2 || mb_strlen($term) > self::MAX_LENGTH) {
                return;
            }

            $now = now();

            $updated = DB::table('search_terms')->where('term', $term)->update([
                'display_term'     => mb_substr($display, 0, 160),
                'hits'             => DB::raw('hits + 1'),
                'results_count'    => max(0, $resultsCount),
                'last_searched_at' => $now,
                'updated_at'       => $now,
            ]);

            if ($updated) {
                return;
            }

            $inserted = DB::table('search_terms')->insertOrIgnore([
                'term'             => $term,
                'display_term'     => mb_substr($display, 0, 160),
                'hits'             => 1,
                'results_count'    => max(0, $resultsCount),
                'last_searched_at' => $now,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);

            if (! $inserted) {
                // درخواست هم‌زمانِ دیگری همین ردیف را ساخته؛ فقط شمارنده بالا برود.
                DB::table('search_terms')->where('term', $term)->update([
                    'hits'             => DB::raw('hits + 1'),
                    'last_searched_at' => $now,
                    'updated_at'       => $now,
                ]);
            }
        } catch (\Throwable $e) {
            // شمارنده نباید مانع نمایش نتیجه‌ی جستجو شود.
        }
    }

    /**
     * پرتکرارترین جستجوهای نتیجه‌دار، برای پیشنهاد به بقیه‌ی کاربرها.
     *
     * دو شرط جدا از هم لازم است و هیچ‌کدام تزیینی نیست:
     *
     *   • results_count > 0 — پیشنهادی که به صفحه‌ی خالی برسد بدتر از نبودنِ
     *     پیشنهاد است. این شرط هم‌زمان فیلترِ عبارت‌های بی‌معنا و اسپم هم هست،
     *     چون رشته‌ی بی‌ربط طبیعتا نتیجه‌ای ندارد.
     *   • hits >= POPULAR_MIN_HITS — جستجوی یک نفر هنوز پرتکرار نیست.
     *
     * کش کوتاه است چون این فهرست در هر بار بارگذاری قالب خوانده می‌شود و
     * تازگیِ چنددقیقه‌ای برایش کافی است.
     *
     * @return string[]
     */
    public static function popular(int $limit = 8): array
    {
        try {
            return Cache::remember('popular_search_terms:' . $limit, 600, function () use ($limit) {
                return DB::table('search_terms')
                    ->where('results_count', '>', 0)
                    ->where('hits', '>=', self::POPULAR_MIN_HITS)
                    ->orderByDesc('hits')
                    ->orderByDesc('last_searched_at')
                    ->limit($limit)
                    ->pluck('display_term')
                    ->all();
            });
        } catch (\Throwable $e) {
            // جدول هنوز مهاجرت نشده یا دیتابیس در دسترس نیست؛ قالب به فهرست
            // پیش‌فرضِ خودش برمی‌گردد، نه اینکه صفحه خطا بدهد.
            return [];
        }
    }
}
