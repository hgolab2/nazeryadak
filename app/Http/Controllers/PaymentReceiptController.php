<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * ثبت رسید پرداخت توسط مشتری.
 *
 * پرداخت آنلاین این فروشگاه معمولا خاموش است و تسویه بعد از تماس کارشناس،
 * کارت‌به‌کارت یا واریز انجام می‌شود. این کنترلر جایی است که مشتری مشخصات آن
 * پرداخت را ثبت می‌کند تا مدیر در پنل ببیند و تأیید یا رد کند.
 */
class PaymentReceiptController extends Controller
{
    /** بیشترین حجم مجاز تصویر رسید (کیلوبایت). */
    private const MAX_IMAGE_KB = 4096;

    private function customer()
    {
        return Auth::guard('customer')->user();
    }

    /** سفارشِ خودِ کاربر یا null */
    private function findOrder($id): ?Order
    {
        $customer = $this->customer();

        if (! $customer) {
            return null;
        }

        return Order::with(['items', 'customer'])
            ->where('id', $id)
            ->where('customer_id', $customer->id)
            ->first();
    }

    /** فرم ثبت رسید */
    public function create($id)
    {
        if (! $this->customer()) {
            session()->put('url.intended', '/profile/order/' . $id . '/payment-receipt');

            return redirect('/login');
        }

        $order = $this->findOrder($id);

        if (! $order) {
            return redirect('/profile/orders')->with('error', 'این سفارش پیدا نشد.');
        }

        if (! $order->canReceiveReceipt()) {
            return redirect('/profile/orderDetail/' . $order->id)
                ->with('error', 'برای این سفارش نیازی به ثبت رسید نیست.');
        }

        return view('order.receipt', [
            'order'    => $order,
            'bank'     => bankTransferInfo(),
            'receipts' => $order->payments()->manual()->orderByDesc('id')->get(),
            'pending'  => $order->pendingReceipt()->first(),
        ]);
    }

