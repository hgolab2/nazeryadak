<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\OtpService;
use App\Support\Mobile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;

/**
 * ورود و ثبت‌نام مشتری.
 *
 * فلو یک مسیر دارد و سرور تصمیم می‌گیرد، نه کاربر:
 *
 *   ۱) شماره موبایل  →  /auth/check
 *   ۲) اگر برای این شماره رمز ثبت شده باشد: صفحه‌ی رمز (پیش‌فرض) با گزینه‌ی
 *      «ورود با کد یکبارمصرف» و «فراموشی رمز».
 *      اگر رمز نداشته باشد یا اصلا حساب نداشته باشد: کد پیامکی، بدون اینکه
 *      کاربر گزینه‌ای ببیند که برایش کار نمی‌کند.
 *   ۳) حساب تازه فقط بعد از تأیید کد ساخته می‌شود و بلافاصله یک گام کوتاه
 *      برای نام و (اختیاری) رمز عبور نشان داده می‌شود.
 */
class CustomerAuthController extends Controller
{
    public function login(Request $request)
    {
        if (Auth::guard('customer')->check()) {
            return redirect(session()->pull('url.intended', '/dashboard'));
        }

        // فقط مسیر داخلی؛ «?redirect=https://…» می‌توانست کاربر را بعد از ورود
        // به سایت دیگری بفرستد و همان صفحه را جعل کند.
        if ($request->filled('redirect') && preg_match('#^/(?!/)[^\s]*$#', $request->redirect)) {
            session()->put('url.intended', $request->redirect);
        }

        return view('auth.login');
    }

