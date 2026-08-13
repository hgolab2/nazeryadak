@php
    $pageTitle = 'تماس با ناظر یدک | مشاوره خرید لوازم یدکی خودرو';
    $pageDescription = 'راه‌های ارتباط با ناظر یدک: تماس تلفنی و واتساپ ' . seo_config('business.phone_display') . '، ایمیل ' . seo_config('business.email') . ' و آدرس فروشگاه در قم. پاسخ‌گویی شنبه تا پنج‌شنبه، ۹ تا ۱۸.';
@endphp
@extends('layout.layout', [
    'title' => $pageTitle,
    'metaDescription' => $pageDescription,
    'keywords' => 'تماس با ناظر یدک, شماره تماس فروشگاه لوازم یدکی, آدرس فروشگاه قطعات ایساکو, پشتیبانی خرید قطعات خودرو',
    'canonical' => seo_url('/contact-us'),
    'schema' => [
        seo_webpage_schema($pageTitle, $pageDescription, seo_url('/contact-us'), 'ContactPage'),
        seo_breadcrumb_schema([
            ['name' => 'ناظر یدک', 'url' => seo_url()],
            ['name' => 'تماس با ما', 'url' => null],
        ]),
    ],
])
@section('main_content')
<main>
    <div class="container">
        <div class="row mt-3 mb-2">
            <div class="col-12">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/" class="breadcrumb-custom">ناظر یدک</a></li>
                    <li class="breadcrumb-item"><span class="breadcrumb-custom">تماس با ما</span></li>
                </ul>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-8">
                <div class="cart-content">
                    <div class="section-header">
                        <h1 class="section-title" style="font-size: 1.1rem;">راه‌های ارتباطی</h1>
                    </div>
                    <div class="row px-3 pb-3">
                        <div class="col-sm-6 mb-4">
                            <div class="text-center p-3" style="background: var(--primary-lighter); border-radius: var(--radius);">
                                <i class="fas fa-phone-alt mb-2" style="font-size: 2rem; color: var(--primary);"></i>
                                <h6 class="font-14 fw-bold">تلفن تماس</h6>
                                <p class="font-13 mb-1">۰۹۱۲۷۴۷۱۶۳۱</p>
                                <p class="font-12 text-muted mb-0">شنبه تا پنج‌شنبه ۹ الی ۱۸</p>
                            </div>
                        </div>
                        <div class="col-sm-6 mb-4">
                            <div class="text-center p-3" style="background: var(--primary-lighter); border-radius: var(--radius);">
                                <i class="fab fa-whatsapp mb-2" style="font-size: 2rem; color: #25d366;"></i>
                                <h6 class="font-14 fw-bold">واتساپ</h6>
                                <p class="font-13 mb-1">۰۹۱۲۷۴۷۱۶۳۱</p>
                                <p class="font-12 text-muted mb-0">پاسخ‌گویی سریع</p>
                            </div>
                        </div>
                        <div class="col-sm-6 mb-4">
                            <div class="text-center p-3" style="background: var(--primary-lighter); border-radius: var(--radius);">
                                <i class="fas fa-envelope mb-2" style="font-size: 2rem; color: var(--accent);"></i>
                                <h6 class="font-14 fw-bold">ایمیل</h6>
                                <p class="font-13 mb-1">info@nazeryadak.ir</p>
                                <p class="font-12 text-muted mb-0">پاسخ‌گویی در کمتر از ۲۴ ساعت</p>
                            </div>
                        </div>
                        <div class="col-sm-6 mb-4">
                            <div class="text-center p-3" style="background: var(--primary-lighter); border-radius: var(--radius);">
                                <i class="fas fa-map-marker-alt mb-2" style="font-size: 2rem; color: var(--danger);"></i>
                                <h6 class="font-14 fw-bold">آدرس</h6>
                                <p class="font-13 mb-1">قم - خیابان انقلاب - کوچه ۴۱</p>
                                <p class="font-12 text-muted mb-0">پلاک ۱۵ - واحد ۲۰۵</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="cart-content p-4">
                    <h6 style="font-weight:700; margin-bottom: 15px;"><i class="fas fa-question-circle me-2" style="color:var(--accent);"></i> سوالی دارید؟</h6>
                    <p class="font-13" style="line-height: 2;">
                        کارشناسان ما آماده مشاوره رایگان در زمینه انتخاب قطعه مناسب خودروی شما هستند. کافیست تماس بگیرید یا از طریق واتساپ پیام دهید.
                    </p>
                    <a href="/faq" class="btn btn-info w-100 mt-2">مشاهده سوالات متداول</a>
                </div>
                <div class="cart-content p-4 mt-3">
                    <h6 style="font-weight:700; margin-bottom: 15px;"><i class="fas fa-clock me-2" style="color:var(--primary);"></i> ساعات کاری</h6>
                    <ul class="ps-0 font-13" style="line-height: 2.5;">
                        <li class="d-flex justify-content-between"><span>شنبه تا چهارشنبه</span> <span>۹:۰۰ - ۱۸:۰۰</span></li>
                        <li class="d-flex justify-content-between"><span>پنج‌شنبه</span> <span>۹:۰۰ - ۱۴:۰۰</span></li>
                        <li class="d-flex justify-content-between text-muted"><span>جمعه</span> <span>تعطیل</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
