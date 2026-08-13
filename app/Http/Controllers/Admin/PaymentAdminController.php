<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomerNotification;
use App\Models\Payment;
use App\Services\OrderFulfillmentService;
use App\Services\OrderNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * مدیریت پرداخت‌ها.
 *
 * مشتری بعد از کارت‌به‌کارت یا واریز، رسیدش را در حساب کاربری ثبت می‌کند و
 * مدیر همین‌جا آن را می‌بیند و تأیید یا رد می‌کند. تأیید، سفارش را «پرداخت
 * شده» می‌کند و همان کارهای پس از پرداخت درگاه (کم کردن موجودی و
 * اطلاع‌رسانی) را انجام می‌دهد؛ رد کردن، دلیلش را برای مشتری می‌فرستد.
 */
class PaymentAdminController extends Controller
{
    private const PER_PAGE = 30;

    private function guard()
    {
        if (! Auth::user()) {
            return redirect('/loginAdmin');
        }
        access(388);

        return null;
    }

    public function index(Request $request)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $query = Payment::with(['order.customer', 'reviewer']);

        // پیش‌فرض روی رسیدهای دستی است؛ همان چیزی که مدیر باید تعیین تکلیف کند
        $type = $request->input('type', 'manual');
        if ($type === 'manual') {
            $query->manual();
        } elseif ($type === 'online') {
            $query->where('gateway', '!=', Payment::GATEWAY_MANUAL);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('order_id')) {
            $query->where('order_id', (int) toLatinDigits($request->input('order_id')));
        }

        if ($request->filled('phone')) {
            $phone = toLatinDigits($request->input('phone'));
            $query->whereHas('order.customer', function ($q) use ($phone) {
                $q->where('phone', 'like', "%{$phone}%");
            });
        }

        $model = $query->orderByDesc('id')->paginate(self::PER_PAGE)->withQueryString();

        $counts = [
            'pending'  => Payment::awaitingReview()->count(),
            'paid'     => Payment::manual()->where('status', 'paid')->count(),
            'rejected' => Payment::manual()->where('status', 'rejected')->count(),
        ];

        return view('payment.admin.list', compact('model', 'counts', 'type'));
    }

    /** تأیید رسید: سفارش پرداخت‌شده می‌شود */
    public function approve(Request $request, $id)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $payment = Payment::with('order')->findOrFail($id);

        if (! $payment->isAwaitingReview()) {
            return back()->with('error', 'این پرداخت قبلا تعیین تکلیف شده است.');
        }

        $order = $payment->order;

        if (! $order) {
            return back()->with('error', 'سفارش این پرداخت پیدا نشد.');
        }

        $payment->update([
            'status'      => 'paid',
            'paid_at'     => $payment->paid_at ?: now(),
            'admin_note'  => trim((string) $request->input('admin_note')) ?: null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $previousStatus = (string) $order->status;

        // سفارشی که قبلا پرداخت‌شده شده، نباید دوباره موجودی‌اش کم شود
        if ($previousStatus !== 'paid') {
            $order->update(['status' => 'paid']);

            try {
                (new OrderFulfillmentService())->decrementStock($order);
            } catch (\Throwable $e) {
                Log::error('کم کردن موجودی پس از تأیید رسید ناموفق بود', [
                    'order_id' => $order->id,
                    'message'  => $e->getMessage(),
                ]);
            }

            try {
                (new OrderNotifier())->statusChanged($order->fresh('customer'), $previousStatus);
            } catch (\Throwable $e) {
                Log::error('اطلاع‌رسانی تأیید پرداخت ناموفق بود', [
                    'order_id' => $order->id,
                    'message'  => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', 'پرداخت سفارش #' . $order->id . ' تأیید شد و وضعیت سفارش «پرداخت شده» است.');
    }

    /** رد رسید همراه با دلیل */
    public function reject(Request $request, $id)
    {
        if ($response = $this->guard()) {
            return $response;
        }

        $payment = Payment::with('order.customer')->findOrFail($id);

        if (! $payment->isAwaitingReview()) {
            return back()->with('error', 'این پرداخت قبلا تعیین تکلیف شده است.');
        }

        $validator = Validator::make($request->all(), [
            'admin_note' => 'required|string|max:500',
        ], [], ['admin_note' => 'دلیل رد']);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $reason = trim((string) $request->input('admin_note'));

        $payment->update([
            'status'      => 'rejected',
            'admin_note'  => $reason,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $this->notifyRejection($payment, $reason);

        return back()->with('success', 'رسید رد شد و دلیلش برای مشتری فرستاده شد.');
    }

    /**
     * اطلاع رد شدن رسید به مشتری.
     *
     * متن این پیام در OrderNotifier::EVENTS نیست چون وضعیت سفارش نیست؛
     * پس همین‌جا ساخته می‌شود. خطای پیامک نباید ثبت «رد» را برگرداند.
     */
    private function notifyRejection(Payment $payment, string $reason): void
    {
        $order    = $payment->order;
        $customer = $order?->customer;

        if (! $order || ! $customer) {
            return;
        }

        $message = seo_site_name() . "\n"
            . "رسید پرداخت سفارش #{$order->id} تأیید نشد.\n"
            . "دلیل: {$reason}\n"
            . 'در صورت نیاز با ' . shopContactPhoneDisplay() . ' تماس بگیرید.';

        try {
            CustomerNotification::create([
                'customer_id' => $customer->id,
                'type'        => 'order',
                'title'       => 'رسید پرداخت تأیید نشد',
                'body'        => str_replace("\n", ' ', $message),
                'url'         => '/profile/order/' . $order->id . '/payment-receipt',
                'icon'        => 'fa-circle-xmark',
            ]);
        } catch (\Throwable $e) {
            Log::error('ساخت اعلان رد رسید ناموفق بود', ['payment_id' => $payment->id, 'message' => $e->getMessage()]);
        }

        if (! empty($customer->phone)) {
            try {
                sendSms($customer->phone, $message);
            } catch (\Throwable $e) {
                Log::error('پیامک رد رسید ناموفق بود', ['payment_id' => $payment->id, 'message' => $e->getMessage()]);
            }
        }
    }
}
