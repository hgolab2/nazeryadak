<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductReview extends Model
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING  => 'در انتظار تأیید',
        self::STATUS_APPROVED => 'تأیید شده',
        self::STATUS_REJECTED => 'رد شده',
    ];

    /**
     * معیارهای امتیازدهی.
     *
     * امتیاز کلی برای خریدارِ بعدی مبهم است: «۳ ستاره» نمی‌گوید ایراد از
     * کیفیت ساخت بوده یا از قیمت. معیارها همان چیزهایی‌اند که خریدارِ قطعه‌ی
     * یدکی واقعا درباره‌شان تصمیم می‌گیرد.
     *
     * افزودن معیار تازه فقط همین‌جا انجام می‌شود؛ مقدارها در یک ستون JSON
     * می‌نشینند و به تغییر ساختار دیتابیس نیاز نیست. کلید را عوض نکنید —
     * امتیازهای ثبت‌شده‌ی قبلی با همان کلید ذخیره شده‌اند.
     */
    public const CRITERIA = [
        'quality'    => 'کیفیت ساخت',
        'value'      => 'ارزش خرید به نسبت قیمت',
        'fitment'    => 'تناسب و نصب روی خودرو',
        'durability' => 'دوام و کارکرد',
    ];

    protected $table = 'product_reviews';

    protected $fillable = [
        'product_id', 'customer_id', 'name', 'rating', 'criteria',
        'title', 'comment', 'status', 'is_buyer', 'ip',
    ];

    protected $casts = [
        'rating'   => 'integer',
        'criteria' => 'array',
        'is_buyer' => 'boolean',
    ];

    /** نتیجه‌ی Schema::hasColumn برای ستون criteria، در همین درخواست. */
    private static ?bool $criteriaSupport = null;

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * آیا ستون امتیازهای تفکیکی روی دیتابیس ساخته شده است؟
     *
     * روی محیطی که هنوز مایگریشن نخورده، ثبت نظر نباید بشکند؛ فرم بدون
     * بخش معیارها نشان داده می‌شود و نظر با امتیاز کلی ثبت می‌شود.
     */
    public static function supportsCriteria(): bool
    {
        return self::$criteriaSupport ??= Schema::hasColumn('product_reviews', 'criteria');
    }

    /** پاک‌کردن کش درون‌درخواستیِ supportsCriteria (تست‌ها و کارگرهای ماندگار). */
    public static function flushCriteriaSupportCache(): void
    {
        self::$criteriaSupport = null;
    }

    /**
     * فقط معیارهای شناخته‌شده با امتیاز ۱ تا ۵ نگه داشته می‌شوند.
     *
     * ورودی از فرم عمومی می‌آید: کلید ناشناس یا عددِ خارج از بازه دور ریخته
     * می‌شود تا میانگین‌ها با داده‌ی ساختگی به هم نریزند.
     *
     * @param  mixed  $input
     * @return array<string,int>|null
     */
    public static function sanitizeCriteria($input): ?array
    {
        if (! is_array($input)) {
            return null;
        }

        $clean = [];
        foreach (self::CRITERIA as $key => $label) {
            $score = $input[$key] ?? null;
            if ($score === null || $score === '') {
                continue;
            }
            $score = (int) $score;
            if ($score >= 1 && $score <= 5) {
                $clean[$key] = $score;
            }
        }

        return $clean ?: null;
    }

    /**
     * امتیازهای همین نظر به‌صورت «برچسب فارسی => امتیاز»، به ترتیب CRITERIA.
     *
     * @return array<string,int>
     */
    public function criteriaScores(): array
    {
        $scores = self::sanitizeCriteria($this->criteria) ?? [];

        $labelled = [];
        foreach ($scores as $key => $score) {
            $labelled[self::CRITERIA[$key]] = $score;
        }

        return $labelled;
    }

    /**
     * خلاصه‌ی امتیازها برای نمایش در صفحه‌ی محصول.
     *
     * ورودی، همان مجموعه‌ی نظرهای تأییدشده‌ای است که با محصول لود شده؛ پس
     * این محاسبه هیچ کوئری اضافه‌ای نمی‌زند.
     *
     * distribution برای نوار «چند نفر چند ستاره داده‌اند» است و criteria
     * برای میانگینِ هر معیار. معیاری که هیچ‌کس به آن امتیاز نداده، با
     * count صفر برمی‌گردد تا نما بتواند حذفش کند.
     *
     * @param  iterable<int,self>  $reviews
     * @return array{count:int, avg:float, distribution:array<int,array{count:int,percent:float}>, criteria:array<string,array{key:string,label:string,avg:float,count:int}>}
     */
    public static function summarize($reviews): array
    {
        $total  = 0;
        $sum    = 0;
        $bars   = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        $cSum   = array_fill_keys(array_keys(self::CRITERIA), 0);
        $cCount = array_fill_keys(array_keys(self::CRITERIA), 0);

        foreach ($reviews as $review) {
            $rating = (int) $review->rating;
            if ($rating >= 1 && $rating <= 5) {
                $total++;
                $sum += $rating;
                $bars[$rating]++;
            }

            foreach (self::sanitizeCriteria($review->criteria) ?? [] as $key => $score) {
                $cSum[$key]   += $score;
                $cCount[$key]++;
            }
        }

        $distribution = [];
        foreach ($bars as $star => $count) {
            $distribution[$star] = [
                'count'   => $count,
                'percent' => $total > 0 ? round($count / $total * 100, 1) : 0.0,
            ];
        }

        $criteria = [];
        foreach (self::CRITERIA as $key => $label) {
            $criteria[$key] = [
                'key'   => $key,
                'label' => $label,
                'count' => $cCount[$key],
                'avg'   => $cCount[$key] > 0 ? round($cSum[$key] / $cCount[$key], 1) : 0.0,
            ];
        }

        return [
            'count'        => $total,
            'avg'          => $total > 0 ? round($sum / $total, 1) : 0.0,
            'distribution' => $distribution,
            'criteria'     => $criteria,
        ];
    }

    /**
     * میانگین و تعداد امتیاز را روی خودِ محصول می‌نویسد.
     *
     * از Query Builder استفاده می‌شود تا updated_at محصول جابه‌جا نشود؛
     * تاریخ آخرین تغییر محصول در lastmod نقشه‌ی سایت می‌آید و تأیید یک نظر
     * نباید به گوگل بگوید مشخصات قطعه عوض شده است.
     */
    public static function recalculate(int $productId): void
    {
        $stats = static::where('product_id', $productId)
            ->approved()
            ->selectRaw('COUNT(*) as cnt, AVG(rating) as avg_rating')
            ->first();

        $count = (int) ($stats->cnt ?? 0);

        DB::table('products')->where('id', $productId)->update([
            'rating_count' => $count,
            'rating_avg'   => $count > 0 ? round((float) $stats->avg_rating, 2) : null,
        ]);
    }

    protected static function booted(): void
    {
        static::saved(fn (self $review) => static::recalculate((int) $review->product_id));
        static::deleted(fn (self $review) => static::recalculate((int) $review->product_id));
    }
}
