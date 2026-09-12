<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * تنها نقطه‌ای که وضعیت سفارش عوض می‌شود.
 *
 * وضعیت از چهار جا تغییر می‌کند: فرم ویرایش، منوی کشویی لیست/داشبورد/مشاهده،
 * تأیید رسید پرداخت، و دکمه‌ی زیر پیام بله. اگر هر کدام خودش وضعیت را
 * می‌نوشت، یکی موجودی را کم می‌کرد و دیگری نه، یکی پیامک می‌داد و دیگری نه.
 * همه از این‌جا رد می‌شوند تا رفتار یکی باشد.
 */
class OrderStatusService
{
    /**
     * @param  string  $status  یکی از کلیدهای Order::STATUSES
     * @param  string  $source  برای لاگ: panel | bale | payment
     * @return bool  false یعنی وضعیت همان بود و کاری نشد
     *
     * @throws \InvalidArgumentException وضعیت نامعتبر
     */
    public function change(Order $order, string $status, string $source = 'panel'): bool
    {
        if (! isset(Order::STATUSES[$status])) {
            throw new \InvalidArgumentException('وضعیت «' . $status . '» معتبر نیست.');
        }

        $previous = (string) $order->status;

        if ($previous === $status) {
            return false;
        }

        $order->status = $status;

        /*
        | اولین ورود به «پرداخت‌شده»: تاریخ تسویه ثبت و موجودی کم می‌شود.
        |
        | paid_at ملاک «یک‌بار» بودن است، نه وضعیت قبلی: سفارشی که مدیر
        | اشتباهی به «در حال آماده‌سازی» برده و برگردانده، نباید دوبار از
        | انبار کم شود. مسیر پرداخت آنلاین موجودی را خودش کم می‌کند و paid_at
        | را هم پر می‌کند، پس این‌جا دوباره کم نمی‌شود.
        */
        $firstSettlement = $status === 'paid' && $order->paid_at === null;

        if ($firstSettlement) {
            $order->paid_at = now();
        }

        $order->save();

        Log::info('وضعیت سفارش تغییر کرد', [
            'order_id' => $order->id,
            'from'     => $previous,
            'to'       => $status,
            'source'   => $source,
        ]);

        if ($firstSettlement) {
            try {
                (new OrderFulfillmentService())->decrementStock($order);
            } catch (\Throwable $e) {
                Log::error('کم کردن موجودی پس از تسویه ناموفق بود', [
                    'order_id' => $order->id,
                    'message'  => $e->getMessage(),
                ]);
            }
        }

        // پیامک و اعلان مشتری + پیام بله برای مدیر. خطای درگاه نباید تغییر
        // وضعیتی را که ذخیره شده، به خطا تبدیل کند.
        try {
            (new OrderNotifier())->statusChanged($order->fresh(['customer', 'items.product', 'address.province']), $previous);
        } catch (\Throwable $e) {
            Log::error('اطلاع‌رسانی تغییر وضعیت سفارش ناموفق بود', [
                'order_id' => $order->id,
                'message'  => $e->getMessage(),
            ]);
        }

        return true;
    }
}
