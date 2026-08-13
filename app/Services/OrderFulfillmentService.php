<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * کارهایی که باید دقیقاً یک‌بار و بعد از پرداخت موفق انجام شوند:
 * کم کردن موجودی و اطلاع‌رسانی پیامکی.
 */
class OrderFulfillmentService
{
    /**
     * موجودی محصولات سفارش را کم می‌کند.
     *
     * تا پیش از این هیچ‌جای پروژه موجودی کم نمی‌شد، یعنی قطعه‌ای با موجودی ۱
     * را می‌شد بی‌نهایت بار فروخت.
     *
     * داخل تراکنش و با قفل ردیف (lockForUpdate) انجام می‌شود تا دو پرداخت
     * هم‌زمان روی یک محصول، موجودی را منفی نکنند.
     *
     * @return array<int,string> گزارش کمبودها برای لاگ
     */
    public function decrementStock(Order $order): array
    {
        $warnings = [];

        DB::transaction(function () use ($order, &$warnings) {
            $order->loadMissing('items');

            foreach ($order->items as $item) {
                $product = Product::where('id', $item->product_id)->lockForUpdate()->first();

                if (!$product) {
                    $warnings[] = "محصول #{$item->product_id} پیدا نشد";
                    continue;
                }

                $quantity = (int) $item->quantity;
                $current  = (int) $product->stock;

                if ($current < $quantity) {
                    $warnings[] = "محصول #{$product->id} ({$product->title}): موجودی {$current} کمتر از {$quantity} سفارش‌شده";
                }

                // هیچ‌وقت منفی نشود
                $product->stock = max(0, $current - $quantity);
                $product->save();
            }
        });

        if ($warnings) {
            Log::warning('Stock shortage on paid order', ['order_id' => $order->id, 'issues' => $warnings]);
        }

        return $warnings;
    }

    /** پیامک تأییدیه برای مشتری و اطلاع به فروشنده */
    public function notify(Order $order): void
    {
        $order->loadMissing(['customer', 'items']);

        $amount = number_format($order->total_price);
        $count  = $order->items->count();

        $customerPhone = $order->customer?->phone;

        // پیامک و اعلانِ داخل سایتِ مشتری از یک نقطه ساخته می‌شوند تا متن‌ها
        // با اطلاع‌رسانی تغییر وضعیت یکی بماند.
        try {
            (new OrderNotifier())->orderPlaced($order);
        } catch (\Throwable $e) {
            Log::error('Order notification to customer failed', ['order_id' => $order->id, 'message' => $e->getMessage()]);
        }

        $adminPhone = config('payment.notify_mobile');
        if ($adminPhone) {
            try {
                sendSms(
                    $adminPhone,
                    "سفارش جدید #{$order->id}\n{$count} قطعه - {$amount} تومان\n" . ($customerPhone ?: '')
                );
            } catch (\Throwable $e) {
                Log::error('Order SMS to admin failed', ['order_id' => $order->id, 'message' => $e->getMessage()]);
            }
        }
    }
}
