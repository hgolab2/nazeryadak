<!DOCTYPE html>
<html lang="fa" dir="rtl" prefix="og: https://ogp.me/ns#">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    @php
        /*
        | مقادیر سئوی هر صفحه از @extends پاس داده می‌شوند و اینجا با
        | پیش‌فرض‌های config/seo.php ترکیب می‌شوند تا هیچ صفحه‌ای بدون
        | title/description/canonical معتبر رندر نشود.
        */
        $seoTitle = $title ?? seo_config('default_title');
        $seoDescription = $metaDescription ?? seo_config('default_description');
        $seoKeywords = $keywords ?? seo_default_keywords();
        $seoCanonical = $canonical ?? seo_canonical();
        $seoImage = isset($ogImage) ? seo_image_url($ogImage) : seo_image_url(seo_config('default_image'));
        $seoImageAlt = $ogImageAlt ?? $seoTitle;
        $seoType = $ogType ?? 'website';
        $seoRobots = $robots ?? (!empty($follow) ? seo_robots_tag(false, false) : seo_robots_tag());
        $seoPrev = $prevPage ?? null;
        $seoNext = $nextPage ?? null;

        // اسکیمای پایه (Organization + WebSite + AutoPartsStore) روی همه‌ی
        // صفحات می‌آید مگر صفحه صراحتا با no_base_schema آن را رد کند.
        $schemaItems = $schema ?? [];
        if (!empty($schemaItems) && array_is_list($schemaItems) === false) {
            $schemaItems = [$schemaItems];
        }
        if (empty($noBaseSchema)) {
            $schemaItems = array_merge(seo_base_schema(), $schemaItems);
        }

        $seoVerification = (array) seo_config('verification', []);
        $seoAnalytics = (array) seo_config('analytics', []);
    @endphp

    {{-- منابع بحرانی زودتر از موعد درخواست می‌شوند؛ مستقیم روی LCP اثر دارد --}}
    <link rel="dns-prefetch" href="//www.googletagmanager.com">
    <link rel="preload" as="style" href="/assets/css/style.css">
    <link rel="preload" as="font" type="font/woff" href="/assets/font/IRANSans/IRANSansWeb(FaNum).woff" crossorigin>
    <link rel="preload" as="image" href="/assets/images/logo.png" fetchpriority="high">

    <title>{{ $seoTitle }}</title>
    <meta name="description" content="{{ $seoDescription }}">
    <meta name="keywords" content="{{ $seoKeywords }}">
    <meta name="robots" content="{{ $seoRobots }}">
    <meta name="googlebot" content="{{ $seoRobots }}">
    <meta name="bingbot" content="{{ $seoRobots }}">
    <meta name="author" content="{{ seo_site_name() }}">
    <meta name="publisher" content="{{ seo_site_name() }}">
    <meta name="language" content="Persian">
    <meta name="rating" content="general">
    <meta name="revisit-after" content="3 days">
    <meta name="format-detection" content="telephone=no">
    <meta name="geo.region" content="IR-25">
    <meta name="geo.placename" content="{{ seo_config('business.city') }}">
    @if(seo_config('business.latitude'))
    <meta name="geo.position" content="{{ seo_config('business.latitude') }};{{ seo_config('business.longitude') }}">
    <meta name="ICBM" content="{{ seo_config('business.latitude') }}, {{ seo_config('business.longitude') }}">
    @endif

    <link rel="canonical" href="{{ $seoCanonical }}">
    <link rel="alternate" hreflang="fa-IR" href="{{ $seoCanonical }}">
    <link rel="alternate" hreflang="x-default" href="{{ $seoCanonical }}">
    @if($seoPrev)<link rel="prev" href="{{ $seoPrev }}">@endif
    @if($seoNext)<link rel="next" href="{{ $seoNext }}">@endif

    {{-- Open Graph --}}
    <meta property="og:locale" content="{{ seo_config('locale', 'fa_IR') }}">
    <meta property="og:site_name" content="{{ seo_site_name() }}">
    <meta property="og:type" content="{{ $seoType }}">
    <meta property="og:title" content="{{ $seoTitle }}">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $seoCanonical }}">
    <meta property="og:image" content="{{ $seoImage }}">
    <meta property="og:image:secure_url" content="{{ $seoImage }}">
    <meta property="og:image:alt" content="{{ $seoImageAlt }}">
    <meta property="og:image:width" content="{{ $ogImageWidth ?? seo_config('default_image_width', 512) }}">
    <meta property="og:image:height" content="{{ $ogImageHeight ?? seo_config('default_image_height', 512) }}">
    @isset($ogPrice)
    <meta property="product:price:amount" content="{{ $ogPrice }}">
    <meta property="product:price:currency" content="{{ seo_config('business.currency', 'IRR') }}">
    <meta property="product:availability" content="{{ $ogAvailability ?? 'in stock' }}">
    <meta property="product:condition" content="new">
    @endisset
    @isset($articlePublished)
    <meta property="article:published_time" content="{{ $articlePublished }}">
    <meta property="article:modified_time" content="{{ $articleModified ?? $articlePublished }}">
    <meta property="article:publisher" content="{{ seo_url() }}">
    @endisset

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $seoImage }}">
    <meta name="twitter:image:alt" content="{{ $seoImageAlt }}">
    @if(seo_config('social.twitter'))<meta name="twitter:site" content="{{ seo_config('social.twitter') }}">@endif

    {{-- تأیید مالکیت در سرچ‌کنسول‌ها --}}
    @if(!empty($seoVerification['google']))<meta name="google-site-verification" content="{{ $seoVerification['google'] }}">@endif
    @if(!empty($seoVerification['bing']))<meta name="msvalidate.01" content="{{ $seoVerification['bing'] }}">@endif
    @if(!empty($seoVerification['yandex']))<meta name="yandex-verification" content="{{ $seoVerification['yandex'] }}">@endif
    @if(!empty($seoVerification['enamad']))<meta name="enamad" content="{{ $seoVerification['enamad'] }}">@endif

    @if(!empty($ampurl))
        <link rel="amphtml" href="{{ $ampurl }}">
    @endif

    {{-- داده‌های ساختاریافته --}}
    @foreach($schemaItems as $schemaItem)
        <script type="application/ld+json">{!! seo_json_ld($schemaItem) !!}</script>
    @endforeach

    {{-- آیکون‌ها و PWA --}}
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/png" sizes="192x192" href="/assets/images/pwa/icon-192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/assets/images/pwa/icon-512.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/images/pwa/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="{{ seo_config('theme_color', '#0f3d6e') }}">
    <meta name="apple-mobile-web-app-title" content="{{ seo_site_name() }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="application-name" content="{{ seo_site_name() }}">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="msapplication-TileColor" content="{{ seo_config('theme_color', '#0f3d6e') }}">
    <meta name="msapplication-TileImage" content="/assets/images/pwa/icon-144.png">

    @yield('head')

    <link rel="stylesheet" href="/assets/css/bootstrap.rtl.css">
    <link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/owl.carousel.min.css">
    <link rel="stylesheet" href="/assets/css/owl.theme.default.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/home-digikala.css">
    <link rel="stylesheet" href="/assets/css/mobile-appbar.css">
    <link rel="stylesheet" href="/assets/css/mobile-checkout.css">

    @if(!empty($seoAnalytics['gtm']))
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','{{ $seoAnalytics['gtm'] }}');</script>
    @elseif(!empty($seoAnalytics['ga4']))
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $seoAnalytics['ga4'] }}"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','{{ $seoAnalytics['ga4'] }}');</script>
    @endif
