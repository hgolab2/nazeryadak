<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function loginAdmin(Request $request)
    {
        $user = Auth::user();
        if($user)
        {
            return redirect(session('url.intended') ?? '/dashboardAdmin');
        }
        return view( 'auth.loginAdmin');
    }

    public function verifyLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);
        if ($validator->fails()) {
            return back()->withErrors([l('کلمه کاربری یا پسورد3 اشتباه است')]);
        }
        // محدودیت تعداد تلاش‌های ورود
        $key = 'login-attempt-' . $request->username;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors([l('تعداد تلاش‌های ورود زیاد است. لطفا بعدا امتحان کنید.')]);
        }
        $user = User::where('username', $request->username)->where('active',1)->first();
        //dd($user);
        if ($user && Hash::check($request->password, $user->password))
        {
            // ورود موفق
            RateLimiter::clear($key); // پاک کردن محدودیت تلاش‌های اشتباه
            session(['langid' => 1]);
            Auth::loginUsingId($user->user_id, true);
            return redirect("/dashboardAdmin");
        }
        else
        {
            // ثبت تلاش ناموفق
            RateLimiter::hit($key, 600); // اعمال محدودیت تلاش‌های ناموفق برای 60 ثانیه

            // ثبت لاگ برای تلاش‌های ناموفق
            Log::warning('Failed login attempt', ['username' => $request->username, 'ip' => $request->ip()]);

            return back()->withErrors([l('کلمه کاربری یا پسورد2 اشتباه است')]);
        }
    }

    public function logout(Request $request)
    {
        $wasAdmin = Auth::check();

        Auth::guard('customer')->logout();
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect($wasAdmin ? '/loginAdmin' : '/');
    }

    public function login(Request $request)
    {
        if (Auth::guard('customer')->check()) {
            return redirect(session('url.intended') ?? '/dashboard');
        }
        if ($request->has('redirect')) {
            session()->put('url.intended', $request->redirect);
        }
        return view('auth.login');
    }

    /**
     * یکسان‌سازی شماره‌ی موبایل: ارقام فارسی/عربی به لاتین، حذف فاصله و
     * خط تیره، و تبدیل +98 و 0098 و 98 به شکل 09xxxxxxxxx.
     *
     * قبلا اعتبارسنجی فقط ^09\d{9}$ بود، پس «۰۹۱۲۳۴۵۶۷۸۹» که کیبورد فارسی
     * موبایل تولید می‌کند رد می‌شد — همان قالبی که در placeholder نوشته شده بود.
     */
    public static function normalizeMobile(?string $value): string
    {
        $value = strtr((string) $value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);
        $value = preg_replace('/[^0-9+]/', '', $value);

        if (str_starts_with($value, '+98')) {
            $value = '0' . substr($value, 3);
        } elseif (str_starts_with($value, '0098')) {
            $value = '0' . substr($value, 4);
        } elseif (str_starts_with($value, '98') && strlen($value) === 12) {
            $value = '0' . substr($value, 2);
        } elseif (strlen($value) === 10 && str_starts_with($value, '9')) {
            $value = '0' . $value;
        }

        return $value;
    }

    private const MOBILE_RULES = ['mobile' => 'required|regex:/^09\d{9}$/'];

    private const MOBILE_MESSAGES = [
        'mobile.required' => 'لطفا شماره موبایل خود را وارد کنید.',
        'mobile.regex'    => 'شماره موبایل باید ۱۱ رقم و به شکل ۰۹۱۲۳۴۵۶۷۸۹ باشد.',
    ];

    public function sendOtp(Request $request)
    {
        $request->merge(['mobile' => self::normalizeMobile($request->mobile)]);

        $request->validate(self::MOBILE_RULES, self::MOBILE_MESSAGES);

        $key = 'otp-' . $request->mobile;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'status' => 'error',
                'message' => "تعداد درخواست زیاد است. لطفا {$seconds} ثانیه صبر کنید."
            ], 429);
        }
        RateLimiter::hit($key, 120);

        $otp = rand(100000, 999999);

        $user = Customer::updateOrCreate(
            ['phone' => $request->mobile],
            [
                'otp_code' => $otp,
                'otp_expires_at' => now()->addMinutes(2)
            ]
        );

        sendSms($request->mobile, "کد فعالسازی شما: {$otp}");

        return response()->json([
            'status' => 'success',
            'step' => 'verify'
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $request->merge([
            'mobile' => self::normalizeMobile($request->mobile),
            'otp'    => self::normalizeMobile($request->otp),
        ]);

        $request->validate([
            'mobile' => 'required',
            'otp' => 'required|digits:6'
        ], [
            'otp.required' => 'لطفا کد تأیید را وارد کنید.',
            'otp.digits'   => 'کد تأیید باید ۶ رقم باشد.',
        ]);

        $user = Customer::where('phone', $request->mobile)
            ->where('otp_code', $request->otp)
            ->where('otp_expires_at', '>', now())
            ->first();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'کد نامعتبر یا منقضی شده'
            ], 422);
        }

        // پاکسازی OTP
        $user->update([
            'otp_code' => null,
            'otp_expires_at' => null
        ]);

        Auth::guard('customer')->login($user, true);

        $redirect = session()->pull('url.intended', '/dashboard');

        return response()->json([
            'status' => 'success',
            'redirect' => $redirect,
        ]);
    }

}
