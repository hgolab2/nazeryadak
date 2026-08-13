@php
    $pageTitle = 'سیاست حریم خصوصی کاربران | ناظر یدک';
    $pageDescription = 'ناظر یدک چه اطلاعاتی از شما ذخیره می‌کند، چگونه از آن محافظت می‌کند و حقوق شما درباره داده‌های شخصی در فروشگاه اینترنتی لوازم یدکی.';
@endphp
@extends('layout.layout', [
    'title' => $pageTitle,
    'metaDescription' => $pageDescription,
    'canonical' => seo_url('/privacy'),
    'schema' => [
        seo_webpage_schema($pageTitle, $pageDescription, seo_url('/privacy')),
        seo_breadcrumb_schema([
            ['name' => 'ناظر یدک', 'url' => seo_url()],
            ['name' => 'حریم خصوصی', 'url' => null],
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
                    <li class="breadcrumb-item"><span class="breadcrumb-custom">حریم خصوصی</span></li>
                </ul>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="cart-content about-us p-4">
                    <div class="section-header">
                        <h1 class="section-title" style="font-size:1.1rem;">سیاست حفظ حریم خصوصی</h1>
                    </div>

                    <p>فروشگاه ناظر یدک (NazerYadak) به حفظ حریم خصوصی کاربران خود متعهد است. این سند نحوه جمع‌آوری، استفاده و محافظت از اطلاعات شما را شرح می‌دهد.</p>

                    <h5 style="font-size:.95rem; color:var(--primary); font-weight:700;">اطلاعاتی که جمع‌آوری می‌کنیم</h5>
                    <ul class="font-13 ps-3" style="line-height:2.5; list-style:disc;">
                        <li><strong>اطلاعات هویتی:</strong> نام، شماره تلفن و آدرس که هنگام ثبت‌نام و ثبت سفارش دریافت می‌شود</li>
                        <li><strong>اطلاعات سفارش:</strong> تاریخچه خرید، محصولات سفارش‌داده‌شده و اطلاعات پرداخت</li>
                        <li><strong>اطلاعات فنی:</strong> آدرس IP، نوع مرورگر و سیستم‌عامل برای بهبود تجربه کاربری</li>
                    </ul>

                    <h5 style="font-size:.95rem; color:var(--primary); font-weight:700;">نحوه استفاده از اطلاعات</h5>
                    <ul class="font-13 ps-3" style="line-height:2.5; list-style:disc;">
                        <li>پردازش و ارسال سفارشات</li>
                        <li>ارتباط با مشتری در خصوص وضعیت سفارش</li>
                        <li>بهبود خدمات و تجربه کاربری سایت</li>
                        <li>ارسال اطلاعیه‌ها و تخفیف‌ها (با رضایت کاربر)</li>
                    </ul>

                    <h5 style="font-size:.95rem; color:var(--primary); font-weight:700;">حفاظت از اطلاعات</h5>
                    <p>اطلاعات شخصی شما با استفاده از پروتکل‌های امنیتی SSL رمزنگاری شده و در سرورهای امن نگهداری می‌شود. اطلاعات بانکی شما نزد ما ذخیره نمی‌شود و تراکنش‌ها مستقیماً از طریق درگاه بانکی انجام می‌پذیرد.</p>

                    <h5 style="font-size:.95rem; color:var(--primary); font-weight:700;">عدم افشای اطلاعات</h5>
                    <p>ناظر یدک متعهد است اطلاعات شخصی کاربران را به هیچ شخص یا سازمان ثالثی ارائه ندهد، مگر در موارد قانونی یا با رضایت صریح کاربر.</p>

                    <h5 style="font-size:.95rem; color:var(--primary); font-weight:700;">حقوق کاربر</h5>
                    <p>شما حق دسترسی، اصلاح و حذف اطلاعات شخصی خود را دارید. برای اعمال این حقوق با پشتیبانی ما تماس بگیرید.</p>

                    <div class="mt-4 p-3 font-12 text-muted" style="background:#f8f9fa; border-radius:var(--radius-sm);">
                        <i class="fas fa-shield-alt me-1"></i>
                        ناظر یدک از اطلاعات شما محافظت می‌کند. در صورت هرگونه سوال با <a href="/contact-us">پشتیبانی</a> تماس بگیرید.
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
