<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OtpService;
use App\Support\Mobile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

/**
 * تأیید شماره با کدی که همراه پیامک ثبت سفارش رفته است.
 *
 * این مسیر عمدا «اختیاری» است: سفارش از قبل ثبت و پرداخت شده و اگر مشتری کد
 * را وارد نکند هیچ‌چیز خراب نمی‌شود؛ کارشناس فروش به‌هرحال تماس می‌گیرد.
 * چیزی که با زدن کد به دست می‌آید، صاحب‌شدن حساب است: سابقه‌ی سفارش‌ها و
 * آدرس ذخیره‌شده برای خرید بعدی.
 *
 * کد این مسیر ۲۴ ساعت زنده است (برخلاف کد ورود که ۱۲۰ ثانیه است)، چون مشتری
 * ممکن است ساعت‌ها بعد پیامک را بخواند. همین طولانی‌بودن یعنی نباید به تنهایی
 * کلید ورود باشد؛ پس فقط در همان مرورگری پذیرفته می‌شود که سفارش را ثبت کرده
 * یا از قبل به همان حساب وارد است.
 */
class PhoneVerificationController extends Controller
{
    /** کلید نشست: سفارش‌هایی که این مرورگر حق تأییدشان را دارد. */
    private const SESSION_KEY = 'phone_verify_orders';

    private const MAX_ATTEMPTS  = 10;
    private const DECAY_SECONDS = 600;

    /**
     * «این مرورگر همین الان این سفارش را ثبت کرد.»
     *
     * از مسیر ثبت سفارش صدا زده می‌شود تا بعدا بشود کد را پذیرفت، حتی اگر
     * مشتری وارد حساب نشده باشد.
     */
    public static function allow(int $orderId): void
    {
        $orders = (array) session()->get(self::SESSION_KEY, []);

        if (! in_array($orderId, $orders, true)) {
            $orders[] = $orderId;
            // فهرست نباید بی‌نهایت رشد کند؛ چند سفارش آخر کافی است.
            session()->put(self::SESSION_KEY, array_slice($orders, -5));
        }
    }

    /** آیا این مرورگر اجازه‌ی تأیید این سفارش را دارد؟ */
    public static function allows(int $orderId): bool
    {
        return in_array($orderId, (array) session()->get(self::SESSION_KEY, []), true);
    }

    /**
     * آیا باید جعبه‌ی تأیید را در صفحه‌ی نتیجه‌ی سفارش نشان دهیم؟
     *
     * فقط وقتی واقعا کاری از آن برمی‌آید: مشتری‌ای هست، شماره‌ای دارد و آن
     * شماره هنوز تأیید نشده. برای مشتری تأییدشده هیچ کدی هم فرستاده نشده.
     */
    public static function needed(?Order $order): bool
    {
        $customer = $order?->customer;

        return $customer !== null
            && ! empty($customer->phone)
            && ! $customer->hasVerifiedPhone();
    }

    public function verify(Request $request)
    {
        $request->merge([
            'code'     => Mobile::digits($request->input('code')),
            'order_id' => Mobile::digits($request->input('order_id')),
        ]);

        $request->validate([
            'order_id' => 'required|integer',
            'code'     => 'required|digits:6',
        ], [
            'code.required' => 'کد تأیید را وارد کنید.',
            'code.digits'   => 'کد تأیید ۶ رقم است.',
        ]);

        // شش رقم را می‌شود حدس زد؛ بدون سقف، ۲۴ ساعت فرصت کافی است.
        $key = 'verify-phone-' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return $this->fail($request, 'تعداد تلاش‌ها زیاد است. کمی بعد دوباره امتحان کنید.', 429);
        }
        RateLimiter::hit($key, self::DECAY_SECONDS);

        $order    = Order::with('customer')->find((int) $request->order_id);
        $customer = $order?->customer;
        $loggedIn = Auth::guard('customer')->user();

        // اجازه یا از نشستِ ثبت سفارش می‌آید یا از اینکه سفارش مال همین
        // کاربرِ واردشده است. پیام خطا عمدا کلی است تا نشود با شماره‌ی
        // سفارش فهمید کدام سفارش وجود دارد.
        $permitted = $order
            && (self::allows($order->id) || ($loggedIn && (int) $order->customer_id === (int) $loggedIn->id));

        if (! $permitted || ! $customer || empty($customer->phone)) {
            return $this->fail($request, 'کد وارد شده نادرست یا منقضی شده است.');
        }

        if ($customer->hasVerifiedPhone()) {
            // مثلا کاربر دکمه را دوبار زده؛ این خطا نیست.
            return $this->done($request, 'شماره‌ی شما از قبل تأیید شده است.');
        }

        if (! OtpService::verify($customer->phone, OtpService::PURPOSE_ORDER, $request->code)) {
            return $this->fail($request, 'کد وارد شده نادرست یا منقضی شده است.');
        }

        $customer->markPhoneVerified();

        // مهمانی که تازه شماره‌اش را تأیید کرده، از همین‌جا صاحب حسابش
        // می‌شود. سفارش‌های قبلی‌اش جای دیگری نیستند: از ابتدا به همین
        // ردیف مشتری وصل شده‌اند، پس چیزی برای انتقال وجود ندارد.
        if (! $loggedIn) {
            Auth::guard('customer')->login($customer, true);
            $request->session()->regenerate();
        }

        return $this->done($request, 'شماره‌ی شما تأیید شد. سابقه‌ی سفارش‌هایتان در حساب کاربری ذخیره شد.');
    }

    private function done(Request $request, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['status' => 'success', 'message' => $message, 'csrf' => csrf_token()]);
        }

        return back()->with('success', $message);
    }

    private function fail(Request $request, string $message, int $code = 422)
    {
        if ($request->expectsJson()) {
            return response()->json(['status' => 'error', 'message' => $message], $code);
        }

        return back()->with('error', $message);
    }
}
