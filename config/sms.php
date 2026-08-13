<?php

return [

    /*
    |--------------------------------------------------------------------------
    | درگاه پیامک (tsms.ir)
    |--------------------------------------------------------------------------
    |
    | این مقادیر قبلا مستقیم با env() در app/Helpers/Helper.php خوانده می‌شدند.
    | اگر روی سرور «php artisan config:cache» اجرا شود، env() بیرون از فایل‌های
    | config مقدار null برمی‌گرداند و ورود با رمز یکبارمصرف از کار می‌افتد.
    |
    */

    'url'      => env('SMS_URL', 'http://tsms.ir/url/tsmshttp.php'),
    'number'   => env('SMS_NUMBER'),
    'username' => env('SMS_USERNAME'),
    'password' => env('SMS_PASSWORD'),

];
