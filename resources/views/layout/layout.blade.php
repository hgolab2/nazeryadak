<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    @php
        $seoTitle = $title ?? seo_site_name();
        $seoDescription = $metaDescription ?? 'خرید آنلاین لوازم یدکی اصلی خودرو، قطعات ایساکو، قطعات مصرفی، موتوری، برقی، بدنه و جلوبندی با ضمانت اصالت کالا و ارسال سراسر کشور از ناظر یدک.';
        $seoKeywords = $keywords ?? seo_default_keywords();
        $seoCanonical = $canonical ?? url()->current();
        $seoImage = $ogImage ?? seo_url('/assets/images/logo.png');
        $seoType = $ogType ?? 'website';
        $schemaItems = $schema ?? [];
        if (!empty($schemaItems) && array_is_list($schemaItems) === false) {
            $schemaItems = [$schemaItems];
        }
    @endphp
    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="keywords" content="{{ $seoKeywords }}">
    <meta name="robots" content="{{ !empty($follow) ? 'noindex,nofollow' : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1' }}">
    <meta name="googlebot" content="{{ !empty($follow) ? 'noindex,nofollow' : 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1' }}">
    <link rel="canonical" href="{{ $seoCanonical }}">
    <meta property="og:locale" content="fa_IR">
    <meta property="og:site_name" content="{{ seo_site_name() }}">
    <meta property="og:type" content="{{ $seoType }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $seoImage }}">
    @if(!empty($ampurl))
        <link rel=amphtml href="{{$ampurl}}">
    @endif
    @foreach($schemaItems as $schemaItem)
        <script type="application/ld+json">{!! seo_json_ld($schemaItem) !!}</script>
    @endforeach
    @yield('head')
    <link rel="icon" type="image/ico" href="/favicon.ico"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/bootstrap.rtl.css">
    <link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/owl.carousel.min.css">
    <link rel="stylesheet" href="/assets/css/owl.theme.default.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

    {{-- هدر بالای صفحه - فقط تماس و ساعت کاری؛ لینک‌ها در منوی اصلی هستند --}}
    <div class="header-top-bar d-none d-lg-block">
        <div class="container">
            <div class="d-flex align-items-center gap-4">
                <span><i class="fas fa-phone-alt"></i> مشاوره و پشتیبانی: علی حاجی ناظری - ۰۹۱۲۷۴۷۱۶۳۱</span>
                <span><i class="fas fa-clock"></i> ساعت کاری: شنبه تا پنج‌شنبه ۹ الی ۱۸</span>
            </div>
        </div>
    </div>

    {{-- هدر اصلی دسکتاپ --}}
    <header class="site-header w-100 d-none d-lg-block">
        <div class="container">
            <div class="header-main">
                <div class="d-flex align-items-center justify-content-between gap-3">
                    {{-- لوگو --}}
                    <div class="flex-shrink-0">
                        <a href="/" class="site-logo">
                            <img src="/assets/images/logo.png"
                                 alt="ناظر یدک - لوازم یدکی خودرو و محصولات اصلی ایساکو">
                        </a>
                    </div>
                    {{-- جستجو: تنها جعبه‌ی جستجوی سایت --}}
                    <div class="flex-grow-1" style="max-width: 560px;">
                        <form method="get" action="/shop">
                            <div class="search-box-header">
                                <input type="search" name="title" value="{{ request('title') }}" placeholder="نام قطعه، خودرو یا کد فنی را بنویسید...">
                                <button type="submit"><i class="fa fa-search"></i> جستجو</button>
                            </div>
                        </form>
                    </div>
                    {{-- ورود و سبد خرید --}}
                    <div class="d-flex align-items-center gap-3 flex-shrink-0">
                        <div class="dropdown">
                            @if(empty(Auth::guard('customer')->user()))
                            <a href="/login" class="header-action-btn">
                                <i class="fa fa-user"></i>
                                ورود / ثبت نام
                            </a>
                            @else
                            <a href="/dashboard" class="header-action-btn" data-bs-toggle="dropdown">
                                <i class="fa fa-user"></i>
                                {{Auth::guard('customer')->user()->fullName() != '' ? Auth::guard('customer')->user()->fullName() : Auth::guard('customer')->user()->phone}}
                            </a>
                            <ul class="dropdown-menu dropdown-menu-custom">
                                <li class="dropdown-user-header">
                                    <div class="dropdown-user-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div>
                                        <span class="dropdown-user-name">
                                            {{Auth::guard('customer')->user()->fullName() != '' ? Auth::guard('customer')->user()->fullName() : Auth::guard('customer')->user()->phone}}
                                        </span>
                                        <a href="/dashboard" class="dropdown-user-link">
                                            مشاهده حساب کاربری <i class="fa fa-chevron-left"></i>
                                        </a>
                                    </div>
                                </li>
                                <li class="dropdown-divider-custom"></li>
                                <li>
                                    <a href="/dashboard" class="dropdown-item-custom"><i class="fas fa-tachometer-alt"></i> داشبورد</a>
                                    <a href="/profile/orders" class="dropdown-item-custom"><i class="fas fa-box"></i> سفارش‌های من</a>
                                    <a href="/favorite" class="dropdown-item-custom"><i class="fas fa-heart"></i> علاقه‌مندی‌ها</a>
                                    <a href="/profile/info" class="dropdown-item-custom"><i class="fas fa-user-edit"></i> اطلاعات حساب</a>
                                </li>
                                <li class="dropdown-divider-custom"></li>
                                <li>
                                    <a href="/logout" class="dropdown-item-custom dropdown-item-logout"><i class="fas fa-sign-out-alt"></i> خروج از حساب</a>
                                </li>
                            </ul>
                            @endif
                        </div>
                        <a href="#shopping-cart" class="header-cart-btn" data-bs-toggle="offcanvas">
                            <img src="/assets/images/cart.png">
                            <div class="count cart-count" id="cart-count">0</div>
                        </a>
                        <div class="offcanvas offcanvas-end" tabindex="-1" data-bs-scroll="true" id="shopping-cart">
                            <div class="offcanvas-header">
                                <p class="offcanvas-title font-12">سبد خرید (<span class="cart-count-title" id="cart-count-title">0</span> کالا)</p>
                                <button type="button" class="text-reset btn-close" data-bs-dismiss="offcanvas"></button>
                            </div>
                            <div class="offcanvas-body cart-items" id="cart-items"></div>
                            <div class="row cart-footer py-3">
                                <div class="col-5">
                                    <p>مبلغ قابل پرداخت:</p>
                                    <p class="cart-total" id="cart-total">0 تومان</p>
                                </div>
                                <div class="col-7">
                                    <a href="/cart" class="btn btn-info font-13 btn-lg ms-1">مشاهده سبد خرید</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- هدر موبایل --}}
    <header class="site-header d-lg-none w-100">
        <div class="container">
            <div class="row py-2 align-items-center">
                <div class="col-3">
                    <a href="#mobile-menu" data-bs-toggle="offcanvas"><i class="fa fa-bars mobile-menu-icon"></i></a>
                    <div class="offcanvas offcanvas-start" tabindex="-1" data-bs-scroll="true" id="mobile-menu">
                        <div class="offcanvas-header" style="background: var(--primary); padding: 15px;">
                            <span class="site-logo site-logo-mobile">
                                <img src="/assets/images/logo.png" alt="ناظر یدک">
                            </span>
                            <button type="button" class="btn-close btn-close-white text-reset" data-bs-dismiss="offcanvas"></button>
                        </div>
                        <div class="offcanvas-body px-0">
                            @if(!empty(Auth::guard('customer')->user()))
                            <div class="mobile-user-box">
                                <div class="mobile-user-avatar"><i class="fas fa-user"></i></div>
                                <div>
                                    <span class="mobile-user-name">{{Auth::guard('customer')->user()->fullName() != '' ? Auth::guard('customer')->user()->fullName() : Auth::guard('customer')->user()->phone}}</span>
                                    <a href="/dashboard" class="mobile-user-link">مشاهده داشبورد <i class="fa fa-chevron-left"></i></a>
                                </div>
                            </div>
                            @else
                            <a href="/login" class="mobile-login-btn"><i class="fas fa-sign-in-alt me-2"></i> ورود / ثبت نام</a>
                            @endif
                            <ul class="mobile-menu-level-1">
                                <li class="has-mobile-submenu"><a href="#"><i class="fas fa-th-large me-2" style="color:var(--primary);"></i> دسته‌بندی محصولات</a>
                                    <ul class="mobile-menu-level-2">
                                        <li><a href="/shop?category=>موتور-و-اجزای-متعلقه">موتور و اجزای متعلقه خودرو</a></li>
                                        <li><a href="/shop?category=قطعات-مصرفی">قطعات مصرفی خودرو</a></li>
                                        <li><a href="/shop?category=شاسی-و-بدنه">شاسی و بدنه خودرو</a></li>
                                        <li><a href="/shop?category=سیستم-گیربکس-و-دیفرانسیل">سیستم گیربکس و دیفرانسیل</a></li>
                                        <li><a href="/shop?category=سیستم-سوخت‌رسانی">سیستم سوخت‌رسانی و جرقه</a></li>
                                        <li><a href="/shop?category=سیستم-چرخ-و-ترمز">سیستم چرخ و ترمز و تعلیق</a></li>
                                        <li><a href="/shop?category=سیستم-برق">سیستم برق و روشنایی</a></li>
                                    </ul>
                                </li>
                                <li><a href="/shop"><i class="fas fa-store me-2" style="color:var(--primary);"></i> فروشگاه</a></li>
                                <li><a href="/blog"><i class="fas fa-newspaper me-2" style="color:var(--primary);"></i> مجله یدکی</a></li>
                                <li><a href="/about-us"><i class="fas fa-info-circle me-2" style="color:var(--primary);"></i> درباره ما</a></li>
                                <li><a href="/contact-us"><i class="fas fa-envelope me-2" style="color:var(--primary);"></i> تماس با ما</a></li>
                            </ul>
                            @if(!empty(Auth::guard('customer')->user()))
                            <div class="mobile-menu-divider"></div>
                            <ul class="mobile-menu-level-1 mt-0">
                                <li><a href="/dashboard"><i class="fas fa-tachometer-alt me-2" style="color:var(--accent);"></i> داشبورد</a></li>
                                <li><a href="/profile/orders"><i class="fas fa-box me-2" style="color:var(--accent);"></i> سفارش‌های من</a></li>
                                <li><a href="/favorite"><i class="fas fa-heart me-2" style="color:var(--accent);"></i> علاقه‌مندی‌ها</a></li>
                                <li><a href="/profile/info"><i class="fas fa-user-edit me-2" style="color:var(--accent);"></i> اطلاعات حساب</a></li>
                                <li><a href="/logout" style="color:var(--danger);"><i class="fas fa-sign-out-alt me-2"></i> خروج</a></li>
                            </ul>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-5 text-center">
                    <a href="/" class="site-logo site-logo-mobile">
                        <img src="/assets/images/logo.png"
                             alt="ناظر یدک - لوازم یدکی خودرو و محصولات اصلی ایساکو">
                    </a>
                </div>
                <div class="col-2 d-flex align-items-center justify-content-end">
                    <div class="dropdown">
                        @if(empty(Auth::guard('customer')->user()))
                        <a href="/login"><i class="fa fa-user signup-login-icon"></i></a>
                        @else
                        <a href="#" data-bs-toggle="dropdown"><i class="fa fa-user signup-login-icon"></i></a>
                        <ul class="dropdown-menu dropdown-menu-custom">
                            <li class="dropdown-user-header">
                                <div class="dropdown-user-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div>
                                    <span class="dropdown-user-name">
                                        {{Auth::guard('customer')->user()->fullName() != '' ? Auth::guard('customer')->user()->fullName() : Auth::guard('customer')->user()->phone}}
                                    </span>
                                    <a href="/dashboard" class="dropdown-user-link">
                                        حساب کاربری <i class="fa fa-chevron-left"></i>
                                    </a>
                                </div>
                            </li>
                            <li class="dropdown-divider-custom"></li>
                            <li>
                                <a href="/dashboard" class="dropdown-item-custom"><i class="fas fa-tachometer-alt"></i> داشبورد</a>
                                <a href="/profile/orders" class="dropdown-item-custom"><i class="fas fa-box"></i> سفارش‌های من</a>
                                <a href="/favorite" class="dropdown-item-custom"><i class="fas fa-heart"></i> علاقه‌مندی‌ها</a>
                                <a href="/profile/info" class="dropdown-item-custom"><i class="fas fa-user-edit"></i> اطلاعات حساب</a>
                            </li>
                            <li class="dropdown-divider-custom"></li>
                            <li>
                                <a href="/logout" class="dropdown-item-custom dropdown-item-logout"><i class="fas fa-sign-out-alt"></i> خروج از حساب</a>
                            </li>
                        </ul>
                        @endif
                    </div>
                </div>
                <div class="col-2 d-flex align-items-center justify-content-end">
                    <a href="#shopping-cart-responsive" class="header-cart-btn" data-bs-toggle="offcanvas">
                        <img src="/assets/images/cart.png">
                        <div class="count cart-count">0</div>
                    </a>
                    <div class="offcanvas offcanvas-end" tabindex="-1" data-bs-scroll="true" id="shopping-cart-responsive">
                        <div class="offcanvas-header">
                            <p class="offcanvas-title font-12">سبد خرید (<span class="cart-count-title" id="cart-count-title">0</span> کالا)</p>
                            <button type="button" class="text-reset btn-close" data-bs-dismiss="offcanvas"></button>
                        </div>
                        <div class="offcanvas-body cart-items"></div>
                        <div class="row cart-footer">
                           <div class="col-5">
                                <p>مبلغ قابل پرداخت:</p>
                                <p class="cart-total">0 تومان</p>
                           </div>
                           <div class="col-7">
                            <a href="/cart" class="btn btn-info font-13 btn-lg ms-4">مشاهده سبد خرید</a>
                           </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- جستجوی موبایل: همیشه دیده می‌شود، نه پنهان در منو --}}
            <form method="get" action="/shop" class="mobile-search">
                <input type="search" name="title" value="{{ request('title') }}" placeholder="نام قطعه، خودرو یا کد فنی...">
                <button type="submit" aria-label="جستجو"><i class="fa fa-search"></i></button>
            </form>
        </div>
    </header>

    {{-- منوی اصلی --}}
    <nav class="d-none d-lg-block navigation">
        <div class="container">
           <ul class="main-menu">
                <li class="has-sub-menu nav-category-item">
                    <a href="#"><i class="fas fa-th-large me-1"></i> دسته‌بندی محصولات <i class="fa fa-angle-down fa-sm"></i></a>
                    <ul class="sub-menu">
                        <li><a href="/shop?category=>موتور-و-اجزای-متعلقه"><i class="fas fa-cog sub-menu-icon"></i> موتور و اجزای متعلقه خودرو</a></li>
                        <li><a href="/shop?category=قطعات-مصرفی"><i class="fas fa-oil-can sub-menu-icon"></i> قطعات مصرفی خودرو</a></li>
                        <li><a href="/shop?category=شاسی-و-بدنه"><i class="fas fa-car sub-menu-icon"></i> شاسی و بدنه خودرو</a></li>
                        <li><a href="/shop?category=سیستم-گیربکس-و-دیفرانسیل"><i class="fas fa-cogs sub-menu-icon"></i> سیستم گیربکس و دیفرانسیل</a></li>
                        <li><a href="/shop?category=سیستم-سوخت‌رسانی"><i class="fas fa-gas-pump sub-menu-icon"></i> سیستم سوخت‌رسانی و جرقه</a></li>
                        <li><a href="/shop?category=سیستم-چرخ-و-ترمز"><i class="fas fa-compact-disc sub-menu-icon"></i> سیستم چرخ و ترمز و تعلیق</a></li>
                        <li><a href="/shop?category=سیستم-برق"><i class="fas fa-bolt sub-menu-icon"></i> سیستم برق و روشنایی</a></li>
                    </ul>
                </li>
                <li><a href="/shop"><i class="fas fa-store me-1 d-none d-xl-inline"></i> فروشگاه</a></li>
                <li><a href="/blog"><i class="fas fa-newspaper me-1 d-none d-xl-inline"></i> مجله یدکی</a></li>
                <li><a href="/about-us">درباره ما</a></li>
                <li><a href="/contact-us">تماس با ما</a></li>
                <li class="me-0 ms-auto"><a href="tel:09127471631" class="nav-phone-link"><i class="fas fa-headset me-1"></i> علی حاجی ناظری - ۰۹۱۲۷۴۷۱۶۳۱</a></li>
           </ul>
        </div>
    </nav>

    @yield('main_content')

    {{-- فوتر --}}
    <footer>
        {{-- بخش مزایا --}}
        <div class="footer-features">
            <div class="container">
                <div class="row text-center">
                    <div class="col-6 col-lg-4 mb-3 mb-lg-0">
                        <div class="footer-feature-item">
                            <div class="footer-feature-icon"><i class="fas fa-truck"></i></div>
                            <h6>ارسال رایگان</h6>
                            <span>قم +۵M | سایر شهرها +۲۰M</span>
                        </div>
                    </div>
                    <div class="col-6 col-lg-4 mb-3 mb-lg-0">
                        <div class="footer-feature-item">
                            <div class="footer-feature-icon"><i class="fas fa-shield-alt"></i></div>
                            <h6>ضمانت اصالت</h6>
                            <span>کالای ۱۰۰٪ اصل</span>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="footer-feature-item">
                            <div class="footer-feature-icon"><i class="fas fa-headset"></i></div>
                            <h6>پشتیبانی</h6>
                            <span>پاسخگویی ۷ روز هفته</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container">
            <div class="footer-main">
                <div class="row">
                    <div class="col-lg-4 col-md-6 footer-box mb-4">
                        <div class="footer-brand">
                            <span class="site-logo" style="margin-bottom:15px;">
                                <img src="/assets/images/logo.png"
                                     alt="ناظر یدک - لوازم یدکی خودرو و محصولات اصلی ایساکو">
                            </span>
                            <div class="footer-about">
                                nazeryadak یک فروشگاه اینترنتی تخصصی در حوزه فروش لوازم یدکی خودرو با تمرکز ویژه بر قطعات اصلی ایساکو (ISACO) است. ما بستری امن برای خرید آنلاین لوازم یدکی فراهم کرده‌ایم.
                            </div>
                            <div class="footer-contact-info">
                                <div><i class="fas fa-phone-alt"></i> <span>۰۹۱۲۷۴۷۱۶۳۱</span></div>
                                <div><i class="fas fa-clock"></i> <span>شنبه تا پنج‌شنبه ۹ الی ۱۸</span></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-6 col-6 footer-box mb-4">
                        <p class="footer-title">دسترسی سریع</p>
                        <ul class="ps-0 footer-links">
                            <li><a href="/shop"><i class="fas fa-angle-left"></i> فروشگاه قطعات</a></li>
                            <li><a href="/blog"><i class="fas fa-angle-left"></i> مجله یدکی</a></li>
                            <li><a href="/contact-us"><i class="fas fa-angle-left"></i> تماس با ما</a></li>
                            <li><a href="/about-us"><i class="fas fa-angle-left"></i> درباره ناظر یدک</a></li>
                        </ul>
                    </div>
                    <div class="col-lg-3 col-md-6 col-6 footer-box mb-4">
                        <p class="footer-title">خدمات مشتریان</p>
                        <ul class="ps-0 footer-links">
                            <li><a href="/faq"><i class="fas fa-angle-left"></i> پرسش‌های متداول</a></li>
                            <li><a href="/terms"><i class="fas fa-angle-left"></i> شرایط استفاده</a></li>
                            <li><a href="/how-to-order"><i class="fas fa-angle-left"></i> نحوه ثبت سفارش</a></li>
                            <li><a href="/shipping"><i class="fas fa-angle-left"></i> رویه ارسال</a></li>
                            <li><a href="/return-policy"><i class="fas fa-angle-left"></i> بازگرداندن کالا</a></li>
                        </ul>
                    </div>
                    <div class="col-lg-3 col-md-6 footer-box mb-4">
                        <p class="footer-title">نماد اعتماد</p>
                        <div class="footer-trust-badges">
                            <img src="/assets/images/f-1.png" class="footer-detail-pic" alt="نماد اعتماد">
                            <img src="/assets/images/f-2.png" class="footer-detail-pic" alt="نماد ساماندهی">
                        </div>
                        <p class="footer-title mt-4">ما را دنبال کنید</p>
                        <div class="footer-social">
                            <a href="https://wa.me/989127471631" class="footer-social-btn" title="واتساپ" target="_blank"><i class="fab fa-whatsapp"></i></a>
                            <a href="tel:09127471631" class="footer-social-btn" title="تماس تلفنی"><i class="fas fa-phone-alt"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <div class="d-flex flex-column flex-md-row align-items-center justify-content-between">
                    <p class="copy-right mb-2 mb-md-0">
                        کلیه حقوق مادی و معنوی این وب‌سایت متعلق به <strong>nazeryadak</strong> می‌باشد.
                    </p>
                    <div class="footer-bottom-links">
                        <a href="/privacy">حریم خصوصی</a>
                        <span>|</span>
                        <a href="/terms">شرایط استفاده</a>
                        <span>|</span>
                        <a href="/payment-methods">شیوه‌های پرداخت</a>
                    </div>
                </div>
            </div>
        </div>
        <a href="#" class="topbutton"><i class="fa fa-chevron-up"></i></a>
    </footer>

