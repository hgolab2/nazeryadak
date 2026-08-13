@php
    $pageTitle = 'پیگیری سفارش | ناظر یدک';
    $pageDescription = 'وضعیت سفارش لوازم یدکی خود را با شماره سفارش و شماره موبایل، بدون نیاز به ورود به حساب کاربری در ناظر یدک پیگیری کنید.';
@endphp
@extends('layout.layout', [
    'title' => $pageTitle,
    'metaDescription' => $pageDescription,
    'keywords' => 'پیگیری سفارش, رهگیری سفارش لوازم یدکی, وضعیت سفارش, ناظر یدک',
    'canonical' => seo_url('/order-tracking'),
    'robots' => seo_robots_tag(! $order, true),
    'schema' => [
        seo_webpage_schema($pageTitle, $pageDescription, seo_url('/order-tracking')),
        seo_breadcrumb_schema([
            ['name' => 'ناظر یدک', 'url' => seo_url()],
            ['name' => 'پیگیری سفارش', 'url' => null],
        ]),
    ],
])
@section('main_content')
<main class="nx-account">
    <div class="nx-wrap">
        <nav class="nx-breadcrumb" aria-label="مسیر صفحه">
            <a href="/">ناظر یدک</a>
            <i class="fas fa-chevron-left"></i>
            <b>پیگیری سفارش</b>
        </nav>

        <div class="nx-track">
            {{-- فرم پیگیری --}}
            <section class="nx-card">
                <div class="nx-card-head">
                    <h2><i class="fas fa-map-marked-alt"></i> پیگیری سفارش</h2>
                </div>

                <div class="nx-panel-body">
                    <p class="nx-track-hint">
                        شماره سفارش و شماره موبایلی که سفارش با آن ثبت شده را وارد کنید.
                        شماره سفارش در پیامک تأیید و در پیش‌فاکتور شما آمده است.
                    </p>

                    <form method="POST" action="/order-tracking" class="nx-track-form">
                        @csrf
                        <div class="nx-track-col">
                            <label class="nx-field-label" for="nx-track-order"><i class="fas fa-hashtag"></i> شماره سفارش</label>
                            <div class="nx-field">
                                <i class="fas fa-receipt"></i>
                                <input type="text" id="nx-track-order" name="order_id" inputmode="numeric"
                                       autocomplete="off" placeholder="مثلا ۱۲۳۴"
                                       value="{{ $orderId }}" required>
                            </div>
                        </div>
                        <div class="nx-track-col">
                            <label class="nx-field-label" for="nx-track-mobile"><i class="fas fa-mobile-alt"></i> شماره موبایل</label>
                            <div class="nx-field">
                                <i class="fas fa-phone"></i>
                                <input type="text" id="nx-track-mobile" name="mobile" inputmode="numeric"
                                       autocomplete="tel" placeholder="۰۹۱۲۳۴۵۶۷۸۹" maxlength="15"
                                       value="{{ $mobile }}" required>
                            </div>
                        </div>
                        <button type="submit" class="nx-btn-red">
                            <i class="fas fa-search"></i> پیگیری سفارش
                        </button>
                    </form>

                    @if($errors->any() || $error)
                        <div class="nx-track-alert is-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <div>
                                @foreach($errors->all() as $message)
                                    <p>{{ $message }}</p>
                                @endforeach
                                @if($error)
                                    <p>{{ $error }}</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    @guest('customer')
                        <p class="nx-track-hint mt-2">
                            برای دیدن همه‌ی سفارش‌ها و فاکتورها
                            <a href="/login?redirect=/profile/orders" style="color:var(--nx-red,#ef4056); font-weight:700;">وارد حساب کاربری</a>
                            شوید.
                        </p>
                    @endguest
                </div>
            </section>

            @if($order)
                @php
                    $statusClass = 'nx-st-' . preg_replace('/[^a-z]/', '', (string) $order->status);
                    $currentStep = $order->trackStep();
                    $address     = $order->address;
                    $isOwner     = Auth::guard('customer')->id() && Auth::guard('customer')->id() === $order->customer_id;
                @endphp

                {{-- وضعیت سفارش --}}
                <section class="nx-card" style="margin-top:16px;">
                    <div class="nx-card-head">
                        <h2><i class="fas fa-truck-loading"></i> سفارش {{ toPersianNumbers($order->id, false) }}</h2>
                        <span class="nx-order-status {{ $statusClass }}">{{ $order->status() }}</span>
                    </div>

                    <div class="nx-panel-body">
                        @if($order->isTrackingHalted())
                            <div class="nx-track-alert is-warn">
                                <i class="fas fa-info-circle"></i>
                                <div><p>{{ $order->trackingNote() }}</p></div>
                            </div>
                        @else
                            <ol class="nx-track-steps" aria-label="مراحل سفارش">
                                @foreach(\App\Models\Order::TRACK_STEPS as $index => $step)
                                    @php $number = $index + 1; @endphp
                                    <li class="nx-track-step {{ $number < $currentStep ? 'is-done' : ($number === $currentStep ? 'is-current' : '') }}">
                                        <span class="nx-track-dot"><i class="fas {{ $step['icon'] }}"></i></span>
                                        <span class="nx-track-label">{{ $step['title'] }}</span>
                                    </li>
                                @endforeach
                            </ol>

                            @if($order->trackingNote())
                                <div class="nx-track-alert {{ in_array($order->status, ['pending', 'failed']) ? 'is-warn' : 'is-info' }}">
                                    <i class="fas fa-info-circle"></i>
                                    <div><p>{{ $order->trackingNote() }}</p></div>
                                </div>
                            @endif
                        @endif
                    </div>

                    <ul class="nx-datalist">
                        <li>
                            <span>تاریخ ثبت</span>
                            {{ gregorian_to_jalali2($order->created_at) }}
                        </li>
                        <li>
                            <span>آخرین بروزرسانی</span>
                            {{ gregorian_to_jalali2($order->updated_at) }}
                        </li>
                        <li>
                            <span>تحویل‌گیرنده</span>
                            {{ $address->receiver_name ?? ($order->customer?->fullName() ?: '—') }}
                        </li>
                        <li>
                            <span>مقصد</span>
                            @if($address)
                                {{ $address->province?->name }} — {{ $address->city }}
                            @else
                                —
                            @endif
                        </li>
                        <li>
                            <span>نحوه ارسال</span>
                            {{ $order->shippingMethod?->title ?? $order->shippingMethod?->name ?? 'ارسال عادی' }}
                        </li>
                        <li>
                            <span>مبلغ سفارش</span>
                            {{ toPersianNumbers(number_format($order->final_price)) }} تومان
                        </li>
                    </ul>

                    <div class="nx-panel-body nx-track-actions">
                        @if($isOwner)
                            <a href="/profile/orderDetail/{{ $order->id }}" class="nx-btn-ghost">
                                <i class="fas fa-eye"></i> جزئیات کامل سفارش
                            </a>
                            @if($order->status === 'awaiting_call')
                                <a href="/order/invoice/{{ $order->id }}" class="nx-btn-ghost">
                                    <i class="fas fa-file-invoice"></i> پیش‌فاکتور
                                </a>
                            @endif
                            @if(in_array($order->status, ['pending', 'failed']) && onlinePaymentEnabled())
                                <a href="/order/payment/{{ $order->id }}" class="nx-btn-red">
                                    <i class="fas fa-credit-card"></i> پرداخت سفارش
                                </a>
                            @endif
                        @endif
                        <a href="tel:{{ shopContactPhone() }}" class="nx-btn-ghost">
                            <i class="fas fa-headset"></i> تماس با کارشناس
                        </a>
                    </div>
                </section>

                {{-- اقلام سفارش --}}
                <section class="nx-card" style="margin-top:16px;">
                    <div class="nx-card-head">
                        <h2><i class="fas fa-cogs"></i> قطعات سفارش</h2>
                        <span class="nx-order-date">{{ toPersianNumbers($order->items->count(), false) }} قطعه</span>
                    </div>

                    <div class="nx-items">
                        @foreach ($order->items as $item)
                            @php $itemContactPrice = (bool) $item->product?->isContactPrice(); @endphp
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

                    <div class="nx-order-body" style="border-top:1px solid var(--nx-soft,#f0f0f1);">
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
                            <span>وضعیت</span>
                            <b>{{ $order->status() }}</b>
                        </div>
                    </div>

                    @if($order->hasContactPriceItems())
                        <div class="nx-panel-body" style="border-top:1px solid var(--nx-soft,#f0f0f1);">
                            <p class="nx-track-hint mb-0">
                                <i class="fas fa-phone-volume" style="color:var(--accent, #ef394e);"></i>
                                مبلغ قطعات بدنه و شاسی روی سایت اعلام نمی‌شود و در جمع بالا نیامده است؛ کارشناس ما آن را تلفنی اعلام می‌کند.
                            </p>
                        </div>
                    @endif
                </section>
            @endif
        </div>
    </div>
</main>
@endsection
