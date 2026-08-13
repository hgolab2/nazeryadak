<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * یک ردیف از تنظیمات ارسال (کلید/مقدار).
 * مقادیر مبلغی به ریال ذخیره می‌شوند — تبدیل به تومان در getShippingRules().
 */
class ShippingSetting extends Model
{
    protected $table = 'shipping_settings';

    protected $fillable = [
        'setting_key',
        'setting_value',
        'description',
    ];
}
