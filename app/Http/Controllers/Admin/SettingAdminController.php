<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\ShippingSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * تنظیمات فروشگاه — فعلاً شامل قواعد ارسال (حداقل خرید برای ارسال رایگان
 * در استان محلی و سایر شهرها، هزینه‌ی پیک محلی و انتخاب استان محلی).
 *
 * نکته‌ی واحد: مقادیر در جدول shipping_settings به «ریال» ذخیره می‌شوند ولی
 * کاربر و کل سایت با «تومان» کار می‌کنند؛ تبدیل فقط همین‌جا و در
 * getShippingRules() انجام می‌شود.
 */
class SettingAdminController extends Controller
{
    /** کلیدهای مبلغی که باید بین ریال و تومان تبدیل شوند. */
    private array $amountKeys = [
        'local_free_threshold',
        'local_shipping_cost',
        'national_free_threshold',
    ];

    public function index()
    {
        if (! Auth::user()) {
            return redirect('/login');
        }
        access(83);

        $rules     = getShippingRules();
        $provinces = Province::orderBy('name')->get();

        return view('admin.settings', compact('rules', 'provinces'));
    }

    public function update(Request $request)
    {
        if (! Auth::user()) {
            return redirect('/login');
        }
        access(83);

        $validator = Validator::make($request->all(), [
            'local_province_id'       => 'required|integer|exists:provinces,id',
            'local_free_threshold'    => 'required|integer|min:0|max:1000000000',
            'local_shipping_cost'     => 'required|integer|min:0|max:1000000000',
            'national_free_threshold' => 'required|integer|min:0|max:1000000000',
        ], [], [
            'local_province_id'       => 'استان محلی',
            'local_free_threshold'    => 'حداقل خرید ارسال رایگان در استان محلی',
            'local_shipping_cost'     => 'هزینه پیک در استان محلی',
            'national_free_threshold' => 'حداقل خرید ارسال رایگان سایر شهرها',
        ]);

        if ($validator->fails()) {
            return redirect('/admin/settings')
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();

        foreach ($this->amountKeys as $key) {
            // تومانِ واردشده → ریالِ ذخیره‌شده
            $this->put($key, (int) $data[$key] * 10);
        }

        $this->put('local_province_id', (int) $data['local_province_id']);

        forgetShippingSettings();

        return redirect('/admin/settings')->with('success', 'تنظیمات ارسال با موفقیت ذخیره شد.');
    }

    private function put(string $key, $value): void
    {
        ShippingSetting::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => (string) $value]
        );
    }
}
