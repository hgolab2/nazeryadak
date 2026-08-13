<?php

namespace App\Services;

use App\Models\CustomerNotification;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

/**
 * اطلاع‌رسانی سفارش از یک نقطه: هم پیامک و هم اعلان داخل سایت.
 *
 * متن هر پیامک از پنل تنظیمات خوانده می‌شود و اگر ادمین چیزی ذخیره نکرده
 * باشد، متن پیش‌فرض همین کلاس استفاده می‌شود؛ پس سایت پیش از اولین ذخیره هم
 * دقیقا مثل قبل کار می‌کند.
 */
class OrderNotifier
{
    /**
     * جانگهدارهایی که ادمین می‌تواند در متن پیامک بنویسد.
     * در صفحه‌ی تنظیمات هم همین فهرست به ادمین نشان داده می‌شود.
     */
    public const PLACEHOLDERS = [
        '{order}'  => 'شماره سفارش',
        '{amount}' => 'مبلغ سفارش (تومان)',
        '{name}'   => 'نام مشتری',
        '{shop}'   => 'نام فروشگاه',
        '{phone}'  => 'شماره تماس پشتیبانی',
    ];

    /**
     * کلید تنظیمات، عنوان اعلان، آیکن و متن پیش‌فرض هر رویداد.
     * وضعیتی که اینجا نباشد (مثل «در انتظار پرداخت») اطلاع‌رسانی نمی‌شود.
     */
    public const EVENTS = [
        'order_placed' => [
            'label'   => 'ثبت سفارش (پرداخت آنلاین)',
            'title'   => 'سفارش شما ثبت شد',
            'icon'    => 'fa-receipt',
            'default' => "{shop}\nسفارش {order} با مبلغ {amount} تومان ثبت شد. وضعیت آن را از حساب کاربری پیگیری کنید.",
        ],
        'awaiting_call' => [
            'label'   => 'ثبت سفارش (در انتظار تماس کارشناس)',
            'title'   => 'سفارش شما ثبت شد؛ منتظر تماس ما باشید',
            'icon'    => 'fa-phone-volume',
            'default' => "{shop}\nسفارش {order} ثبت شد و پیش‌فاکتور آن صادر شد. کارشناسان ما به‌زودی برای تأیید و هماهنگی پرداخت با شما تماس می‌گیرند.",
        ],
        'paid' => [
            'label'   => 'تأیید پرداخت',
            'title'   => 'پرداخت سفارش تأیید شد',
            'icon'    => 'fa-circle-check',
            'default' => "{shop}\nپرداخت سفارش {order} با موفقیت تأیید شد. سفارش شما در حال بررسی است.",
        ],
        'processing' => [
            'label'   => 'در حال آماده‌سازی',
            'title'   => 'سفارش در حال آماده‌سازی است',
            'icon'    => 'fa-box-open',
            'default' => "{shop}\nسفارش {order} در حال آماده‌سازی است و به‌زودی ارسال می‌شود.",
        ],
        'shipped' => [
            'label'   => 'ارسال شد',
            'title'   => 'سفارش ارسال شد',
            'icon'    => 'fa-truck',
            'default' => "{shop}\nسفارش {order} ارسال شد. لطفا در دسترس باشید.",
        ],
        'delivered' => [
            'label'   => 'تحویل داده شد',
            'title'   => 'سفارش تحویل داده شد',
            'icon'    => 'fa-circle-check',
            'default' => "{shop}\nسفارش {order} تحویل داده شد. از خرید شما سپاسگزاریم.",
        ],
        'canceled' => [
            'label'   => 'لغو سفارش',
            'title'   => 'سفارش لغو شد',
            'icon'    => 'fa-circle-xmark',
            'default' => "{shop}\nسفارش {order} لغو شد. در صورت نیاز با پشتیبانی تماس بگیرید.",
        ],
    ];

    /** کلید تنظیماتِ متن هر رویداد. */
    public static function settingKey(string $event): string
    {
        return 'sms_' . $event;
    }

    /** متن ذخیره‌شده‌ی ادمین یا متن پیش‌فرض. */
    public static function template(string $event): string
    {
        $default = self::EVENTS[$event]['default'] ?? '';

        return (string) Setting::get(self::settingKey($event), $default);
    }

    /** پیام ثبت سفارش، جدا از تغییر وضعیت است. */
    public function orderPlaced(Order $order): void
    {
        // سفارشی که بدون پرداخت آنلاین ثبت شده، منتظر تماس کارشناس است؛
        // پیامکش هم باید همین را بگوید نه «پرداخت شد».
        $event = (string) $order->status === 'awaiting_call' ? 'awaiting_call' : 'order_placed';

        $this->dispatch($order, $event);
    }

    /** فقط وقتی وضعیت واقعا عوض شده باشد صدا زده می‌شود. */
    public function statusChanged(Order $order, ?string $previousStatus = null): void
    {
        $status = (string) $order->status;

        if ($previousStatus !== null && $previousStatus === $status) {
            return;
        }

        // order_placed رویداد ثبت است نه وضعیت؛ از این مسیر نباید بیرون بیاید
        if ($status === 'order_placed' || ! isset(self::EVENTS[$status])) {
            return;
        }

        $this->dispatch($order, $status);
    }

    private function dispatch(Order $order, string $event): void
    {
        $config  = self::EVENTS[$event];
        $message = trim($this->render(self::template($event), $order));

        if ($message === '') {
            // ادمین متن را خالی گذاشته یعنی این اطلاع‌رسانی را نمی‌خواهد
            return;
        }

        $this->push($order, $config['title'], $message, $config['icon']);
    }

    /** جایگذاری جانگهدارها در متن */
    private function render(string $template, Order $order): string
    {
        $customer = $order->customer;

        return strtr($template, [
            '{order}'  => $this->orderNumber($order),
            '{amount}' => number_format((int) $order->final_price),
            '{name}'   => $customer?->fullName() ?: '',
            '{shop}'   => seo_site_name(),
            '{phone}'  => shopContactPhoneDisplay(),
        ]);
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