<script src="/assets/js/jquery.min.js"></script>
<script src="/assets/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/owl.carousel.min.js"></script>
<script src="/assets/js/jquery.simple.timer.js"></script>
<script src="/assets/js/script.js"></script>
<script src="/js/sweetalert2.all.js"></script>
<script>
const toast = Swal.mixin({
    toast: true,
    position: 'top-center',
    showConfirmButton: false,
    timer: 2500,
    timerProgressBar: true
});
function addFavorite(id) {
    @if(\Auth::guard('customer')->user())
    $.get("/products/favorite/" + id, function (data) {
        let icon = $('.itemFavorite_' + id + ' i');
        if (data.result == 1) {
            icon.removeClass('far').addClass('fas');
            toast.fire({
                icon: 'success',
                title: 'به علاقه‌مندی‌ها اضافه شد'
            });
        } else {
            icon.removeClass('fas').addClass('far');
            toast.fire({
                icon: 'info',
                title: 'از علاقه‌مندی‌ها حذف شد'
            });
        }
    });
    @else
        window.location = "/login";
    @endif
}
$(document).on('click', '.add-cart-btn', function (e) {
    e.preventDefault();
    let productId = $(this).data('id');
    $.ajax({
        url: "/cart/add",
        type: "POST",
        data: {
            product_id: productId,
            _token: "{{ csrf_token() }}"
        },
        success: function (response) {
            if (response.status === "success") {
                toast.fire({
                    title: 'انجام شد!',
                    text: 'محصول با موفقیت به سبد خرید اضافه شد',
                    icon: 'success',
                    confirmButtonText: 'باشه',
                    confirmButtonColor: '#d33',
                    timer: 2000
                });
            }
        },
        error: function (xhr) {
            Swal.fire({
                title: 'خطا!',
                text: 'مشکلی در افزودن محصول پیش آمد',
                icon: 'error',
                confirmButtonText: 'باشه'
            });
        }
    });
});
function loadCart() {
    $.ajax({
        url: "/cart/data",
        type: "GET",
        success: function (res) {
            $(".cart-count").text(res.count);
            $(".cart-count-title").text(res.count);
            $(".cart-total").text(res.total + " تومان");
            let html = "";
            $.each(res.items, function (id, item) {
                html += `
                    <div class="row">
                        <div class="col-4">
                            <img src="${item.image}" class="img-fluid img-thumbnail">
                        </div>
                        <div class="col-8 d-flex align-items-center">
                            <a href="${item.url}" class="cart-product-title">${item.title}</a>
                        </div>
                    </div>
                    <div class="row my-3 border-bottom">
                        <div class="col-6 d-flex">
                            <span class="number">${item.quantity} عدد</span>
                        </div>
                        <div class="col-6 d-flex justify-content-end">
                            <p class="cart-product-price">${item.price.toLocaleString()} تومان</p>
                        </div>
                    </div>
                `;
            });
            $(".cart-items").html(html);
        }
    });
}
$('#shopping-cart').on('show.bs.offcanvas', function () {
    loadCart();
});
$(document).on('click', '.add-cart-btn', function () {
    setTimeout(loadCart, 500);
});
$(document).on('click', '.cart-delete-btn', function () {
    let id = $(this).data('id');
    $.post("{{ route('cart.remove') }}", {
        id: id,
        _token: "{{ csrf_token() }}"
    }, function () {
        loadCart();
    });
});
loadCart();
</script>
@yield('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('img[data-fetch]').forEach(function(img) {
        var url = img.getAttribute('data-fetch');
        img.removeAttribute('data-fetch');
        fetch(url).then(function(r) { return r.json(); }).then(function(data) {
            if (data.image && data.image !== '/images/no-image.svg') {
                img.src = data.image;
            }
        });
    });
});
</script>
</body>
</html>
