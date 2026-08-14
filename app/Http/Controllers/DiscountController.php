<?php

namespace App\Http\Controllers;

use App\Models\DiscountCode;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ثبت و برداشتن کد تخفیف روی سفارشِ در حال خرید.
 *
 * هر دو متد JSON برمی‌گردانند و علاوه بر پیام، جمع‌های تازه‌ی فاکتور و
 * HTML کادر تخفیف را می‌فرستند؛ همان الگویی که فرم آدرس (address-preview)
 * دارد، تا صفحه‌ی خرید بدون بارگذاری دوباره به‌روز شود.
 */
class DiscountController extends Controller
{
    private function customer()
    {
        return Auth::guard('customer')->user();
    }

    /**
     * سفارشِ در حال خرید همین مشتری.
     *
     * فقط pending و failed پذیرفته می‌شوند؛ سفارشی که ثبت یا پرداخت شده
     * فاکتور بسته دارد و تخفیفش نباید عوض شود.
     */
    private function editableOrder($orderId): ?Order
    {
        $customer = $this->customer();

        if (! $customer) {
            return null;
        }

        return Order::where('id', $orderId)
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['pending', 'failed'])
            ->first();
    }

    public function apply(Request $request)
    {
        if (! $this->customer()) {
            return response()->json(['status' => 'error', 'message' => 'لطفا ابتدا وارد شوید.'], 401);
        }

        $order = $this->editableOrder($request->input('order_id'));

        if (! $order) {
            return response()->json(['status' => 'error', 'message' => 'این سفارش برای اعمال تخفیف در دسترس نیست.'], 404);
        }

        $input = (string) $request->input('code', '');

        if (trim($input) === '') {
            return response()->json(['status' => 'error', 'message' => 'کد تخفیف را وارد کنید.'], 422);
        }

        $code = DiscountCode::findByCode($input);

        if (! $code) {
            return response()->json(['status' => 'error', 'message' => 'کد تخفیف واردشده معتبر نیست.'], 422);
        }

        if ($reason = $code->reasonUnusableFor($order, $order->customer_id)) {
            return response()->json(['status' => 'error', 'message' => $reason], 422);
        }

        // کدی که روی این فاکتور به هیچ تخفیفی نمی‌رسد (مثلا فاکتور صفر است)
        // نباید «اعمال شد» بگیرد؛ کاربر بعدا دنبال تخفیفِ نیامده می‌گردد.
        if ($code->discountFor($order->itemsSubtotal()) <= 0) {
            return response()->json([
                'status'  => 'error',
                'message' => 'این کد روی مبلغ فعلی سفارش تخفیفی ایجاد نمی‌کند.',
            ], 422);
        }

        $order->discount_code_id = $code->id;
        $order->syncDiscount();
        $order->save();

        $message = 'کد تخفیف اعمال شد؛ '
            . toPersianNumbers(number_format((int) $order->discount_amount)) . ' تومان کم شد.';

        if ($code->hitsCap($order->itemsSubtotal())) {
            $message .= ' (سقف تخفیف این کد)';
        }

        return $this->stateResponse($order, $message);
    }

    public function remove(Request $request)
    {
        if (! $this->customer()) {
            return response()->json(['status' => 'error', 'message' => 'لطفا ابتدا وارد شوید.'], 401);
        }

        $order = $this->editableOrder($request->input('order_id'));

        if (! $order) {
            return response()->json(['status' => 'error', 'message' => 'این سفارش برای تغییر تخفیف در دسترس نیست.'], 404);
        }

        $order->discount_code_id = null;
        $order->syncDiscount();
        $order->save();

        return $this->stateResponse($order, 'کد تخفیف برداشته شد.');
    }

    /** پاسخ مشترک: پیام + کادر تازه‌ی تخفیف + جمع‌های تازه‌ی فاکتور. */
    private function stateResponse(Order $order, string $message)
    {
        return response()->json([
            'status'          => 'success',
            'message'         => $message,
            'html'            => view('order.discount-box', ['order' => $order])->render(),
            'discount_amount' => (int) $order->discount_amount,
            'discount_code'   => $order->discount_code,
            'total_price'     => (int) $order->total_price,
            'final_price'     => $order->itemsSubtotal(),
        ]);
    }
}
