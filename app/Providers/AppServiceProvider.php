<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema; // این خط را اضافه کنید

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // تنظیم طول پیش‌فرض رشته برای سازگاری با MySQL
        // (رفع خطای Specified key was too long)
        Schema::defaultStringLength(191);
    }
}
