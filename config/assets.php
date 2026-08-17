<?php

/*
|--------------------------------------------------------------------------
| فایل‌های ظاهری سایت
|--------------------------------------------------------------------------
| سرور روی HTTP/1.1 است و مرورگر همزمان بیش از شش اتصال به یک دامنه باز
| نمی‌کند؛ یازده فایل CSS یعنی یازده رفت‌وبرگشت که همه‌شان رندر را متوقف
| می‌کنند. دستور `php artisan assets:css` این فهرست را به یک فایل تبدیل
| می‌کند و قالب، همان یک فایل را صدا می‌زند.
|
| ترتیب مهم است و باید دقیقا همان ترتیب قبلی در <head> بماند، وگرنه
| قاعده‌های بعدی، قبلی‌ها را بازنویسی نمی‌کنند.
*/

return [

    'css' => [

        // خروجی ادغام‌شده، نسبت به public
        'bundle' => '/assets/css/app.bundle.css',

        'sources' => [
            '/assets/css/bootstrap.rtl.css',
            '/assets/fontawesome/css/all.min.css',
            '/assets/css/owl.carousel.min.css',
            '/assets/css/owl.theme.default.min.css',
            '/assets/css/style.css',
            '/assets/css/home-digikala.css',
            '/assets/css/mobile-appbar.css',
            '/assets/css/mobile-checkout.css',
            '/assets/css/account.css',
            '/assets/css/search-suggest.css',
            '/assets/css/auth.css',
        ],
    ],

    /*
    | اسکریپت‌های انتهای <body>.
    |
    | فقط آن‌هایی که defer ندارند اینجا می‌آیند: ادغام، ترتیب اجرا را دقیقا
    | حفظ می‌کند ولی اسکریپت defer‌دار بعد از پارس صفحه اجرا می‌شود و
    | آوردنش داخل باندل، زمان اجرایش را جلو می‌اندازد.
    |
    | اسکریپت‌های inline بعد از این باندل می‌آیند و به همین ترتیب هم اجرا
    | می‌شوند، پس کدی که به jQuery یا Swal وابسته است سالم می‌ماند.
    */
    'js' => [

        'bundle' => '/assets/js/app.bundle.js',

        'sources' => [
            '/assets/js/jquery.min.js',
            '/assets/js/bootstrap.bundle.min.js',
            '/assets/js/owl.carousel.min.js',
            '/assets/js/jquery.simple.timer.js',
            '/assets/js/script.js',
            '/js/sweetalert2.all.js',
        ],
    ],
];
