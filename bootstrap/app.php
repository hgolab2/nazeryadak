<?php

use App\Services\ErrorReporter;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // یکسان‌سازی آدرس‌ها (https / بدون www / بدون پارامتر ردیابی) پیش از
        // هر پردازش دیگری، تا گوگل از هر صفحه فقط یک نسخه ببیند.
        //
        // SeoRedirect بعد از آن می‌آید تا قواعد ریدایرکتِ پنل روی آدرسِ
        // یکسان‌شده اعمال شود، نه روی نسخه‌ی حاوی utm یا www.
        $middleware->prepend([
            \App\Http\Middleware\CanonicalUrl::class,
            \App\Http\Middleware\SeoRedirect::class,
        ]);

        // سایت پشت پروکسی/CDN اجرا می‌شود؛ بدون این تنظیم، Laravel آدرس‌های
        // مطلق را http می‌سازد و canonical با آدرس واقعی صفحه فرق می‌کند.
        $middleware->trustProxies(at: '*');

        // کاربرِ واردنشده‌ی مسیرهای پنل باید به صفحه‌ی ورود پنل برود، نه به
        // صفحه‌ی ورود مشتری؛ مقصد پیش‌فرض لاراول مسیرِ نام‌گذاری‌شده‌ی login است
        // که همان ورود مشتری با پیامک است و مدیر آنجا کاری نمی‌تواند بکند.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin/*', 'dashboardAdmin')
            ? '/loginAdmin'
            : '/login');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        /*
        | هر خطای سرور در جدول errorlog ثبت و در بله اطلاع‌رسانی می‌شود.
        |
        | این کار در report انجام می‌شود نه render، چون:
        |
        |   - خطای دستورهای artisan و صف هم باید ثبت شود؛ render فقط برای
        |     درخواست‌های وب صدا زده می‌شود.
        |   - لاراول خودش استثناهای بی‌اهمیت (۴۰۴، ۴۱۹، خطای اعتبارسنجی،
        |     ورود نکردن کاربر) را گزارش نمی‌کند، پس دیگر لازم نیست دستی
        |     فیلترشان کنیم. مسیرهای ۴۰۴ همچنان در «مانیتور ۴۰۴» ثبت می‌شوند.
        |
        | نسخه‌ی قبلی این بخش توکن بله را با env() می‌خواند؛ روی سرور بعد از
        | «php artisan config:cache» مقدارِ env بیرون از فایل‌های config همیشه
        | null است و هشدارها بی‌سروصدا قطع می‌شدند. حالا از config/bale.php
        | خوانده می‌شود.
        */
        $exceptions->report(function (Throwable $e) {
            ErrorReporter::capture($e);
        });
    })->create();
