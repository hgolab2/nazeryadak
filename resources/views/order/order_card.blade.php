@php
    /* کارت سفارش؛ هم در «سفارش‌های من» و هم در داشبورد استفاده می‌شود.
       روی موبایل هر مبلغ با برچسب خودش زیر هم می‌آید، برخلاف جدول قبلی که
       باید افقی اسکرول می‌شد. */
    $statusClass = 'nx-st-' . preg_replace('/[^a-z]/', '', (string) $order->status);
@endphp
<article class="nx-order">
    <header class="nx-order-head">
        <span class="nx-order-id">سفارش {{ toPersianNumbers($order->id, false) }}</span>
        <span class="nx-order-date">{{ gregorian_to_jalali2($order->created_at) }}</span>
        <span class="nx-order-status {{ $statusClass }}">{{ $order->status() }}</span>
    </header>

    <div class="nx-order-body">
        <div class="nx-order-cell">
            <span>مبلغ کالاها</span>
            <b>{{ toPersianNumbers(number_format($order->total_price)) }} <small>تومان</small></b>
        </div>
        <div class="nx-order-cell">
            <span>هزینه ارسال</span>
            <b>
                @if((int) $order->shipping_price > 0)
                    {{ toPersianNumbers(number_format($order->shipping_price)) }} <small>تومان</small>
                @else
                    رایگان
                @endif
            </b>
        </div>
        <div class="nx-order-cell">
            <span>مبلغ پرداختی</span>
            <b>{{ toPersianNumbers(number_format($order->final_price)) }} <small>تومان</small></b>
        </div>
        <div class="nx-order-cell">
            <span>تعداد اقلام</span>
            <b>{{ toPersianNumbers($order->items->count(), false) }} <small>قطعه</small></b>
        </div>
    </div>

    <footer class="nx-order-foot">
        <a href="/profile/orderDetail/{{ $order->id }}" class="nx-btn-ghost">
            <i class="fas fa-eye"></i> جزئیات سفارش
        </a>
        @if($order->status === 'awaiting_call')
            {{-- سفارش بدون پرداخت آنلاین؛ سند در دست مشتری همان پیش‌فاکتور است --}}
            <a href="/order/invoice/{{ $order->id }}" class="nx-btn-ghost">
                <i class="fas fa-file-invoice"></i> پیش‌فاکتور
            </a>
        @endif
        @if($order->canReceiveReceipt())
            @if($order->pendingReceipt)
                {{-- رسید فرستاده شده؛ دکمه‌ی تکراری فقط باعث ثبت دوباره می‌شود --}}
                <span class="nx-btn-ghost" style="cursor:default; color:#b8860b;">
                    <i class="fas fa-hourglass-half"></i> رسید در حال بررسی
                </span>
            @else
                <a href="/profile/order/{{ $order->id }}/payment-receipt" class="nx-btn-ghost">
                    <i class="fas fa-receipt"></i> ثبت رسید پرداخت
                </a>
            @endif
        @endif
    </footer>
</article>
