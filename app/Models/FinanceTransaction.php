<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * یک سطر از دفتر درآمد و هزینه.
 *
 * درآمد فروش سفارش‌ها اینجا ثبت نمی‌شود — از خود جدول orders خوانده می‌شود
 * (FinanceReport). این جدول برای چیزهایی است که سیستم خودش نمی‌داند: پول
 * سرور، پیامک، هزینه‌ی پستِ یک سفارش، یا درآمدی بیرون از سایت.
 */
class FinanceTransaction extends Model
{
    public const TYPE_INCOME  = 'income';
    public const TYPE_EXPENSE = 'expense';

    public const TYPES = [
        self::TYPE_EXPENSE => 'هزینه',
        self::TYPE_INCOME  => 'درآمد',
    ];

    /**
     * دسته‌ها به تفکیک نوع. کلید در دیتابیس می‌نشیند و برچسب در پنل.
     * دسته‌های «سفارشی» آن‌هایی‌اند که معمولا بابت یک سفارش خاص خرج می‌شوند.
     */
    public const CATEGORIES = [
        self::TYPE_EXPENSE => [
            'shipping'  => 'پست و حمل',
            'packaging' => 'بسته‌بندی',
            'purchase'  => 'خرید کالا',
            'server'    => 'سرور و هاست',
            'domain'    => 'دامنه و SSL',
            'sms'       => 'پیامک',
            'ads'       => 'تبلیغات',
            'fees'      => 'کارمزد درگاه و بانک',
            'salary'    => 'حقوق و دستمزد',
            'refund'    => 'عودت وجه',
            'other'     => 'سایر هزینه‌ها',
        ],
        self::TYPE_INCOME => [
            'sale'    => 'فروش خارج از سایت',
            'service' => 'خدمات',
            'other'   => 'سایر درآمدها',
        ],
    ];

    protected $fillable = [
        'type',
        'category',
        'title',
        'amount',
        'order_id',
        'occurred_on',
        'note',
        'created_by',
    ];

    protected $casts = [
        'amount'      => 'integer',
        'occurred_on' => 'date',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function scopeExpenses($query)
    {
        return $query->where('type', self::TYPE_EXPENSE);
    }

    public function scopeIncomes($query)
    {
        return $query->where('type', self::TYPE_INCOME);
    }

    /**
     * بازه‌ی تاریخ (شامل هر دو سر).
     *
     * کران‌ها با ساعت نوشته می‌شوند تا اگر ستون به‌شکل datetime ذخیره شده باشد
     * (sqlite در تست‌ها) روز آخر جا نیفتد؛ MySQL هم DATE را با datetime درست
     * مقایسه می‌کند.
     */
    public function scopeBetween($query, $from, $to)
    {
        $from = substr((string) $from, 0, 10) . ' 00:00:00';
        $to   = substr((string) $to, 0, 10) . ' 23:59:59';

        return $query->whereBetween('occurred_on', [$from, $to]);
    }

    public function isExpense(): bool
    {
        return $this->type === self::TYPE_EXPENSE;
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->type][$this->category] ?? $this->category;
    }

    /** برچسب دسته بدون دانستن نوع؛ برای گزارش‌ها که فقط کلید را دارند. */
    public static function categoryName(string $category): string
    {
        foreach (self::CATEGORIES as $categories) {
            if (isset($categories[$category])) {
                return $categories[$category];
            }
        }

        return $category;
    }
}
