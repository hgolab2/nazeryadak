<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
        $exceptions->render(function (Throwable $e, Request $request) {
            try {


                try {
                    $level = method_exists($e, 'getStatusCode') && $e->getStatusCode() < 500 ? 'warning' : 'error';
                    $userid = Auth::check() ? Auth::id() : "";
                    $line = $e->getLine() ?? 0;
                    $ip = $request->ip() ?? '';
                    $text =
                        "⚠️ Laravel Error Alert\n".
                        "Level:{$level}\n".
                        "Message: {$e->getMessage()}\n".
                        "File: {$e->getFile()}:{$e->getLine()}\n".
                        "URL: ".$request->fullUrl()."\n".
                        "User_id: {$userid}\n".
                        "Line: {$line}\n".
                        "IP: {$ip}\n".
                        "Time: ".now();

                    $token   = env('BALE_BOT_TOKEN');
                    $chat_id = env('BALE_CHAT_ID');

                    $url = "https://tapi.bale.ai/bot{$token}/sendMessage";

                    file_get_contents($url . "?" . http_build_query([
                        'chat_id' => $chat_id,
                        'text'    => $text
                    ]));

                } catch (\Throwable $telegramError) {
                    // ignore
                }


            } catch (\Exception $e) {
                // جلوگیری از خطای بیشتر
            }
        });
    })->create();
