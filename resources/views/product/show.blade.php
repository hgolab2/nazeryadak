@php
    $productUrl = seo_url($model->url());
    $productImage = str_starts_with($model->image(), 'http') ? $model->image() : seo_url($model->image());
    $productDescription = seo_description(($model->description ?: ($model->title . ' مناسب خرید از فروشگاه لوازم یدکی ناظر یدک با تمرکز بر قطعات اصلی ایساکو، ضمانت اصالت کالا و ارسال سراسر کشور.')) . ($model->sku ? ' کد فنی: ' . $model->sku : '') . ($model->car_model ? ' مناسب خودرو: ' . $model->car_model : ''));
    $productKeywords = implode(', ', array_filter([$model->title, $model->sku ? 'خرید ' . $model->sku : null, $model->car_model ? 'لوازم یدکی ' . $model->car_model : null, 'خرید لوازم یدکی اصلی', 'قطعات ایساکو', 'قطعات اصلی ایساکو', 'ناظر یدک']));
    $productSchema = ['@context' => 'https://schema.org', '@type' => 'Product', '@id' => $productUrl . '#product', 'name' => $model->title, 'description' => $productDescription, 'image' => [$productImage], 'sku' => $model->sku ?: (string) $model->id, 'brand' => ['@type' => 'Brand', 'name' => 'ISACO'], 'offers' => ['@type' => 'Offer', 'url' => $productUrl, 'priceCurrency' => 'IRR', 'price' => (string) (($model->price ?? 0) * 10), 'availability' => 'https://schema.org/InStock', 'itemCondition' => 'https://schema.org/NewCondition', 'seller' => ['@id' => seo_url('#store')]]];
