<?php

/*
|--------------------------------------------------------------------------
| تنظیمات مرکزی سئو
|--------------------------------------------------------------------------
| همه‌ی اطلاعات هویتی، ساختاریافته و ابزارهای تحلیلی سایت از اینجا خوانده
| می‌شوند. هر تغییری در نام، آدرس، تلفن یا شبکه‌های اجتماعی فقط در همین
| فایل انجام می‌شود تا خروجی متاتگ‌ها، Schema، sitemap و robots هماهنگ بماند.
*/

return [

    // آدرس قطعی سایت در محیط پروداکشن. اگر SEO_BASE_URL خالی باشد از APP_URL
    // استفاده می‌شود. تنظیم این مقدار روی سرور الزامی است چون تمام لینک‌های
    // مطلق (canonical، og:url، sitemap) از آن ساخته می‌شوند.
    'base_url' => env('SEO_BASE_URL', env('APP_URL', 'https://nazeryadak.ir')),

    'site_name'      => 'ناظر یدک',
    'site_name_en'   => 'Nazer Yadak',
    'alternate_name' => ['nazeryadak', 'ناظریدک', 'فروشگاه قطعات ایساکو ناظر یدک'],
    'locale'         => 'fa_IR',
    'language'       => 'fa-IR',

    'default_title'       => 'ناظر یدک | خرید لوازم یدکی خودرو و قطعات اصلی ایساکو',
    'default_description' => 'خرید آنلاین لوازم یدکی اصلی خودرو، قطعات ایساکو، قطعات مصرفی، موتوری، برقی، بدنه و جلوبندی با ضمانت اصالت کالا و ارسال سراسر کشور از ناظر یدک.',
    'default_image'       => '/assets/images/logo.png',
    'default_image_width' => 512,
    'default_image_height'=> 512,
    'logo'                => '/assets/images/logo.png',

    'default_keywords' => 'خرید لوازم یدکی خودرو, قطعات ایساکو, لوازم یدکی ایساکو, خرید قطعات اصلی ایساکو, نمایندگی قطعات ایساکو, قطعات اصلی خودرو, خرید قطعات خودرو, فروشگاه لوازم یدکی, قطعات پژو 206, قطعات پژو 405, قطعات سمند, قطعات دنا, قطعات پراید, کد فنی قطعه خودرو',

    // اطلاعات کسب‌وکار برای Schema و صفحه تماس
    'business' => [
        'legal_name'   => 'فروشگاه لوازم یدکی ناظر یدک',
        'founder'      => 'علی حاجی ناظری',
        'phone'        => '+989127471631',
        'phone_display'=> '۰۹۱۲۷۴۷۱۶۳۱',
        'email'        => 'info@nazeryadak.ir',
        'street'       => 'خیابان انقلاب، کوچه ۴۱، پلاک ۱۵، واحد ۲۰۵',
        'city'         => 'قم',
        'region'       => 'قم',
        'postal_code'  => '3715696311',
        'country'      => 'IR',
        'latitude'     => '34.6416',
        'longitude'    => '50.8746',
        'price_range'  => '$$',
        'currency'     => 'IRR',
        // قیمت‌ها در دیتابیس به «تومان» ذخیره می‌شوند و Schema باید ریال
        // (واحد ISO ایران) بدهد؛ پس در ضریب زیر ضرب می‌شوند.
        'currency_multiplier' => 10,
        'opening_hours' => [
            ['days' => ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'], 'opens' => '09:00', 'closes' => '18:00'],
        ],
        'area_served'  => 'IR',
        'founding_date'=> '2019-01-01',
    ],

    'social' => [
        'whatsapp'  => 'https://wa.me/989127471631',
        'instagram' => env('SEO_INSTAGRAM', ''),
        'telegram'  => env('SEO_TELEGRAM', ''),
        'twitter'   => env('SEO_TWITTER', ''),
        'aparat'    => env('SEO_APARAT', ''),
        'linkedin'  => env('SEO_LINKEDIN', ''),
    ],

    // کدهای تأیید مالکیت. مقدار خالی یعنی تگ رندر نمی‌شود.
    'verification' => [
        'google'   => env('SEO_VERIFY_GOOGLE', ''),
        'bing'     => env('SEO_VERIFY_BING', ''),
        'yandex'   => env('SEO_VERIFY_YANDEX', ''),
        'enamad'   => env('SEO_VERIFY_ENAMAD', ''),
    ],

    'analytics' => [
        'ga4'   => env('SEO_GA4_ID', ''),          // G-XXXXXXX
        'gtm'   => env('SEO_GTM_ID', ''),          // GTM-XXXXXX
        'clarity' => env('SEO_CLARITY_ID', ''),
    ],

    'theme_color' => '#ffffff',

    // ریدایرکت‌های کانونیکال. روی لوکال خاموش می‌ماند تا محیط توسعه نشکند.
    'canonical' => [
        'force_https'  => env('SEO_FORCE_HTTPS', false),
        'force_host'   => env('SEO_FORCE_HOST', ''),   // مثلا nazeryadak.ir
        'strip_params' => ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid', 'yclid', 'msclkid', 'ref', 'gad_source'],
    ],

    // پارامترهایی که اجازه دارند در canonical باقی بمانند.
    // «category» اینجا نیست چون به مسیر آدرس منتقل شده: /shop/{slug}
    'canonical_query_whitelist' => ['page', 'car_model'],

    'sitemap' => [
        'cache_minutes'   => 180,
        'products_per_map'=> 2000,
    ],
];
