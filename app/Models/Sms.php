<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
class Sms extends Model
{
    protected $table = 'sms';
    protected $fillable = [
        'type',
        'mobile',
        'user_id',
        'text',
        'udh'
    ];
    protected $hidden = ['created_at'];

    /**
     * کاربر ثبت کننده ملک
     *
     * @return object
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    /*برای سایت شماره 10 دوبی کاربرد دارد*/
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }

    /*
    | ستون udh پاسخ خام درگاه tsms است: در ارسال موفق شناسه‌ی پیام (عددی بلند)
    | و در شکست یک کد خطای کوتاه برمی‌گردد. اگر درگاه اصلا در دسترس نبوده،
    | sendSms() مقدار null ذخیره می‌کند.
    */
    public const GATEWAY_MSG_ID_MIN = 100;

    public function wasSent(): bool
    {
        $code = trim((string) $this->udh);

        return $code !== '' && ctype_digit($code) && (int) $code >= self::GATEWAY_MSG_ID_MIN;
    }

    public function statusLabel(): string
    {
        if ($this->wasSent()) {
            return 'ارسال شد';
        }

        return trim((string) $this->udh) === '' ? 'بدون پاسخ درگاه' : 'ناموفق';
    }

    public function statusBadgeClass(): string
    {
        if ($this->wasSent()) {
            return 'bg-success';
        }

        return trim((string) $this->udh) === '' ? 'bg-secondary' : 'bg-danger';
    }

    /** جستجوی متن و شماره؛ در کنترلر و در شمارنده‌ها یکسان استفاده می‌شود */
    public function scopeSearch(Builder $query, ?string $mobile, ?string $text): Builder
    {
        if (! empty($mobile)) {
            $query->where('mobile', 'like', '%' . toLatinDigits($mobile) . '%');
        }

        if (! empty($text)) {
            $query->where('text', 'like', '%' . $text . '%');
        }

        return $query;
    }
}
