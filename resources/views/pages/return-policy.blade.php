@extends('layout.layout', ['title' => 'رویه بازگرداندن کالا | ناظر یدک'])
@section('main_content')
<main>
    <div class="container">
        <div class="row mt-3 mb-2">
            <div class="col-12">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/" class="breadcrumb-custom">ناظر یدک</a></li>
                    <li class="breadcrumb-item"><span class="breadcrumb-custom">رویه بازگرداندن کالا</span></li>
                </ul>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="cart-content about-us p-4">
                    <div class="section-header">
                        <h1 class="section-title" style="font-size:1.1rem;">رویه بازگرداندن کالا</h1>
                    </div>

                    <div class="p-3 mb-4 d-flex align-items-start gap-2" style="background:var(--primary-lighter); border-radius:var(--radius-sm); border-right:3px solid var(--primary);">
                        <i class="fas fa-undo-alt mt-1" style="color:var(--primary); font-size:1.2rem;"></i>
                        <p class="font-13 mb-0" style="line-height:2;">
                            <strong>رضایت شما اولویت ماست.</strong> بازگشت کالا فقط در صورت داشتن عیب فنی یا مغایرت با سفارش امکان‌پذیر است.
                        </p>
                    </div>

                    <h5 style="font-size:.95rem; color:var(--primary); font-weight:700;">شرایط بازگشت کالا</h5>
                    <ul class="font-13 ps-3" style="line-height:2.5; list-style:disc;">
                        <li>بازگشت فقط در صورت <strong>عیب فنی</strong> یا <strong>مغایرت با سفارش</strong> پذیرفته می‌شود</li>
                        <li>قطعه باید <strong>استفاده نشده</strong> و در بسته‌بندی اصلی باشد</li>
                        <li>هولوگرام و برچسب‌های محصول نباید آسیب دیده باشند</li>
                        <li>فاکتور یا رسید خرید باید همراه کالا ارسال شود</li>
                    </ul>

                    <h5 style="font-size:.95rem; color:var(--primary); font-weight:700;">قطعاتی که قابل بازگشت نیستند</h5>
                    <ul class="font-13 ps-3" style="line-height:2.5; list-style:disc;">
                        <li>قطعات الکتریکی و سنسورها پس از نصب</li>
                        <li>قطعاتی که بسته‌بندی آنها باز شده و آسیب دیده</li>
                        <li>قطعات سفارشی و اختصاصی</li>
                        <li>روغن، فیلتر و سایر مواد مصرفی پس از باز شدن بسته‌بندی</li>
                    </ul>

                    <h5 style="font-size:.95rem; color:var(--primary); font-weight:700;">مراحل بازگشت</h5>
                    <div class="row mb-3">
                        <div class="col-md-3 text-center mb-3">
                            <div class="p-3" style="background:#f8f9fa; border-radius:var(--radius);">
                                <div style="width:40px; height:40px; background:var(--primary); color:#fff; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-weight:700; margin-bottom:8px;">۱</div>
                                <p class="font-12 fw-bold mb-0">تماس با پشتیبانی</p>
                            </div>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="p-3" style="background:#f8f9fa; border-radius:var(--radius);">
                                <div style="width:40px; height:40px; background:var(--primary); color:#fff; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-weight:700; margin-bottom:8px;">۲</div>
                                <p class="font-12 fw-bold mb-0">تأیید درخواست</p>
                            </div>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="p-3" style="background:#f8f9fa; border-radius:var(--radius);">
                                <div style="width:40px; height:40px; background:var(--primary); color:#fff; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-weight:700; margin-bottom:8px;">۳</div>
                                <p class="font-12 fw-bold mb-0">ارسال کالا</p>
                            </div>
                        </div>
                        <div class="col-md-3 text-center mb-3">
                            <div class="p-3" style="background:#f8f9fa; border-radius:var(--radius);">
                                <div style="width:40px; height:40px; background:var(--accent); color:#fff; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-weight:700; margin-bottom:8px;">۴</div>
                                <p class="font-12 fw-bold mb-0">بازگشت وجه</p>
                            </div>
                        </div>
                    </div>

                    <h5 style="font-size:.95rem; color:var(--primary); font-weight:700;">نحوه بازگشت وجه</h5>
                    <p>پس از دریافت و بررسی کالای مرجوعی، مبلغ خرید به همان روش پرداخت اولیه (حساب بانکی یا کیف پول) بازگردانده می‌شود. هزینه ارسال مرجوعی بر عهده خریدار است مگر اینکه کالا معیوب بوده باشد.</p>

                    <div class="text-center p-3 mt-3" style="background:var(--primary-lighter); border-radius:var(--radius);">
                        <p class="font-13 mb-2">برای ثبت درخواست بازگشت کالا:</p>
                        <a href="/contact-us" class="btn btn-info">تماس با پشتیبانی</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
