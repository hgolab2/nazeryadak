<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

/**
 * پیگیری سفارش بدون نیاز به ورود به حساب کاربری.
 *
 * مشتری شماره‌ی سفارش و شماره‌ی موبایلی که سفارش با آن ثبت شده را می‌دهد و
 * وضعیت سفارش را می‌بیند. شماره‌ی موبایل نقش رمز را دارد، پس تعداد تلاش‌ها
 * برای هر IP محدود شده و پیام خطا هم عمدا کلی است تا نشود با حدس‌زدن
 * شماره‌ی سفارش فهمید کدام سفارش وجود دارد.
 */
class OrderTrackingController extends Controller
{
    private const MAX_ATTEMPTS   = 10;
    private const DECAY_SECONDS  = 300;

    public function form(Request $request)
    {
        // بعد از خطای اعتبارسنجی، Laravel به همین مسیر برمی‌گردد؛ مقدارهای
        // قبلی از old() می‌آیند تا کاربر دوباره تایپ نکند.
        return view('order.track', [
            'order'   => null,
            'orderId' => old('order_id', $request->query('order')),
            'mobile'  => old('mobile', Auth::guard('customer')->user()?->phone),
            'error'   => null,
        ]);
    }

    public function track(Request $request)
    {
        $request->merge([
            'order_id' => self::normalizeDigits($request->input('order_id')),
            'mobile'   => UserController::normalizeMobile($request->input('mobile')),
        ]);

        $request->validate([
            'order_id' => 'required|regex:/^\d{1,12}$/',
            'mobile'   => 'required|regex:/^09\d{9}$/',
        ], [
            'order_id.required' => 'شماره سفارش را وارد کنید.',
            'order_id.regex'    => 'شماره سفارش فقط عدد است؛ آن را از پیامک یا صفحه‌ی سفارش‌ها بردارید.',
            'mobile.required'   => 'شماره موبایل را وارد کنید.',
            'mobile.regex'      => 'شماره موبایل باید ۱۱ رقم و به شکل ۰۹۱۲۳۴۵۶۷۸۹ باشد.',
        ]);

        $key = 'track-order-' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($key);

            return view('order.track', [
                'order'   => null,
                'orderId' => $request->input('order_id'),
                'mobile'  => $request->input('mobile'),
                'error'   => "تعداد درخواست‌ها زیاد است. لطفا {$seconds} ثانیه دیگر دوباره تلاش کنید.",
            ]);
        }
        RateLimiter::hit($key, self::DECAY_SECONDS);

        $mobile = $request->input('mobile');

        // سفارش‌های draft هنوز سبد خرید نیمه‌کاره‌اند و شماره‌ی سفارش واقعی ندارند.
        $order = Order::with(['items.product.categories', 'shippingMethod', 'address.province', 'customer'])
            ->where('id', $request->input('order_id'))
            ->where('status', '!=', 'draft')
            ->where(function ($query) use ($mobile) {
                $query->whereHas('customer', fn ($c) => $c->where('phone', $mobile))
                      ->orWhereHas('address', fn ($a) => $a->where('receiver_phone', $mobile));
            })
            ->first();

        if (! $order) {
            return view('order.track', [
                'order'   => null,
                'orderId' => $request->input('order_id'),
                'mobile'  => $mobile,
                'error'   => 'سفارشی با این شماره سفارش و شماره موبایل پیدا نشد. شماره‌ها را از پیامک تأیید سفارش بررسی کنید.',
            ]);
        }

        RateLimiter::clear($key);

        return view('order.track', [
            'order'   => $order,
            'orderId' => $order->id,
            'mobile'  => $mobile,
            'error'   => null,
        ]);
    }

    /** ارقام فارسی/عربی به لاتین و حذف هر چیزی جز عدد (مثلا «سفارش #۱۲۳»). */
    private static function normalizeDigits(?string $value): string
    {
        $value = strtr((string) $value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        $digits = preg_replace('/\D/', '', $value);

        // «۰۰۱۲» همان سفارش ۱۲ است؛ ولی رشته‌ی تماما صفر نباید خالی شود.
        return ltrim($digits, '0') ?: ($digits === '' ? '' : '0');
    }
}
