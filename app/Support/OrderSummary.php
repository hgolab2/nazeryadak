<?php

namespace App\Support;

use App\Models\Order;

/**
 * خلاصه‌ی کامل یک سفارش برای پیام بله.
 *
 * هدف این است که مدیر بدون باز کردن پنل بداند چه کسی چه چیزی سفارش داده:
 * مشخصات سفارش‌دهنده، نشانی تحویل، تک‌تک اقلام با تعداد و قیمت، و جمع‌ها.
 *
 * چون هر سه رویدادِ مرتبط با سفارش (ثبت و تغییر وضعیت، پرداخت ناموفق، ثبت
 * رسید) همین اطلاعات را می‌خواهند، ساختش یک‌جا جمع شده تا سه جای مختلف از
 * هم جدا نیفتند.
 */
class OrderSummary
{
    /**
     * بیشترین تعداد قلمی که در پیام می‌آید.
     *
     * پیام بله سقف طول دارد و سفارش عمده می‌تواند ده‌ها قلم داشته باشد؛
     * بقیه با یک خط «و N قلم دیگر» به پنل ارجاع داده می‌شوند.
     */
    private const MAX_ITEMS = 20;

    /**
     * فیلدهای پیام بله.
     *
     * کلید رشته‌ای «برچسب: مقدار» می‌شود و کلید عددی یک بلوک چندخطی است که
     * عیناً چاپ می‌شود؛ خط خالی ابتدای هر بلوک، فاصله‌ی بین بخش‌هاست.
     */
    public static function baleFields(Order $order): array
    {
        // بدون این، به ازای هر قلم سفارش یک کوئری محصول اجرا می‌شود
        $order->loadMissing(['customer', 'address.province', 'items.product']);

        $fields = [
            'سفارش' => '#' . $order->id,
            'وضعیت' => self::statusLabel($order),
            'ثبت'   => self::date($order->created_at),
            // بالای پیام می‌آید نه پایینش: سفارشِ بلند ممکن است به سقف طول
            // بله بخورد و بریده شود، و لینک پنل همان چیزی است که نباید برود
            'پنل'   => url('/admin/order/show/' . $order->id),
        ];

        $fields[] = self::customerBlock($order);
        $fields[] = self::addressBlock($order);
        $fields[] = self::itemsBlock($order);
        $fields[] = self::totalsBlock($order);

        return $fields;
    }

    /** مشخصات کسی که سفارش را ثبت کرده است. */
    private static function customerBlock(Order $order): string
    {
        $customer = $order->customer;

        if (! $customer) {
            return '';
        }

        $lines = ['', '👤 سفارش‌دهنده'];
        $lines[] = 'نام: ' . ($customer->fullName() ?: '—');
        $lines[] = 'موبایل: ' . ($customer->phone ?: '—');

        if (! empty($customer->email)) {
            $lines[] = 'ایمیل: ' . $customer->email;
        }

        return implode("\n", $lines);
    }

    /** نشانی تحویل؛ گیرنده می‌تواند با سفارش‌دهنده فرق داشته باشد. */
    private static function addressBlock(Order $order): string
    {
        $address = $order->address;

        if (! $address) {
            return '';
        }

        $region = trim(($address->province?->name ?: '') . ' / ' . ($address->city ?: ''), ' /');

        $lines = ['', '📍 تحویل'];
        $lines[] = 'گیرنده: ' . ($address->receiver_name ?: '—');
        $lines[] = 'تلفن: ' . ($address->receiver_phone ?: '—');

        if ($region !== '') {
            $lines[] = 'استان/شهر: ' . $region;
        }

        if (! empty($address->postal_code)) {
            $lines[] = 'کدپستی: ' . $address->postal_code;
        }

        if (! empty($address->address_line)) {
            $lines[] = 'نشانی: ' . $address->address_line;
        }

        return implode("\n", $lines);
    }

    /** تک‌تک قطعه‌های سفارش با تعداد و قیمت. */
    private static function itemsBlock(Order $order): string
    {
        $items = $order->items;
        $count = $items->count();

        if ($count === 0) {
            return '';
        }

        $lines = ['', '🛒 اقلام (' . $count . ' قلم)'];

        foreach ($items->take(self::MAX_ITEMS)->values() as $index => $item) {
            $product = $item->product;
            $title   = $product?->title ?: ('محصول #' . $item->product_id);
            $code    = $product?->isaco_code ?: $product?->sku;

            $lines[] = ($index + 1) . ') ' . $title . ($code ? ' — کد ' . $code : '');

            // قطعه‌ی استعلامی مبلغ صفر دارد؛ «۰ تومان» گمراه‌کننده است
            $lines[] = (int) $item->unit_price > 0
                ? '   ' . $item->quantity . ' عدد × ' . number_format((int) $item->unit_price)
                    . ' = ' . number_format((int) $item->total_price) . ' تومان'
                : '   ' . $item->quantity . ' عدد — قیمت استعلامی';
        }

        if ($count > self::MAX_ITEMS) {
            $lines[] = 'و ' . ($count - self::MAX_ITEMS) . ' قلم دیگر — فهرست کامل در پنل';
        }

        return implode("\n", $lines);
    }

    /** جمع اقلام، تخفیف، ارسال و مبلغ قابل پرداخت. */
    private static function totalsBlock(Order $order): string
    {
        $lines = ['', '💰 مبالغ'];
        $lines[] = 'جمع اقلام: ' . number_format($order->itemsSubtotal()) . ' تومان';

        if ($order->hasDiscount()) {
            $code = $order->discount_code ? ' (' . $order->discount_code . ')' : '';
            $lines[] = 'تخفیف' . $code . ': ' . number_format((int) $order->discount_amount) . ' تومان';
        }

        $lines[] = 'ارسال: ' . ((int) $order->shipping_price > 0
            ? number_format((int) $order->shipping_price) . ' تومان'
            : 'رایگان یا پسکرایه');

        $lines[] = 'قابل پرداخت: ' . number_format((int) $order->total_price) . ' تومان';

        // مبلغ نهایی سفارشی که قلم استعلامی دارد هنوز قطعی نیست
        try {
            if ($order->hasContactPriceItems()) {
                $lines[] = '⚠️ قلم استعلامی دارد؛ مبلغ نهایی با تماس کارشناس تعیین می‌شود.';
            }
        } catch (\Throwable $e) {
            // تشخیص قلم استعلامی نشد؛ بقیه‌ی پیام نباید از دست برود
        }

        return implode("\n", $lines);
    }

    private static function statusLabel(Order $order): string
    {
        $status = (string) $order->status;

        return Order::STATUSES[$status] ?? $status;
    }

    private static function date($value): string
    {
        if (! $value) {
            return '';
        }

        try {
            return function_exists('toPersianDate') ? toPersianDate($value, false) : (string) $value;
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }
}
