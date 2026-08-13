<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Auth\ImpersonationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * تعیین و تغییر رمز عبور مشتری.
 *
 * ورود با کد یکبارمصرف همیشه در دسترس است؛ رمز عبور راه سریع‌تر است، نه
 * جایگزین. برای همین حذف رمز هم پیش‌بینی شده و کاربر قفل نمی‌شود.
 */
class CustomerPasswordController extends Controller
{
    public function edit()
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer) {
            return redirect('/login');
        }

        return view('profile.password', [
            'customer'      => $customer,
            'hasPassword'   => ! empty($customer->password),
            'isImpersonated' => session()->has(ImpersonationController::SESSION_KEY),
        ]);
    }

    public function update(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer) {
            return redirect('/login');
        }

        if ($guard = $this->impersonationGuard()) {
            return $guard;
        }

        $hasPassword = ! empty($customer->password);

        $rules = [
            'password' => ['required', 'confirmed', Password::min(6)],
        ];

        // اگر از قبل رمز دارد، برای تغییرش باید رمز فعلی را بداند؛ وگرنه
        // هر کسی که به نشست باز دسترسی پیدا کند می‌تواند رمز را عوض کند.
        if ($hasPassword) {
            $rules['current_password'] = ['required'];
        }

        $validated = $request->validate($rules, [
            'current_password.required' => 'رمز عبور فعلی را وارد کنید.',
            'password.required'         => 'رمز عبور جدید را وارد کنید.',
            'password.confirmed'        => 'تکرار رمز عبور با رمز جدید یکسان نیست.',
            'password.min'              => 'رمز عبور باید حداقل ۶ کاراکتر باشد.',
        ]);

        if ($hasPassword && ! Hash::check($validated['current_password'], $customer->password)) {
            return back()->withErrors(['current_password' => 'رمز عبور فعلی درست نیست.']);
        }

        $customer->password = Hash::make($validated['password']);
        $customer->save();

        return redirect('/profile/password')->with('success', $hasPassword
            ? 'رمز عبور با موفقیت تغییر کرد.'
            : 'رمز عبور شما تنظیم شد. از این پس می‌توانید با رمز هم وارد شوید.');
    }

    /** حذف رمز؛ ورود با کد یکبارمصرف همچنان کار می‌کند. */
    public function destroy(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer) {
            return redirect('/login');
        }

        if ($guard = $this->impersonationGuard()) {
            return $guard;
        }

        $customer->password = null;
        $customer->save();

        return redirect('/profile/password')->with('success', 'رمز عبور حذف شد. ورود فقط با کد پیامکی انجام می‌شود.');
    }

    /**
     * مدیری که «به‌عنوان کاربر» وارد شده نباید رمز او را از این‌جا عوض کند؛
     * برای این کار فرم مخصوصش در پنل مدیریت هست که در لاگ ثبت می‌شود.
     */
    private function impersonationGuard()
    {
        if (! session()->has(ImpersonationController::SESSION_KEY)) {
            return null;
        }

        return redirect('/profile/password')
            ->withErrors(['در حالت «ورود به‌عنوان کاربر»، تغییر رمز از این‌جا ممکن نیست. از پنل مدیریت اقدام کنید.']);
    }
}
