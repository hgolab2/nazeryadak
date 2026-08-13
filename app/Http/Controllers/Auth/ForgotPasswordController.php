<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\OtpService;
use App\Support\Mobile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * بازیابی رمز عبور فراموش‌شده.
 *
 * چون هویت کاربر با شماره‌ی موبایل تأیید می‌شود، بازیابی هم با همان کد پیامکی
 * انجام می‌گیرد: شماره → کد → رمز جدید. کد با purpose جدا فرستاده می‌شود تا
 * کد بازیابی نتواند کسی را مستقیم وارد حساب کند.
 */
class ForgotPasswordController extends Controller
{
    /** مهلت تعیین رمز جدید بعد از تأیید کد */
    private const WINDOW = 600;

    private const SESSION_KEY = 'password_reset';

    /**
     * صفحه برای کاربر واردنشده و واردشده هر دو باز است: کسی که وارد حساب است
     * ولی رمز فعلی‌اش را به یاد ندارد هم راهی جز همین مسیر ندارد.
     */
    public function show()
    {
        return view('auth.forgot-password');
    }

    public function sendOtp(Request $request)
    {
        $mobile = $this->validatedMobile($request);

        if (! Customer::where('phone', $mobile)->exists()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'برای این شماره حسابی ثبت نشده است. با همین شماره ثبت‌نام کنید.',
            ], 404);
        }

        $result = OtpService::send($mobile, OtpService::PURPOSE_RESET, $request->ip());

        if (! $result['ok']) {
            return response()->json([
                'status'  => 'error',
                'message' => $result['message'],
                'wait'    => $result['wait'],
            ], 429);
        }

        return response()->json(array_filter([
            'status'   => 'success',
            'step'     => 'otp',
            'wait'     => $result['wait'],
            'masked'   => Mobile::mask($mobile),
            'dev_code' => $result['code'] ?? null,
        ], fn ($value) => $value !== null));
    }

    public function verifyOtp(Request $request)
    {
        $mobile = $this->validatedMobile($request);

        $request->merge(['otp' => Mobile::digits($request->otp)]);
        $request->validate(['otp' => 'required|digits:6'], [
            'otp.required' => 'لطفا کد تأیید را وارد کنید.',
            'otp.digits'   => 'کد تأیید باید ۶ رقم باشد.',
        ]);

        if (! OtpService::verify($mobile, OtpService::PURPOSE_RESET, $request->otp)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'کد وارد شده نادرست یا منقضی شده است.',
            ], 422);
        }

        // اجازه‌ی تعیین رمز روی نشست ثبت می‌شود، نه روی فرم؛ وگرنه با دستکاری
        // شماره در فرم می‌شد رمز حساب دیگری را عوض کرد.
        session()->put(self::SESSION_KEY, ['mobile' => $mobile, 'at' => time()]);

        return response()->json(['status' => 'success', 'step' => 'reset']);
    }

    public function reset(Request $request)
    {
        $grant = session(self::SESSION_KEY);

        if (! $grant || time() - $grant['at'] > self::WINDOW) {
            session()->forget(self::SESSION_KEY);

            return response()->json([
                'status'  => 'error',
                'message' => 'مهلت تعیین رمز تمام شد. لطفا دوباره کد بگیرید.',
                'step'    => 'mobile',
            ], 419);
        }

        $request->validate(
            ['password' => ['required', 'confirmed', Password::min(6)]],
            [
                'password.required'  => 'رمز عبور جدید را وارد کنید.',
                'password.confirmed' => 'تکرار رمز عبور با رمز جدید یکسان نیست.',
                'password.min'       => 'رمز عبور باید حداقل ۶ کاراکتر باشد.',
            ]
        );

        $customer = Customer::where('phone', $grant['mobile'])->first();

        if (! $customer) {
            return response()->json(['status' => 'error', 'message' => 'حساب پیدا نشد.'], 404);
        }

        $customer->password = Hash::make($request->password);
        $customer->save();

        session()->forget(self::SESSION_KEY);

        Auth::guard('customer')->login($customer, true);
        $request->session()->regenerate();

        return response()->json([
            'status'   => 'success',
            'redirect' => session()->pull('url.intended', '/dashboard'),
        ]);
    }

    private function validatedMobile(Request $request): string
    {
        $request->merge(['mobile' => Mobile::normalize($request->mobile)]);
        $request->validate(Mobile::RULES, Mobile::MESSAGES);

        return $request->mobile;
    }
}
