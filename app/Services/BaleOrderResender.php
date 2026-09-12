<?php

namespace App\Services;

use App\Models\Order;
use App\Support\OrderSummary;

/**
 * ارسال دوباره‌ی سفارش‌هایی که پیامشان به بله نرسیده (bale_notified_at خالی).
 *
 * هم دستور bale:resend-orders و هم دکمه‌ی صفحه‌ی «بله» در پنل از این‌جا
 * استفاده می‌کنند. ارسال فوری است (نه terminating) تا نتیجه همان لحظه
 * معلوم باشد.
 */
class BaleOrderResender
{
    /** سفارش‌هایی که باید دوباره فرستاده شوند. */
    public static function pending(int $days = 7)
    {
        return Order::with(['customer', 'items.product', 'address.province', 'expenses'])
            ->whereNull('bale_notified_at')
            ->where('status', '!=', 'draft')
            // «در انتظار پرداخت» یعنی مشتری هنوز به درگاه نرفته؛ سفارش نیست
            ->where('status', '!=', 'pending')
            ->where('created_at', '>=', now()->subDays(max(1, $days)))
            ->orderBy('id');
    }

    /**
     * @return array{total: int, sent: int, failed: int}
     */
    public function resend(int $days = 7): array
    {
        $orders = self::pending($days)->get();
        $sent   = 0;

        foreach ($orders as $order) {
            $result = BaleNotifier::sendNow('order_resend', OrderSummary::baleFields($order), [
                'keyboard' => OrderSummary::keyboard($order),
            ]);

            if ($result['ok']) {
                $order->forceFill(['bale_notified_at' => now()])->save();
                $sent++;
            }
        }

        return ['total' => $orders->count(), 'sent' => $sent, 'failed' => $orders->count() - $sent];
    }
}
