<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * یک ردیف به‌ازای هر خطای سرور.
 *
 * نام جدول «errorlog» است (نه errror_logs)؛ جدول از قبل روی سرور ساخته شده
 * بود و تغییر نامش یعنی از دست دادن سابقه، پس همان نام نگه داشته شد.
 *
 * پر کردن ردیف‌ها کار App\Services\ErrorReporter است؛ مستقیم از جایی دیگر
 * چیزی اینجا ننویسید تا قواعد کوتاه‌سازی و پاک‌سازی داده‌ی حساس دور زده نشود.
 */
class ErrorLog extends Model
{
    protected $table = 'errorlog';

    protected $fillable = [
        'message', 'stack_trace', 'level', 'url', 'route_name', 'method',
        'request_data', 'user_id', 'code', 'file', 'line', 'fullurl', 'ip',
    ];

    protected $casts = [
        'line'    => 'integer',
        'user_id' => 'integer',
    ];
}
