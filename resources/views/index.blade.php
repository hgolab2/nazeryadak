@extends('layout.layout', [
    'title' => "nazeryadak | فروش تخصصی لوازم یدکی ایساکو و قطعات اصلی خودرو"
])
@section('main_content')

    <main>
        <div class="container">

            {{-- محصولات پیشنهادی --}}
            <section class="product-slider mt-3">
                <div class="row">
                    <div class="col-12">
                        <div class="section-header">
                            <h2 class="section-title">پیشنهاد ویژه</h2>
                            <a href="/shop" class="section-link">
                                مشاهده همه محصولات
                                <i class="fa fa-chevron-left"></i>
                            </a>
                        </div>
                        <div class="owl-carousel owl-theme custom-product-slider">
                            @foreach($products as $product)
                                @include('product.product_card' , ['product' => $product])
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            {{-- اسلایدر اصلی --}}
            <section class="main-slider mt-3">
                <div id="main-slider" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        @php $count = 1; @endphp
                        @foreach($advertisements as $adv)
                            @if(isset($adv->media))
                            <div class="carousel-item @if($count == 1)active @endif">
                                <a href="{{$adv->link}}" target="_blank" class="d-block w-100">
                                    <img src="{{$adv->media->getPath()}}" class="d-block w-100" alt="{{$adv->title}}">
                                </a>
                            </div>
                            @php $count++; @endphp
                            @endif
                        @endforeach
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#main-slider" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#main-slider" data-bs-slide="next">
                        <span class="carousel-control-next-icon"></span>
                    </button>
                </div>
            </section>

            {{-- نشان‌های اعتماد --}}
            <section class="trust-badges mt-4">
                <div class="row g-0">
                    <div class="col-6 col-md trust-badge-item">
                        <div class="trust-badge-icon">
                            <img src="/assets/images/1.svg" alt="ارسال سریع">
                        </div>
                        <h6>ارسال رایگان</h6>
                        <p>قم بالای ۵M | شهرستان بالای ۲۰M</p>
                    </div>
                    <div class="col-6 col-md trust-badge-item">
                        <div class="trust-badge-icon">
                            <img src="/assets/images/2.svg" alt="پشتیبانی">
                        </div>
                        <h6>پشتیبانی ۲۴ ساعته</h6>
                        <p>مشاوره تخصصی لوازم یدکی</p>
                    </div>
                    <div class="col-6 col-md trust-badge-item">
                        <div class="trust-badge-icon">
                            <img src="/assets/images/3.svg" alt="پرداخت">
                        </div>
                        <h6>پرداخت در محل</h6>
                        <p>امکان پرداخت هنگام تحویل</p>
                    </div>
                    <div class="col-12 col-md trust-badge-item">
                        <div class="trust-badge-icon">
                            <img src="/assets/images/5.svg" alt="اصالت">
                        </div>
                        <h6>ضمانت اصالت کالا</h6>
                        <p>قطعات اورجینال و تضمینی</p>
                    </div>
                </div>
            </section>

            {{-- خرید بر اساس خودرو --}}
            @if(isset($carCategories) && $carCategories->count() > 0)
            <section class="car-select-section mt-4">
                <div class="section-header">
                    <h2 class="section-title"><i class="fas fa-car me-2" style="color:var(--accent);"></i> خرید بر اساس خودرو</h2>
                    <a href="/shop" class="section-link">
                        مشاهده همه
                        <i class="fa fa-chevron-left"></i>
                    </a>
                </div>
                <div class="row justify-content-center g-3">
                    @foreach($carCategories as $cat)
                    <div class="col-6 col-md-4 col-lg-3">
                        <a href="/shop?car_model={{ urlencode($cat->name) }}" class="car-card-link">
                            <div class="car-card">
                                @if($cat->image)
                                    <img src="{{ $cat->image }}" alt="{{ $cat->name }}" class="car-card-img">
                                @else
                                    <div class="car-card-icon"><i class="fas fa-car"></i></div>
                                @endif
                                <div class="car-card-info">
                                    <h6 class="car-card-name">{{ $cat->name }}</h6>
                                    <span class="car-card-count">{{ number_format($cat->products_count) }} قطعه</span>
                                </div>
                            </div>
                        </a>
                    </div>
                    @endforeach
                </div>
            </section>
            <style>
            .car-card-link { text-decoration: none; display: block; height: 100%; }
            .car-card {
                background: #fff;
                border-radius: 12px;
                box-shadow: 0 2px 8px rgba(0,0,0,.06);
                padding: 16px;
                text-align: center;
                transition: all .25s ease;
                height: 100%;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }
            .car-card:hover {
                box-shadow: 0 6px 20px rgba(0,0,0,.12);
                transform: translateY(-4px);
            }
            .car-card-img {
                width: 100%;
                max-width: 180px;
                height: 90px;
                object-fit: contain;
                margin-bottom: 10px;
            }
            .car-card-icon {
                width: 80px; height: 80px;
                display: flex; align-items: center; justify-content: center;
                font-size: 2rem; color: var(--primary);
                background: var(--primary-lighter, #e8f4fd);
                border-radius: 50%;
                margin-bottom: 10px;
            }
            .car-card-info { text-align: center; }
            .car-card-name {
                font-size: 0.95rem;
                font-weight: 700;
                color: #333;
                margin-bottom: 4px;
            }
            .car-card-count {
                font-size: 0.78rem;
                color: #999;
            }
            </style>
            @endif

            {{-- بنرهای تبلیغاتی --}}
            <div class="row mt-4 g-3">
                <div class="col-md-6">
                    <a href="/shop?categories=2" class="d-block w-100">
                        <img src="/assets/images/banner3.webp" class="ads-img" alt="لوازم مصرفی خودرو">
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="/shop?categories=7" class="d-block w-100">
                        <img src="/assets/images/banner4.jpg" class="ads-img" alt="سیستم برق و صوتی خودرو">
                    </a>
                </div>
            </div>

            {{-- بنر CTA --}}
            <section class="cta-banner mt-4">
                <h3>به دنبال قطعه خاصی هستید؟</h3>
                <p>کارشناسان ما آماده مشاوره و راهنمایی در انتخاب قطعه مناسب خودروی شما هستند</p>
                <a href="/contact-us" class="btn-accent">
                    <i class="fas fa-phone-alt me-2"></i>
                    تماس با کارشناسان
                </a>
            </section>

            {{-- آخرین مطالب بلاگ --}}
            <section class="product-slider mt-4">
                <div class="row">
                    <div class="col-12">
                        <div class="section-header">
                            <h2 class="section-title">آخرین مطالب</h2>
                            <a href="/blog" class="section-link">
                                مشاهده همه مطالب
                                <i class="fa fa-chevron-left"></i>
                            </a>
                        </div>
                        <div class="row mt-2 g-3">
                            @foreach ($articles as $article)
                                <div class="col-xl-3 col-lg-3 col-sm-6">
                                    <a href="{{$article->getUrl()}}" class="d-block h-100 text-decoration-none">
                                        <div class="blog-card-new" style="border-radius:12px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.06); height:100%; background:#fff;">
                                            @if($article->image && $article->images)
                                                <img title="{{$article->titr}}" src="{{$article->images->getPath()}}" style="width:100%; height:180px; object-fit:cover;">
                                            @else
                                                @php
                                                    $blogIcons = ['fa-oil-can','fa-cogs','fa-fan','fa-car-battery'];
                                                    $blogColors = ['#e65100','#1565c0','#2e7d32','#7b1fa2'];
                                                    $idx = $loop->index % 4;
                                                @endphp
                                                <div class="d-flex align-items-center justify-content-center" style="height:180px; background:{{ $blogColors[$idx] }}10;">
                                                    <i class="fas {{ $blogIcons[$idx] }}" style="font-size:3.5rem; color:{{ $blogColors[$idx] }}40;"></i>
                                                </div>
                                            @endif
                                            <div class="p-3">
                                                <h6 class="mb-1" style="font-size:0.85rem; font-weight:700; color:#333; line-height:1.8;">{{$article->titr}}</h6>
                                                @if($article->sutitr)
                                                <p class="mb-0 text-muted" style="font-size:0.75rem; line-height:1.7;">{{ Str::limit($article->sutitr, 60) }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            {{-- بنرهای تبلیغاتی پایین --}}
            <div class="row mt-4 mb-4 g-3">
                <div class="col-md-6">
                    <a href="/shop" class="d-block w-100">
                        <img src="/assets/images/banner2.webp" class="ads-img" alt="لوازم یدکی خودرو">
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="/shop" class="d-block w-100">
                        <img src="/assets/images/banner1.webp" class="ads-img" alt="لوازم جانبی خودرو">
                    </a>
                </div>
            </div>

        </div>
    </main>
@endsection
