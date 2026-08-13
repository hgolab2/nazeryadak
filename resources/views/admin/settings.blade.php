@extends('layout.managmentLayout', [
    'title' => 'تنظیمات ارسال',
    'menu' => 'settings'
])
@section('main_content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 style="font-weight:700; margin:0;">
            <i class="fas fa-cog me-2" style="color:var(--admin-primary);"></i> تنظیمات ارسال و پرداخت
        </h5>
        <p style="font-size:0.8rem; color:#777; margin:5px 0 0;">
            این مقادیر هم در محاسبه‌ی هزینه‌ی سفارش و هم در متن‌های نمایشی سایت (فوتر، سبد خرید، رویه ارسال، سوالات متداول) استفاده می‌شوند.
        </p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="border-radius:10px; font-size:0.85rem;">
        <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger" style="border-radius:10px; font-size:0.85rem;">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="admin-card">
    <form method="POST" action="/admin/settings">
        @csrf
        @method('PUT')

        <div class="admin-card-title"><i class="fas fa-map-marker-alt"></i> استان محلی</div>
        <div class="row">
            <div class="col-md-4 mb-4">
                <label style="font-size:0.82rem; font-weight:600; color:#555;">استان محلی (ارسال با پیک)</label>
                <select name="local_province_id" class="form-select" required
                        style="border-radius:8px; font-size:0.88rem; padding:10px 14px;">
                    @foreach($provinces as $province)
                        <option value="{{ $province->id }}"
                            {{ (int) old('local_province_id', $rules['local_province_id']) === (int) $province->id ? 'selected' : '' }}>
                            {{ $province->name }}
                        </option>
                    @endforeach
                </select>
                <small style="font-size:0.75rem; color:#888;">سفارش‌های این استان با پیک ارسال می‌شوند؛ بقیه با تیپاکس.</small>
            </div>
        </div>

        <div class="admin-card-title"><i class="fas fa-truck"></i> حداقل خرید برای ارسال رایگان</div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label style="font-size:0.82rem; font-weight:600; color:#555;">
                    حداقل خرید در {{ $rules['local_province_name'] }} <span style="color:#999;">(تومان)</span>
                </label>
                <input type="number" name="local_free_threshold" min="0" step="1000" required
                       class="form-control" value="{{ old('local_free_threshold', $rules['local_free_threshold']) }}"
                       style="border-radius:8px; font-size:0.88rem; padding:10px 14px; direction:ltr; text-align:left;">
                <small style="font-size:0.75rem; color:#888;">
                    سفارش‌های بالای این مبلغ در استان محلی رایگان ارسال می‌شوند.
                    <span class="text-muted">فعلی: {{ toPersianNumbers($rules['local_free_threshold']) }} تومان</span>
                </small>
            </div>

            <div class="col-md-4 mb-3">
                <label style="font-size:0.82rem; font-weight:600; color:#555;">
                    حداقل خرید در سایر شهرها <span style="color:#999;">(تومان)</span>
                </label>
                <input type="number" name="national_free_threshold" min="0" step="1000" required
                       class="form-control" value="{{ old('national_free_threshold', $rules['national_free_threshold']) }}"
                       style="border-radius:8px; font-size:0.88rem; padding:10px 14px; direction:ltr; text-align:left;">
                <small style="font-size:0.75rem; color:#888;">
                    سفارش‌های بالای این مبلغ در سایر شهرها رایگان ارسال می‌شوند.
                    <span class="text-muted">فعلی: {{ toPersianNumbers($rules['national_free_threshold']) }} تومان</span>
                </small>
            </div>

            <div class="col-md-4 mb-3">
                <label style="font-size:0.82rem; font-weight:600; color:#555;">
                    هزینه پیک در {{ $rules['local_province_name'] }} <span style="color:#999;">(تومان)</span>
                </label>
                <input type="number" name="local_shipping_cost" min="0" step="1000" required
                       class="form-control" value="{{ old('local_shipping_cost', $rules['local_shipping_cost']) }}"
                       style="border-radius:8px; font-size:0.88rem; padding:10px 14px; direction:ltr; text-align:left;">
                <small style="font-size:0.75rem; color:#888;">
                    هزینه ارسال سفارش‌های زیر حد آستانه در استان محلی.
                    <span class="text-muted">فعلی: {{ toPersianNumbers($rules['local_shipping_cost']) }} تومان</span>
                </small>
            </div>
        </div>

        <div class="alert" style="background:#fff8e1; border:1px solid #ffe082; border-radius:10px; font-size:0.8rem; color:#7a5c00;">
            <i class="fas fa-info-circle me-1"></i>
            سفارش‌های سایر شهرها که به حد ارسال رایگان نرسند، با تیپاکس و به‌صورت «پسکرایه از گیرنده» ارسال می‌شوند و مبلغی به فاکتور اضافه نمی‌شود.
        </div>

        <div class="admin-card-title mt-4"><i class="fas fa-credit-card"></i> پرداخت آنلاین</div>
        <div class="row">
            <div class="col-md-8 mb-3">
                <label class="d-flex align-items-start gap-2" style="font-size:0.85rem; font-weight:600; color:#555; cursor:pointer;">
                    <input type="checkbox" name="online_payment_enabled" value="1" class="form-check-input mt-1"
                           {{ old('online_payment_enabled', $onlinePayment) ? 'checked' : '' }}>
                    <span>
                        پرداخت اینترنتی (درگاه زرین‌پال) فعال باشد
                        <small class="d-block mt-1" style="font-size:0.75rem; color:#888; font-weight:400; line-height:2;">
                            اگر این گزینه خاموش باشد، دکمه‌ی پرداخت در کل سایت نمایش داده نمی‌شود؛ مشتری سفارش را ثبت می‌کند،
                            یک «پیش‌فاکتور» می‌بیند و کارشناسان برای هماهنگی پرداخت با او تماس می‌گیرند.
                            وضعیت این سفارش‌ها «در انتظار تماس کارشناس» ثبت می‌شود.
                        </small>
                    </span>
                </label>
                <div class="mt-2" style="font-size:0.78rem;">
                    وضعیت فعلی:
                    @if($onlinePayment)
                        <span class="badge" style="background:#e8f5e9; color:#2e7d32;">فعال</span>
                    @else
                        <span class="badge" style="background:#ffebee; color:#c62828;">غیرفعال — سفارش‌ها با پیش‌فاکتور و تماس تلفنی نهایی می‌شوند</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="admin-card-title mt-4"><i class="fas fa-building-columns"></i> حساب فروشگاه (کارت به کارت / واریز)</div>
        <div class="alert" style="background:#eef4ff; border:1px solid #c9dcff; border-radius:10px; font-size:0.78rem; color:#2c4b8b; line-height:2;">
            <i class="fas fa-info-circle me-1"></i>
            این مشخصات در صفحه‌ی «ثبت رسید پرداخت» به مشتری نشان داده می‌شود. مشتری بعد از واریز، رسیدش را ثبت می‌کند و شما
            در <a href="/admin/payment/list">صفحه‌ی پرداخت‌ها</a> آن را تأیید یا رد می‌کنید. اگر خالی بگذارید، به مشتری گفته می‌شود
            شماره حساب را تلفنی بگیرد.
        </div>
        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label" style="font-size:0.85rem; font-weight:600; color:#555;">نام بانک</label>
                <input type="text" name="bank_name" class="form-control"
                       value="{{ old('bank_name', $bank['bank_name']) }}" placeholder="ملت">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" style="font-size:0.85rem; font-weight:600; color:#555;">شماره کارت</label>
                <input type="text" name="bank_card_number" class="form-control" dir="ltr"
                       value="{{ old('bank_card_number', $bank['card_number']) }}" placeholder="6104337812345678">
                <small class="d-block mt-1" style="font-size:0.75rem; color:#888;">فقط ارقام ذخیره می‌شود.</small>
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" style="font-size:0.85rem; font-weight:600; color:#555;">شماره شبا</label>
                <input type="text" name="bank_sheba" class="form-control" dir="ltr"
                       value="{{ old('bank_sheba', $bank['sheba']) }}" placeholder="IR120570028780010957775101">
            </div>
            <div class="col-md-3 mb-3">
                <label class="form-label" style="font-size:0.85rem; font-weight:600; color:#555;">نام صاحب حساب</label>
                <input type="text" name="bank_account_name" class="form-control"
                       value="{{ old('bank_account_name', $bank['account_name']) }}">
            </div>
        </div>

        <div class="admin-card-title mt-4"><i class="fas fa-headset"></i> مشخصات کارشناس پشتیبانی</div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label" style="font-size:0.85rem; font-weight:600; color:#555;">نام کارشناس</label>
                <input type="text" name="expert_name" class="form-control"
                       value="{{ old('expert_name', $contact['expert_name']) }}">
                <small class="d-block mt-1" style="font-size:0.75rem; color:#888;">در نوار بالای سایت و منوی اصلی نمایش داده می‌شود.</small>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" style="font-size:0.85rem; font-weight:600; color:#555;">شماره تماس</label>
                <input type="text" name="contact_phone" class="form-control" dir="ltr"
                       value="{{ old('contact_phone', $contact['contact_phone']) }}" placeholder="09121234567">
                <small class="d-block mt-1" style="font-size:0.75rem; color:#888;">هم برای نمایش و هم برای لینک تماس استفاده می‌شود.</small>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" style="font-size:0.85rem; font-weight:600; color:#555;">ساعت کاری</label>
                <input type="text" name="working_hours" class="form-control"
                       value="{{ old('working_hours', $contact['working_hours']) }}">
            </div>
        </div>

        <div class="admin-card-title mt-4"><i class="fas fa-tag"></i> نشانی فرستنده (برچسب پستی)</div>
        <div class="row">
            <div class="col-md-8 mb-3">
                <label class="form-label" style="font-size:0.85rem; font-weight:600; color:#555;">نشانی فروشگاه</label>
                <input type="text" name="shop_address" class="form-control"
                       value="{{ old('shop_address', $contact['shop_address']) }}"
                       placeholder="قم، خیابان ...، پلاک ...">
                <small class="d-block mt-1" style="font-size:0.75rem; color:#888;">در بخش «فرستنده» برچسب پستی چاپ می‌شود؛ برای مرسولات مرجوعی لازم است.</small>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" style="font-size:0.85rem; font-weight:600; color:#555;">کد پستی فروشگاه</label>
                <input type="text" name="shop_postal_code" class="form-control" dir="ltr"
                       value="{{ old('shop_postal_code', $contact['shop_postal_code']) }}" placeholder="3714000000">
            </div>
        </div>

        <div class="admin-card-title mt-4"><i class="fas fa-comment-sms"></i> متن پیامک‌ها</div>
        <div class="alert" style="background:#eef4ff; border:1px solid #c9dcff; border-radius:10px; font-size:0.78rem; color:#2c4b8b; line-height:2;">
            <i class="fas fa-info-circle me-1"></i>
            در متن‌ها می‌توانید از این جانگهدارها استفاده کنید؛ هنگام ارسال با مقدار واقعی جایگزین می‌شوند:
            <div class="mt-2 d-flex flex-wrap gap-2">
                @foreach($smsPlaceholders as $token => $description)
                    <span class="badge" style="background:#fff; color:#2c4b8b; border:1px solid #c9dcff; font-weight:600;" dir="ltr">{{ $token }}</span>
                    <span style="font-size:0.72rem; color:#5a76b5;">{{ $description }}</span>
                @endforeach
            </div>
            <div class="mt-2">اگر متنی را <strong>خالی</strong> بگذارید، آن پیامک و اعلانش اصلاً ارسال نمی‌شود.</div>
        </div>
        <div class="row">
            @foreach($smsEvents as $event => $config)
                <div class="col-md-6 mb-3">
                    <label class="form-label" style="font-size:0.85rem; font-weight:600; color:#555;">{{ $config['label'] }}</label>
                    <textarea name="sms[{{ $event }}]" rows="3" class="form-control"
                              style="font-size:0.82rem; line-height:2;">{{ old('sms.' . $event, $smsTemplates[$event]) }}</textarea>
                </div>
            @endforeach
        </div>

        <div class="text-end mt-3">
            <button type="submit" class="btn"
                    style="background:var(--admin-primary); color:#fff; border-radius:8px; font-size:0.88rem; padding:10px 24px;">
                <i class="fas fa-check me-1"></i> ذخیره تنظیمات
            </button>
        </div>
    </form>
</div>

<div class="admin-card">
    <div class="admin-card-title"><i class="fas fa-eye"></i> پیش‌نمایش قوانین فعلی</div>
    <table class="table table-bordered text-center mb-0" style="font-size:0.83rem;">
        <thead style="background:var(--admin-bg);">
            <tr>
                <th>مقصد</th>
                <th>مبلغ فاکتور</th>
                <th>هزینه ارسال</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $rules['local_province_name'] }}</td>
                <td>بالای {{ shippingAmountWords($rules['local_free_threshold']) }}</td>
                <td><span class="text-success fw-bold">رایگان</span></td>
            </tr>
            <tr>
                <td>{{ $rules['local_province_name'] }}</td>
                <td>کمتر از {{ shippingAmountWords($rules['local_free_threshold']) }}</td>
                <td>{{ toPersianNumbers($rules['local_shipping_cost']) }} تومان</td>
            </tr>
            <tr>
                <td>سایر شهرها</td>
                <td>بالای {{ shippingAmountWords($rules['national_free_threshold']) }}</td>
                <td><span class="text-success fw-bold">رایگان</span></td>
            </tr>
            <tr>
                <td>سایر شهرها</td>
                <td>کمتر از {{ shippingAmountWords($rules['national_free_threshold']) }}</td>
                <td>تیپاکس (پسکرایه از گیرنده)</td>
            </tr>
        </tbody>
    </table>
</div>
@endsection
