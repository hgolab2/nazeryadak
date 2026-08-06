@extends('layout.layout', [
    'title' => 'ناظر یدک | خرید لوازم یدکی اصلی خودرو و قطعات ایساکو',
    'metaDescription' => 'ناظر یدک فروشگاه تخصصی خرید لوازم یدکی خودرو، قطعات اصلی ایساکو، قطعات مصرفی، موتوری، برقی، بدنه و جلوبندی با ضمانت اصالت کالا و ارسال سراسر کشور است.',
    'keywords' => seo_default_keywords(),
    'canonical' => seo_url(),
    'ogImage' => seo_url('/assets/images/logo.png'),
    'schema' => [seo_store_schema(), ['@context' => 'https://schema.org', '@type' => 'WebSite', '@id' => seo_url('#website'), 'url' => seo_url(), 'name' => seo_site_name(), 'inLanguage' => 'fa-IR', 'potentialAction' => ['@type' => 'SearchAction', 'target' => seo_url('/shop?title={search_term_string}'), 'query-input' => 'required name=search_term_string']]],
])
@section('main_content')
<main class="home-page">
    <section class="home-hero">
        <div class="container">
            <div class="home-hero-grid">
                <div class="home-hero-content">
                    <span class="home-kicker"><i class="fas fa-shield-alt"></i> تمرکز ویژه بر قطعات اصلی ایساکو</span>
                    <h1>خرید سریع لوازم یدکی خودرو با کد فنی یا نام قطعه</h1>
                    <p>بخش بزرگی از محصولات ناظر یدک از قطعات ایساکو و قطعات اصلی خودرو است؛ قطعات مصرفی، موتوری، برقی، بدنه، ترمز و جلوبندی را برای پژو، سمند، دنا، پراید، تیبا و تارا سریع پیدا کنید.</p>
                    <form method="get" action="/shop" class="home-hero-search">
                        <input type="search" name="title" placeholder="مثلا لنت ترمز، فیلتر روغن، 206 یا کد فنی..." aria-label="جستجوی لوازم یدکی">
                        <button type="submit"><i class="fa fa-search"></i><span>جستجو</span></button>
                    </form>
                    <div class="home-hero-actions">
                        <a href="/shop" class="home-primary-action"><i class="fas fa-store"></i> فروشگاه قطعات</a>
                        <a href="tel:09127471631" class="home-secondary-action"><i class="fas fa-headset"></i> مشاوره خرید</a>
                    </div>
                </div>
                <div class="home-hero-media home-isaco-card" aria-label="قطعات ایساکو">
                    <img src="/assets/images/isaco-logo.png" class="home-isaco-logo" alt="لوگوی ایساکو - قطعات اصلی خودرو">
                    <div class="isaco-card-copy">
                        <strong>تمرکز بر قطعات اصلی ایساکو</strong>
                        <span>جستجو با نام قطعه، خودرو یا کد فنی</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container">
        <section class="home-mobile-shortcuts" aria-label="دسترسی سریع">
            <a href="/shop?title=لنت ترمز"><i class="fas fa-compact-disc"></i><span>لنت ترمز</span></a>
            <a href="/shop?title=فیلتر روغن"><i class="fas fa-oil-can"></i><span>فیلتر روغن</span></a>
            <a href="/shop?title=تسمه تایم"><i class="fas fa-cogs"></i><span>تسمه تایم</span></a>
            <a href="/shop?title=سنسور"><i class="fas fa-bolt"></i><span>سنسورها</span></a>
        </section>

        <section class="home-trust-strip" aria-label="مزیت‌های ناظر یدک">
            <div><i class="fas fa-medal"></i><strong>قطعات ایساکو</strong><span>تمرکز بر کالای اصلی و قابل پیگیری</span></div>
            <div><i class="fas fa-truck"></i><strong>ارسال سریع</strong><span>قم و سراسر کشور</span></div>
            <div><i class="fas fa-barcode"></i><strong>جستجوی کد فنی</strong><span>پیدا کردن قطعه دقیق</span></div>
            <div><i class="fas fa-headset"></i><strong>مشاوره تخصصی</strong><span>انتخاب قطعه مناسب خودرو</span></div>
        </section>

        <section class="product-slider home-section">
            <div class="section-header">
                <h2 class="section-title">پیشنهاد ویژه قطعات خودرو و ایساکو</h2>
                <a href="/shop" class="section-link">مشاهده همه محصولات <i class="fa fa-chevron-left"></i></a>
            </div>
            <div class="owl-carousel owl-theme custom-product-slider">
                @foreach($products as $product)
                    @include('product.product_card', ['product' => $product])
                @endforeach
            </div>
        </section>

        @if(isset($carCategories) && $carCategories->count() > 0)
        <section class="home-section car-select-section">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-car me-2" style="color:var(--accent);"></i> خرید بر اساس خودرو</h2>
                <a href="/shop" class="section-link">مشاهده همه <i class="fa fa-chevron-left"></i></a>
            </div>
            <div class="home-car-grid">
                @foreach($carCategories as $cat)
                <a href="/shop?car_model={{ urlencode($cat->name) }}" class="home-car-card">
                    @if($cat->image)
                        <img src="{{ $cat->image }}" alt="لوازم یدکی {{ $cat->name }}">
                    @else
                        <span class="home-car-icon"><i class="fas fa-car"></i></span>
                    @endif
                    <strong>{{ $cat->name }}</strong>
                    <span>{{ number_format($cat->products_count) }} قطعه</span>
                </a>
                @endforeach
            </div>
        </section>
        @endif

        <section class="home-section home-category-band">
            <div class="section-header">
                <h2 class="section-title">دسته‌بندی‌های پرجستجو</h2>
                <a href="/shop" class="section-link">ورود به فروشگاه <i class="fa fa-chevron-left"></i></a>
            </div>
            <div class="home-category-grid">
                <a href="/shop?category=قطعات-مصرفی"><i class="fas fa-oil-can"></i><strong>قطعات مصرفی</strong><span>روغن، فیلتر، لنت و تسمه</span></a>
                <a href="/shop?category=موتور-و-اجزای-متعلقه"><i class="fas fa-cogs"></i><strong>قطعات موتوری</strong><span>قطعات اصلی موتور خودرو</span></a>
                <a href="/shop?category=سیستم-چرخ-و-ترمز"><i class="fas fa-compact-disc"></i><strong>ترمز و جلوبندی</strong><span>ایمنی و کنترل خودرو</span></a>
                <a href="/shop?category=سیستم-برق"><i class="fas fa-bolt"></i><strong>برق و سنسورها</strong><span>چراغ، سنسور و برق خودرو</span></a>
            </div>
        </section>

        <section class="home-section home-help-panel">
            <div>
                <h2>قطعه مناسب خودروی خود را پیدا نمی‌کنید؟</h2>
                <p>نام خودرو، نام قطعه یا کد فنی را بفرستید تا برای انتخاب لوازم یدکی اصلی راهنمایی شوید.</p>
            </div>
            <a href="/contact-us"><i class="fas fa-phone-alt"></i> تماس با کارشناسان</a>
        </section>

        <section class="home-section home-seo-hub" aria-labelledby="home-seo-title">
            <div class="section-header">
                <h2 class="section-title" id="home-seo-title">فروشگاه تخصصی لوازم یدکی و قطعات ایساکو</h2>
                <a href="/shop" class="section-link">خرید قطعات <i class="fa fa-chevron-left"></i></a>
            </div>
            <div class="home-seo-copy">
                <p>در ناظر یدک می‌توانید برای خودروهای پرتقاضا مثل پژو ۲۰۶، پژو ۴۰۵، سمند، دنا، رانا، تارا، تیبا و پراید قطعات اصلی و مصرفی را بر اساس نام قطعه، کد فنی، دسته‌بندی و خودرو مناسب پیدا کنید. تمرکز فروشگاه روی قطعات اصلی ایساکو، قیمت شفاف، ضمانت اصالت و ارسال سراسر کشور است.</p>
                <div class="home-keyword-links">
                    <a href="/shop?title=لنت ترمز">خرید لنت ترمز</a>
                    <a href="/shop?title=فیلتر روغن">خرید فیلتر روغن</a>
                    <a href="/shop?title=تسمه تایم">خرید تسمه تایم</a>
                    <a href="/shop?car_model=پژو 206">لوازم یدکی پژو ۲۰۶</a>
                    <a href="/shop?car_model=سمند">لوازم یدکی سمند</a>
                    <a href="/shop?car_model=دنا">لوازم یدکی دنا</a>
                    <a href="/shop?car_model=پراید">لوازم یدکی پراید</a>
                    <a href="/shop?title=ایساکو">قطعات اصلی ایساکو</a>
                </div>
            </div>
        </section>

        <section class="product-slider home-section">
            <div class="section-header">
                <h2 class="section-title">آخرین مطالب راهنمای خرید</h2>
                <a href="/blog" class="section-link">مشاهده همه مطالب <i class="fa fa-chevron-left"></i></a>
            </div>
            <div class="row mt-2 g-3">
                @foreach ($articles as $article)
                    <div class="col-xl-3 col-lg-3 col-sm-6">
                        <a href="{{$article->getUrl()}}" class="d-block h-100 text-decoration-none">
                            <div class="blog-card-new">
                                @if($article->image && $article->images)
                                    <img title="{{$article->titr}}" src="{{$article->images->getPath()}}" alt="{{$article->titr}}">
                                @else
                                    @php
                                        $blogIcons = ['fa-oil-can','fa-cogs','fa-fan','fa-car-battery'];
                                        $blogColors = ['#e65100','#1565c0','#2e7d32','#7b1fa2'];
                                        $idx = $loop->index % 4;
                                    @endphp
                                    <div class="blog-card-fallback" style="background:{{ $blogColors[$idx] }}12; color:{{ $blogColors[$idx] }}55;"><i class="fas {{ $blogIcons[$idx] }}"></i></div>
                                @endif
                                <div class="p-3">
                                    <h3>{{$article->titr}}</h3>
                                    @if($article->sutitr)
                                        <p>{{ Str::limit($article->sutitr, 72) }}</p>
                                    @endif
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</main>
@endsection