    /**
     * گام اول: این شماره چه راهی برای ورود دارد؟
     *
     * برای حسابی که رمز دارد، پاسخ «رمز» است و کدی فرستاده نمی‌شود؛ برای
     * بقیه همان‌جا کد پیامک می‌شود تا کاربر یک کلیک اضافه نکند.
     */
    public function check(Request $request)
    {
        $mobile = $this->validatedMobile($request);

        // پاسخ این مسیر می‌گوید کدام شماره حساب دارد؛ بدون سقف، می‌شد با آن
        // فهرست مشتری‌ها را از روی بازه‌ی شماره‌ها استخراج کرد.
        $key = 'auth-check-' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 40)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'تعداد درخواست زیاد است. کمی بعد دوباره تلاش کنید.',
            ], 429);
        }
        RateLimiter::hit($key, 600);

        $customer = Customer::where('phone', $mobile)->first();

        if ($customer && ! $this->isActive($customer)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'حساب شما غیرفعال است. لطفا با پشتیبانی تماس بگیرید.',
            ], 403);
        }

        if ($customer && ! empty($customer->password)) {
            return response()->json([
                'status'   => 'success',
                'step'     => 'password',
                'is_new'   => false,
                'greeting' => $customer->fullName() !== '' ? $customer->fullName() : null,
            ]);
        }

        return $this->dispatchOtp($request, $mobile, ! $customer);
    }

    /** ارسال یا ارسال مجدد کد؛ هم برای گام دوم، هم دکمه‌ی «ارسال مجدد». */
    public function sendOtp(Request $request)
    {
        $mobile = $this->validatedMobile($request);

        $exists = Customer::where('phone', $mobile)->exists();

        return $this->dispatchOtp($request, $mobile, ! $exists);
    }

    private function dispatchOtp(Request $request, string $mobile, bool $isNew)
    {
        $result = OtpService::send($mobile, OtpService::PURPOSE_LOGIN, $request->ip());

        if (! $result['ok']) {
            return response()->json([
                'status'  => 'error',
                'message' => $result['message'],
                'wait'    => $result['wait'],
            ], 429);
        }

        return response()->json(array_filter([
            'status'  => 'success',
            'step'    => 'otp',
            'is_new'  => $isNew,
            'wait'    => $result['wait'],
            'masked'  => Mobile::mask($mobile),
            'dev_code' => $result['code'] ?? null,
        ], fn ($value) => $value !== null));
    }

    /**
     * تأیید کد: ورود برای حساب موجود، ثبت‌نام برای شماره‌ی تازه.
     */
    public function verifyOtp(Request $request)
    {
        $mobile = $this->validatedMobile($request);

        $request->merge(['otp' => Mobile::digits($request->otp)]);
        $request->validate(['otp' => 'required|digits:6'], [
            'otp.required' => 'لطفا کد تأیید را وارد کنید.',
            'otp.digits'   => 'کد تأیید باید ۶ رقم باشد.',
        ]);

        if (! OtpService::verify($mobile, OtpService::PURPOSE_LOGIN, $request->otp)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'کد وارد شده نادرست یا منقضی شده است.',
            ], 422);
        }

        $customer = Customer::where('phone', $mobile)->first();
        $isNew    = ! $customer;

        if ($isNew) {
            $customer = Customer::create(['phone' => $mobile, 'status' => 1]);
        } elseif (! $this->isActive($customer)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'حساب شما غیرفعال است. لطفا با پشتیبانی تماس بگیرید.',
            ], 403);
        }

        $this->startSession($request, $customer);

        // حسابی که هنوز نام ندارد، یک گام دیگر در همین کارت می‌بیند: نام و
        // (اختیاری) رمز عبور. جای درست پرسیدن رمز همین‌جاست — نه صفحه‌ی ورود،
        // که کاربر هنوز حسابی ندارد تا برایش رمز داشته باشد.
        $needsProfile = $isNew || $customer->fullName() === '';

        return response()->json([
            'status'   => 'success',
            'is_new'   => $isNew,
            'step'     => $needsProfile ? 'profile' : 'done',
            // در گام پروفایل، مقصد فقط خوانده می‌شود و در نشست می‌ماند تا
            // completeProfile هم بتواند کاربر را به همان‌جا برگرداند.
            'redirect' => $needsProfile
                ? session('url.intended', '/dashboard')
                : session()->pull('url.intended', '/dashboard'),
        ]);
    }

    /**
     * گام پایانی ثبت‌نام: نام، و رمز عبور اگر بخواهد.
     *
     * رمز اختیاری است؛ کسی که آن را رد کند همچنان با کد پیامکی وارد می‌شود.
     */
    public function completeProfile(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer) {
            return response()->json(['status' => 'error', 'message' => 'ابتدا وارد شوید.'], 401);
        }

        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'password'   => ['nullable', 'confirmed', Password::min(6)],
        ], [
            'first_name.required' => 'نام خود را وارد کنید.',
            'last_name.required'  => 'نام خانوادگی خود را وارد کنید.',
            'password.confirmed'  => 'تکرار رمز عبور با رمز یکسان نیست.',
            'password.min'        => 'رمز عبور باید حداقل ۶ کاراکتر باشد.',
        ]);

        $customer->first_name = $request->first_name;
        $customer->last_name  = $request->last_name;

        if ($request->filled('password')) {
            $customer->password = Hash::make($request->password);
        }

        $customer->save();

        return response()->json([
            'status'   => 'success',
            'redirect' => session()->pull('url.intended', '/dashboard'),
        ]);
    }

    /** ورود با رمز عبور؛ راه پیش‌فرض کسی که رمز تنظیم کرده است. */
    public function loginWithPassword(Request $request)
    {
        $mobile = $this->validatedMobile($request);

        $request->validate(
            ['password' => 'required|string'],
            ['password.required' => 'رمز عبور را وارد کنید.']
        );

        // همان محدودیتی که روی ارسال کد هست، اینجا هم لازم است تا رمز را
        // نشود با تلاش پشت‌سرهم حدس زد.
        $key = 'login-password-' . $mobile;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'تعداد تلاش زیاد است. لطفا ' . RateLimiter::availableIn($key) . ' ثانیه صبر کنید.',
            ], 429);
        }
        RateLimiter::hit($key, 300);

        $customer = Customer::where('phone', $mobile)->first();

        if (! $customer || empty($customer->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'برای این شماره رمز عبوری ثبت نشده است. با کد پیامکی وارد شوید.',
                'step'    => 'otp',
            ], 422);
        }

        if (! Hash::check($request->password, $customer->password)) {
            Log::warning('Failed customer password login', ['mobile' => $mobile, 'ip' => $request->ip()]);

            return response()->json([
                'status'  => 'error',
                'message' => 'رمز عبور درست نیست.',
            ], 422);
        }

        if (! $this->isActive($customer)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'حساب شما غیرفعال است. لطفا با پشتیبانی تماس بگیرید.',
            ], 403);
        }

        RateLimiter::clear($key);
        $this->startSession($request, $customer);

        return response()->json([
            'status'   => 'success',
            'redirect' => session()->pull('url.intended', '/dashboard'),
        ]);
    }

    /** شماره‌ی نرمال‌شده و معتبر؛ در غیر این صورت خطای اعتبارسنجی. */
    private function validatedMobile(Request $request): string
    {
        $request->merge(['mobile' => Mobile::normalize($request->mobile)]);
        $request->validate(Mobile::RULES, Mobile::MESSAGES);

        return $request->mobile;
    }

    /**
     * ستون status ملاک است؛ active ستون قدیمی و روی بیشتر ردیف‌ها خالی است.
     */
    private function isActive(Customer $customer): bool
    {
        return $customer->status === null || (bool) $customer->status;
    }

    private function startSession(Request $request, Customer $customer): void
    {
        Auth::guard('customer')->login($customer, true);

        // بازتولید نشست بعد از ورود، جلوی session fixation را می‌گیرد.
        $request->session()->regenerate();

        $customer->forceFill([
            'last_login_at' => now(),
            'otp_code'      => null,
            'otp_expires_at' => null,
        ])->save();
    }
}
