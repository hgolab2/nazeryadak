@php
    // وقتی پرداخت آنلاین از تنظیمات خاموش است، این صفحه «بازبینی و ثبت نهایی
    // سفارش» می‌شود و به‌جای درگاه، پیش‌فاکتور صادر می‌شود.
    $onlinePayment = $onlinePayment ?? onlinePaymentEnabled();
    $hasContactPriceItems = $hasContactPriceItems ?? false;
    // درگاه فقط وقتی نشان داده می‌شود که هم فعال باشد و هم مبلغ سفارش کامل باشد
    $canPayOnline = $canPayOnline ?? ($onlinePayment && ! $hasContactPriceItems);
@endphp
@extends('layout.layout', ['title' => ($canPayOnline ? 'پرداخت' : 'ثبت نهایی سفارش') . ' | ناظر یدک', 'robots' => seo_robots_tag(false, true), 'noBaseSchema' => true, 'bodyClass' => 'has-actionbar'])
@section('main_content')
<div class="container">
    <div class="row mt-3 mb-2">
        <div class="col-12">
            <div class="cart-content py-3 px-4 mb-3">
                <ul class="checkout-steps mb-0">
                    <li class="is-completed-extra"><a href="javascript:void(0)" class="checkout-steps-active active-link-shopping">اطلاعات ارسال</a></li>
                    <li class="is-completed"><a href="javascript:void(0)" class="checkout-steps-active active-link-shopping">{{ $canPayOnline ? 'پرداخت' : 'بازبینی سفارش' }}</a></li>
                    <li class="is-active"><a href="javascript:void(0)" class="checkout-steps-item active-link">{{ $canPayOnline ? 'اتمام خرید' : 'پیش‌فاکتور' }}</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
