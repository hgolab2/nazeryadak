<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * ورود مدیر به حساب مشتری («مشاهده به‌عنوان کاربر»).
 *
 * برای پشتیبانی لازم است: وقتی مشتری می‌گوید «سبد خریدم خالی شد» یا «سفارشم
 * را نمی‌بینم»، مدیر باید همان چیزی را ببیند که او می‌بیند.
 *
 * نشست مدیر بسته نمی‌شود؛ فقط گارد customer برای او باز می‌شود و شناسه‌ی مدیر
 * در نشست می‌ماند. بنابراین بازگشت به پنل بدون ورود دوباره ممکن است و در همه‌ی
 * صفحات یک نوار هشدار نشان داده می‌شود تا مدیر فراموش نکند به‌جای چه کسی است.
 */
class ImpersonationController extends Controller
{
    public const SESSION_KEY = 'impersonator_id';

    public function start(Request $request, $id)
    {
        $admin = Auth::guard('web')->user();

        if (! $admin) {
            return redirect('/loginAdmin');
        }

        access(388);

        $customer = Customer::findOrFail($id);

        session()->put(self::SESSION_KEY, $admin->getAuthIdentifier());
        Auth::guard('customer')->login($customer);

        Log::info('Admin impersonation started', [
            'admin_id'    => $admin->getAuthIdentifier(),
            'customer_id' => $customer->id,
            'ip'          => $request->ip(),
        ]);

        return redirect('/dashboard');
    }

    public function stop(Request $request)
    {
        if (! session()->has(self::SESSION_KEY)) {
            return redirect('/');
        }

        Log::info('Admin impersonation ended', [
            'admin_id'    => session(self::SESSION_KEY),
            'customer_id' => Auth::guard('customer')->id(),
        ]);

        // فقط کلید نشستِ گارد پاک می‌شود، نه logout() کامل: logout توکن
        // «مرا به خاطر بسپار» مشتری را عوض می‌کند و او را از دستگاه خودش هم
        // بیرون می‌اندازد — عارضه‌ای که خروجِ مدیر نباید داشته باشد.
        $request->session()->forget(Auth::guard('customer')->getName());
        session()->forget(self::SESSION_KEY);

        // گاردهای حل‌شده کاربر را در حافظه نگه می‌دارند؛ بدون این، ادامه‌ی
        // همین درخواست هنوز مشتری را وارد‌شده می‌بیند.
        Auth::forgetGuards();

        return redirect('/admin/customer/list');
    }
}
