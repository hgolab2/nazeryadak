<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * یک ردیف به‌ازای هر مسیر ۴۰۴ (نه هر بازدید).
 *
 * کاربردش پیدا کردن لینک‌های شکسته‌ی داخلی و آدرس‌هایی است که گوگل هنوز
 * سراغشان می‌آید؛ از روی همین فهرست می‌شود مستقیم ریدایرکت ساخت.
 */
class NotFoundLog extends Model
{
    protected $table = 'not_found_logs';

    protected $fillable = ['path', 'referer', 'user_agent', 'hits', 'last_seen_at'];

    protected $casts = [
        'hits'         => 'integer',
        'last_seen_at' => 'datetime',
    ];
}
