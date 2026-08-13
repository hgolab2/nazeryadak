@php
    /* اسکیمای صفحه‌ی اصلی: فهرست دسته‌بندی‌ها + پرسش‌های پرتکرار خانه */
    $homeCategories = collect(\App\Enums\ProductCategory::cases())->map(fn($c) => [
        'name' => $c->label(),
        'url'  => seo_url('/shop/' . rawurlencode($c->slug())),
    ])->all();

    $homeFaqs = [
        ['q' => 'آیا قطعات ناظر یدک اصل و دارای ضمانت اصالت هستند؟', 'a' => 'بله. تمام قطعات از تأمین‌کنندگان رسمی مانند ایساکو و سایپا یدک تهیه می‌شوند و روی بسته‌بندی، هولوگرام اصالت کالا وجود دارد.'],
        ['q' => 'چطور قطعه‌ی مناسب خودرویم را پیدا کنم؟', 'a' => 'می‌توانید بر اساس نام قطعه، مدل خودرو یا کد فنی (OEM) جستجو کنید؛ همچنین دسته‌بندی‌های فروشگاه، قطعات هر بخش از خودرو را جدا کرده‌اند.'],
        ['q' => 'ارسال سفارش به چه صورت است؟', 'a' => 'سفارش‌های ثبت‌شده تا ساعت ۱۴ همان روز ارسال می‌شوند و بسته به شهر مقصد، بین ۱ تا ۵ روز کاری به دست شما می‌رسد.'],
    ];
@endphp
@extends('layout.layout', [
    'title' => 'ناظر یدک | خرید لوازم یدکی خودرو و قطعات اصلی ایساکو',
    'metaDescription' => 'خرید آنلاین لوازم یدکی خودرو و قطعات اصلی ایساکو با ضمانت اصالت، قیمت روز و ارسال سراسر کشور؛ قطعات پژو، سمند، دنا، پراید و تیبا.',
    'keywords' => seo_default_keywords(),
    'canonical' => seo_url(),
    'ogImage' => seo_config('default_image'),
    'ogImageAlt' => 'فروشگاه اینترنتی لوازم یدکی ناظر یدک',
    'schema' => [
        seo_webpage_schema(
            'ناظر یدک | خرید لوازم یدکی خودرو و قطعات اصلی ایساکو',
            'خرید آنلاین لوازم یدکی خودرو و قطعات اصلی ایساکو با ضمانت اصالت کالا و ارسال سراسر کشور.',
            seo_url(),
            'CollectionPage'
        ),
        seo_itemlist_schema('دسته‌بندی قطعات یدکی خودرو', $homeCategories, seo_url()),
        seo_faq_schema($homeFaqs),
    ],
])

