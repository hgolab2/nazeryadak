@extends('layout.layout', ['title' => 'شرایط استفاده | ناظر یدک'])
@section('main_content')
<main>
    <div class="container">
        <div class="row mt-3 mb-2">
            <div class="col-12">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/" class="breadcrumb-custom">ناظر یدک</a></li>
                    <li class="breadcrumb-item"><span class="breadcrumb-custom">شرایط استفاده</span></li>
                </ul>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="cart-content about-us p-4">
                    <div class="section-header">
                        <h1 class="section-title" style="font-size:1.1rem;">شرایط و قوانین استفاده</h1>
                    </div>

                    <h5 style="font-size:.95rem; color:var(--primary); font-weight:700;">۱. مقدمه</h5>
                    <p>استفاده از خدمات فروشگاه اینترنتی ناظر یدک (NazerYadak) به معنای پذیرش کامل قوانین و شرایط زیر است. لطفاً قبل از خرید، این شرایط را با دقت مطالعه فرمایید.</p>

                    <h5 style="font-size:.95rem; color:var(--primary); font-weight:700;">۲. ثبت‌نام و حساب کاربری</h5>
                    <p>کاربران برای ثبت سفارش باید حساب کاربری ایجاد کنند. اطلاعات ارائه‌شده باید صحیح و به‌روز باشد. هر فرد تنها مجاز به داشتن یک حساب کاربری است و مسئولیت حفظ امنیت حساب بر عهده کاربر است.</p>

                    <h5 style="font-size:.95rem; color:var(--primary); font-weight:700;">۳. قیمت‌گذاری و پرداخت</h5>
                    <p>قیمت‌های درج‌شده در سایت به تومان بوده و ممکن است بدون اطلاع قبلی تغییر کنند. قیمت نهایی سفارش شامل قیمت محصول به‌علاوه هزینه ارسال است که در مرحله تسویه نمایش داده می‌شود. در صورت وجود تخفیف، قیمت قبل و بعد از تخفیف نمایش داده خواهد شد.</p>

                    <h5 style="font-size:.95rem; color:var(--primary); font-weight:700;">۴. ارسال سفارش</h5>
                    <p>ناظر یدک متعهد به ارسال سفارشات در سریع‌ترین زمان ممکن است. زمان تحویل تخمینی بوده و ممکن است تحت تأثیر شرایط جوی، تعطیلات رسمی یا حجم سفارشات تغییر کند. مسئولیت ارائه آدرس صحیح بر عهده خریدار است.</p>

                    <h5 style="font-size:.95rem; color:var(--primary); font-weight:700;">۵. بازگشت و تعویض</h5>
                    <p>بازگشت کالا فقط در صورت داشتن عیب فنی یا مغایرت با سفارش امکان‌پذیر است. قطعات الکتریکی و سنسورها پس از نصب قابل بازگشت نیستند.</p>

                    <h5 style="font-size:.95rem; color:var(--primary); font-weight:700;">۶. مالکیت معنوی</h5>
                    <p>کلیه محتوای سایت شامل متون، تصاویر، لوگو و طراحی متعلق به ناظر یدک بوده و هرگونه کپی‌برداری بدون اجازه کتبی ممنوع است.</p>

                    <h5 style="font-size:.95rem; color:var(--primary); font-weight:700;">۷. محدودیت مسئولیت</h5>
                    <p>ناظر یدک مسئولیتی در قبال خسارات ناشی از نصب نادرست قطعات توسط خریدار یا تعمیرکار ندارد. توصیه می‌شود نصب قطعات توسط مکانیک متخصص انجام شود.</p>

                    <div class="mt-4 p-3 font-12 text-muted" style="background:#f8f9fa; border-radius:var(--radius-sm);">
                        <i class="fas fa-info-circle me-1"></i>
                        آخرین بروزرسانی: خرداد ۱۴۰۵ — ناظر یدک حق تغییر این شرایط را برای خود محفوظ می‌دارد.
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