@endphp
@extends('layout.layout', [
    'title' => 'خرید ' . $model->title . ' | قیمت و مشخصات در ناظر یدک',
    'metaDescription' => $productDescription,
    'keywords' => $productKeywords,
    'canonical' => $productUrl,
    'ogImage' => $productImage,
    'ogType' => 'product',
    'schema' => [seo_store_schema(), $productSchema, ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array_values(array_filter([['@type' => 'Question', 'name' => 'آیا ' . $model->title . ' اصل است؟', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'این محصول در ناظر یدک با تاکید بر ضمانت اصالت کالا عرضه می‌شود و بهتر است پیش از خرید، نام قطعه، کد فنی و خودرو مناسب بررسی شود.']], $model->sku ? ['@type' => 'Question', 'name' => 'کد فنی این قطعه چیست؟', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'کد فنی ثبت‌شده برای این محصول ' . $model->sku . ' است.']] : null, $model->car_model ? ['@type' => 'Question', 'name' => 'این قطعه برای چه خودرویی مناسب است؟', 'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'بر اساس اطلاعات محصول، این قطعه برای ' . $model->car_model . ' مناسب است.']] : null]))], seo_breadcrumb_schema([['name' => 'ناظر یدک', 'url' => seo_url()], ['name' => 'فروشگاه لوازم یدکی', 'url' => seo_url('/shop')], ['name' => $model->title, 'url' => $productUrl]])],
])
@section('main_content')

    <div class="container">
        <div class="row mt-3">
            <div class="col-12">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/" class="breadcrumb-custom">ناظر یدک</a></li>
                    <li class="breadcrumb-item"><a href="/shop" class="breadcrumb-custom">فروشگاه قطعات</a></li>
                    <li class="breadcrumb-item"><span class="breadcrumb-custom">{{$model->title}}</span></li>
                </ul>
            </div>
        </div>
    </div>

    <main>
        <div class="container">

            <div class="product-content">
                <div class="row">
                    {{-- تصویر محصول --}}
                    <div class="col-lg-4 col-12">
                        <div class="row">
                            <div class="col-1 text-center product-icons">
                                <span class="itemFavorite_{{ $model->id }}">
                                    <i class="far fa-heart heart favorite-icon d-block my-4"
                                       onclick="addFavorite({{ $model->id }})"
                                       data-bs-toggle="tooltip" title="افزودن به علاقمندی‌ها"></i>
                                </span>
                                <span data-bs-toggle="modal" data-bs-target="#share-modal">
                                    <i class="fa fa-share-alt share d-block my-4" data-bs-toggle="tooltip" title="اشتراک‌گذاری"></i>
                                </span>
                                <div class="modal fade" id="share-modal">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <p class="modal-title font-13">اشتراک‌گذاری</p>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="font-12">این محصول را با دوستان خود به اشتراک بگذارید!</p>
                                                <button type="button" class="btn btn-share" onclick="navigator.clipboard.writeText(window.location.href);this.innerHTML='<i class=\'fa fa-check me-2\'></i>کپی شد!'"><i class="fa fa-copy me-2"></i>کپی لینک</button>
                                                <div class="d-flex justify-content-center mt-4">
                                                    <a href="https://t.me/share/url?url={{ urlencode(url('/product/'.$model->id)) }}&text={{ urlencode($model->title) }}" target="_blank"><i class="fab fa-telegram social-media" style="color: var(--primary);"></i></a>
                                                    <a href="https://wa.me/?text={{ urlencode($model->title . ' ' . url('/product/'.$model->id)) }}" target="_blank"><i class="fab fa-whatsapp text-success social-media"></i></a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-11 pb-4 mb-3">
                                <div class="text-center p-3">
                                    <img src="{{$model->image()}}" class="img-fluid lazy-img"
                                         style="max-height: 350px; object-fit: contain;"
                                         @if(!$model->hasImage()) data-fetch="/product/fetch-image/{{$model->id}}" @endif
                                         alt="{{$model->title}}">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- مشخصات محصول --}}
                    <div class="col-lg-5 col-md-8 product-details">
                        <h1 style="font-size: 1.1rem; font-weight: 700; line-height: 2;">{{$model->title}}</h1>

                        @if($model->categories?->count())
                        <div class="mb-3">
                            <span class="font-13 text-muted">دسته‌بندی:</span>
                            @foreach($model->categories as $pic)
                                @php
                                    $catEnum = \App\Enums\ProductCategory::tryFrom($pic->category_id);
                                @endphp
                                @if($catEnum)
                                <span class="badge" style="background: var(--primary-lighter); color: var(--primary);">{{ $catEnum->label() }}</span>
                                @endif
                            @endforeach
                        </div>
                        @endif

                        <div class="mt-3">
                            <p class="fw-bold font-14" style="color: var(--primary);">
                                <i class="fas fa-list-ul me-1"></i>
                                مشخصات قطعه
                            </p>
                            <ul class="font-13 ps-1" style="line-height: 2.5;">
                                @if($model->weight)
                                <li><i class="fas fa-weight-hanging me-2" style="color: var(--primary); width: 20px; text-align: center;"></i> وزن: {{$model->weight}} گرم</li>
                                @endif
                                @if($model->width && $model->height)
                                <li><i class="fas fa-ruler-combined me-2" style="color: var(--primary); width: 20px; text-align: center;"></i> ابعاد: {{$model->width}} × {{$model->height}} سانتی‌متر</li>
                                @endif
                                @if($model->sku)
                                <li><i class="fas fa-barcode me-2" style="color: var(--primary); width: 20px; text-align: center;"></i> کد فنی: <bdi class="sku-code">{{$model->sku}}</bdi></li>
                                @endif
                                @if($model->car_model)
                                <li><i class="fas fa-car me-2" style="color: var(--primary); width: 20px; text-align: center;"></i> خودرو مناسب: {{$model->car_model}}</li>
                                @endif
                            </ul>
                        </div>

                        {{-- نشان‌های اطمینان --}}
                        <div class="d-flex flex-wrap gap-3 mt-3 pt-3 border-top">
                            <span class="font-12 text-muted"><i class="fas fa-check-circle me-1" style="color: var(--success);"></i> اورجینال</span>
                            <span class="font-12 text-muted"><i class="fas fa-shield-alt me-1" style="color: var(--primary);"></i> گارانتی اصالت</span>

                        </div>
                    </div>

                    {{-- باکس خرید --}}
                    <div class="col-lg-3 col-md-4">
                        <div class="add-cart-box">
                            <div class="product-seller-row">
                                <span><i class="fas fa-store me-1"></i> فروشنده</span>
                                <span class="fw-bold">ناظر یدک</span>
                            </div>
                            <div class="product-seller-row">
                                <span><i class="fas fa-box me-1"></i> وضعیت</span>
                                <span style="color: var(--success);">
                                    <i class="fas fa-check-circle me-1"></i> موجود
                                </span>
                            </div>
                            <div class="product-seller-row">
                                <span><i class="fas fa-shipping-fast me-1"></i> ارسال</span>
                                <span>سراسر کشور</span>
                            </div>
                            <div class="text-center py-3">
                                @if($model->discountPercent() > 0)
                                <del class="text-muted font-13 d-block mb-1">{{toPersianNumbers($model->regular_price)}} تومان</del>
                                <span class="badge mb-2" style="background: var(--accent); color: #fff;">{{$model->discountPercent()}}% تخفیف</span>
                                @endif
                                <p class="mb-0" style="font-size: 1.3rem; font-weight: 700; color: var(--primary);">
                                    {{toPersianNumbers($model->price)}} <small class="font-13">تومان</small>
                                </p>
                            </div>
                            <button type="button" class="btn add-cart-btn" data-id="{{ $model->id }}">
                                <i class="fa fa-cart-plus me-1"></i>
                                افزودن به سبد خرید
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- آیکون‌های خدمات --}}
            <div class="d-none d-lg-block product-delivery-icons">
                <div class="row">
                    <div class="col-6 col-sm text-center"><i class="fa fa-shipping-fast"></i><p>ارسال سریع</p></div>
                    <div class="col-6 col-sm text-center"><i class="fa fa-headset"></i><p>پشتیبانی تخصصی</p></div>
                    <div class="col-6 col-sm text-center"><i class="fa fa-credit-card"></i><p>پرداخت امن</p></div>
                    <div class="col-6 col-sm text-center"><i class="fa fa-truck"></i><p>ارسال رایگان قم +۵M</p></div>
                    <div class="col-12 col-sm text-center"><i class="fa fa-medal"></i><p>ضمانت اصالت کالا</p></div>
                </div>
            </div>

            {{-- توضیحات محصول --}}
            <div class="product-tab-content">
                <div class="row pb-3">
                    <div class="col-12">
                        <ul class="nav nav-pills custom-nav-pills">
                            <li class="nav-item"><a href="#description" data-bs-toggle="tab" class="nav-link active">توضیحات محصول</a></li>
                            <li class="nav-item"><a href="#detail" data-bs-toggle="tab" class="nav-link">مشخصات فنی</a></li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="description">
                                <div class="m-3 font-13" style="line-height: 2.2;">
                                    @if($model->description)
                                        {!! nl2br(e($model->description)) !!}
                                    @else
                                        <p class="text-muted text-center py-4">توضیحاتی برای این محصول ثبت نشده است.</p>
                                    @endif
                                </div>
                            </div>

                            <div class="tab-pane fade" id="detail">
                                <div class="mx-3 mt-3">
                                    @if($model->weight)
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><div class="box-line">وزن</div></div>
                                        <div class="col-sm-8"><div class="box-line">{{$model->weight}} گرم</div></div>
                                    </div>
                                    @endif
                                    @if($model->width && $model->height)
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><div class="box-line">ابعاد</div></div>
                                        <div class="col-sm-8"><div class="box-line">{{$model->width}} × {{$model->height}} سانتی‌متر</div></div>
                                    </div>
                                    @endif
                                    @if($model->sku)
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><div class="box-line">کد فنی</div></div>
                                        <div class="col-sm-8"><div class="box-line"><bdi class="sku-code">{{$model->sku}}</bdi></div></div>
                                    </div>
                                    @endif
                                    @if($model->car_model)
                                    <div class="row mb-2">
                                        <div class="col-sm-4"><div class="box-line">خودرو مناسب</div></div>
                                        <div class="col-sm-8"><div class="box-line">{{$model->car_model}}</div></div>
                                    </div>
                                    @endif
                                    @if(!$model->weight && !$model->sku && !$model->car_model)
                                    <p class="text-muted text-center py-4">مشخصات فنی تکمیل نشده است.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <section class="product-seo-faq product-tab-content" aria-labelledby="product-faq-title">
                <div class="row pb-3">
                    <div class="col-12">
                        <h2 class="section-title" id="product-faq-title">راهنمای خرید {{$model->title}}</h2>
                        <div class="mx-3 mt-3 font-13" style="line-height:2.2;">
                            <h3 class="font-14 fw-bold">آیا {{$model->title}} اصل است؟</h3>
                            <p class="text-muted">این محصول از فروشگاه ناظر یدک با تاکید بر ضمانت اصالت کالا عرضه می‌شود. هنگام خرید قطعات خودرو، تطبیق نام قطعه، کد فنی و خودرو مناسب اهمیت زیادی دارد.</p>
                            @if($model->sku)
                            <h3 class="font-14 fw-bold">کد فنی این قطعه چیست؟</h3>
                            <p class="text-muted">کد فنی ثبت‌شده برای این محصول {{$model->sku}} است. جستجو با کد فنی دقیق‌ترین روش برای پیدا کردن لوازم یدکی اصلی خودرو محسوب می‌شود.</p>
                            @endif
                            @if($model->car_model)
                            <h3 class="font-14 fw-bold">این قطعه برای چه خودرویی مناسب است؟</h3>
                            <p class="text-muted">بر اساس اطلاعات محصول، {{$model->title}} برای {{$model->car_model}} مناسب است. قبل از نهایی کردن سفارش، مشخصات فنی و مدل خودرو را بررسی کنید.</p>
                            @endif
                            <h3 class="font-14 fw-bold">ارسال این قطعه چگونه انجام می‌شود؟</h3>
                            <p class="text-muted mb-0">ناظر یدک امکان ارسال قطعات خودرو به قم و سایر شهرهای کشور را فراهم کرده است و سفارش پس از ثبت، بر اساس روش ارسال انتخاب‌شده پردازش می‌شود.</p>
                        </div>
                    </div>
                </div>
            </section>
            {{-- محصولات مرتبط --}}
            <div class="product-slider mb-4">
                <div class="row">
                    <div class="col-12">
                        <div class="section-header">
                            <h2 class="section-title">قطعات مرتبط</h2>
                            <a href="/shop" class="section-link">مشاهده همه <i class="fa fa-chevron-left"></i></a>
                        </div>
                        <div class="owl-carousel owl-theme custom-product-slider">
                            @foreach($products as $product)
                            @include('product.product_card' , ['product' => $product])
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

@endsection
@section('js')
<script src="/assets/js/owl.carousel.min.js"></script>
<script src="/assets/js/jquery.simple.timer.js"></script>
<script src="/assets/js/script.js"></script>
@endsection