@section('main_content')
<main class="nx-home">
    <div class="nx-wrap">

        {{-- اسلایدر اصلی --}}
        <section class="nx-hero" aria-label="اسلایدر تبلیغات">
            <div class="owl-carousel owl-theme nx-hero-slider">
                @php
                    $heroAds = isset($advertisements)
                        ? $advertisements->filter(fn($ad) => $ad->media)
                        : collect();
                @endphp

                @if($heroAds->count())
                    @foreach($heroAds as $ad)
                        <a href="{{ $ad->link ?: '/shop' }}" class="nx-slide">
                            <img src="{{ $ad->media->getPath() }}" alt="{{ $ad->title ?: 'پیشنهاد ویژه ناظر یدک' }}" width="1200" height="400" fetchpriority="high" decoding="async">
                        </a>
                    @endforeach
                @else
                    <a href="/shop" class="nx-slide nx-slide-promo">
                        <span class="nx-slide-copy">
                            <small><i class="fas fa-shield-alt"></i> ضمانت اصالت کالا</small>
                            <strong>قطعات اصلی خودرو، مستقیم از ناظر یدک</strong>
                            <p>جستجو با کد فنی، نام قطعه یا مدل خودرو در میان هزاران قطعه موجود</p>
                            <span class="nx-slide-cta">ورود به فروشگاه <i class="fas fa-chevron-left"></i></span>
                        </span>
                        <i class="fas fa-car-side nx-slide-art"></i>
                    </a>
                    <a href="/shop" class="nx-slide nx-slide-promo nx-slide-promo-2">
                        <span class="nx-slide-copy">
                            <small><i class="fas fa-truck"></i> ارسال به سراسر کشور</small>
                            <strong>قطعات مصرفی و سرویس دوره‌ای</strong>
                            <p>لنت، فیلتر، تسمه تایم و روغن‌ موتور با قیمت و موجودی به‌روز</p>
                            <span class="nx-slide-cta">مشاهده قطعات <i class="fas fa-chevron-left"></i></span>
                        </span>
                        <i class="fas fa-oil-can nx-slide-art"></i>
                    </a>
                    <a href="/contact-us" class="nx-slide nx-slide-promo nx-slide-promo-3">
                        <span class="nx-slide-copy">
                            <small><i class="fas fa-headset"></i> مشاوره تخصصی</small>
                            <strong>قطعه مناسب خودرویتان را پیدا نمی‌کنید؟</strong>
                            <p>با کد فنی یا مدل خودرو تماس بگیرید تا کارشناس ما راهنمایی‌تان کند</p>
                            <span class="nx-slide-cta">تماس با کارشناس <i class="fas fa-chevron-left"></i></span>
                        </span>
                        <i class="fas fa-headset nx-slide-art"></i>
                    </a>
                @endif
            </div>
        </section>

        {{-- دسترسی سریع --}}
        <nav class="nx-circles" aria-label="دسترسی سریع به دسته‌ها">
            <a href="/shop?title={{ urlencode('لنت ترمز') }}" class="nx-circle nx-t1">
                <i class="fas fa-compact-disc"></i> لنت ترمز
            </a>
            <a href="/shop?title={{ urlencode('فیلتر روغن') }}" class="nx-circle nx-t2">
                <i class="fas fa-oil-can"></i> فیلتر روغن
            </a>
            <a href="/shop?title={{ urlencode('تسمه تایم') }}" class="nx-circle nx-t3">
                <i class="fas fa-cogs"></i> تسمه تایم
            </a>
            <a href="/shop?title={{ urlencode('سنسور') }}" class="nx-circle nx-t4">
                <i class="fas fa-bolt"></i> سنسورها
            </a>
            <a href="/shop?title={{ urlencode('سپر') }}" class="nx-circle nx-t5">
                <i class="fas fa-car-side"></i> قطعات بدنه
            </a>
            <a href="/shop?title={{ urlencode('چراغ') }}" class="nx-circle nx-t6">
                <i class="fas fa-lightbulb"></i> چراغ و روشنایی
            </a>
            <a href="/shop?title={{ urlencode('باتری') }}" class="nx-circle nx-t7">
                <i class="fas fa-car-battery"></i> باتری و برق
            </a>
            <a href="/shop?title={{ urlencode('کمک فنر') }}" class="nx-circle nx-t8">
                <i class="fas fa-life-ring"></i> جلوبندی و تعلیق
            </a>
        </nav>

        {{-- پیشنهاد ویژه --}}
        @if(isset($specialProducts) && $specialProducts->count())
            <section class="nx-amazing" aria-label="پیشنهاد ویژه">
                <div class="nx-amazing-side">
                    <i class="fas fa-bolt"></i>
                    <strong>{{ !empty($specialHasDiscount) ? 'شگفت‌انگیز' : 'پیشنهاد ویژه' }}</strong>
                    <span>{{ !empty($specialHasDiscount) ? 'تخفیف‌های امروز ناظر یدک' : 'منتخب قطعات اصلی و پرفروش' }}</span>
                    <a href="/shop">مشاهده همه <i class="fas fa-chevron-left"></i></a>
                </div>
                <div class="nx-rail owl-carousel owl-theme nx-slider">
                    @foreach($specialProducts as $product)
                        @include('product.product_card', ['product' => $product])
                    @endforeach
                </div>
            </section>
        @endif

        {{-- بنرهای میانی --}}
        <section class="nx-banners">
            <a href="/shop" class="nx-banner nx-banner-isaco">
                <img src="/assets/images/isaco-logo.png" alt="قطعات اصلی ایساکو - ناظر یدک" width="120" height="120" loading="lazy" decoding="async">
                <span>
                    <strong>قطعات اصلی ایساکو</strong>
                    <p>اصالت کالا، موجودی لحظه‌ای و خرید سریع</p>
                    <span class="nx-banner-cta">ورود به فروشگاه <i class="fas fa-chevron-left"></i></span>
                </span>
            </a>
            <a href="/contact-us" class="nx-banner nx-banner-support">
                <i class="fas fa-headset"></i>
                <span>
                    <strong>قطعه مناسب را پیدا نمی‌کنید؟</strong>
                    <p>با کد فنی یا مدل خودرو راهنمایی می‌شوید</p>
                    <span class="nx-banner-cta">تماس با کارشناس <i class="fas fa-chevron-left"></i></span>
                </span>
            </a>
        </section>

        {{-- عنوان اصلی صفحه؛ هر صفحه‌ی ایندکس‌شونده باید دقیقا یک H1 داشته باشد
             که موضوع صفحه را برای گوگل و صفحه‌خوان‌ها مشخص کند. --}}
        <h1 class="nx-page-title">خرید لوازم یدکی خودرو و قطعات اصلی ایساکو</h1>

        {{-- جدیدترین قطعات --}}
        <section class="nx-card">
            <div class="nx-card-head">
                <h2><i class="fas fa-star"></i> جدیدترین قطعات خودرو</h2>
                <a href="/shop">مشاهده همه <i class="fas fa-chevron-left"></i></a>
            </div>
            <div class="nx-rail owl-carousel owl-theme nx-slider">
                @foreach($products as $product)
                    @include('product.product_card', ['product' => $product])
                @endforeach
            </div>
        </section>

        {{-- خرید بر اساس خودرو --}}
        @if(isset($carCategories) && $carCategories->count())
            <section class="nx-card">
                <div class="nx-card-head">
                    <h2><i class="fas fa-car"></i> خرید بر اساس خودرو</h2>
                    <a href="/shop">همه خودروها <i class="fas fa-chevron-left"></i></a>
                </div>
                <div class="nx-cars">
                    @foreach($carCategories as $cat)
                        <a href="/shop?car_model={{ urlencode($cat->name) }}" class="nx-car">
                            @if($cat->image)
                                <img src="{{ $cat->image }}" alt="قطعات {{ $cat->name }}" loading="lazy" decoding="async" width="120" height="120">
                            @else
                                <span class="nx-car-fallback"><i class="fas fa-car"></i></span>
                            @endif
                            <strong>{{ $cat->name }}</strong>
                            <span>{{ number_format($cat->products_count) }} قطعه</span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- مجله --}}
        @if(isset($articles) && $articles->count())
            <section class="nx-card">
                <div class="nx-card-head">
                    <h2><i class="fas fa-newspaper"></i> مجله ناظر یدک</h2>
                    <a href="/blog">مشاهده همه <i class="fas fa-chevron-left"></i></a>
                </div>
                <div class="nx-posts">
                    @foreach($articles as $article)
                        <a href="{{ $article->getUrl() }}" class="nx-post">
                            <img src="{{ article_cover($article) }}" alt="{{ $article->titr }}" class="nx-post-img" loading="lazy" width="600" height="340">
                            <span class="nx-post-body">
                                <h3>{{ $article->titr }}</h3>
                                @if($article->sutitr)
                                    <p>{{ Str::limit($article->sutitr, 72) }}</p>
                                @endif
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

    </div>
</main>
@endsection

@section('js')
<script>
$(function () {
    var navText = [
        '<i class="fas fa-chevron-right"></i>',
        '<i class="fas fa-chevron-left"></i>'
    ];

    var $hero = $('.nx-hero-slider');
    if ($hero.length && !$hero.hasClass('owl-loaded')) {
        $hero.owlCarousel({
            rtl: true,
            items: 1,
            loop: $hero.children().length > 1,
            dots: true,
            nav: true,
            navText: navText,
            autoplay: true,
            autoplayTimeout: 5200,
            autoplayHoverPause: true
        });
    }

    $('.nx-slider').each(function () {
        var $rail = $(this);
        if ($rail.hasClass('owl-loaded')) {
            return;
        }
        $rail.owlCarousel({
            rtl: true,
            nav: true,
            dots: false,
            margin: 0,
            loop: false,
            navText: navText,
            responsive: {
                0: { items: 2 },
                768: { items: 3 },
                992: { items: 4 },
                1200: { items: 5 },
                1400: { items: 6 }
            }
        });
    });
});
</script>
@endsection