</head>
{{-- صفحه‌هایی که نوار عمل چسبان موبایل دارند این کلاس را می‌فرستند تا
     محتوای انتهای صفحه زیر نوار پنهان نشود --}}
<body class="{{ $bodyClass ?? '' }}">

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
                                 width="150" height="50" fetchpriority="high" decoding="async"
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
                            <img src="/assets/images/cart.png" alt="سبد خرید" width="28" height="28" loading="lazy" decoding="async">
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
                                <img src="/assets/images/logo.png" alt="ناظر یدک" width="120" height="40" loading="lazy" decoding="async">
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
                                        <li><a href="/shop/%D9%85%D9%88%D8%AA%D9%88%D8%B1-%D9%88-%D8%A7%D8%AC%D8%B2%D8%A7%DB%8C-%D9%85%D8%AA%D8%B9%D9%84%D9%82%D9%87">موتور و اجزای متعلقه خودرو</a></li>
                                        <li><a href="/shop/%D9%82%D8%B7%D8%B9%D8%A7%D8%AA-%D9%85%D8%B5%D8%B1%D9%81%DB%8C">قطعات مصرفی خودرو</a></li>
                                        <li><a href="/shop/%D8%B4%D8%A7%D8%B3%DB%8C-%D9%88-%D8%A8%D8%AF%D9%86%D9%87">شاسی و بدنه خودرو</a></li>
                                        <li><a href="/shop/%D8%B3%DB%8C%D8%B3%D8%AA%D9%85-%DA%AF%DB%8C%D8%B1%D8%A8%DA%A9%D8%B3-%D9%88-%D8%AF%DB%8C%D9%81%D8%B1%D8%A7%D9%86%D8%B3%DB%8C%D9%84">سیستم گیربکس و دیفرانسیل</a></li>
                                        <li><a href="/shop/%D8%B3%DB%8C%D8%B3%D8%AA%D9%85-%D8%B3%D9%88%D8%AE%D8%AA%E2%80%8C%D8%B1%D8%B3%D8%A7%D9%86%DB%8C">سیستم سوخت‌رسانی و جرقه</a></li>
                                        <li><a href="/shop/%D8%B3%DB%8C%D8%B3%D8%AA%D9%85-%DA%86%D8%B1%D8%AE-%D9%88-%D8%AA%D8%B1%D9%85%D8%B2">سیستم چرخ و ترمز و تعلیق</a></li>
                                        <li><a href="/shop/%D8%B3%DB%8C%D8%B3%D8%AA%D9%85-%D8%A8%D8%B1%D9%82">سیستم برق و روشنایی</a></li>
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
                             width="130" height="44" fetchpriority="high" decoding="async"
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
                        <img src="/assets/images/cart.png" alt="سبد خرید" width="28" height="28" loading="lazy" decoding="async">
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
                        <li><a href="/shop/%D9%85%D9%88%D8%AA%D9%88%D8%B1-%D9%88-%D8%A7%D8%AC%D8%B2%D8%A7%DB%8C-%D9%85%D8%AA%D8%B9%D9%84%D9%82%D9%87"><i class="fas fa-cog sub-menu-icon"></i> موتور و اجزای متعلقه خودرو</a></li>
                        <li><a href="/shop/%D9%82%D8%B7%D8%B9%D8%A7%D8%AA-%D9%85%D8%B5%D8%B1%D9%81%DB%8C"><i class="fas fa-oil-can sub-menu-icon"></i> قطعات مصرفی خودرو</a></li>
                        <li><a href="/shop/%D8%B4%D8%A7%D8%B3%DB%8C-%D9%88-%D8%A8%D8%AF%D9%86%D9%87"><i class="fas fa-car sub-menu-icon"></i> شاسی و بدنه خودرو</a></li>
                        <li><a href="/shop/%D8%B3%DB%8C%D8%B3%D8%AA%D9%85-%DA%AF%DB%8C%D8%B1%D8%A8%DA%A9%D8%B3-%D9%88-%D8%AF%DB%8C%D9%81%D8%B1%D8%A7%D9%86%D8%B3%DB%8C%D9%84"><i class="fas fa-cogs sub-menu-icon"></i> سیستم گیربکس و دیفرانسیل</a></li>
                        <li><a href="/shop/%D8%B3%DB%8C%D8%B3%D8%AA%D9%85-%D8%B3%D9%88%D8%AE%D8%AA%E2%80%8C%D8%B1%D8%B3%D8%A7%D9%86%DB%8C"><i class="fas fa-gas-pump sub-menu-icon"></i> سیستم سوخت‌رسانی و جرقه</a></li>
                        <li><a href="/shop/%D8%B3%DB%8C%D8%B3%D8%AA%D9%85-%DA%86%D8%B1%D8%AE-%D9%88-%D8%AA%D8%B1%D9%85%D8%B2"><i class="fas fa-compact-disc sub-menu-icon"></i> سیستم چرخ و ترمز و تعلیق</a></li>
                        <li><a href="/shop/%D8%B3%DB%8C%D8%B3%D8%AA%D9%85-%D8%A8%D8%B1%D9%82"><i class="fas fa-bolt sub-menu-icon"></i> سیستم برق و روشنایی</a></li>
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
                    @php $footerShipping = getShippingRules(); @endphp
                    <div class="col-6 col-lg-4 mb-3 mb-lg-0">
                        <div class="footer-feature-item">
                            <div class="footer-feature-icon"><i class="fas fa-truck"></i></div>
                            <h6>ارسال رایگان</h6>
                            <span>{{ $footerShipping['local_province_name'] }} +{{ shippingAmountShort($footerShipping['local_free_threshold']) }} | سایر شهرها +{{ shippingAmountShort($footerShipping['national_free_threshold']) }}</span>
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
                                     width="150" height="50" loading="lazy" decoding="async"
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
                            <img src="/assets/images/f-1.png" class="footer-detail-pic" alt="نماد اعتماد الکترونیکی ناظر یدک" width="90" height="90" loading="lazy" decoding="async">
                            <img src="/assets/images/f-2.png" class="footer-detail-pic" alt="نماد ساماندهی وزارت فرهنگ و ارشاد اسلامی" width="90" height="90" loading="lazy" decoding="async">
                        </div>
                        <p class="footer-title mt-4">ما را دنبال کنید</p>
                        <div class="footer-social">
                            <a href="https://wa.me/989127471631" class="footer-social-btn" title="واتساپ" target="_blank"><i class="fab fa-whatsapp"></i></a>
                            <a href="tel:09127471631" class="footer-social-btn" title="تماس تلفنی"><i class="fas fa-phone-alt"></i></a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- لینک‌سازی داخلی: صفحات دسته‌بندی و مدل خودرو از هر صفحه‌ی سایت
                 یک لینک مستقیم می‌گیرند. این کار هم عمق خزش را کم می‌کند و هم
                 متن لنگر (anchor text) مرتبط به آن صفحات می‌رساند. --}}
            <nav class="footer-seo-links" aria-label="دسته‌بندی قطعات و مدل خودروها">
                <div class="footer-seo-group">
                    <p class="footer-title">خرید بر اساس دسته‌بندی</p>
                    <ul class="footer-tag-list">
                        @foreach(\App\Enums\ProductCategory::cases() as $footerCategory)
                            <li><a href="/shop/{{ rawurlencode($footerCategory->slug()) }}">{{ $footerCategory->label() }}</a></li>
                        @endforeach
                    </ul>
                </div>
                <div class="footer-seo-group">
                    <p class="footer-title">خرید بر اساس خودرو</p>
                    <ul class="footer-tag-list">
                        @foreach(['پژو 206', 'پژو 405', 'پژو پارس', 'سمند', 'دنا', 'رانا', 'تیبا', 'پراید', 'کوییک', 'ساینا', 'شاهین', 'تارا'] as $footerCar)
                            <li><a href="/shop?car_model={{ rawurlencode($footerCar) }}">قطعات {{ $footerCar }}</a></li>
                        @endforeach
                    </ul>
                </div>
            </nav>

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

    {{-- ================= نوار پایین چسبان موبایل ================= --}}
    @php
        $isHomeTab   = request()->is('/');
        $isShopTab   = request()->is('shop') || request()->is('shop/*') || request()->is('product*');
        $isCartTab   = request()->is('cart') || request()->is('cart/*');
        $isUserTab   = request()->is('dashboard') || request()->is('profile*') || request()->is('login') || request()->is('favorite');
        $appbarCategories = [
            ['title' => 'موتور و اجزای متعلقه', 'icon' => 'fas fa-cog',          'url' => '/shop/%D9%85%D9%88%D8%AA%D9%88%D8%B1-%D9%88-%D8%A7%D8%AC%D8%B2%D8%A7%DB%8C-%D9%85%D8%AA%D8%B9%D9%84%D9%82%D9%87'],
            ['title' => 'قطعات مصرفی',          'icon' => 'fas fa-oil-can',      'url' => '/shop/%D9%82%D8%B7%D8%B9%D8%A7%D8%AA-%D9%85%D8%B5%D8%B1%D9%81%DB%8C'],
            ['title' => 'شاسی و بدنه',          'icon' => 'fas fa-car',          'url' => '/shop/%D8%B4%D8%A7%D8%B3%DB%8C-%D9%88-%D8%A8%D8%AF%D9%86%D9%87'],
            ['title' => 'گیربکس و دیفرانسیل',   'icon' => 'fas fa-cogs',         'url' => '/shop/%D8%B3%DB%8C%D8%B3%D8%AA%D9%85-%DA%AF%DB%8C%D8%B1%D8%A8%DA%A9%D8%B3-%D9%88-%D8%AF%DB%8C%D9%81%D8%B1%D8%A7%D9%86%D8%B3%DB%8C%D9%84'],
            ['title' => 'سوخت‌رسانی و جرقه',    'icon' => 'fas fa-gas-pump',     'url' => '/shop/%D8%B3%DB%8C%D8%B3%D8%AA%D9%85-%D8%B3%D9%88%D8%AE%D8%AA%E2%80%8C%D8%B1%D8%B3%D8%A7%D9%86%DB%8C'],
            ['title' => 'چرخ و ترمز و تعلیق',   'icon' => 'fas fa-compact-disc', 'url' => '/shop/%D8%B3%DB%8C%D8%B3%D8%AA%D9%85-%DA%86%D8%B1%D8%AE-%D9%88-%D8%AA%D8%B1%D9%85%D8%B2'],
            ['title' => 'برق و روشنایی',        'icon' => 'fas fa-bolt',         'url' => '/shop/%D8%B3%DB%8C%D8%B3%D8%AA%D9%85-%D8%A8%D8%B1%D9%82'],
            ['title' => 'همه محصولات',          'icon' => 'fas fa-store',        'url' => '/shop'],
            ['title' => 'مجله یدکی',            'icon' => 'fas fa-newspaper',    'url' => '/blog'],
        ];
    @endphp
    <nav class="mobile-appbar" id="mobileAppbar" aria-label="منوی اصلی موبایل">
        <a href="/" class="mobile-appbar__item {{ $isHomeTab ? 'is-active' : '' }}" @if($isHomeTab) aria-current="page" @endif>
            <span class="mobile-appbar__icon"><i class="fas fa-home"></i></span>
            <span class="mobile-appbar__label">خانه</span>
        </a>
        <a href="#app-categories-sheet" class="mobile-appbar__item {{ $isShopTab ? 'is-active' : '' }}" data-bs-toggle="offcanvas" role="button">
            <span class="mobile-appbar__icon"><i class="fas fa-th-large"></i></span>
            <span class="mobile-appbar__label">دسته‌بندی</span>
        </a>
        <a href="#app-search-sheet" class="mobile-appbar__item" data-bs-toggle="offcanvas" role="button">
            <span class="mobile-appbar__icon"><i class="fas fa-search"></i></span>
            <span class="mobile-appbar__label">جستجو</span>
        </a>
        <a href="#shopping-cart-responsive" class="mobile-appbar__item {{ $isCartTab ? 'is-active' : '' }}" data-bs-toggle="offcanvas" role="button">
            <span class="mobile-appbar__icon">
                <i class="fas fa-shopping-cart"></i>
                <span class="mobile-appbar__badge cart-count is-empty" id="appbar-cart-count">0</span>
            </span>
            <span class="mobile-appbar__label">سبد خرید</span>
        </a>
        <a href="{{ empty(Auth::guard('customer')->user()) ? '/login' : '/dashboard' }}" class="mobile-appbar__item {{ $isUserTab ? 'is-active' : '' }}">
            <span class="mobile-appbar__icon"><i class="fas fa-user"></i></span>
            <span class="mobile-appbar__label">{{ empty(Auth::guard('customer')->user()) ? 'ورود' : 'حساب من' }}</span>
        </a>
    </nav>

    {{-- شیت دسته‌بندی‌ها --}}
    <div class="offcanvas offcanvas-bottom app-sheet d-lg-none" tabindex="-1" id="app-categories-sheet" aria-labelledby="app-categories-title">
        <div class="app-sheet__grabber"></div>
        <div class="offcanvas-header">
            <p class="offcanvas-title" id="app-categories-title">دسته‌بندی محصولات</p>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="بستن"></button>
        </div>
        <div class="offcanvas-body">
            <div class="app-sheet__grid">
                @foreach($appbarCategories as $appbarCategory)
                <a href="{{ $appbarCategory['url'] }}" class="app-sheet__tile">
                    <i class="{{ $appbarCategory['icon'] }}"></i>
                    <span>{{ $appbarCategory['title'] }}</span>
                </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- شیت جستجو --}}
    <div class="offcanvas offcanvas-bottom app-sheet d-lg-none" tabindex="-1" id="app-search-sheet" aria-labelledby="app-search-title">
        <div class="app-sheet__grabber"></div>
        <div class="offcanvas-header">
            <p class="offcanvas-title" id="app-search-title">جستجو در محصولات</p>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="بستن"></button>
        </div>
        <div class="offcanvas-body">
            <form method="get" action="/shop">
                <div class="app-sheet__search">
                    <i class="fa fa-search" style="color:#81858b;font-size:14px;"></i>
                    <input type="search" name="title" value="{{ request('title') }}" placeholder="نام قطعه، خودرو یا کد فنی..." data-appbar-search>
                    <button type="submit">جستجو</button>
                </div>
            </form>
            <p class="app-sheet__hint">جستجوهای پرتکرار</p>
            <div class="app-sheet__chips">
                <a href="/shop?title=لنت ترمز">لنت ترمز</a>
                <a href="/shop?title=فیلتر روغن">فیلتر روغن</a>
                <a href="/shop?title=تسمه تایم">تسمه تایم</a>
                <a href="/shop?title=شمع">شمع</a>
                <a href="/shop?title=دیسک و صفحه">دیسک و صفحه</a>
                <a href="/shop?title=کمک فنر">کمک فنر</a>
            </div>
        </div>
    </div>

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
    // اگر دکمه به یک انتخابگر تعداد وصل باشد (صفحه‌ی محصول)، همان تعداد ارسال
    // می‌شود؛ در کارت‌های فروشگاه مثل قبل یک عدد اضافه می‌شود
    let qtySource = $(this).data('qty-from');
    let quantity = qtySource ? (parseInt($('#' + qtySource).val(), 10) || 1) : 1;
    let btn = $(this);
    btn.prop('disabled', true);
    $.ajax({
        url: "/cart/add",
        type: "POST",
        data: {
            product_id: productId,
            quantity: quantity,
            _token: "{{ csrf_token() }}"
        },
        success: function (response) {
            if (response.status === "success") {
                toast.fire({
                    title: 'انجام شد!',
                    text: quantity > 1
                        ? quantity + ' عدد به سبد خرید اضافه شد'
                        : 'محصول با موفقیت به سبد خرید اضافه شد',
                    icon: 'success',
                    confirmButtonText: 'باشه',
                    confirmButtonColor: '#d33',
                    timer: 2000
                });
            }
        },
        complete: function () {
            btn.prop('disabled', false);
        },
        error: function (xhr) {
            // سرور دلیل دقیق را می‌فرستد (مثلاً «موجودی کافی نیست») — همان را نشان بده
            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                ? xhr.responseJSON.message
                : 'مشکلی در افزودن محصول پیش آمد';
            toast.fire({ icon: 'error', title: msg });
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
                            <img src="${item.image}" class="img-fluid img-thumbnail" alt="${item.title || ''}" width="70" height="70" loading="lazy">
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
            if (!html) {
                // حالت خالی؛ قبلا کشوی سبد روی موبایل کاملا سفید باز می‌شد
                html = `
                    <div class="text-center py-5">
                        <i class="fas fa-shopping-cart" style="font-size:2.2rem; color:#d7d7dd;"></i>
                        <p class="font-13 mt-3 mb-3">سبد خرید شما خالی است</p>
                        <a href="/shop" class="btn btn-info font-13 px-4">رفتن به فروشگاه</a>
                    </div>`;
            }
            $(".cart-items").html(html);
            // شمارنده‌ی خالی نباید روی نوار پایین موبایل نشان داده شود
            $(".mobile-appbar__badge").toggleClass('is-empty', !res.count);
        }
    });
}
$('#shopping-cart, #shopping-cart-responsive').on('show.bs.offcanvas', function () {
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
$('#shopping-cart-responsive').on('show.bs.offcanvas', function () {
    loadCart();
});
loadCart();

/* ---------- رفتار نوار پایین موبایل ---------- */
(function () {
    var bar = document.getElementById('mobileAppbar');
    if (!bar) return;

    /* پنهان‌شدن هنگام اسکرول به پایین، نمایش هنگام اسکرول به بالا */
    var lastY = window.pageYOffset || 0;
    var ticking = false;

    function onScroll() {
        var y = window.pageYOffset || 0;
        if (Math.abs(y - lastY) > 8) {
            var nearBottom = (y + window.innerHeight) >= (document.body.scrollHeight - 60);
            bar.classList.toggle('is-hidden', y > lastY && y > 220 && !nearBottom);
            lastY = y;
        }
        ticking = false;
    }

    window.addEventListener('scroll', function () {
        if (!ticking) {
            ticking = true;
            window.requestAnimationFrame(onScroll);
        }
    }, { passive: true });

    /* هنگام باز شدن هر شیت، نوار دوباره دیده شود */
    document.addEventListener('show.bs.offcanvas', function () {
        bar.classList.remove('is-hidden');
    });

    /* نشان‌دادن/پنهان‌کردن شمارنده سبد خرید بر اساس مقدار آن */
    var badge = document.getElementById('appbar-cart-count');
    if (badge) {
        var syncBadge = function () {
            var n = parseInt((badge.textContent || '').replace(/[^\d]/g, ''), 10);
            badge.classList.toggle('is-empty', !n);
        };
        syncBadge();
        new MutationObserver(syncBadge).observe(badge, { childList: true, characterData: true, subtree: true });
    }

    /* فوکوس روی فیلد جستجو پس از باز شدن شیت */
    var searchSheet = document.getElementById('app-search-sheet');
    if (searchSheet) {
        searchSheet.addEventListener('shown.bs.offcanvas', function () {
            var input = searchSheet.querySelector('[data-appbar-search]');
            if (input) input.focus();
        });
    }
})();
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

{{-- ============ PWA ============ --}}

{{-- بنر نصب اپلیکیشن؛ فقط وقتی مرورگر امکان نصب را اعلام کند نمایش داده می‌شود --}}
<div id="pwa-install-banner" class="pwa-install-banner" hidden>
    <img src="/assets/images/pwa/icon-192.png" alt="{{ seo_site_name() }}">
    <div class="pwa-install-text">
        <strong>نصب اپلیکیشن {{ seo_site_name() }}</strong>
        <span>دسترسی سریع‌تر، بدون نیاز به مرورگر</span>
    </div>
    <button type="button" id="pwa-install-btn" class="pwa-install-btn">نصب</button>
    <button type="button" id="pwa-install-close" class="pwa-install-close" aria-label="بستن">&times;</button>
</div>

<style>
    .pwa-install-banner {
        position: fixed;
        z-index: 1080;
        right: 12px;
        left: 12px;
        bottom: calc(12px + env(safe-area-inset-bottom));
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 14px;
        border-radius: 14px;
        background: #fff;
        box-shadow: 0 6px 28px rgba(35, 37, 78, .18);
        animation: pwa-slide-up .3s ease-out;
    }

    /* روی موبایل نوار پایین سایت وجود دارد؛ بنر بالاتر از آن می‌نشیند */
    @media (max-width: 991px) {
        .pwa-install-banner { bottom: calc(72px + env(safe-area-inset-bottom)); }
    }

    @media (min-width: 992px) {
        .pwa-install-banner { right: auto; left: 20px; max-width: 380px; }
    }

    @keyframes pwa-slide-up {
        from { opacity: 0; transform: translateY(16px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .pwa-install-banner img { width: 44px; height: 44px; border-radius: 10px; flex-shrink: 0; }
    .pwa-install-text { flex: 1; min-width: 0; line-height: 1.7; }
    .pwa-install-text strong { display: block; font-size: 13px; color: #23254e; }
    .pwa-install-text span { display: block; font-size: 11px; color: #62666d; }

    .pwa-install-btn {
        flex-shrink: 0;
        border: 0;
        cursor: pointer;
        padding: 8px 18px;
        border-radius: 8px;
        font-size: 13px;
        font-family: inherit;
        background: #ef394e;
        color: #fff;
    }

    .pwa-install-btn:hover { background: #c7352f; }

    .pwa-install-close {
        flex-shrink: 0;
        border: 0;
        background: none;
        cursor: pointer;
        padding: 0 4px;
        font-size: 22px;
        line-height: 1;
        color: #a1a3a8;
    }

    .pwa-install-close:hover { color: #62666d; }
</style>

<script>
(function () {
    'use strict';

    if (!('serviceWorker' in navigator)) return;

    window.addEventListener('load', function () {
        navigator.serviceWorker.register('/sw.js', { scope: '/' }).then(function (registration) {
            // نسخه‌ی جدید سرویس‌ورکر به محض آماده شدن جایگزین شود
            registration.addEventListener('updatefound', function () {
                var worker = registration.installing;
                if (!worker) return;

                worker.addEventListener('statechange', function () {
                    if (worker.state === 'installed' && navigator.serviceWorker.controller) {
                        worker.postMessage({ type: 'SKIP_WAITING' });
                    }
                });
            });
        }).catch(function (error) {
            console.warn('Service worker registration failed:', error);
        });
    });

    // با فعال شدن سرویس‌ورکر جدید، صفحه یک بار تازه می‌شود تا فایل‌های قدیمی نمانند
    var refreshing = false;
    navigator.serviceWorker.addEventListener('controllerchange', function () {
        if (refreshing) return;
        refreshing = true;
        window.location.reload();
    });

    /* ---- بنر نصب ---- */

    var DISMISS_KEY = 'pwa-install-dismissed-at';
    var DISMISS_DAYS = 14;

    function recentlyDismissed() {
        try {
            var at = parseInt(localStorage.getItem(DISMISS_KEY), 10);
            if (!at) return false;
            return (Date.now() - at) < DISMISS_DAYS * 24 * 60 * 60 * 1000;
        } catch (e) {
            return false;
        }
    }

    var deferredPrompt = null;

    window.addEventListener('beforeinstallprompt', function (event) {
        event.preventDefault();
        deferredPrompt = event;

        if (recentlyDismissed()) return;

        var banner = document.getElementById('pwa-install-banner');
        if (!banner) return;

        banner.hidden = false;

        document.getElementById('pwa-install-btn').addEventListener('click', function () {
            banner.hidden = true;
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(function () {
                deferredPrompt = null;
            });
        });

        document.getElementById('pwa-install-close').addEventListener('click', function () {
            banner.hidden = true;
            try {
                localStorage.setItem(DISMISS_KEY, String(Date.now()));
            } catch (e) {}
        });
    });

    window.addEventListener('appinstalled', function () {
        deferredPrompt = null;
        var banner = document.getElementById('pwa-install-banner');
        if (banner) banner.hidden = true;
    });
})();
</script>
</body>
</html>
