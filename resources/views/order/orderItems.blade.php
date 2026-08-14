@extends('layout.layout', ['title' => 'جزئیات سفارش #' . $order->id . ' | ناظر یدک', 'robots' => seo_robots_tag(false, true), 'noBaseSchema' => true])
@section('main_content')
@php
    $statusClass = 'nx-st-' . preg_replace('/[^a-z]/', '', (string) $order->status);
    $address = $order->address;
@endphp
<main class="nx-account">
    <div class="nx-wrap">
        <nav class="nx-breadcrumb" aria-label="مسیر صفحه">
            <a href="/">ناظر یدک</a>
            <i class="fas fa-chevron-left"></i>
            <a href="/profile/orders">سفارش‌ها</a>
            <i class="fas fa-chevron-left"></i>
            <b>سفارش {{ toPersianNumbers($order->id, false) }}</b>
        </nav>

        <div class="nx-account-layout">
            @include('layout.sidebar', ['menu' => 'orders'])

            <div class="nx-account-main">
                {{-- خلاصه سفارش --}}
                <section class="nx-card">
                    <div class="nx-card-head">
                        <h2><i class="fas fa-file-invoice"></i> سفارش {{ toPersianNumbers($order->id, false) }}</h2>
                        <span class="nx-order-status {{ $statusClass }}">{{ $order->status() }}</span>
                    </div>

                    <ul class="nx-datalist">
                        <li>
                            <span>تاریخ ثبت</span>
                            {{ gregorian_to_jalali2($order->created_at) }}
                        </li>
                        <li>
                            <span>تحویل‌گیرنده</span>
                            {{ $address->receiver_name ?? ($order->customer?->fullName() ?: '—') }}
                        </li>
                        <li>
                            <span>شماره تماس</span>
                            {{-- بدون false، شماره‌ی تلفن با جداکننده‌ی هزارگان نمایش داده می‌شود --}}
                            <bdi>{{ toPersianNumbers($address->receiver_phone ?? ($order->customer?->phone ?? '—'), false) }}</bdi>
                        </li>
                        <li>
                            <span>نحوه ارسال</span>
                            {{ $order->shippingMethod?->title ?? $order->shippingMethod?->name ?? 'ارسال عادی' }}
                        </li>
                        @if($address)
                            <li class="is-wide">
                                <span>آدرس تحویل</span>
                                <span style="color:var(--nx-ink,#23254e); text-align:left;">
                                    {{ $address->province?->name }} — {{ $address->city }} — {{ $address->address_line }}
                                    @if($address->postal_code) (کد پستی: <bdi>{{ toPersianNumbers($address->postal_code) }}</bdi>) @endif
                                </span>
                            </li>
                        @endif
                    </ul>
                </section>

                {{-- اقلام --}}
                <section class="nx-card" style="margin-top:16px;">
                    <div class="nx-card-head">
                        <h2><i class="fas fa-cogs"></i> قطعات سفارش</h2>
                        <span class="nx-order-date">{{ toPersianNumbers($order->items->count(), false) }} قطعه</span>
                    </div>

                    <div class="nx-items">
                        @foreach ($order->items as $item)
                            <div class="nx-item">
                                <a href="{{ $item->product?->url() ?: '#' }}" class="nx-item-thumb">
                                    <img src="{{ $item->product?->image() ?: '/images/no-image.svg' }}"
                                         alt="{{ $item->product?->title }}" loading="lazy"
                                         onerror="this.onerror=null;this.src='/images/no-image.svg';">
                                </a>
                                <div class="nx-item-info">
                                    @if($item->product)
                                        <a href="{{ $item->product->url() }}">{{ $item->product->title }}</a>
                                    @else
                                        <b>قطعه حذف‌شده</b>
                                    @endif
                                    @php $itemContactPrice = (bool) $item->product?->isContactPrice(); @endphp
                                    <div class="nx-item-meta">
                                        {{ toPersianNumbers($item->quantity, false) }} عدد
                                        @if(!$itemContactPrice)
                                            × {{ toPersianNumbers(number_format($item->unit_price)) }} تومان
                                        @endif
                                    </div>
                                </div>
                                @if($itemContactPrice)
                                    {{-- قطعات بدنه و شاسی مبلغ اعلامی ندارند؛ تلفنی هماهنگ می‌شود --}}
                                    <div class="nx-item-price" style="font-size:12px; color:var(--accent, #ef394e);">
                                        استعلام تلفنی
                                    </div>
                                @else
                                    <div class="nx-item-price">
                                        {{ toPersianNumbers(number_format($item->total_price ?: $item->unit_price * $item->quantity)) }}
                                        <small style="font-size:11px; color:var(--nx-muted,#81858b);">تومان</small>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- جمع مبالغ --}}
                    <div class="nx-order-body" style="border-top:1px solid var(--nx-soft,#f0f0f1);">
                        {{-- برچسب‌ها پیش از این جابه‌جا بودند: final_price جمع
                             اقلام است و total_price مبلغ پرداختی --}}
                        <div class="nx-order-cell">
                            <span>مبلغ کالاها</span>
                            <b>{{ toPersianNumbers(number_format($order->final_price)) }} <small>تومان</small></b>
                        </div>
                        @if($order->hasDiscount())
                        <div class="nx-order-cell">
                            <span>تخفیف
                                @if($order->discount_code)
                                    <bdi style="font-size:11px;">({{ $order->discount_code }})</bdi>
                                @endif
                            </span>
                            <b style="color:#17a566;">−{{ toPersianNumbers(number_format((int) $order->discount_amount)) }} <small>تومان</small></b>
                        </div>
                        @endif
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
                            <b>{{ toPersianNumbers(number_format($order->total_price)) }} <small>تومان</small></b>
                        </div>
                        <div class="nx-order-cell">
                            <span>وضعیت</span>
                            <b>{{ $order->status() }}</b>
                        </div>
                    </div>

                    @if($order->status === 'awaiting_call')
                    {{-- سفارش بدون پرداخت آنلاین: پیش‌فاکتور صادر شده و منتظر تماس است --}}
                    <div class="nx-order-body" style="border-top:1px solid var(--nx-soft,#f0f0f1);">
                        <p class="font-13 mb-2" style="line-height:2;">
                            <i class="fas fa-phone-volume me-1" style="color:var(--accent, #ef394e);"></i>
                            این سفارش ثبت شده و کارشناسان ما برای تأیید نهایی و هماهنگی پرداخت با شما تماس می‌گیرند.
                            @if($order->hasContactPriceItems())
                                مبلغ قطعات بدنه و شاسی در جمع بالا نیامده و در همین تماس اعلام می‌شود.
                            @endif
                        </p>
                        <a href="/order/invoice/{{ $order->id }}" class="nx-btn" style="font-size:13px;">
                            <i class="fas fa-file-invoice"></i> مشاهده پیش‌فاکتور
                        </a>
                    </div>
                    @endif
                </section>

                {{-- پرداخت: وضعیت رسیدِ ثبت‌شده و دکمه‌ی ثبت رسید تازه --}}
                @php $receipt = $receipt ?? null; @endphp
                @if($receipt || $order->canReceiveReceipt())
                <section class="nx-card" style="margin-top:16px;">
                    <div class="nx-card-head">
                        <h2><i class="fas fa-money-check-dollar"></i> پرداخت</h2>
                        @if($receipt)
                            <span class="nx-order-date" style="font-weight:700; color:{{ $receipt->status === 'paid' ? '#17a566' : ($receipt->status === 'rejected' ? '#c62828' : '#b8860b') }};">
                                {{ $receipt->statusLabel() }}
                            </span>
                        @endif
                    </div>

                    @if($receipt)
                        <ul class="nx-datalist">
                            <li><span>مبلغ ثبت‌شده</span> {{ toPersianNumbers(number_format((int) $receipt->amount)) }} تومان</li>
                            <li><span>روش پرداخت</span> {{ $receipt->methodLabel() }}</li>
                            <li>
                                <span>تاریخ پرداخت</span>
                                {{ $receipt->paid_at ? gregorian_to_jalali2($receipt->paid_at) : '—' }}
                            </li>
                            @if($receipt->reference)
                                <li><span>شماره پیگیری</span> <bdi>{{ toPersianNumbers($receipt->reference, false) }}</bdi></li>
                            @endif
                        </ul>

                        <div class="nx-panel-body" style="font-size:12.5px; line-height:2;">
                            @if($receipt->status === 'pending')
                                <i class="fas fa-hourglass-half" style="color:#b8860b;"></i>
                                رسید شما ثبت شده و در حال بررسی توسط کارشناسان است؛ نتیجه را پیامک می‌کنیم.
                            @elseif($receipt->status === 'paid')
                                <i class="fas fa-circle-check" style="color:#17a566;"></i>
                                پرداخت شما تأیید شد.
                            @elseif($receipt->status === 'rejected')
                                <i class="fas fa-circle-exclamation" style="color:#c62828;"></i>
                                رسید تأیید نشد@if($receipt->admin_note) — دلیل: {{ $receipt->admin_note }}@endif.
                                در صورت نیاز با {{ shopContactPhoneDisplay() }} تماس بگیرید.
                            @endif
                        </div>
                    @else
                        <div class="nx-panel-body" style="font-size:12.5px; line-height:2; color:var(--nx-muted,#81858b);">
                            اگر مبلغ سفارش را کارت‌به‌کارت یا واریز کرده‌اید، مشخصات پرداخت را ثبت کنید تا کارشناسان بررسی و تأیید کنند.
                        </div>
                    @endif

                    @if($order->canReceiveReceipt() && (! $receipt || $receipt->status === 'rejected'))
                        <div class="nx-panel-body" style="padding-top:0;">
                            <a href="/profile/order/{{ $order->id }}/payment-receipt" class="nx-btn-red">
                                <i class="fas fa-receipt"></i> {{ $receipt ? 'ثبت رسید جدید' : 'ثبت رسید پرداخت' }}
                            </a>
                        </div>
                    @endif
                </section>
                @endif
            </div>
        </div>
    </div>
</main>
@endsection
