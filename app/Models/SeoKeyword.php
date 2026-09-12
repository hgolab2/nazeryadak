<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * یک عبارت هدف سئو و صفحه‌ای که باید برایش رتبه بگیرد.
 *
 * قانون یک‌به‌یک: هر عبارت دقیقا یک صفحه‌ی هدف دارد. دو صفحه برای یک عبارت
 * با هم رقابت می‌کنند و هر دو می‌بازند؛ ستون gsc_top_page همین را لو می‌دهد:
 * اگر گوگل صفحه‌ی دیگری را برای این عبارت نشان بدهد، هدف‌گذاری اشتباه است.
 */
class SeoKeyword extends Model
{
    protected $table = 'seo_keywords';

    public const PRIORITIES = [
        1 => 'اصلی',
        2 => 'مهم',
        3 => 'فرعی',
    ];

    protected $fillable = [
        'keyword', 'term', 'target_url', 'priority', 'note',
        'http_status', 'page_title', 'page_h1', 'page_description',
        'in_title', 'in_h1', 'in_description', 'body_hits', 'is_indexable', 'checked_at',
        'gsc_clicks', 'gsc_impressions', 'gsc_position', 'gsc_ctr', 'gsc_top_page', 'gsc_synced_at',
    ];

    protected $casts = [
        'priority'        => 'integer',
        'http_status'     => 'integer',
        'in_title'        => 'boolean',
        'in_h1'           => 'boolean',
        'in_description'  => 'boolean',
        'body_hits'       => 'integer',
        'is_indexable'    => 'boolean',
        'checked_at'      => 'datetime',
        'gsc_clicks'      => 'integer',
        'gsc_impressions' => 'integer',
        'gsc_position'    => 'float',
        'gsc_ctr'         => 'float',
        'gsc_synced_at'   => 'datetime',
    ];

    /**
     * نمره‌ی ۰ تا ۱۰۰ از آخرین ممیزی؛ null یعنی هنوز بررسی نشده.
     *
     * وزن‌ها عمدی‌اند: title و H1 بیشترین اثر را روی رتبه دارند، description
     * فقط روی نرخ کلیک اثر می‌گذارد، و نبودنِ عبارت در بدنه یعنی صفحه
     * درباره‌ی چیز دیگری است.
     */
    public function score(): ?int
    {
        if ($this->checked_at === null) {
            return null;
        }

        if ($this->http_status !== 200 || $this->is_indexable === false) {
            return 0;
        }

        $score = 0;
        $score += $this->in_title ? 35 : 0;
        $score += $this->in_h1 ? 30 : 0;
        $score += $this->in_description ? 15 : 0;
        $score += min(20, (int) $this->body_hits * 5);

        return $score;
    }

    /** آیا گوگل برای این عبارت صفحه‌ای غیر از هدف را نشان می‌دهد؟ */
    public function hasCannibalization(): bool
    {
        if (blank($this->gsc_top_page) || blank($this->target_url)) {
            return false;
        }

        return rtrim(urldecode($this->gsc_top_page), '/') !== rtrim(urldecode($this->target_url), '/');
    }

    /**
     * یافتن یا ساختن ردیف از روی عبارت خام. اگر عبارت از قبل باشد، همان
     * برمی‌گردد و هدفِ قبلی دست نمی‌خورد.
     */
    public static function findOrCreateFromKeyword(string $keyword, ?string $targetUrl = null, int $priority = 2): self
    {
        $keyword = trim(preg_replace('/\s+/u', ' ', $keyword));
        $term    = Product::normalizeTerm($keyword);

        $existing = static::where('term', $term)->first();
        if ($existing) {
            return $existing;
        }

        return static::create([
            'keyword'    => mb_substr($keyword, 0, 160),
            'term'       => mb_substr($term, 0, 120),
            'target_url' => $targetUrl,
            'priority'   => $priority,
        ]);
    }
}
