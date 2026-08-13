<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Auth\ImpersonationController;
use App\Models\User;
use App\Support\Mobile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

/**
 * ورود کاربران پنل مدیریت. ورود مشتری‌ها در Auth\CustomerAuthController است.
 */
class UserController extends Controller
{
    /** برای سازگاری با کدهای قدیمی؛ منطق در App\Support\Mobile است. */
    public static function normalizeMobile(?string $value): string
    {
        return Mobile::normalize($value);
    }

    public function loginAdmin(Request $request)
    {
        if (Auth::guard('web')->check()) {
            return redirect(session('url.intended') ?? '/dashboardAdmin');
        }

        return view('auth.loginAdmin');
    }

    public function verifyLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors(['نام کاربری و رمز عبور را وارد کنید.'])->withInput();
        }

        // محدودیت تعداد تلاش‌های ورود
        $key = 'login-attempt-' . $request->username;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors(["تعداد تلاش‌های ورود زیاد است. لطفا {$seconds} ثانیه دیگر تلاش کنید."]);
        }

        $user = User::where('username', $request->username)->where('active', 1)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            RateLimiter::clear($key);
            session(['langid' => 1]);
            Auth::loginUsingId($user->user_id, true);
            $request->session()->regenerate();

            return redirect(session()->pull('url.intended', '/dashboardAdmin'));
        }

        RateLimiter::hit($key, 600);
        Log::warning('Failed login attempt', ['username' => $request->username, 'ip' => $request->ip()]);

        return back()->withErrors(['نام کاربری یا رمز عبور اشتباه است.'])->withInput();
    }

    public function logout(Request $request)
    {
        // مدیری که به حساب مشتری وارد شده، با «خروج» باید به پنل خودش برگردد،
        // نه اینکه نشست مدیریتش هم بسته شود.
        if ($request->session()->has(ImpersonationController::SESSION_KEY)) {
            return app(ImpersonationController::class)->stop($request);
        }

        $wasAdmin = Auth::guard('web')->check();

        Auth::guard('customer')->logout();
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect($wasAdmin ? '/loginAdmin' : '/');
    }
}
