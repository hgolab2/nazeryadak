@extends('layout.layout', ['title' => 'رویه ارسال سفارش | ناظر یدک'])
@section('main_content')
<main>
    <div class="container">
        <div class="row mt-3 mb-2">
            <div class="col-12">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/" class="breadcrumb-custom">ناظر یدک</a></li>
                    <li class="breadcrumb-item"><span class="breadcrumb-custom">رویه ارسال سفارش</span></li>
                </ul>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="cart-content about-us p-4">
                    <div class="section-header">
                        <h1 class="section-title" style="font-size:1.1rem;">رویه ارسال سفارش</h1>
                    </div>

                    <h5 style="font-size:.95rem; color:var(--primary); font-weight:700;"><i class="fas fa-shipping-fast me-1"></i> روش‌های ارسال</h5>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <div class="text-center p-3" style="background:var(--primary-lighter); border-radius:var(--radius); height:100%;">
                                <i class="fas fa-motorcycle mb-2" style="font-size:1.5rem; color:var(--accent);"></i>
                                <h6 class="font-13 fw-bold">پیک در قم</h6>
                                <p class="font-12 text-muted mb-0">تحویل درب منزل در شهر قم</p>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="text-center p-3" style="background:var(--primary-lighter); border-radius:var(--radius); height:100%;">
                                <i class="fas fa-truck mb-2" style="font-size:1.5rem; color:var(--primary);"></i>
                                <h6 class="font-13 fw-bold">تیپاکس</h6>
                                <p class="font-12 text-muted mb-0">ارسال به سراسر کشور</p>
                            </div>
                        </div>
                    </div>

                    <h5 style="font-size:.95rem; color:var(--primary); font-weight:700;"><i class="fas fa-money-bill-wave me-1"></i> هزینه ارسال</h5>
                    <table class="table table-bordered font-13 text-center mb-4" style="border-radius:var(--radius); overflow:hidden;">
                        <thead style="background:var(--primary); color:#fff;">
                            <tr>
                                <th>مقصد</th>
                                <th>مبلغ فاکتور</th>
                                <th>هزینه ارسال</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>قم</td>
                                <td>بالای ۵ میلیون تومان</td>
                                <td><span class="text-success fw-bold">رایگان</span></td>
                            </tr>
                            <tr>
                                <td>قم</td>
                                <td>کمتر از ۵ میلیون تومان</td>
                                <td>۵۰,۰۰۰ تومان</td>
                            </tr>
                            <tr>
                                <td>سایر شهرها</td>
                                <td>بالای ۲۰ میلیون تومان</td>
                                <td><span class="text-success fw-bold">رایگان</span></td>
                            </tr>
                            <tr>
                                <td>سایر شهرها</td>
                                <td>کمتر از ۲۰ میلیون تومان</td>
                                <td>تیپاکس (پسکرایه از گیرنده)</td>
                            </tr>
                        </tbody>
                    </table>

                    <h5 style="font-size:.95rem; color:var(--primary); font-weight:700;"><i class="fas fa-clock me-1"></i> زمان پردازش سفارش</h5>
                    <p>سفارش‌هایی که تا ساعت ۱۴:۰۰ ثبت و پرداخت شوند، همان روز کاری پردازش و آماده ارسال می‌شوند. سفارش‌های بعد از این ساعت، روز کاری بعد پردازش خواهند شد.</p>

                    <h5 style="font-size:.95rem; color:var(--primary); font-weight:700;"><i class="fas fa-box-open me-1"></i> بسته‌بندی</h5>
                    <p>تمامی قطعات با بسته‌بندی اصلی کارخانه و لایه محافظ اضافی ارسال می‌شوند تا از آسیب در حمل‌ونقل جلوگیری شود. برای قطعات حساس مانند سنسورها و قطعات الکتریکی، بسته‌بندی ویژه انجام می‌شود.</p>

                    <h5 style="font-size:.95rem; color:var(--primary); font-weight:700;"><i class="fas fa-map-pin me-1"></i> پیگیری سفارش</h5>
                    <p>پس از ارسال سفارش، کد رهگیری پستی از طریق پیامک برای شما ارسال می‌شود. همچنین از بخش «سفارش‌های من» در حساب کاربری می‌توانید وضعیت سفارش خود را پیگیری کنید.</p>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
