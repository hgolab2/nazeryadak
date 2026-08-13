@php
    $pageTitle = 'شیوه‌های پرداخت امن سفارش | ناظر یدک';
    $pageDescription = 'پرداخت آنلاین از درگاه بانکی امن، پرداخت در محل و کارت به کارت؛ همه روش‌های پرداخت سفارش لوازم یدکی در فروشگاه ناظر یدک.';
@endphp
@extends('layout.layout', [
    'title' => $pageTitle,
    'metaDescription' => $pageDescription,
    'keywords' => 'پرداخت آنلاین لوازم یدکی, درگاه بانکی امن, پرداخت در محل قطعات خودرو',
    'canonical' => seo_url('/payment-methods'),
    'schema' => [
        seo_webpage_schema($pageTitle, $pageDescription, seo_url('/payment-methods')),
        seo_breadcrumb_schema([
            ['name' => 'ناظر یدک', 'url' => seo_url()],
            ['name' => 'شیوه‌های پرداخت', 'url' => null],
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
                    <li class="breadcrumb-item"><span class="breadcrumb-custom">شیوه‌های پرداخت</span></li>
                </ul>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="cart-content p-4">
                    <div class="section-header">
                        <h1 class="section-title" style="font-size:1.1rem;">شیوه‌های پرداخت</h1>
                    </div>
                    <p class="font-13" style="line-height:2;">ناظر یدک برای رفاه حال مشتریان، چندین روش پرداخت امن و مطمئن ارائه می‌دهد:</p>

                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="p-4 h-100" style="background:var(--primary-lighter); border-radius:var(--radius); border-right:3px solid var(--primary);">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <i class="fas fa-credit-card" style="font-size:1.5rem; color:var(--primary);"></i>
                                    <h6 class="mb-0 fw-bold">پرداخت آنلاین</h6>
                                </div>
                                <p class="font-13 text-muted mb-0" style="line-height:2;">
                                    پرداخت مستقیم از طریق درگاه بانکی امن با پشتیبانی از تمامی کارت‌های بانکی عضو شبکه شتاب. تراکنش‌ها با رمزنگاری SSL محافظت می‌شوند.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="p-4 h-100" style="background:#fff8e1; border-radius:var(--radius); border-right:3px solid var(--accent);">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <i class="fas fa-money-bill-wave" style="font-size:1.5rem; color:var(--accent);"></i>
                                    <h6 class="mb-0 fw-bold">پرداخت در محل</h6>
                                </div>
                                <p class="font-13 text-muted mb-0" style="line-height:2;">
                                    برای سفارش‌های شهر تهران، امکان پرداخت نقدی یا کارت‌خوان هنگام تحویل سفارش وجود دارد. مبلغ دقیق سفارش را هنگام تحویل پرداخت کنید.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="p-4 h-100" style="background:#e8f5e9; border-radius:var(--radius); border-right:3px solid var(--success);">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <i class="fas fa-exchange-alt" style="font-size:1.5rem; color:var(--success);"></i>
                                    <h6 class="mb-0 fw-bold">کارت به کارت</h6>
                                </div>
                                <p class="font-13 text-muted mb-0" style="line-height:2;">
                                    امکان واریز مبلغ سفارش به شماره کارت اعلام‌شده و ارسال تصویر رسید پرداخت. پس از تأیید واریز، سفارش پردازش می‌شود.
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="p-4 h-100" style="background:#fce4ec; border-radius:var(--radius); border-right:3px solid #e91e63;">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <i class="fas fa-file-invoice-dollar" style="font-size:1.5rem; color:#e91e63;"></i>
                                    <h6 class="mb-0 fw-bold">فاکتور رسمی</h6>
                                </div>
                                <p class="font-13 text-muted mb-0" style="line-height:2;">
                                    برای خریدهای سازمانی و شرکتی، امکان صدور فاکتور رسمی وجود دارد. با بخش فروش سازمانی تماس بگیرید.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 mt-2 font-13 d-flex align-items-center gap-2" style="background:#f8f9fa; border-radius:var(--radius-sm);">
                        <i class="fas fa-lock" style="color:var(--success);"></i>
                        <span>تمامی تراکنش‌های مالی در ناظر یدک با پروتکل امنیتی SSL رمزنگاری شده و اطلاعات بانکی شما نزد ما ذخیره نمی‌شود.</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
