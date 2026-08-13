<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    /** درگاهِ رکوردهایی که مشتری خودش رسیدشان را ثبت کرده است. */
    public const GATEWAY_MANUAL = 'manual';

    /** روش‌های پرداخت دستی که مشتری می‌تواند انتخاب کند. */
    public const METHODS = [
        'card_to_card'  => 'کارت به کارت',
        'bank_transfer' => 'واریز/انتقال بانکی (پایا، ساتنا، اینترنت‌بانک)',
        'pos'           => 'کارتخوان فروشگاه',
        'cash'          => 'پرداخت نقدی',
    ];

    /** وضعیت‌ها: pending یعنی «منتظر بررسی مدیر» برای رسید دستی. */
    public const STATUSES = [
        'pending'  => 'در انتظار بررسی',
        'paid'     => 'تأیید شده',
        'rejected' => 'رد شده',
        'failed'   => 'ناموفق',
    ];

    protected $fillable = [
        'order_id',
        'gateway',
        'method',
        'amount',
        'status',
        'ref_id',
        'reference',
        'card_last4',
        'payer_name',
        'receipt_image',
        'customer_note',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
        'paid_at',
    ];

    protected $casts = [
        'paid_at'     => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    /* Relations */

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /** مدیری که رسید را تأیید یا رد کرده است. */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /* Scopes */

    public function scopeManual(Builder $query): Builder
    {
        return $query->where('gateway', self::GATEWAY_MANUAL);
    }

    /** رسیدهای دستی که هنوز تعیین تکلیف نشده‌اند. */
    public function scopeAwaitingReview(Builder $query): Builder
    {
        return $query->manual()->where('status', 'pending');
    }

    /* Helpers */

    public function isManual(): bool
    {
        return $this->gateway === self::GATEWAY_MANUAL;
    }

    /** فقط رسید دستیِ در انتظار بررسی را می‌شود تأیید یا رد کرد. */
    public function isAwaitingReview(): bool
    {
        return $this->isManual() && $this->status === 'pending';
    }

    public function statusLabel(): string
    {
        // پرداخت درگاه هنوز به مقصد نرسیده، نه اینکه منتظر بررسی مدیر باشد
        if (! $this->isManual() && $this->status === 'pending') {
            return 'در انتظار پرداخت';
        }

        return self::STATUSES[$this->status] ?? 'نامشخص';
    }

    public function methodLabel(): string
    {
        if (! $this->isManual()) {
            return 'درگاه اینترنتی' . ($this->gateway ? ' (' . $this->gateway . ')' : '');
        }

        return self::METHODS[$this->method] ?? 'نامشخص';
    }

    /** کلاس رنگ نشان وضعیت در پنل مدیریت. */
    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'paid'     => 'bg-success',
            'rejected' => 'bg-danger',
            'failed'   => 'bg-secondary',
            default    => 'bg-warning text-dark',
        };
    }

    /** آدرس تصویر رسید؛ خالی یعنی مشتری فایلی نگذاشته است. */
    public function receiptUrl(): ?string
    {
        return $this->receipt_image ?: null;
    }
}
