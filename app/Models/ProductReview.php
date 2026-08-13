<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    protected $table = 'product_reviews';

    protected $fillable = [
        'product_id', 'customer_id', 'name', 'rating',
        'title', 'comment', 'status', 'is_buyer', 'ip',
    ];

    protected $casts = [
        'rating'   => 'integer',
        'is_buyer' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
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
