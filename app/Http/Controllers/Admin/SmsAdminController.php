<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Sms;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * مدیریت پیامک‌ها.
 *
 * هر پیامکی که sendSms() می‌فرستد (کد ورود، تغییر وضعیت سفارش، رد رسید و ...)
 * در جدول sms لاگ می‌شود. اینجا مدیر همان لاگ را می‌بیند، وضعیت درگاه را
 * بررسی می‌کند و در صورت نیاز دستی برای یک شماره پیامک می‌فرستد.
 */
class SmsAdminController extends Controller
{
    private const PER_PAGE = 30;

    private function guard()
    {
        if (! Auth::user()) {
            return redirect('/loginAdmin');
        }
        access(83);

        return null;
    }

    public function index(Request $request)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $mobile = $request->input('mobile');
        $text   = $request->input('text');
        $status = $request->input('status');
        $days   = (int) $request->input('days', 0);

        $query = Sms::search($mobile, $text);

        if ($days > 0) {
            $query->where('created_at', '>=', now()->subDays($days));
        }

        /* پاسخ درگاه در ارسال موفق شناسه‌ی بلند است و در شکست کد کوتاه؛
           فیلتر وضعیت باید همان تفکیک مدل را روی دیتابیس تکرار کند. */
        if ($status === 'sent') {
            $query->whereRaw('udh REGEXP ? AND CAST(udh AS UNSIGNED) >= ?', ['^[0-9]+$', Sms::GATEWAY_MSG_ID_MIN]);
        } elseif ($status === 'failed') {
            $query->where(function ($q) {
                $q->whereNull('udh')
                  ->orWhere('udh', '')
                  ->orWhereRaw('NOT (udh REGEXP ? AND CAST(udh AS UNSIGNED) >= ?)', ['^[0-9]+$', Sms::GATEWAY_MSG_ID_MIN]);
            });
        }

        $model = $query->orderByDesc('id')->paginate(self::PER_PAGE)->withQueryString();

        /* شماره‌ها به مشتری وصل نیستند (user_id فقط وقتی پر می‌شود که مشتری
           لاگین بوده)؛ برای نمایش نام، با خود شماره‌ی موبایل هم تطبیق می‌دهیم. */
        $customers = Customer::whereIn('phone', $model->pluck('mobile')->filter()->unique())
            ->get()
            ->keyBy('phone');

        $counts = [
            'total'  => Sms::count(),
            'today'  => Sms::whereDate('created_at', now()->toDateString())->count(),
            'week'   => Sms::where('created_at', '>=', now()->subDays(7))->count(),
            'failed' => Sms::where(function ($q) {
                $q->whereNull('udh')
                  ->orWhere('udh', '')
                  ->orWhereRaw('NOT (udh REGEXP ? AND CAST(udh AS UNSIGNED) >= ?)', ['^[0-9]+$', Sms::GATEWAY_MSG_ID_MIN]);
            })->count(),
        ];

        $gatewayReady = ! empty(config('sms.username')) && ! empty(config('sms.password'));

        return view('sms.admin.list', compact('model', 'counts', 'customers', 'gatewayReady', 'status', 'days'));
    }

    /** ارسال دستی پیامک از پنل؛ خود sendSms لاگش را در همین جدول می‌نویسد */
    public function send(Request $request)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $mobile = toLatinDigits($request->input('mobile'));
        $text   = trim((string) $request->input('text'));

        $validator = Validator::make(['mobile' => $mobile, 'text' => $text], [
            'mobile' => 'required|regex:/^09[0-9]{9}$/',
            'text'   => 'required|string|max:500',
        ], [
            'mobile.required' => 'شماره موبایل را وارد کنید.',
            'mobile.regex'    => 'شماره موبایل باید ۱۱ رقم و با ۰۹ شروع شود.',
            'text.required'   => 'متن پیامک را وارد کنید.',
            'text.max'        => 'متن پیامک حداکثر ۵۰۰ کاراکتر است.',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        if (empty(config('sms.username')) || empty(config('sms.password'))) {
            return back()->with('error', 'درگاه پیامک تنظیم نشده است؛ مقادیر SMS_USERNAME و SMS_PASSWORD را در فایل .env پر کنید.')->withInput();
        }

        // sendSms خودش ردیف لاگ را با همین پاسخ درگاه در جدول sms می‌سازد
        $result = sendSms($mobile, $text);

        if ((new Sms(['udh' => $result]))->wasSent()) {
            return back()->with('success', 'پیامک به ' . $mobile . ' ارسال شد.');
        }

        return back()->withInput()->with('error', 'ارسال پیامک ناموفق بود. پاسخ درگاه: ' . ($result === null || $result === '' ? 'بدون پاسخ' : $result));
    }

    /** حذف یک ردیف از لاگ پیامک‌ها */
    public function destroy($id)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $sms = Sms::find($id);

        if (! $sms) {
            return response()->json(['success' => false, 'message' => 'پیامک موردنظر پیدا نشد'], 404);
        }

        $sms->delete();

        return response()->json(['success' => true, 'message' => 'پیامک حذف شد']);
    }
}
