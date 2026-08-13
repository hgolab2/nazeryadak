<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * تعیین و تغییر رمز عبور مشتری.
 *
 * ورود پیش‌فرض سایت با کد یکبارمصرف است؛ رمز عبور یک راه ورود اضافه است،
 * نه جایگزین. برای همین حذف رمز هم پیش‌بینی شده و کاربر قفل نمی‌شود.
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
            'customer'    => $customer,
            'hasPassword' => ! empty($customer->password),
        ]);
    }

    public function update(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        if (! $customer) {
            return redirect('/login');
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

        $customer->password = null;
        $customer->save();

        return redirect('/profile/password')->with('success', 'رمز عبور حذف شد. ورود فقط با کد پیامکی انجام می‌شود.');
    }
}
