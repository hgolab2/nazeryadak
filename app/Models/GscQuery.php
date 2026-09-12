<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * یک ردیف خام از گزارش Performance سرچ کنسول: عبارت × صفحه.
 *
 * منبع یا API است (seo:gsc-sync) یا CSV خروجیِ خودِ سرچ کنسول که مدیر
 * در پنل آپلود می‌کند. CSV بعدِ «صفحه» ندارد، پس page خالی ذخیره می‌شود.
 */
class GscQuery extends Model
{
    protected $table = 'gsc_queries';

    protected $fillable = [
        'query', 'term', 'page', 'page_hash', 'clicks', 'impressions', 'ctr', 'position', 'period_days', 'synced_at',
    ];

    protected $casts = [
        'clicks'      => 'integer',
        'impressions' => 'integer',
        'ctr'         => 'float',
        'position'    => 'float',
        'period_days' => 'integer',
        'synced_at'   => 'datetime',
    ];

    /**
     * ثبت یا بازنویسی یک ردیف. کلید یکتا (term, page_hash) است؛ اعداد جمع
     * نمی‌شوند بلکه جایگزین می‌شوند، چون هر همگام‌سازی یک عکسِ کامل از
     * بازه‌ی گزارش است.
     *
     * @param  array{query:string,page?:string,clicks:int,impressions:int,ctr:float,position:float}  $row
     */
    public static function upsertRow(array $row, int $periodDays, \DateTimeInterface $syncedAt): bool
    {
        $query = trim(preg_replace('/\s+/u', ' ', (string) $row['query']));
        $term  = Product::normalizeTerm($query);
        if ($term === '' || mb_strlen($term) > 120) {
            return false;
        }

        $page = static::relativePage((string) ($row['page'] ?? ''));

        DB::table('gsc_queries')->upsert([[
            'query'       => mb_substr($query, 0, 200),
            'term'        => $term,
            'page'        => mb_substr($page, 0, 500),
            'page_hash'   => md5($page),
            'clicks'      => (int) $row['clicks'],
            'impressions' => (int) $row['impressions'],
            'ctr'         => round((float) $row['ctr'], 4),
            'position'    => round((float) $row['position'], 2),
            'period_days' => $periodDays,
            'synced_at'   => $syncedAt,
            'created_at'  => $syncedAt,
            'updated_at'  => $syncedAt,
        ]], ['term', 'page_hash'], ['query', 'page', 'clicks', 'impressions', 'ctr', 'position', 'period_days', 'synced_at', 'updated_at']);

        return true;
    }

    /** آدرس مطلق سرچ کنسول → مسیر نسبیِ خوانا (بدون دامنه، بدون درصدکدگذاری). */
    public static function relativePage(string $page): string
    {
        $page = trim($page);
        if ($page === '') {
            return '';
        }

        // سرچ کنسول همیشه آدرس مطلق می‌دهد؛ ورودی دستی ممکن است نسبی باشد.
        $path        = parse_url($page, PHP_URL_PATH) ?: '/';
        $queryString = parse_url($page, PHP_URL_QUERY);

        $path = urldecode($path);

        return $queryString ? $path . '?' . urldecode($queryString) : $path;
    }

    /**
     * جمع آمار یک عبارت روی همه‌ی صفحه‌ها + پربازدیدترین صفحه‌اش.
     *
     * @return array{clicks:int,impressions:int,position:float,ctr:float,top_page:?string}|null
     */
    public static function summaryFor(string $term): ?array
    {
        $rows = static::where('term', $term)->get();
        if ($rows->isEmpty()) {
            return null;
        }

        $impressions = (int) $rows->sum('impressions');
        $clicks      = (int) $rows->sum('clicks');

        // میانگین رتبه وزن‌دار با نمایش: صفحه‌ای که ۱۰۰۰ بار نمایش داده شده
        // رتبه‌ی واقعی را تعیین می‌کند، نه صفحه‌ای که ۲ بار.
        $position = $impressions > 0
            ? $rows->sum(fn ($r) => $r->position * $r->impressions) / $impressions
            : (float) $rows->avg('position');

        $top = $rows->filter(fn ($r) => $r->page !== '')->sortByDesc('impressions')->first();

        return [
            'clicks'      => $clicks,
            'impressions' => $impressions,
            'position'    => round($position, 2),
            'ctr'         => $impressions > 0 ? round($clicks / $impressions, 4) : 0.0,
            'top_page'    => $top?->page,
        ];
    }
}
