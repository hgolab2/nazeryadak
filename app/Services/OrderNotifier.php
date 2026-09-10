<?php

namespace App\Services;

use App\Models\CustomerNotification;
use App\Models\Order;
use App\Models\Setting;
use App\Services\OtpService;
use App\Support\OrderSummary;
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
        '{code}'   => 'کد تأیید شماره (فقط پیامک ثبت سفارش، و فقط برای شماره‌ی تأییدنشده)',
    ];

    /**
     * رویدادهایی که در لحظه‌ی ثبت سفارش می‌افتند.
     *
     * کد تأیید فقط به همین‌ها می‌چسبد؛ پیامک «ارسال شد» یا «تحویل شد»
     * روزها بعد می‌رود و کد داخلش معنایی ندارد.
     */
    private const CODE_EVENTS = ['order_placed', 'awaiting_call'];

    /**
     * کلید تنظیمات، عنوان اعلان، آیکن و متن پیش‌فرض هر رویداد.
     * وضعیتی که اینجا نباشد (مثل «در انتظار پرداخت») اطلاع‌رسانی نمی‌شود.
     */
    public const EVENTS = [
        'order_placed' => [
            'label'   => 'ثبت سفارش (پرداخت آنلاین)',
            'title'   => 'سفارش شما ثبت شد',
            'icon'    => 'fa-receipt',
            'default' => "{shop}\nسفارش {order} با مبلغ {amount} تومان ثبت شد. وضعیت آن را از حساب کاربری پیگیری کنید.{code}",
        ],
        'awaiting_call' => [
            'label'   => 'ثبت سفارش (در انتظار تماس کارشناس)',
            'title'   => 'سفارش شما ثبت شد؛ منتظر تماس ما باشید',
            'icon'    => 'fa-phone-volume',
            'default' => "{shop}\nسفارش {order} ثبت شد و پیش‌فاکتور آن صادر شد. کارشناسان ما به‌زودی برای تأیید و هماهنگی پرداخت با شما تماس می‌گیرند.{code}",
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
        $config   = self::EVENTS[$event];
        $template = self::template($event);

        /*
        | کد تأیید فقط در پیامک معنا دارد.
        |
        | اعلان داخل سایت را کسی می‌بیند که از قبل وارد شده، پس نشان‌دادن کد
        | به او هم بی‌فایده است و هم کد را بی‌دلیل در جای دومی تکرار می‌کند.
        */
        $smsMessage    = trim($this->render($template, $order, $this->verificationBlock($order, $event)));
        $notifyMessage = trim($this->render($template, $order, ''));

        // اطلاع‌رسانی بله برای مدیر است و به متن پیامکِ مشتری وابسته نیست؛
        // اگر ادمین متن پیامک را خالی کرده باشد هم باید از سفارش باخبر شود.
        BaleNotifier::send($event, OrderSummary::baleFields($order));

        if ($notifyMessage === '' && $smsMessage === '') {
            // ادمین متن را خالی گذاشته یعنی این اطلاع‌رسانی را نمی‌خواهد
            return;
        }

        $this->push($order, $config['title'], $notifyMessage, $smsMessage, $config['icon']);
    }

    /**
     * بلوک کد تأیید، یا رشته‌ی خالی.
     *
     * بلوک عمداً کامل و خودایستا است: اگر جای کد فقط عدد می‌آمد، متنِ قالب
     * باید برچسب «کد تأیید:» را خودش می‌داشت و آن وقت برای مشتریِ تأییدشده
     * یک برچسب بی‌مقدار ته پیامک می‌ماند.
     */
    private function verificationBlock(Order $order, string $event): string
    {
        if (! in_array($event, self::CODE_EVENTS, true)) {
            return '';
        }

        $customer = $order->customer;

        if (! $customer || empty($customer->phone) || $customer->hasVerifiedPhone()) {
            return '';
        }

        try {
            $code = OtpService::issue($customer->phone, OtpService::PURPOSE_ORDER);
        } catch (\Throwable $e) {
            // نبودن کد نباید جلوی پیامک ثبت سفارش را بگیرد؛ آن پیام اصلی است.
            Log::error('ساخت کد تأیید سفارش ناموفق بود', ['order' => $order->id, 'message' => $e->getMessage()]);

            return '';
        }

        /*
        | خط آخر برای WebOTP است: کروم اندروید با دیدن همین الگو کد را خودش
        | در فرم می‌گذارد. صفحه‌ی نتیجه‌ی سفارش دقیقاً همین لحظه باز است، پس
        | بهترین حالت ممکن برای این قابلیت است.
        */
        return "\nکد تأیید شماره: {$code}"
            . "\nبا زدن این کد در سایت، سابقه‌ی سفارش‌ها و آدرستان ذخیره می‌شود."
            . "\n@" . self::webOtpHost() . " #{$code}";
    }

    /** دامنه‌ی سایت بدون پروتکل؛ WebOTP دقیقاً همین را می‌خواهد. */
    private static function webOtpHost(): string
    {
        return (string) (parse_url((string) seo_url(), PHP_URL_HOST) ?: request()->getHost());
    }

    /** جایگذاری جانگهدارها در متن */
    private function render(string $template, Order $order, string $code = ''): string
    {
        $customer = $order->customer;

        return strtr($template, [
            '{order}'  => $this->orderNumber($order),
            // مبلغ قابل پرداخت (اقلام − تخفیف + ارسال)، نه جمع خام اقلام
            '{amount}' => number_format((int) $order->total_price),
            '{name}'   => $customer?->fullName() ?: '',
            '{shop}'   => seo_site_name(),
            '{phone}'  => shopContactPhoneDisplay(),
            '{code}'   => $code,
        ]);
    }

    private function push(Order $order, string $title, string $message, string $smsMessage, string $icon): void
    {
        $customer = $order->customer;

        if (! $customer) {
            return;
        }

        // اعلان داخل سایت اول ساخته می‌شود تا خطای درگاه پیامک آن را از بین نبرد.
        try {
            if ($message === '') {
                // ادمین متن این رویداد را خالی کرده؛ فقط اعلان ساخته نمی‌شود
                throw new \DomainException('empty');
            }

            CustomerNotification::create([
                'customer_id' => $customer->id,
                'type'        => 'order',
                'title'       => $title,
                'body'        => str_replace("\n", ' ', $message),
                'url'         => '/profile/orderDetail/' . $order->id,
                'icon'        => $icon,
            ]);
        } catch (\DomainException $e) {
            // متن خالی، خطا نیست
        } catch (\Throwable $e) {
            Log::error('ساخت اعلان سفارش ناموفق بود', ['order' => $order->id, 'message' => $e->getMessage()]);
        }

        if (! empty($customer->phone) && $smsMessage !== '') {
            try {
                sendSms($customer->phone, $smsMessage);
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
