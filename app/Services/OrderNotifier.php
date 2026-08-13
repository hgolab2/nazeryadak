<?php

namespace App\Services;

use App\Models\CustomerNotification;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

/**
 * اطلاع‌رسانی سفارش از یک نقطه: هم پیامک و هم اعلان داخل سایت.
 *
 * هر دو مسیرِ «ثبت سفارش» و «تغییر وضعیت توسط ادمین» از همین کلاس استفاده
 * می‌کنند تا متن‌ها یکی بماند و اضافه‌شدن یک وضعیت جدید فقط یک جا تغییر بخواهد.
 * اگر درگاه پیامک تنظیم نشده باشد، sendSms خودش بی‌سروصدا لاگ می‌کند و
 * اعلان داخل سایت مستقل از آن ساخته می‌شود.
 */
class OrderNotifier
{
    /**
     * متن پیامک و اعلان برای هر وضعیت. کلید null یعنی برای آن وضعیت
     * اطلاع‌رسانی نمی‌کنیم (مثلا «در انتظار پرداخت» که هنوز کاری نشده).
     */
    private const STATUS_MESSAGES = [
        'awaiting_call' => [
            'title' => 'سفارش در انتظار تماس کارشناس',
            'sms'   => 'ناظر یدک%sسفارش %s ثبت شد. کارشناسان ما برای هماهنگی نهایی با شما تماس می‌گیرند.',
            'icon'  => 'fa-phone-volume',
        ],
        'paid' => [
            'title' => 'پرداخت سفارش تأیید شد',
            'sms'   => 'ناظر یدک%sپرداخت سفارش %s با موفقیت تأیید شد. سفارش شما در حال بررسی است.',
            'icon'  => 'fa-circle-check',
        ],
        'processing' => [
            'title' => 'سفارش در حال آماده‌سازی است',
            'sms'   => 'ناظر یدک%sسفارش %s در حال آماده‌سازی است و به‌زودی ارسال می‌شود.',
            'icon'  => 'fa-box-open',
        ],
        'shipped' => [
            'title' => 'سفارش ارسال شد',
            'sms'   => 'ناظر یدک%sسفارش %s ارسال شد. لطفا در دسترس باشید.',
            'icon'  => 'fa-truck',
        ],
        'delivered' => [
            'title' => 'سفارش تحویل داده شد',
            'sms'   => 'ناظر یدک%sسفارش %s تحویل داده شد. از خرید شما سپاسگزاریم.',
            'icon'  => 'fa-circle-check',
        ],
        'canceled' => [
            'title' => 'سفارش لغو شد',
            'sms'   => 'ناظر یدک%sسفارش %s لغو شد. در صورت نیاز با پشتیبانی تماس بگیرید.',
            'icon'  => 'fa-circle-xmark',
        ],
    ];

    /** پیام ثبت سفارش، جدا از تغییر وضعیت است. */
    public function orderPlaced(Order $order): void
    {
        // سفارشی که بدون پرداخت آنلاین ثبت شده، منتظر تماس کارشناس است؛
        // پیامکش هم باید همین را بگوید نه «پرداخت شد».
        if ((string) $order->status === 'awaiting_call') {
            $this->push(
                $order,
                'سفارش شما ثبت شد؛ منتظر تماس ما باشید',
                sprintf(
                    'ناظر یدک%sسفارش %s ثبت شد و پیش‌فاکتور آن صادر شد. کارشناسان ما به‌زودی برای تأیید و هماهنگی پرداخت با شما تماس می‌گیرند.',
                    "\n",
                    $this->orderNumber($order)
                ),
                'fa-phone-volume'
            );

            return;
        }

        $this->push(
            $order,
            'سفارش شما ثبت شد',
            sprintf(
                'ناظر یدک%sسفارش %s با مبلغ %s تومان ثبت شد. وضعیت آن را از حساب کاربری پیگیری کنید.',
                "\n",
                $this->orderNumber($order),
                number_format((int) $order->final_price)
            ),
            'fa-receipt'
        );
    }

    /** فقط وقتی وضعیت واقعا عوض شده باشد صدا زده می‌شود. */
    public function statusChanged(Order $order, ?string $previousStatus = null): void
    {
        $status = (string) $order->status;

        if ($previousStatus !== null && $previousStatus === $status) {
            return;
        }

        if (! isset(self::STATUS_MESSAGES[$status])) {
            return;
        }

        $template = self::STATUS_MESSAGES[$status];

        $this->push(
            $order,
            $template['title'],
            sprintf($template['sms'], "\n", $this->orderNumber($order)),
            $template['icon']
        );
    }

    private function push(Order $order, string $title, string $message, string $icon): void
    {
        $customer = $order->customer;

        if (! $customer) {
            return;
        }

        // اعلان داخل سایت اول ساخته می‌شود تا خطای درگاه پیامک آن را از بین نبرد.
        try {
            CustomerNotification::create([
                'customer_id' => $customer->id,
                'type'        => 'order',
                'title'       => $title,
                'body'        => str_replace("\n", ' ', $message),
                'url'         => '/profile/orderDetail/' . $order->id,
                'icon'        => $icon,
            ]);
        } catch (\Throwable $e) {
            Log::error('ساخت اعلان سفارش ناموفق بود', ['order' => $order->id, 'message' => $e->getMessage()]);
        }

        if (! empty($customer->phone)) {
            try {
                sendSms($customer->phone, $message);
            } catch (\Throwable $e) {
                Log::error('ارسال پیامک سفارش ناموفق بود', ['order' => $order->id, 'message' => $e->getMessage()]);
            }
        }
    }

    private function orderNumber(Order $order): string
    {
        return '#' . $order->id;
    }
}
