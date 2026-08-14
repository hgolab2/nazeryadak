<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderFulfillmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    private function merchantId()
    {
        return config('payment.zarinpal.merchant_id');
    }

    private function callbackUrl()
    {
        return config('payment.zarinpal.callback_url') ?: url('/payment/zarinpal/callback');
    }

    public function request(Request $request, $orderId)
    {
        $customer = Auth::guard('customer')->user();
        if (!$customer) {
            session()->put('url.intended', '/order/payment/' . $orderId);
            return redirect('/login');
        }

        // سفارش باید متعلق به همین کاربر باشد؛ قبلاً هر کسی می‌توانست
        // درگاه پرداخت سفارش دیگران را باز کند
        $order = Order::where('id', $orderId)
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['pending', 'failed'])
            ->first();

        if (!$order) {
            return redirect('/profile/orders')
                ->with('error', 'این سفارش برای پرداخت در دسترس نیست.');
        }

        // پرداخت آنلاین از پنل مدیریت خاموش شده است؛ سفارش باید از مسیر
        // پیش‌فاکتور و تماس کارشناس نهایی شود.
        if (! onlinePaymentEnabled()) {
            return redirect()->route('order.payment', $order->id)
                ->with('error', 'پرداخت اینترنتی در حال حاضر فعال نیست. سفارش خود را ثبت کنید تا کارشناسان ما با شما تماس بگیرند.');
        }

        // قلم استعلامی مبلغ ندارد و نباید ناقص پرداخت شود.
        if ($order->hasContactPriceItems()) {
            return redirect()->route('order.payment', $order->id)
                ->with('error', 'سفارش شما قطعه‌ی بدنه و شاسی دارد که قیمتش تلفنی اعلام می‌شود؛ لطفا سفارش را ثبت کنید تا کارشناسان با شما تماس بگیرند.');
        }

        // مبلغی که به درگاه می‌رود باید تخفیفِ همین لحظه را داشته باشد؛ اگر
        // کد بین صفحه‌ی پرداخت و کلیک روی «پرداخت امن» منقضی شده باشد،
        // فروشگاه نباید تخفیفِ باطل‌شده را بپذیرد.
        $order->syncDiscount();
        $order->save();

        if ((int) $order->total_price <= 0) {
            return redirect()->route('order.payment', $order->id)
                ->with('error', 'مبلغ سفارش نامعتبر است. لطفا با پشتیبانی تماس بگیرید.');
        }

        if (empty($this->merchantId())) {
            return redirect()->route('order.payment', $order->id)
                ->with('error', 'درگاه پرداخت پیکربندی نشده است. لطفا با پشتیبانی تماس بگیرید.');
        }

        $payment = null;

        try {
            // ساخت رکورد پرداخت هم داخل try است؛ قبلا بیرون بود و هر خطای
            // دیتابیس مستقیم به صفحه‌ی ۵۰۰ ختم می‌شد، آن هم دقیقا سر پرداخت
            $payment = Payment::create([
                'order_id' => $order->id,
                'gateway'  => 'zarinpal',
                'amount'   => $order->total_price,
                'status'   => 'pending',
            ]);

            $response = Http::timeout(20)->post('https://payment.zarinpal.com/pg/v4/payment/request.json', [
                'merchant_id'  => $this->merchantId(),
                'amount'       => (int) $payment->amount,
                'currency'     => config('payment.zarinpal.currency', 'IRT'),
                'description'  => 'سفارش شماره ' . $order->id . ' - ناظر یدک',
                'callback_url' => $this->callbackUrl(),
                'metadata'     => [
                    'order_id'   => $order->id,
                    'payment_id' => $payment->id,
                ],
            ]);

            $body = $response->json();

            if (isset($body['data']['code']) && $body['data']['code'] == 100) {
                $payment->update([
                    'ref_id' => $body['data']['authority'],
                ]);

                return redirect()->away(
                    'https://payment.zarinpal.com/pg/StartPay/' . $body['data']['authority']
                );
            }

            $payment->update(['status' => 'failed']);
            Log::warning('Zarinpal request failed', ['order_id' => $order->id, 'response' => $body]);

            return redirect()->route('order.payment', $order->id)
                ->with('error', 'خطا در اتصال به درگاه پرداخت. لطفا مجددا تلاش کنید.');

        } catch (\Throwable $e) {
            if ($payment) {
                $payment->update(['status' => 'failed']);
            }
            Log::error('Zarinpal request exception', ['order_id' => $order->id, 'message' => $e->getMessage()]);

            return redirect()->route('order.payment', $order->id)
                ->with('error', 'خطا در اتصال به درگاه پرداخت. لطفا مجددا تلاش کنید.');
        }
    }

    public function callback(Request $request)
    {
        $authority = $request->Authority;
        $status    = $request->Status;

        $payment = Payment::where('ref_id', $authority)
            ->where('gateway', 'zarinpal')
            ->first();

        if (!$payment) {
            abort(404);
        }

        $order = $payment->order;

        if ($status !== 'OK') {
            $payment->update(['status' => 'failed']);
            $order->update(['status' => 'failed']);

            return view('order.success', [
                'order'         => $order,
                'paymentStatus' => 'failed',
            ]);
        }

        try {
            $response = Http::timeout(20)->post('https://payment.zarinpal.com/pg/v4/payment/verify.json', [
                'merchant_id' => $this->merchantId(),
                'amount'      => (int) $payment->amount,
                'currency'    => config('payment.zarinpal.currency', 'IRT'),
                'authority'   => $authority,
            ]);

            $body = $response->json();

            if (isset($body['data']['code']) && $body['data']['code'] == 100) {
                $payment->update([
                    'status'  => 'paid',
                    'paid_at' => now(),
                    'ref_id'  => $body['data']['ref_id'],
                ]);

                $this->markPaid($order);
                session()->forget('cart');

                return view('order.success', [
                    'order'         => $order,
                    'paymentStatus' => 'paid',
                ]);
            }

            // کد ۱۰۱ یعنی این تراکنش قبلا تایید شده؛ ممکن است کاربر صفحه‌ی
            // بازگشت را دوباره باز کرده باشد. markPaid فقط یک‌بار اثر می‌کند.
            if (isset($body['data']['code']) && $body['data']['code'] == 101) {
                $this->markPaid($order);

                return view('order.success', [
                    'order'         => $order,
                    'paymentStatus' => 'paid',
                ]);
            }

            $payment->update(['status' => 'failed']);
            $order->update(['status' => 'failed']);

            return view('order.success', [
                'order'         => $order,
                'paymentStatus' => 'failed',
            ]);

        } catch (\Throwable $e) {
            $payment->update(['status' => 'failed']);
            $order->update(['status' => 'failed']);

            return view('order.success', [
                'order'         => $order,
                'paymentStatus' => 'failed',
            ]);
        }
    }

    /**
     * سفارش را «پرداخت‌شده» می‌کند و کارهای پس از فروش را انجام می‌دهد.
     *
     * فقط وقتی اثر می‌کند که سفارش هنوز paid نشده باشد، پس اگر کاربر صفحه‌ی
     * بازگشت از درگاه را دوباره باز کند موجودی دوبار کم نمی‌شود و پیامک
     * تکراری هم نمی‌رود.
     */
    private function markPaid(Order $order): void
    {
        if ($order->status === 'paid') {
            return;
        }

        $order->update(['status' => 'paid']);

        $fulfillment = new OrderFulfillmentService();

        // اگر کم کردن موجودی یا پیامک به مشکل بخورد، نباید صفحه‌ی
        // «پرداخت موفق» خراب شود — پول از مشتری گرفته شده است
        try {
            $fulfillment->decrementStock($order);
        } catch (\Throwable $e) {
            Log::error('Stock decrement failed', ['order_id' => $order->id, 'message' => $e->getMessage()]);
        }

        try {
            $fulfillment->notify($order);
        } catch (\Throwable $e) {
            Log::error('Order notification failed', ['order_id' => $order->id, 'message' => $e->getMessage()]);
        }
    }
}