    /** ذخیره‌ی رسید */
    public function store(Request $request, $id)
    {
        if (! $this->customer()) {
            return redirect('/login');
        }

        $order = $this->findOrder($id);

        if (! $order) {
            return redirect('/profile/orders')->with('error', 'این سفارش پیدا نشد.');
        }

        if (! $order->canReceiveReceipt()) {
            return redirect('/profile/orderDetail/' . $order->id)
                ->with('error', 'برای این سفارش نیازی به ثبت رسید نیست.');
        }

        // دو رسیدِ همزمانِ منتظر بررسی، کار مدیر را دوباره‌کاری می‌کند
        if ($order->pendingReceipt()->exists()) {
            return redirect('/profile/order/' . $order->id . '/payment-receipt')
                ->with('error', 'یک رسید برای این سفارش ثبت شده و در حال بررسی است. تا اعلام نتیجه، رسید تازه‌ای لازم نیست.');
        }

        // ارقام فارسی را پیش از اعتبارسنجی لاتین می‌کنیم
        $request->merge([
            'amount'     => preg_replace('/[^\d]/', '', toLatinDigits($request->input('amount'))),
            'reference'  => trim(toLatinDigits($request->input('reference'))),
            'card_last4' => preg_replace('/[^\d]/', '', toLatinDigits($request->input('card_last4'))),
        ]);

        $validator = Validator::make($request->all(), [
            'method'        => 'required|in:' . implode(',', array_keys(Payment::METHODS)),
            'amount'        => 'required|integer|min:1000|max:100000000000',
            'paid_at'       => 'nullable|date|before_or_equal:now',
            'reference'     => 'nullable|string|max:100',
            'card_last4'    => 'nullable|digits:4',
            'payer_name'    => 'nullable|string|max:100',
            'customer_note' => 'nullable|string|max:500',
            'receipt_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:' . self::MAX_IMAGE_KB,
        ], [], [
            'method'        => 'روش پرداخت',
            'amount'        => 'مبلغ پرداختی',
            'paid_at'       => 'تاریخ پرداخت',
            'reference'     => 'شماره پیگیری',
            'card_last4'    => 'چهار رقم آخر کارت',
            'payer_name'    => 'نام پرداخت‌کننده',
            'customer_note' => 'توضیحات',
            'receipt_image' => 'تصویر رسید',
        ]);

        // کارت‌به‌کارت و واریز بدون شماره پیگیری قابل راستی‌آزمایی نیست؛
        // یا شماره پیگیری یا تصویر رسید باید باشد
        $validator->after(function ($validator) use ($request) {
            $needsProof = in_array($request->input('method'), ['card_to_card', 'bank_transfer'], true);

            if ($needsProof && ! $request->filled('reference') && ! $request->hasFile('receipt_image')) {
                $validator->errors()->add(
                    'reference',
                    'برای کارت‌به‌کارت یا واریز بانکی، شماره پیگیری را وارد کنید یا تصویر رسید را بارگذاری کنید.'
                );
            }
        });

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = $validator->validated();

        $imagePath = null;
        if ($request->hasFile('receipt_image')) {
            try {
                $imagePath = $this->storeReceiptImage($request);
            } catch (\Throwable $e) {
                Log::error('بارگذاری تصویر رسید ناموفق بود', [
                    'order_id' => $order->id,
                    'message'  => $e->getMessage(),
                ]);

                return back()->withInput()
                    ->with('error', 'بارگذاری تصویر رسید ناموفق بود. لطفا دوباره تلاش کنید یا بدون تصویر ثبت کنید.');
            }
        }

        $payment = Payment::create([
            'order_id'      => $order->id,
            'gateway'       => Payment::GATEWAY_MANUAL,
            'method'        => $data['method'],
            'amount'        => (int) $data['amount'],
            'status'        => 'pending',
            'reference'     => $data['reference'] ?? null,
            'card_last4'    => $data['card_last4'] ?? null,
            'payer_name'    => $data['payer_name'] ?? null,
            'receipt_image' => $imagePath,
            'customer_note' => $data['customer_note'] ?? null,
            // خالی یعنی همین حالا پرداخت شده است
            'paid_at'       => ! empty($data['paid_at']) ? $data['paid_at'] : now(),
        ]);

        $this->notifyAdmin($order, $payment);

        return redirect('/profile/orderDetail/' . $order->id)
            ->with('success', 'رسید پرداخت شما ثبت شد و برای بررسی به کارشناسان ما رفت. نتیجه را پیامک می‌کنیم.');
    }

    /** ذخیره‌ی تصویر رسید کنار بقیه‌ی آپلودهای سایت */
    private function storeReceiptImage(Request $request): string
    {
        $file      = $request->file('receipt_image');
        $relative  = 'upload/receipts/' . date('Y/m');
        $directory = public_path($relative);

        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new \RuntimeException('ساخت پوشه‌ی رسیدها ممکن نشد: ' . $directory);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename  = uniqid('receipt-', true) . '.' . $extension;

        $file->move($directory, $filename);

        return '/' . $relative . '/' . $filename;
    }

    /** پیامک به مدیر؛ شکستش نباید ثبت رسید را خراب کند. */
    private function notifyAdmin(Order $order, Payment $payment): void
    {
        $adminPhone = config('payment.notify_mobile');

        if (! $adminPhone) {
            return;
        }

        try {
            sendSms(
                $adminPhone,
                "رسید پرداخت جدید برای سفارش #{$order->id}\n"
                . number_format((int) $payment->amount) . " تومان - " . $payment->methodLabel() . "\n"
                . ($order->customer?->phone ?: '')
            );
        } catch (\Throwable $e) {
            Log::error('پیامک رسید پرداخت به مدیر ناموفق بود', [
                'order_id' => $order->id,
                'message'  => $e->getMessage(),
            ]);
        }
    }
}
