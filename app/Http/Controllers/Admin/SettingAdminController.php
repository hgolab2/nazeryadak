<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Province;
use App\Models\Setting;
use App\Models\ShippingSetting;
use App\Services\OrderNotifier;
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

        $rules          = getShippingRules();
        $provinces      = Province::orderBy('name')->get();
        $onlinePayment  = onlinePaymentEnabled();

        $contact = [
            'expert_name'      => shopExpertName(),
            'contact_phone'    => Setting::get('contact_phone', shopContactPhoneDisplay()),
            'working_hours'    => shopWorkingHours(),
            // نشانی فرستنده روی برچسب پستی
            'shop_address'     => shopAddress(),
            'shop_postal_code' => shopPostalCode(),
        ];

        // حساب فروشگاه برای کارت‌به‌کارت/واریز؛ در فرم ثبت رسید به مشتری نشان داده می‌شود
        $bank = bankTransferInfo();

        $smsEvents       = OrderNotifier::EVENTS;
        $smsPlaceholders = OrderNotifier::PLACEHOLDERS;
        $smsTemplates    = [];
        foreach (array_keys($smsEvents) as $event) {
            $smsTemplates[$event] = OrderNotifier::template($event);
        }

        return view('admin.settings', compact(
            'rules', 'provinces', 'onlinePayment', 'bank',
            'contact', 'smsEvents', 'smsPlaceholders', 'smsTemplates'
        ));
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
            'expert_name'             => 'nullable|string|max:100',
            'contact_phone'           => 'nullable|string|max:20',
            'working_hours'           => 'nullable|string|max:120',
            'bank_name'               => 'nullable|string|max:60',
            'bank_card_number'        => 'nullable|string|max:30',
            'bank_sheba'              => 'nullable|string|max:34',
            'bank_account_name'       => 'nullable|string|max:100',
            'shop_address'            => 'nullable|string|max:255',
            'shop_postal_code'        => 'nullable|string|max:20',
            'sms'                     => 'nullable|array',
            'sms.*'                   => 'nullable|string|max:600',
        ], [], [
            'local_province_id'       => 'استان محلی',
            'local_free_threshold'    => 'حداقل خرید ارسال رایگان در استان محلی',
            'local_shipping_cost'     => 'هزینه پیک در استان محلی',
            'national_free_threshold' => 'حداقل خرید ارسال رایگان سایر شهرها',
            'expert_name'             => 'نام کارشناس',
            'contact_phone'           => 'شماره تماس',
            'working_hours'           => 'ساعت کاری',
            'shop_address'            => 'نشانی فروشگاه',
            'shop_postal_code'        => 'کد پستی فروشگاه',
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

        // تیک‌نخورده اصلا در ورودی نمی‌آید، پس مقدارش صفر است.
        $this->put('online_payment_enabled', $request->boolean('online_payment_enabled') ? '1' : '0');

        // مشخصات تماس و نشانی فرستنده
        foreach (['expert_name', 'contact_phone', 'working_hours', 'shop_address', 'shop_postal_code'] as $key) {
            Setting::put($key, trim((string) $request->input($key)));
        }

        // حساب فروشگاه برای کارت‌به‌کارت/واریز. ارقام فارسی و فاصله و خط تیره
        // پاک می‌شوند تا مشتری بتواند شماره‌ی کارت را کپی کند و بانک قبولش کند.
        Setting::put('bank_name', trim((string) $request->input('bank_name')));
        Setting::put('bank_account_name', trim((string) $request->input('bank_account_name')));
        Setting::put('bank_card_number', preg_replace('/\D/', '', toLatinDigits($request->input('bank_card_number'))));
        Setting::put('bank_sheba', strtoupper(preg_replace('/[^A-Za-z0-9]/', '', toLatinDigits($request->input('bank_sheba')))));

        // متن پیامک‌ها؛ متن خالی یعنی آن اطلاع‌رسانی ارسال نشود
        foreach (array_keys(OrderNotifier::EVENTS) as $event) {
            Setting::put(
                OrderNotifier::settingKey($event),
                trim((string) $request->input('sms.' . $event, ''))
            );
        }

        forgetShippingSettings();

        return redirect('/admin/settings')->with('success', 'تنظیمات فروشگاه با موفقیت ذخیره شد.');
    }

    private function put(string $key, $value): void
    {
        ShippingSetting::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => (string) $value]
        );
    }
}
