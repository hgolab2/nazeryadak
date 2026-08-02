@extends('layout.layout', [
    'title' => "درباره ناظر یدک | NazerYadak"
])
@section('main_content')
<main>
    <div class="container">
        <div class="row mt-3 mb-2">
            <div class="col-12">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/" class="breadcrumb-custom">ناظر یدک</a></li>
                    <li class="breadcrumb-item"><span class="breadcrumb-custom">درباره ما</span></li>
                </ul>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-8">
                <div class="cart-content about-us">
                    <div class="section-header">
                        <h1 class="section-title" style="font-size: 1.1rem;">درباره ناظر یدک</h1>
                    </div>
                    <div class="px-2">
                        <p>
                            <strong>NazerYadak</strong> یک فروشگاه اینترنتی تخصصی در حوزه فروش لوازم یدکی خودرو با تمرکز ویژه بر قطعات اصلی ایساکو (ISACO) است. ما با هدف فراهم‌کردن دسترسی سریع و مطمئن به قطعات استاندارد و مورد تأیید خودروسازان داخلی، بستری امن برای خرید آنلاین لوازم یدکی فراهم کرده‌ایم تا کاربران بتوانند با اطمینان خاطر، قطعه مناسب خودروی خود را انتخاب و تهیه کنند.
                        </p>
                        <h5 style="font-size: 1rem; color: var(--primary); font-weight:700;">چرا ناظر یدک؟</h5>
                        <p>
                            در ناظر یدک اصالت کالا، شفافیت اطلاعات و رضایت مشتریان در اولویت قرار دارد. ارائه قطعات اورجینال ایساکو، قیمت‌گذاری منصفانه، پشتیبانی پاسخ‌گو و ارسال سریع، از مهم‌ترین تعهدات ما به مشتریان است.
                        </p>
                        <h5 style="font-size: 1rem; color: var(--primary); font-weight:700;">مأموریت ما</h5>
                        <p>
                            ما همواره تلاش می‌کنیم با گسترش تنوع محصولات و بهبود خدمات، مرجعی قابل اعتماد برای خرید لوازم یدکی ایساکو و سایر قطعات اصلی خودرو باشیم. هدف ما ساده‌سازی فرایند خرید قطعات یدکی و حذف واسطه‌ها برای رسیدن به قیمت مناسب‌تر است.
                        </p>
                        <h5 style="font-size: 1rem; color: var(--primary); font-weight:700;">تعهدات ما</h5>
                        <ul class="font-13 ps-3" style="line-height: 2.5; list-style: disc;">
                            <li>تمامی قطعات دارای ضمانت اصالت و اورجینال هستند</li>
                            <li>قیمت‌گذاری شفاف و رقابتی بدون واسطه</li>
                            <li>ارسال سریع به تمامی نقاط ایران</li>

                            <li>مشاوره تخصصی رایگان توسط کارشناسان خودرو</li>
                            <li>پشتیبانی ۲۴ ساعته از طریق تلفن و پیام‌رسان</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="cart-content text-center p-4">
                    <div style="width:80px; height:80px; background:var(--primary-lighter); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 15px;">
                        <i class="fas fa-car" style="font-size:2rem; color:var(--primary);"></i>
                    </div>
                    <h6 style="font-weight:700;">تخصص ما: لوازم یدکی خودرو</h6>
                    <p class="font-13 text-muted" style="line-height:2;">فروش قطعات اصلی ایساکو، سایپا یدک و سایر برندهای معتبر</p>
                </div>
                <div class="cart-content p-4 mt-3">
                    <h6 style="font-weight:700; margin-bottom: 15px;"><i class="fas fa-phone-alt me-2" style="color:var(--primary);"></i> ارتباط سریع</h6>
                    <p class="font-13 mb-2"><i class="fas fa-headset me-2 text-muted"></i> مشاوره: ۰۹۱۲۷۴۷۱۶۳۱</p>
                    <p class="font-13 mb-2"><i class="fas fa-clock me-2 text-muted"></i> شنبه تا پنج‌شنبه ۹ الی ۱۸</p>
                    <p class="font-13 mb-2"><i class="fas fa-map-marker-alt me-2 text-muted"></i> قم - خیابان انقلاب - کوچه ۴۱ - پلاک ۱۵ - واحد ۲۰۵</p>
                    <p class="font-13 mb-0"><i class="fas fa-envelope me-2 text-muted"></i> info@nazeryadak.ir</p>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