<main>
    <div class="container">
        @if(session('error'))
        <div class="alert alert-danger font-13 d-flex align-items-center gap-2" role="alert">
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        </div>
        @endif

        <div class="row">
            <div class="col-lg-8">
                <div class="cart-content p-4 mb-3">
                    <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                        <i class="fas {{ $canPayOnline ? 'fa-credit-card' : 'fa-headset' }}" style="color:var(--primary);"></i>
                        <h6 class="mb-0 font-13 fw-bold">{{ $canPayOnline ? 'انتخاب شیوه پرداخت' : 'شیوه نهایی‌سازی سفارش' }}</h6>
                    </div>
                    @if($canPayOnline)
                    <div class="p-3 d-flex align-items-center justify-content-between" style="border:2px solid var(--primary); border-radius:var(--radius-sm); background:var(--primary-lighter);">
                        <div class="d-flex align-items-center gap-3">
                            <input type="radio" class="form-check-input" checked style="margin-top:0;">
                            <div>
                                <p class="font-13 fw-bold mb-0"><i class="fa fa-credit-card me-1" style="color:var(--primary);"></i> پرداخت اینترنتی</p>
                                <p class="font-12 text-muted mb-0">از طریق درگاه امن زرین‌پال</p>
                            </div>
                        </div>
                        <div style="background:#ffd700; border-radius:6px; padding:6px 10px;">
                            <span style="font-weight:700; font-size:0.85rem; color:#1a1a2e;">ZarinPal</span>
                        </div>
                    </div>
                    @else
                    {{-- پرداخت آنلاین غیرفعال است: سفارش ثبت می‌شود و کارشناس تماس می‌گیرد --}}
                    <div class="p-3" style="border:2px solid var(--primary); border-radius:var(--radius-sm); background:var(--primary-lighter);">
                        <p class="font-13 fw-bold mb-1"><i class="fas fa-phone-volume me-1" style="color:var(--primary);"></i> تأیید تلفنی توسط کارشناس</p>
                        <p class="font-12 mb-0" style="line-height:2; color:var(--text-dark);">
                            @if($onlinePayment)
                                مبلغ این سفارش کامل نیست و پرداخت اینترنتی برای آن ممکن نیست.
                            @else
                                در حال حاضر پرداخت اینترنتی روی سایت فعال نیست.
                            @endif
                            با زدن دکمه‌ی «ثبت نهایی سفارش»،
                            سفارش شما ثبت می‌شود و <b>یک پیش‌فاکتور</b> برایتان صادر می‌گردد.
                            سپس کارشناسان ما برای تأیید موجودی، اعلام مبلغ دقیق و هماهنگی روش پرداخت
                            با شماره‌ی شما تماس می‌گیرند.
                        </p>
                    </div>
                    @if($hasContactPriceItems)
                    <div class="mt-3 p-3 font-12" style="background:#fff8e1; border:1px solid #ffe082; border-radius:var(--radius-sm); color:#7a5c00; line-height:2;">
                        <i class="fas fa-info-circle me-1"></i>
                        سفارش شما قطعه‌ی <b>بدنه و شاسی</b> دارد که قیمتش روی سایت اعلام نمی‌شود؛
                        مبلغ آن در جمع پیش‌فاکتور نیامده و هنگام تماس به شما اعلام می‌شود.
                    </div>
                    @endif
                    @endif
                </div>

                {{-- آدرس تحویل: کاربر باید پیش از پرداخت ببیند کالا کجا می‌رود --}}
                @if(!empty($address))
                <div class="cart-content p-4 mb-3">
                    <div class="d-flex align-items-center justify-content-between pb-2 mb-3 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-map-marker-alt" style="color:var(--primary);"></i>
                            <h6 class="mb-0 font-13 fw-bold">آدرس تحویل</h6>
                        </div>
                        <a href="/order/shopping" class="font-12" style="color:var(--primary);">
                            <i class="fas fa-edit me-1"></i> ویرایش
                        </a>
                    </div>
                    <p class="font-13 mb-1"><strong>{{ $address->receiver_name }}</strong> — {{ $address->receiver_phone }}</p>
                    <p class="font-13 text-muted mb-0" style="line-height:2;">
                        {{ optional($address->province)->name }}، {{ $address->city }}، {{ $address->address_line }}
                        @if($address->postal_code)
                            <span class="d-block">کد پستی: {{ $address->postal_code }}</span>
                        @endif
                    </p>
                </div>
                @endif

                <div class="cart-content p-4">
                    <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                        <i class="fas fa-box" style="color:var(--accent);"></i>
                        <h6 class="mb-0 font-13 fw-bold">خلاصه سفارش</h6>
                    </div>
                    <div class="row">
                        @foreach($order->items as $item)
                        <div class="col-md-4 col-6 mb-3">
                            <a href="{{ $item->product?->url() }}" class="d-block">
                                <div class="card custom-card text-center p-2">
                                    @if($item->product?->image() != '')
                                    <img src="{{ $item->product?->image() }}" class="slider-pic" alt="">
                                    @else
                                    <div class="d-flex align-items-center justify-content-center" style="height:100px;"><i class="fas fa-image" style="font-size:2rem; color:#ddd;"></i></div>
                                    @endif
                                    <div class="card-body py-2">
                                        <p class="font-12 mb-0" style="line-height:1.7;">{{ $item->product?->title }}</p>
                                        @if($item->product?->isContactPrice())
                                            <span class="font-12 d-block mt-1" style="color:var(--accent, #ef394e);">
                                                <i class="fas fa-phone-alt me-1"></i> قیمت: استعلام تلفنی
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="cart-content p-3" style="position:sticky; top:20px;">
                    <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                        <i class="fas fa-receipt" style="color:var(--primary);"></i>
                        <h6 class="mb-0 font-13 fw-bold">صورتحساب</h6>
                    </div>
                    <div class="d-flex justify-content-between py-2 font-13">
                        <span class="text-muted">مبلغ محصولات</span>
                        <span>{{ number_format($order->final_price) }} تومان</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 font-13 border-bottom">
                        <span class="text-muted"><i class="fas fa-truck me-1"></i> هزینه ارسال</span>
                        {{-- همان برچسبی که در مرحله‌ی قبل نشان داده شد؛ قبلا اینجا
                             «۰ تومان» می‌آمد و کاربر فکر می‌کرد ارسال رایگان است --}}
                        <span>
                            @if(!empty($shippingInfo) && $shippingInfo['type'] === 'free')
                                <span class="text-success fw-bold">{{ $shippingInfo['label'] }}</span>
                            @elseif(!empty($shippingInfo) && $shippingInfo['type'] === 'tipax')
                                <span style="color:var(--accent);">{{ $shippingInfo['label'] }}</span>
                            @else
                                {{ number_format($order->shipping_price) }} تومان
                            @endif
                        </span>
                    </div>
                    @if(!empty($shippingInfo) && $shippingInfo['type'] === 'tipax')
                    {{-- کرایه‌ی تیپاکس در فاکتور اینترنتی نیست؛ کاربر باید پیش از
                         پرداخت بداند مبلغ دیگری هنگام تحویل می‌پردازد --}}
                    <p class="font-12 text-muted py-2 mb-0" style="line-height:1.9;">
                        <i class="fas fa-info-circle me-1" style="color:var(--accent);"></i>
                        کرایه‌ی تیپاکس در این فاکتور محاسبه نشده و هنگام تحویل مرسوله از گیرنده دریافت می‌شود.
                    </p>
                    @endif
                    <div class="d-flex justify-content-between py-3">
                        <span class="fw-bold">{{ $canPayOnline ? 'مبلغ قابل پرداخت' : 'جمع پیش‌فاکتور' }}</span>
                        <span class="fw-bold" style="font-size:1.1rem; color:var(--primary);">{{ number_format($order->total_price) }} <small class="font-12 fw-normal">تومان</small></span>
                    </div>
                    @if($canPayOnline)
                    <a href="/payment/zarinpal/{{ $order->id }}" class="btn add-cart-btn2 text-center d-block font-13 fw-bold hide-on-mobile-buy" style="background:linear-gradient(135deg, var(--success), #43a047);">
                        <i class="fas fa-lock me-1"></i> پرداخت امن
                    </a>
                    <div class="text-center mt-3">
                        <p class="font-12 text-muted mb-0"><i class="fas fa-shield-alt me-1" style="color:var(--success);"></i> اتصال امن SSL</p>
                    </div>
                    @else
                    <form method="POST" action="/order/place/{{ $order->id }}" class="hide-on-mobile-buy">
                        @csrf
                        <button type="submit" class="btn add-cart-btn2 text-center d-block w-100 font-13 fw-bold" style="background:linear-gradient(135deg, var(--success), #43a047);">
                            <i class="fas fa-file-invoice me-1"></i> ثبت نهایی سفارش و دریافت پیش‌فاکتور
                        </button>
                    </form>
                    <div class="text-center mt-3">
                        <p class="font-12 text-muted mb-0" style="line-height:1.9;">
                            <i class="fas fa-headset me-1" style="color:var(--success);"></i>
                            پرداختی همین حالا انجام نمی‌شود؛ کارشناسان ما با شما تماس می‌گیرند.
                        </p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</main>

{{-- نوار چسبان موبایل: مبلغ نهایی و دکمه‌ی پرداخت همیشه در دسترس --}}
<div class="mobile-actionbar" role="region" aria-label="{{ $canPayOnline ? 'پرداخت سفارش' : 'ثبت نهایی سفارش' }}">
    <div class="mobile-actionbar__info">
        <span class="mobile-actionbar__label">{{ $canPayOnline ? 'مبلغ قابل پرداخت' : 'جمع پیش‌فاکتور' }}</span>
        <span class="mobile-actionbar__price">{{ number_format($order->total_price) }} <small>تومان</small></span>
    </div>
    @if($canPayOnline)
    <a href="/payment/zarinpal/{{ $order->id }}" class="mobile-actionbar__btn is-pay">
        <i class="fas fa-lock"></i> پرداخت امن
    </a>
    @else
    <form method="POST" action="/order/place/{{ $order->id }}" style="margin:0;">
        @csrf
        <button type="submit" class="mobile-actionbar__btn is-pay" style="border:none;">
            <i class="fas fa-file-invoice"></i> ثبت نهایی سفارش
        </button>
    </form>
    @endif
</div>
@endsection
