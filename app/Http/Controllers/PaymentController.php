<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    private function merchantId()
    {
        return env('ZARINPAL_MERCHANT_ID');
    }

    private function callbackUrl()
    {
        return env('ZARINPAL_CALLBACK_URL', url('/payment/zarinpal/callback'));
    }

    public function request(Request $request, $orderId)
    {
        $order = Order::where('id', $orderId)
            ->whereIn('status', ['pending', 'failed'])
            ->firstOrFail();

        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway'  => 'zarinpal',
            'amount'   => $order->total_price,
            'status'   => 'pending',
        ]);

        try {
            $response = Http::post('https://payment.zarinpal.com/pg/v4/payment/request.json', [
                'merchant_id'  => $this->merchantId(),
                'amount'       => (int) $payment->amount,
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

            return redirect()->route('order.payment', $order->id)
                ->with('error', 'خطا در اتصال به درگاه پرداخت. لطفا مجددا تلاش کنید.');

        } catch (\Throwable $e) {
            $payment->update(['status' => 'failed']);

            return redirect()->route('order.payment', $order->id)
                ->with('error', 'خطا در اتصال به درگاه پرداخت.');
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
            $response = Http::post('https://payment.zarinpal.com/pg/v4/payment/verify.json', [
                'merchant_id' => $this->merchantId(),
                'amount'      => (int) $payment->amount,
                'authority'   => $authority,
            ]);

            $body = $response->json();

            if (isset($body['data']['code']) && $body['data']['code'] == 100) {
                $payment->update([
                    'status'  => 'paid',
                    'paid_at' => now(),
                    'ref_id'  => $body['data']['ref_id'],
                ]);

                $order->update(['status' => 'paid']);

                session()->forget('cart');

                return view('order.success', [
                    'order'         => $order,
                    'paymentStatus' => 'paid',
                ]);
            }

            if (isset($body['data']['code']) && $body['data']['code'] == 101) {
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
}
