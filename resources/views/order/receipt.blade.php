@extends('layout.layout', [
    'title' => 'ثبت رسید پرداخت سفارش ' . $order->id . ' | ناظر یدک',
    'robots' => seo_robots_tag(false, true),
    'noBaseSchema' => true,
])
@section('main_content')
@php
    $hasBank = ($bank['card_number'] ?? '') !== '' || ($bank['sheba'] ?? '') !== '';
@endphp
<main class="nx-account">
    <div class="nx-wrap">
        <nav class="nx-breadcrumb" aria-label="مسیر صفحه">
            <a href="/">ناظر یدک</a>
            <i class="fas fa-chevron-left"></i>
            <a href="/profile/orders">سفارش‌ها</a>
            <i class="fas fa-chevron-left"></i>
            <a href="/profile/orderDetail/{{ $order->id }}">سفارش {{ toPersianNumbers($order->id, false) }}</a>
            <i class="fas fa-chevron-left"></i>
            <b>ثبت رسید پرداخت</b>
        </nav>

        <div class="nx-account-layout">
            @include('layout.sidebar', ['menu' => 'orders'])

            <div class="nx-account-main">
                @if(session('error'))
                    <div class="nx-card" style="margin-bottom:16px;">
                        <div class="nx-panel-body" style="color:#c62828; font-size:13px;">
                            <i class="fas fa-circle-exclamation"></i> {{ session('error') }}
                        </div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="nx-card" style="margin-bottom:16px;">
                        <div class="nx-panel-body" style="color:#c62828; font-size:13px;">
                            @foreach($errors->all() as $error)
                                <div><i class="fas fa-circle-exclamation"></i> {{ $error }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- مبلغی که باید پرداخت شود و مشخصات حساب فروشگاه --}}
                <section class="nx-card">
                    <div class="nx-card-head">
                        <h2><i class="fas fa-money-check-dollar"></i> پرداخت سفارش {{ toPersianNumbers($order->id, false) }}</h2>
                        {{-- مبلغی که باید واریز شود، یعنی بعد از کسر تخفیف و با
                             هزینه‌ی ارسال؛ نه جمع خام اقلام --}}
                        <span class="nx-order-date">{{ toPersianNumbers(number_format($order->total_price)) }} تومان</span>
                    </div>

                    <div class="nx-panel-body" style="font-size:12.5px; line-height:2; color:var(--nx-muted,#81858b);">
                        پس از واریز وجه، مشخصات پرداخت را در فرم زیر ثبت کنید. کارشناسان ما رسید را بررسی می‌کنند و
                        نتیجه‌ی تأیید یا رد آن را با پیامک به شما اعلام می‌کنیم.
                        @if($order->hasContactPriceItems())
                            <br>
                            <i class="fas fa-circle-info"></i>
                            مبلغ قطعات بدنه و شاسی در جمع بالا نیست؛ مبلغ نهایی را در تماس با کارشناس هماهنگ کنید.
                        @endif
                    </div>

                    @if($hasBank)
                        <ul class="nx-datalist">
                            @if($bank['bank_name'])
                                <li><span>بانک</span> {{ $bank['bank_name'] }}</li>
                            @endif
                            @if($bank['card_number'])
                                <li>
                                    <span>شماره کارت</span>
                                    <bdi style="font-weight:700; letter-spacing:1px;">{{ toPersianNumbers($bank['card_number'], false) }}</bdi>
                                </li>
                            @endif
                            @if($bank['sheba'])
                                <li>
                                    <span>شماره شبا</span>
                                    <bdi style="direction:ltr;">{{ $bank['sheba'] }}</bdi>
                                </li>
                            @endif
                            @if($bank['account_name'])
                                <li><span>به نام</span> {{ $bank['account_name'] }}</li>
                            @endif
                        </ul>
                    @else
                        <div class="nx-panel-body" style="font-size:12.5px; line-height:2;">
                            <i class="fas fa-phone-volume"></i>
                            شماره حساب را در تماس با کارشناس ({{ shopContactPhoneDisplay() }}) دریافت کنید و سپس رسید را همین‌جا ثبت کنید.
                        </div>
                    @endif
                </section>

                {{-- رسیدهای قبلی همین سفارش --}}
                @if($receipts->count() > 0)
                    <section class="nx-card" style="margin-top:16px;">
                        <div class="nx-card-head">
                            <h2><i class="fas fa-clock-rotate-left"></i> رسیدهای ثبت‌شده</h2>
                        </div>
                        @foreach($receipts as $receipt)
                            <div class="nx-panel-body" style="border-top:1px solid var(--nx-soft,#f0f0f1); font-size:12.5px; line-height:2;">
                                <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                                    <b>{{ toPersianNumbers(number_format((int) $receipt->amount)) }} تومان — {{ $receipt->methodLabel() }}</b>
                                    <span style="font-weight:700; color:{{ $receipt->status === 'paid' ? '#17a566' : ($receipt->status === 'rejected' ? '#c62828' : '#b8860b') }};">
                                        {{ $receipt->statusLabel() }}
                                    </span>
                                </div>
                                <div style="color:var(--nx-muted,#81858b);">
                                    تاریخ پرداخت: {{ $receipt->paid_at ? gregorian_to_jalali2($receipt->paid_at) : '—' }}
                                    @if($receipt->reference)
                                        — پیگیری: <bdi>{{ toPersianNumbers($receipt->reference, false) }}</bdi>
                                    @endif
                                </div>
                                @if($receipt->status === 'rejected' && $receipt->admin_note)
                                    <div style="color:#c62828;">
                                        <i class="fas fa-circle-exclamation"></i> دلیل رد: {{ $receipt->admin_note }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </section>
                @endif

                {{-- فرم ثبت رسید --}}
                <section class="nx-card" style="margin-top:16px;">
                    <div class="nx-card-head">
                        <h2><i class="fas fa-receipt"></i> ثبت رسید جدید</h2>
                    </div>

                    @if($pending)
                        <div class="nx-panel-body" style="font-size:12.5px; line-height:2;">
                            <i class="fas fa-hourglass-half" style="color:#b8860b;"></i>
                            رسید شما ثبت شده و در حال بررسی است. تا اعلام نتیجه، نیازی به ثبت رسید تازه نیست.
                        </div>
                    @else
                        <form method="POST" action="/profile/order/{{ $order->id }}/payment-receipt" enctype="multipart/form-data">
                            @csrf
                            <div class="nx-form">
                                <div>
                                    <label for="method">روش پرداخت *</label>
                                    <select id="method" name="method" required>
                                        @foreach(\App\Models\Payment::METHODS as $key => $label)
                                            <option value="{{ $key }}" {{ old('method') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="amount">مبلغ پرداختی (تومان) *</label>
                                    <input id="amount" type="text" name="amount" inputmode="numeric" required
                                           value="{{ old('amount', (int) $order->total_price) }}">
                                </div>
                                <div>
                                    <label for="reference">شماره پیگیری / رهگیری</label>
                                    <input id="reference" type="text" name="reference" inputmode="numeric"
                                           value="{{ old('reference') }}" placeholder="روی رسید بانکی درج شده است">
                                </div>
                                <div>
                                    <label for="card_last4">چهار رقم آخر کارت شما</label>
                                    <input id="card_last4" type="text" name="card_last4" inputmode="numeric" maxlength="4"
                                           value="{{ old('card_last4') }}">
                                </div>
                                <div>
                                    <label for="paid_at">تاریخ و ساعت پرداخت</label>
                                    <input id="paid_at" type="datetime-local" name="paid_at" value="{{ old('paid_at') }}">
                                </div>
                                <div>
                                    <label for="payer_name">نام پرداخت‌کننده</label>
                                    <input id="payer_name" type="text" name="payer_name"
                                           value="{{ old('payer_name', $order->customer?->fullName()) }}">
                                </div>
                                <div class="is-wide">
                                    <label for="receipt_image">تصویر رسید (اختیاری، حداکثر ۴ مگابایت)</label>
                                    <input id="receipt_image" type="file" name="receipt_image" accept="image/*">
                                </div>
                                <div class="is-wide">
                                    <label for="customer_note">توضیحات</label>
                                    <textarea id="customer_note" name="customer_note" rows="3"
                                              placeholder="اگر نکته‌ای درباره‌ی پرداخت هست بنویسید">{{ old('customer_note') }}</textarea>
                                </div>
                                <div class="is-wide" style="display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap;">
                                    <a href="/profile/orderDetail/{{ $order->id }}" class="nx-btn-ghost">
                                        <i class="fas fa-arrow-right"></i> بازگشت به سفارش
                                    </a>
                                    <button type="submit" class="nx-btn-red">
                                        <i class="fas fa-paper-plane"></i> ثبت رسید پرداخت
                                    </button>
                                </div>
                            </div>
                        </form>
                    @endif
                </section>
            </div>
        </div>
    </div>
</main>
@endsection
