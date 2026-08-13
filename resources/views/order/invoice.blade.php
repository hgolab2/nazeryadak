@php
    /* پیش‌فاکتور: سندی که مشتری بعد از ثبت سفارش می‌بیند و تا تماس کارشناس
       در دست دارد. هیچ پرداختی اینجا انجام نمی‌شود؛ هدف این است که کاربر
       دقیقا بداند چه چیزی ثبت شده و مرحله‌ی بعد چیست. */
    $items = $order->items;
    $hasContactPriceItems = $order->hasContactPriceItems();
    $itemsSum = (int) $order->final_price;
    $awaitingCall = $order->status === 'awaiting_call';
@endphp
@extends('layout.layout', [
    'title' => 'پیش‌فاکتور سفارش | ناظر یدک',
    'robots' => seo_robots_tag(false, true),
    'noBaseSchema' => true,
])
@section('main_content')
<div class="container">
    <div class="row mt-3 mb-2">
        <div class="col-12">
            <div class="cart-content py-3 px-4 mb-3">
                <ul class="checkout-steps mb-0">
                    <li class="is-completed-extra"><a href="javascript:void(0)" class="checkout-steps-active active-link-shopping">اطلاعات ارسال</a></li>
                    <li class="is-completed-extra"><a href="javascript:void(0)" class="checkout-steps-active active-link-shopping">بازبینی سفارش</a></li>
                    <li class="is-active"><a href="javascript:void(0)" class="checkout-steps-active active-link-shopping">پیش‌فاکتور</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<main>
    <div class="container">
        {{-- پیام اصلی: سفارش ثبت شد و کارشناس تماس می‌گیرد --}}
        <div class="cart-content p-4 mb-3 text-center" style="border-top:4px solid var(--success);">
            <div style="width:78px; height:78px; background:#e8f5e9; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 15px;">
                <i class="fas fa-check" style="font-size:2.3rem; color:var(--success);"></i>
            </div>
            <h1 class="fw-bold mb-2" style="font-size:1.15rem; color:var(--success);">سفارش شما با موفقیت ثبت شد</h1>
            <p class="font-13 text-muted mb-3">
                شماره پیگیری:
                <span class="badge" style="background:var(--primary-lighter); color:var(--primary); font-size:.85rem;">
                    NY-{{ $order->id }}
                </span>
            </p>

            <div class="mx-auto p-3 text-start" style="max-width:640px; background:var(--primary-lighter); border-radius:var(--radius-sm);">
                <p class="font-13 fw-bold mb-2" style="color:var(--primary);">
                    <i class="fas fa-phone-volume me-1"></i> مرحله بعد: کارشناسان ما با شما تماس می‌گیرند
                </p>
                <ul class="font-13 mb-0 ps-3" style="line-height:2.2;">
                    <li>این برگه یک <b>پیش‌فاکتور</b> است و هنوز هیچ مبلغی از شما دریافت نشده است.</li>
                    <li>کارشناسان ناظر یدک در اولین فرصت کاری با شماره‌ی
                        <bdi class="fw-bold">{{ optional($address)->receiver_phone ?? $order->customer?->phone ?? '—' }}</bdi>
                        تماس می‌گیرند تا موجودی قطعات، مبلغ نهایی و هزینه‌ی ارسال را تأیید کنند.</li>
                    <li>پس از تأیید تلفنی، روش پرداخت (کارت‌به‌کارت یا پرداخت درب منزل) با شما هماهنگ می‌شود.</li>
                    @if($hasContactPriceItems)
                    <li>قطعات <b>بدنه و شاسی</b> این سفارش قیمت اعلامی روی سایت ندارند؛ مبلغشان در همین تماس اعلام و به فاکتور نهایی اضافه می‌شود.</li>
                    @endif
                    <li>تا آن زمان کالاها رزرو قطعی نشده‌اند؛ اگر عجله دارید، خودتان با ما تماس بگیرید:
                        <a href="tel:{{ shopContactPhone() }}" class="fw-bold" style="color:var(--primary);">{{ shopContactPhoneDisplay() }}</a>
                    </li>
                </ul>
            </div>
        </div>

        {{-- ناحیه‌ی چاپ: تنها همین بلوک روی کاغذ می‌رود --}}
        <div id="invoice-print">

            {{-- سربرگ کاغذ؛ روی صفحه دیده نمی‌شود --}}
            <div class="invoice-print-header">
                <div class="ip-brand">
                    <img src="/assets/images/logo.png" alt="{{ seo_site_name() }}" width="150" height="50">
                    <div class="ip-brand-meta">
                        <strong>{{ seo_site_name() }}</strong>
                        <span>{{ shopContactPhoneDisplay() }} — {{ shopWorkingHours() }}</span>
                        <span>{{ seo_url() }}</span>
                    </div>
                </div>
                <div class="ip-doc">
                    <strong>پیش‌فاکتور فروش</strong>
                    <span>شماره: <bdi>NY-{{ $order->id }}</bdi></span>
                    <span>تاریخ: {{ gregorian_to_jalali2($order->created_at, true) }}</span>
                </div>
            </div>

        <div class="row">
            <div class="col-lg-8">
                {{-- اقلام سفارش --}}
                <div class="cart-content p-4 mb-3">
                    <div class="d-flex align-items-center justify-content-between pb-2 mb-3 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-file-invoice" style="color:var(--primary);"></i>
                            <h2 class="mb-0 font-13 fw-bold">اقلام پیش‌فاکتور</h2>
                        </div>
                        <span class="font-12 text-muted">
                            تاریخ ثبت: {{ gregorian_to_jalali2($order->created_at, true) }}
                        </span>
                    </div>

                    <div style="overflow-x:auto;">
                        <table class="table align-middle mb-0" style="font-size:.85rem;">
                            <thead style="background:#f8f9fa;">
                                <tr>
                                    <th style="width:40px;">#</th>
                                    <th>قطعه</th>
                                    <th style="width:70px;">تعداد</th>
                                    <th style="width:130px;">قیمت واحد</th>
                                    <th style="width:130px;">جمع</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $index => $item)
                                @php $itemContactPrice = (bool) $item->product?->isContactPrice(); @endphp
                                <tr>
                                    <td>{{ toPersianNumbers($index + 1, false) }}</td>
                                    <td style="line-height:1.9;">
                                        <a href="{{ $item->product?->url() ?? '#' }}" style="color:var(--text-dark);">
                                            {{ $item->product?->title ?? 'قطعه حذف شده' }}
                                        </a>
                                        @if($item->product?->sku)
                                            <small class="d-block text-muted">کد فنی: <bdi>{{ $item->product->sku }}</bdi></small>
                                        @endif
                                    </td>
                                    <td>{{ toPersianNumbers($item->quantity, false) }}</td>
                                    @if($itemContactPrice)
                                        <td colspan="2" style="color:var(--accent, #ef394e);">
                                            <i class="fas fa-phone-alt me-1"></i> استعلام تلفنی
                                        </td>
                                    @else
                                        <td>{{ toPersianNumbers(number_format($item->unit_price)) }}</td>
                                        <td class="fw-bold">{{ toPersianNumbers(number_format($item->total_price)) }}</td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- آدرس تحویل --}}
                @if(!empty($address))
                <div class="cart-content p-4 mb-3">
                    <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                        <i class="fas fa-map-marker-alt" style="color:var(--primary);"></i>
                        <h2 class="mb-0 font-13 fw-bold">آدرس تحویل</h2>
                    </div>
                    <p class="font-13 mb-1"><strong>{{ $address->receiver_name }}</strong> — <bdi>{{ $address->receiver_phone }}</bdi></p>
                    <p class="font-13 text-muted mb-0" style="line-height:2;">
                        {{ optional($address->province)->name }}، {{ $address->city }}، {{ $address->address_line }}
                        @if($address->postal_code)
                            <span class="d-block">کد پستی: <bdi>{{ $address->postal_code }}</bdi></span>
                        @endif
                    </p>
                </div>
                @endif
            </div>

            {{-- خلاصه مبالغ --}}
            <div class="col-lg-4">
                <div class="cart-content p-3" style="position:sticky; top:20px;">
                    <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                        <i class="fas fa-receipt" style="color:var(--primary);"></i>
                        <h2 class="mb-0 font-13 fw-bold">خلاصه پیش‌فاکتور</h2>
                    </div>

                    <div class="d-flex justify-content-between py-2 font-13">
                        <span class="text-muted">مبلغ قطعات</span>
                        <span>{{ toPersianNumbers(number_format($itemsSum)) }} تومان</span>
                    </div>

                    <div class="d-flex justify-content-between py-2 font-13 border-bottom">
                        <span class="text-muted"><i class="fas fa-truck me-1"></i> هزینه ارسال</span>
                        <span>
                            @if(!empty($shippingInfo) && $shippingInfo['type'] === 'free')
                                <span class="text-success fw-bold">{{ $shippingInfo['label'] }}</span>
                            @elseif(!empty($shippingInfo) && $shippingInfo['type'] === 'tipax')
                                <span style="color:var(--accent);">{{ $shippingInfo['label'] }}</span>
                            @else
                                {{ toPersianNumbers(number_format($order->shipping_price)) }} تومان
                            @endif
                        </span>
                    </div>

                    <div class="d-flex justify-content-between py-3">
                        <span class="fw-bold">جمع پیش‌فاکتور
                            @if($hasContactPriceItems)
                                <small class="font-12 fw-normal text-muted d-block">(بدون قطعات استعلامی)</small>
                            @endif
                        </span>
                        <span class="fw-bold" style="font-size:1.1rem; color:var(--primary);">
                            {{ toPersianNumbers(number_format($order->total_price)) }} <small class="font-12 fw-normal">تومان</small>
                        </span>
                    </div>

                    <div class="font-12 p-2 mb-3" style="background:#fff8e1; border-radius:var(--radius-sm); color:#7a5c00; line-height:1.9;">
                        <i class="fas fa-info-circle me-1"></i>
                        این مبلغ نهایی نیست و پس از تأیید تلفنی کارشناس قطعی می‌شود.
                    </div>

                    <div class="no-print">
                        <a href="tel:{{ shopContactPhone() }}" class="btn add-cart-btn2 text-center d-block font-13 fw-bold mb-2">
                            <i class="fas fa-headset me-1"></i> تماس با کارشناس
                        </a>
                        @if($order->canReceiveReceipt())
                        {{-- مشتری بعد از کارت‌به‌کارت، رسیدش را همین‌جا ثبت می‌کند --}}
                        <a href="/profile/order/{{ $order->id }}/payment-receipt" class="btn w-100 font-13 mb-2"
                           style="border:1px solid var(--success); color:var(--success); border-radius:var(--radius-sm); background:#fff;">
                            <i class="fas fa-receipt me-1"></i> پرداخت کرده‌ام؛ ثبت رسید
                        </a>
                        @endif
                        <button type="button" onclick="window.print()" class="btn w-100 font-13 mb-2"
                                style="border:1px solid var(--border-color); color:var(--text-dark); border-radius:var(--radius-sm); background:#fff;">
                            <i class="fas fa-print me-1"></i> چاپ پیش‌فاکتور
                        </button>
                        <a href="/profile/orderDetail/{{ $order->id }}" class="btn w-100 font-13"
                           style="border:1px solid var(--primary); color:var(--primary); border-radius:var(--radius-sm);">
                            <i class="fas fa-box me-1"></i> پیگیری سفارش
                        </a>
                    </div>

                    <div class="text-center mt-3">
                        <span class="badge py-1 px-2" style="background:{{ $awaitingCall ? '#fff8e1' : '#e8f5e9' }}; color:{{ $awaitingCall ? '#7a5c00' : 'var(--success)' }};">
                            وضعیت: {{ $order->status() }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

            {{-- پاورقی کاغذ؛ روی صفحه دیده نمی‌شود --}}
            <div class="invoice-print-footer">
                این برگه پیش‌فاکتور است و سند پرداخت محسوب نمی‌شود؛ مبلغ و موجودی پس از تأیید تلفنی کارشناس قطعی می‌شود.
                <span>{{ seo_site_name() }} — {{ shopContactPhoneDisplay() }}</span>
            </div>

        </div>{{-- /#invoice-print --}}

        <div class="text-center my-4">
            <a href="/shop" class="font-13" style="color:var(--primary);">
                <i class="fas fa-arrow-right me-1"></i> ادامه خرید از فروشگاه
            </a>
        </div>
    </div>
</main>

<style>
    /* سربرگ و پاورقی مخصوص کاغذ روی صفحه‌ی وب دیده نمی‌شوند */
    .invoice-print-header,
    .invoice-print-footer { display: none; }

    /* نسخه‌ی چاپی: فهرست‌سفید — فقط #invoice-print روی کاغذ می‌آید.
       با پنهان‌کردن نام‌به‌نام المان‌ها (هدر، منو، فوتر، نوار موبایل، کشوها،
       بنر PWA و ...) همیشه یکی جا می‌ماند؛ پس به‌جای آن هر چیزی که در مسیر
       ناحیه‌ی چاپ نیست پنهان می‌شود. */
    @media print {
        @page { size: A4; margin: 12mm; }

        html, body {
            background: #fff !important;
            padding: 0 !important;
            margin: 0 !important;
        }

        /* همه‌ی فرزندان مستقیم body جز <main> حذف؛ سپس فقط مسیر ناحیه‌ی چاپ باز می‌شود */
        body > *                            { display: none !important; }
        body > main                         { display: block !important; }
        main > .container > *               { display: none !important; }
        main > .container                   { display: block !important; max-width: none !important; padding: 0 !important; }
        main > .container > #invoice-print  { display: block !important; }

        /* دکمه‌ها و هر چیزی که فقط روی صفحه معنی دارد */
        #invoice-print .no-print,
        #invoice-print .btn { display: none !important; }

        .invoice-print-header {
            display: flex !important;
            align-items: flex-start;
            justify-content: space-between;
            gap: 20px;
            padding-bottom: 10px;
            margin-bottom: 14px;
            border-bottom: 2px solid #000;
        }
        .invoice-print-header .ip-brand { display: flex; align-items: center; gap: 12px; }
        .invoice-print-header .ip-brand img { width: 130px; height: auto; }
        .invoice-print-header .ip-brand-meta,
        .invoice-print-header .ip-doc { display: flex; flex-direction: column; gap: 3px; font-size: 10pt; line-height: 1.8; }
        .invoice-print-header .ip-doc { text-align: left; }
        .invoice-print-header strong { font-size: 12pt; }

        .invoice-print-footer {
            display: block !important;
            margin-top: 14px;
            padding-top: 8px;
            border-top: 1px solid #999;
            font-size: 9pt;
            line-height: 2;
            text-align: center;
        }
        .invoice-print-footer span { display: block; }

        /* ستون‌ها روی کاغذ زیر هم می‌نشینند و کادرها ساده می‌شوند */
        #invoice-print .row { display: block !important; margin: 0 !important; }
        #invoice-print [class*="col-"] { width: 100% !important; max-width: 100% !important; flex: none !important; padding: 0 !important; }
        #invoice-print .cart-content {
            box-shadow: none !important;
            border: 1px solid #999 !important;
            border-radius: 0 !important;
            position: static !important;   /* ستون خلاصه روی صفحه sticky است */
            padding: 10px !important;
            margin-bottom: 10px !important;
            break-inside: avoid;
        }
        #invoice-print [style*="overflow-x"] { overflow: visible !important; }

        #invoice-print table { width: 100% !important; font-size: 10pt !important; border-collapse: collapse !important; }
        #invoice-print thead { display: table-header-group; }  /* تکرار سربرگ جدول در صفحه‌های بعد */
        #invoice-print th,
        #invoice-print td { border: 1px solid #999 !important; padding: 5px 6px !important; }
        #invoice-print tr { break-inside: avoid; }

        /* لینک‌ها روی کاغذ مثل متن معمولی دیده شوند */
        #invoice-print a { color: #000 !important; text-decoration: none !important; }
    }
</style>
@endsection
