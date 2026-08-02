@extends('layout.layout', ['title' => 'نحوه ثبت سفارش | ناظر یدک'])
@section('main_content')
<main>
    <div class="container">
        <div class="row mt-3 mb-2">
            <div class="col-12">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/" class="breadcrumb-custom">ناظر یدک</a></li>
                    <li class="breadcrumb-item"><span class="breadcrumb-custom">نحوه ثبت سفارش</span></li>
                </ul>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="cart-content p-4">
                    <div class="section-header">
                        <h1 class="section-title" style="font-size:1.1rem;">راهنمای ثبت سفارش</h1>
                    </div>
                    <p class="font-13" style="line-height:2;">خرید از ناظر یدک ساده و سریع است. کافیست مراحل زیر را دنبال کنید:</p>

                    @php
                    $steps = [
                        ['icon' => 'fa-search', 'title' => 'جستجوی قطعه', 'desc' => 'قطعه مورد نظر خود را از طریق جستجوی نام، کد فنی یا دسته‌بندی پیدا کنید. می‌توانید از فیلترها برای محدود کردن نتایج استفاده کنید.'],
                        ['icon' => 'fa-cart-plus', 'title' => 'افزودن به سبد خرید', 'desc' => 'پس از انتخاب قطعه، روی دکمه «افزودن به سبد خرید» کلیک کنید. می‌توانید چند محصول را همزمان به سبد اضافه کنید.'],
                        ['icon' => 'fa-user-check', 'title' => 'ورود یا ثبت‌نام', 'desc' => 'برای تکمیل خرید باید وارد حساب کاربری شوید. اگر حساب ندارید، فقط با شماره موبایل و کد تأیید ثبت‌نام کنید.'],
                        ['icon' => 'fa-map-marker-alt', 'title' => 'ثبت آدرس', 'desc' => 'آدرس دقیق تحویل سفارش را وارد کنید. استان، شهر، آدرس کامل و کدپستی را با دقت پر کنید.'],
                        ['icon' => 'fa-credit-card', 'title' => 'پرداخت', 'desc' => 'روش پرداخت خود را انتخاب کنید: آنلاین از طریق درگاه بانکی، پرداخت در محل یا کارت به کارت.'],
                        ['icon' => 'fa-check-circle', 'title' => 'تأیید و ارسال', 'desc' => 'پس از پرداخت، سفارش شما ثبت شده و کد پیگیری دریافت می‌کنید. سفارش در سریع‌ترین زمان بسته‌بندی و ارسال می‌شود.'],
                    ];
                    @endphp

                    @foreach($steps as $i => $step)
                    <div class="d-flex gap-3 mb-4 p-3" style="background:{{ $i % 2 === 0 ? 'var(--primary-lighter)' : '#f8f9fa' }}; border-radius:var(--radius-sm); border-right:3px solid var(--primary);">
                        <div class="flex-shrink-0 text-center" style="width:50px;">
                            <div style="width:40px; height:40px; background:var(--primary); color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:.9rem;">{{ $i + 1 }}</div>
                        </div>
                        <div>
                            <h6 class="mb-1" style="font-size:.9rem; font-weight:700;">
                                <i class="fas {{ $step['icon'] }} me-1" style="color:var(--primary);"></i>
                                {{ $step['title'] }}
                            </h6>
                            <p class="font-13 text-muted mb-0" style="line-height:2;">{{ $step['desc'] }}</p>
                        </div>
                    </div>
                    @endforeach

                    <div class="text-center p-3 mt-3" style="background:var(--primary-lighter); border-radius:var(--radius);">
                        <p class="font-13 mb-2"><i class="fas fa-headset me-1" style="color:var(--primary);"></i> در هر مرحله با مشکل مواجه شدید؟</p>
                        <a href="/contact-us" class="btn btn-info">تماس با پشتیبانی</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
