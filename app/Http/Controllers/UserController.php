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

    public function sendOtp(Request $request)
    {
        $request->validate([
            'mobile' => 'required|regex:/^09\d{9}$/'
        ]);

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
        $request->validate([
            'mobile' => 'required',
            'otp' => 'required|digits:6'
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
