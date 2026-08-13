@php
    $fa = fn($s) => urldecode($s);
    $productUrl = seo_url($model->url());
    $productImage = str_starts_with($model->image(), 'http') ? $model->image() : seo_url($model->image());
    /* توضیحات متا: ابتدا متن خودِ محصول، و اگر خالی یا کوتاه بود یک جمله‌ی
       کامل از روی نام، کد فنی و مدل خودرو ساخته می‌شود. توضیحات زیر ۷۰
       کاراکتر معمولا توسط گوگل کنار گذاشته و با متن دلخواه جایگزین می‌شود. */
    $productDescription = seo_description($model->description ?: ($model->short_description ?: ''));
    if (mb_strlen($productDescription) < 80) {
        $productDescription = seo_description(
            $fa('%D8%AE%D8%B1%DB%8C%D8%AF%20') . $model->title
            . ($model->sku ? ' ' . $fa('%D8%A8%D8%A7%20%DA%A9%D8%AF%20%D9%81%D9%86%DB%8C%20') . $model->sku : '')
            . ($model->car_model ? $fa('%20%D9%85%D9%86%D8%A7%D8%B3%D8%A8%20') . $model->car_model : '')
            . $fa('%20%D8%A7%D8%B5%D9%84%20%D9%88%20%D8%AF%D8%A7%D8%B1%D8%A7%DB%8C%20%D8%B6%D9%85%D8%A7%D9%86%D8%AA%20%D8%A7%D8%B5%D8%A7%D9%84%D8%AA%20%DA%A9%D8%A7%D9%84%D8%A7%D8%8C%20%D9%82%DB%8C%D9%85%D8%AA%20%D8%B1%D9%88%D8%B2%20%D9%88%20%D8%A7%D8%B1%D8%B3%D8%A7%D9%84%20%D8%B3%D8%B1%DB%8C%D8%B9%20%D8%A8%D9%87%20%D8%B3%D8%B1%D8%A7%D8%B3%D8%B1%20%D8%A7%DB%8C%D8%B1%D8%A7%D9%86%20%D8%A7%D8%B2%20%D9%81%D8%B1%D9%88%D8%B4%DA%AF%D8%A7%D9%87%20%D9%86%D8%A7%D8%B8%D8%B1%20%DB%8C%D8%AF%DA%A9.')
        );
    }
    $galleryImages = $model->images->count() ? $model->images : collect([(object) ['path' => $model->image(), 'alt' => $model->title]]);

    /* --- سئوی صفحه محصول --- */
    $productInStock = !isset($model->stock) || $model->stock === null || (int) $model->stock > 0;

    // موجودی قابل خرید؛ محصول بدون stock ثبت‌شده عملا قابل افزودن به سبد نیست
    $stock = (int) $model->stock;

    /* قطعات دسته‌ی «شاسی و بدنه» قیمت ثابت ندارند: به‌جای مبلغ، دعوت به تماس
       نشان داده می‌شود و در سبد و فاکتور هم به‌صورت «استعلام تلفنی» می‌آید. */
    $contactPrice = $model->isContactPrice();

    // عنوان: «خرید {نام قطعه} + کد فنی» تا کوئری‌های کد فنی هم پوشش داده شود.
    $productTitle = seo_title(
        $fa('%D8%AE%D8%B1%DB%8C%D8%AF%20') . $model->title
        . ($model->isaco_code ? ' ' . $fa('%DA%A9%D8%AF%20') . $model->isaco_code : '')
    );

    $productBreadcrumb = [
        ['name' => seo_site_name(), 'url' => seo_url()],
        ['name' => $fa('%D9%81%D8%B1%D9%88%D8%B4%DA%AF%D8%A7%D9%87%20%D9%84%D9%88%D8%A7%D8%B2%D9%85%20%DB%8C%D8%AF%DA%A9%DB%8C'), 'url' => seo_url('/shop')],
    ];
    if (!empty($model->car_model)) {
        $productBreadcrumb[] = ['name' => $model->car_model, 'url' => seo_url('/shop?car_model=' . rawurlencode($model->car_model))];
    }
    $productBreadcrumb[] = ['name' => $model->title, 'url' => null];

    $productSchema = seo_product_schema($model, $galleryImages->all(), $productBreadcrumb);
    $productBreadcrumbSchema = seo_breadcrumb_schema($productBreadcrumb);
    $productBreadcrumbSchema['@id'] = $productUrl . '#breadcrumb';
@endphp
@extends('layout.layout', [
    'title' => $productTitle,
    'metaDescription' => $productDescription,
    'keywords' => trim(implode(', ', array_filter([
        $model->title,
        $model->sku,
        $model->isaco_code,
        $model->car_model ? $fa('%D9%82%D8%B7%D8%B9%D8%A7%D8%AA%20') . $model->car_model : null,
        seo_default_keywords(),
    ])), ', '),
    'canonical' => $productUrl,
    'bodyClass' => 'has-actionbar',
    'ogImage' => $productImage,
    'ogImageAlt' => $model->title,
    'ogType' => 'product',
    'ogPrice' => $contactPrice ? null : seo_price((int) $model->price),
    'ogAvailability' => $productInStock ? 'in stock' : 'out of stock',
    'schema' => [
        $productSchema,
        $productBreadcrumbSchema,
        seo_webpage_schema($productTitle, $productDescription, $productUrl, 'ItemPage'),
    ],
])
@section('main_content')
<main class="nx-home nx-detail">
    <div class="nx-wrap">
        <nav class="nx-breadcrumb" aria-label="{{ $fa('%D9%85%D8%B3%DB%8C%D8%B1%20%D8%B5%D9%81%D8%AD%D9%87') }}">
            <a href="/">{{ $fa('%D9%86%D8%A7%D8%B8%D8%B1%20%DB%8C%D8%AF%DA%A9') }}</a>
            <i class="fas fa-chevron-left"></i>
            <a href="/shop">{{ $fa('%D9%81%D8%B1%D9%88%D8%B4%DA%AF%D8%A7%D9%87') }}</a>
            <i class="fas fa-chevron-left"></i>
            <b>{{ $model->title }}</b>
        </nav>

        <section class="nx-card dk-product-detail">
            <div class="dk-product-gallery">
                <div class="dk-product-actions">
                    <button type="button" onclick="addFavorite({{ $model->id }})" class="itemFavorite_{{ $model->id }}"><i class="far fa-heart"></i></button>
                    <button type="button" data-bs-toggle="modal" data-bs-target="#share-modal"><i class="fa fa-share-alt"></i></button>
                </div>
                @php $heroImage = $galleryImages->first()->path; $heroWebp = webp_variant($heroImage); @endphp
                <div class="dk-product-main-image">
                    <picture>
                        @if($heroWebp)<source srcset="{{ $heroWebp }}" type="image/webp">@endif
                        <img src="{{ $heroImage }}" id="product-main-image" @if(!$model->hasImage()) data-fetch="/product/fetch-image/{{$model->id}}" @endif alt="{{ $productTitle }}" title="{{ $model->title }}" width="600" height="600" fetchpriority="high" decoding="async">
                    </picture>
                </div>
                @if($galleryImages->count() > 1)
                <div class="dk-product-thumbs">
                    @foreach($galleryImages as $image)
                        @php $thumbWebp = webp_variant($image->path); @endphp
                        <button type="button" class="product-thumb" data-src="{{ $image->path }}" @if($thumbWebp) data-webp="{{ $thumbWebp }}" @endif>
                            <picture>
                                @if($thumbWebp)<source srcset="{{ $thumbWebp }}" type="image/webp">@endif
                                <img src="{{ $image->path }}" alt="{{ $image->alt ?? $model->title }}" width="80" height="80" loading="lazy" decoding="async">
                            </picture>
                        </button>
                    @endforeach
                </div>
                @endif
            </div>

            <div class="dk-product-info">
                <h1>{{ $model->title }}</h1>
                @if($model->short_description)<p class="dk-short-desc">{{ $model->short_description }}</p>@endif
                <h2>{{ $fa('%D9%88%DB%8C%DA%98%DA%AF%DB%8C%E2%80%8C%D9%87%D8%A7') }}</h2>
                <ul>
                    @if($model->sku)<li><span>{{ $fa('%DA%A9%D8%AF%20%D9%81%D9%86%DB%8C') }}</span><bdi class="sku-code">{{ $model->sku }}</bdi></li>@endif
                    @if($model->car_model)<li><span>{{ $fa('%D8%AE%D9%88%D8%AF%D8%B1%D9%88%DB%8C%20%D9%85%D9%86%D8%A7%D8%B3%D8%A8') }}</span>{{ $model->car_model }}</li>@endif
                    @if($model->weight)<li><span>{{ $fa('%D9%88%D8%B2%D9%86') }}</span>{{ $model->weight }} {{ $fa('%DA%AF%D8%B1%D9%85') }}</li>@endif
                    @if($model->width && $model->height)<li><span>{{ $fa('%D8%A7%D8%A8%D8%B9%D8%A7%D8%AF') }}</span>{{ $model->width }} x {{ $model->height }}</li>@endif
                </ul>
                <div class="dk-assurance"><span><i class="fas fa-shield-alt"></i>{{ $fa('%D8%B6%D9%85%D8%A7%D9%86%D8%AA%20%D8%A7%D8%B5%D8%A7%D9%84%D8%AA') }}</span><span><i class="fas fa-truck"></i>{{ $fa('%D8%A7%D8%B1%D8%B3%D8%A7%D9%84%20%D8%B3%D8%B1%D8%A7%D8%B3%D8%B1%20%DA%A9%D8%B4%D9%88%D8%B1') }}</span></div>
            </div>

            <aside class="add-cart-box dk-buy-box">
                <div class="product-seller-row"><span><i class="fas fa-store"></i>{{ $fa('%D9%81%D8%B1%D9%88%D8%B4%D9%86%D8%AF%D9%87') }}</span><b>{{ $fa('%D9%86%D8%A7%D8%B8%D8%B1%20%DB%8C%D8%AF%DA%A9') }}</b></div>
                {{-- وضعیت واقعی از روی موجودی خوانده می‌شود؛ قبلا همیشه «موجود» بود و
                     کاربر پس از کلیک، خطای بی‌ربط «محصول یافت نشد» می‌گرفت --}}
                <div class="product-seller-row">
                    <span><i class="fas fa-check-circle"></i>{{ $fa('%D9%88%D8%B6%D8%B9%DB%8C%D8%AA') }}</span>
                    @if($stock > 0)
                        <b style="color:var(--success);">{{ $fa('%D9%85%D9%88%D8%AC%D9%88%D8%AF') }}</b>
                    @else
                        <b style="color:#d33;">ناموجود</b>
                    @endif
                </div>
                @if($stock > 0 && $stock <= 5)
                <p class="font-12 mb-2" style="color:var(--accent);">
                    <i class="fas fa-exclamation-circle me-1"></i>
                    تنها {{ toPersianNumbers($stock, false) }} عدد در انبار باقی مانده است
                </p>
                @endif
                @if($contactPrice)
                    {{-- قطعات بدنه و شاسی: قیمت روی سایت اعلام نمی‌شود --}}
                    <div class="dk-detail-price is-contact-price" style="font-size:1.05rem; color:var(--accent, #ef394e);">
                        <i class="fas fa-phone-alt me-1"></i> {{ contactPriceLabel() }}
                    </div>
                    <p class="font-12 text-muted mb-2" style="line-height:1.9;">
                        قیمت قطعات بدنه و شاسی بسته به رنگ، کیفیت و موجودی روز تعیین می‌شود؛
                        برای اعلام قیمت با کارشناسان ما تماس بگیرید.
                    </p>
                    <a href="tel:{{ shopContactPhone() }}" class="btn w-100 text-center d-block mb-2"
                       style="background:var(--accent, #ef394e); color:#fff; border:none; border-radius:var(--radius-sm); padding:12px;">
                        <i class="fas fa-headset me-1"></i> تماس با کارشناس ({{ shopContactPhoneDisplay() }})
                    </a>
                @else
                    @if($model->compareAtPrice())<div class="dk-detail-old"><del>{{ toPersianNumbers($model->compareAtPrice()) }} {{ $fa('%D8%AA%D9%88%D9%85%D8%A7%D9%86') }}</del><span class="dk-detail-discount">{{ toPersianNumbers(round($model->discountPercent()), false) }}%</span></div>@endif
                    <div class="dk-detail-price">{{ toPersianNumbers($model->price) }} <small>{{ $fa('%D8%AA%D9%88%D9%85%D8%A7%D9%86') }}</small></div>
                @endif
                @if($stock > 0)
                    {{-- انتخاب تعداد در همین صفحه؛ قبلا برای خرید چند عدد باید در سبد
                         چند بار روی + کلیک می‌شد --}}
                    <div class="dk-buy-qty d-flex align-items-center justify-content-between mb-2">
                        <span class="font-13 text-muted">تعداد</span>
                        <div class="d-flex align-items-center gap-1">
                            <button type="button" class="js-qty-plus" aria-label="افزایش تعداد" style="background:var(--primary); color:#fff; border:none; border-radius:6px; width:36px; height:36px; font-size:1rem; cursor:pointer;">+</button>
                            <input type="text" id="buy-qty" value="1" readonly data-max="{{ $stock }}" aria-label="تعداد"
                                   class="form-control text-center" style="width:52px; height:36px; border-radius:6px; direction:ltr;">
                            <button type="button" class="js-qty-minus" aria-label="کاهش تعداد" style="background:var(--border-color); color:var(--text-dark); border:none; border-radius:6px; width:36px; height:36px; font-size:1rem; cursor:pointer;">−</button>
                        </div>
                    </div>
                    <button type="button" class="btn add-cart-btn" data-id="{{ $model->id }}" data-qty-from="buy-qty">
                        <i class="fa fa-cart-plus me-1"></i>
                        @if($contactPrice)
                            افزودن به سبد (استعلام قیمت)
                        @else
                            {{ $fa('%D8%A7%D9%81%D8%B2%D9%88%D8%AF%D9%86%20%D8%A8%D9%87%20%D8%B3%D8%A8%D8%AF%20%D8%AE%D8%B1%DB%8C%D8%AF') }}
                        @endif
                    </button>
                @else
                    <button type="button" class="btn w-100" disabled style="background:#ccc; color:#fff; border:none; border-radius:var(--radius-sm); padding:12px;">
                        <i class="fas fa-ban me-1"></i> ناموجود
                    </button>
                    <p class="font-12 text-muted mt-2 mb-0" style="line-height:1.9;">
                        <i class="fas fa-phone-alt me-1"></i>
                        برای اطلاع از زمان موجود شدن این قطعه با پشتیبانی تماس بگیرید.
                    </p>
                @endif
            </aside>
        </section>

        <section class="nx-card dk-detail-tabs">
            <div class="nx-card-head"><h2><i class="fas fa-align-right"></i> {{ $fa('%D8%AA%D9%88%D8%B6%DB%8C%D8%AD%D8%A7%D8%AA%20%D9%85%D8%AD%D8%B5%D9%88%D9%84') }}</h2></div>
            <div class="dk-detail-tabs-body">@if($model->description)<div class="product-description-html">{!! $model->description !!}</div>@else<p>{{ $fa('%D8%AA%D9%88%D8%B6%DB%8C%D8%AD%D8%A7%D8%AA%DB%8C%20%D8%A8%D8%B1%D8%A7%DB%8C%20%D8%A7%DB%8C%D9%86%20%D9%85%D8%AD%D8%B5%D9%88%D9%84%20%D8%AB%D8%A8%D8%AA%20%D9%86%D8%B4%D8%AF%D9%87%20%D8%A7%D8%B3%D8%AA.') }}</p>@endif</div>
        </section>

        @if(isset($products) && $products->count())
        <section class="nx-card">
            <div class="nx-card-head">
                <h2><i class="fas fa-layer-group"></i> {{ $fa('%D9%82%D8%B7%D8%B9%D8%A7%D8%AA%20%D9%85%D8%B1%D8%AA%D8%A8%D8%B7') }}</h2>
                <a href="/shop">{{ $fa('%D9%85%D8%B4%D8%A7%D9%87%D8%AF%D9%87%20%D9%87%D9%85%D9%87') }} <i class="fas fa-chevron-left"></i></a>
            </div>
            <div class="nx-rail owl-carousel owl-theme nx-slider">
                @foreach($products as $product) @include('product.product_card', ['product' => $product]) @endforeach
            </div>
        </section>
        @endif
    </div>
</main>

{{-- نوار خرید چسبان موبایل: قیمت و دکمه‌ی خرید همیشه در دسترس شست کاربر است
     و دیگر لازم نیست برای افزودن به سبد تا پایین صفحه اسکرول شود --}}
<div class="mobile-actionbar" role="region" aria-label="خرید محصول">
    @if($stock > 0)
        <div class="mobile-actionbar__qty">
            <button type="button" class="js-qty-plus" aria-label="افزایش تعداد">+</button>
            <span class="js-qty-view" aria-live="polite">۱</span>
            <button type="button" class="js-qty-minus" aria-label="کاهش تعداد">−</button>
        </div>
        <div class="mobile-actionbar__info">
            <span class="mobile-actionbar__label">قیمت</span>
            @if($contactPrice)
                <span class="mobile-actionbar__price" style="font-size:.85rem; color:var(--accent, #ef394e);">{{ contactPriceLabel() }}</span>
            @else
                <span class="mobile-actionbar__price">{{ toPersianNumbers($model->price) }} <small>تومان</small></span>
            @endif
        </div>
        <button type="button" class="mobile-actionbar__btn add-cart-btn" data-id="{{ $model->id }}" data-qty-from="buy-qty">
            <i class="fa fa-cart-plus"></i> افزودن به سبد
        </button>
    @else
        <div class="mobile-actionbar__info">
            <span class="mobile-actionbar__label">وضعیت</span>
            <span class="mobile-actionbar__price" style="color:#d33;">ناموجود</span>
        </div>
        <a href="tel:09127471631" class="mobile-actionbar__btn" style="background:var(--accent, #ef394e);">
            <i class="fas fa-headset"></i> تماس با پشتیبانی
        </a>
    @endif
</div>

<div class="modal fade" id="share-modal"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><p class="modal-title font-13">{{ $fa('%D8%A7%D8%B4%D8%AA%D8%B1%D8%A7%DA%A9%E2%80%8C%DA%AF%D8%B0%D8%A7%D8%B1%DB%8C') }}</p><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><button type="button" class="btn btn-share" onclick="navigator.clipboard.writeText(window.location.href)">{{ $fa('%DA%A9%D9%BE%DB%8C%20%D9%84%DB%8C%D9%86%DA%A9') }}</button></div></div></div></div>
@endsection
@section('js')
<script>
// انتخاب تعداد؛ سقف آن موجودی انبار است تا کاربر عددی انتخاب نکند که سرور
// بعدا رد کند. دو مجموعه کنترل داریم (جعبه‌ی خرید و نوار چسبان موبایل) که
// هر دو روی همان ورودی #buy-qty کار می‌کنند تا مقدارشان از هم جدا نیفتد
$(function () {
    var $qty = $('#buy-qty');
    if (!$qty.length) return;
    var max = parseInt($qty.data('max'), 10) || 1;

    function toFa(n) {
        return String(n).replace(/\d/g, function (d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; });
    }

    function setQty(value) {
        value = Math.min(Math.max(value, 1), max);
        $qty.val(value);
        $('.js-qty-view').text(toFa(value));
        $('.js-qty-plus').prop('disabled', value >= max).css('opacity', value >= max ? .45 : 1);
        $('.js-qty-minus').prop('disabled', value <= 1).css('opacity', value <= 1 ? .45 : 1);
    }

    $(document).on('click', '.js-qty-plus', function () { setQty((parseInt($qty.val(), 10) || 1) + 1); });
    $(document).on('click', '.js-qty-minus', function () { setQty((parseInt($qty.val(), 10) || 1) - 1); });
    setQty(1);
});
</script>
{{-- انتخابگر عمداً به گالری محدود شده؛ کارت‌های «قطعات مرتبط» هم کلاس product-thumb دارند --}}
<script>
// تعویض تصویر اصلی گالری. چون تصویر داخل <picture> است، تنها عوض کردن
// img.src کافی نیست؛ تا وقتی <source> نسخه‌ی WebP تصویر قبلی را معرفی
// می‌کند، مرورگر همان را نشان می‌دهد. پس هر دو با هم به‌روز می‌شوند.
document.addEventListener('click', function (e) {
    const t = e.target.closest('.dk-product-thumbs .product-thumb');
    if (!t) return;
    const m = document.getElementById('product-main-image');
    if (m) {
        const src = t.dataset.src;
        const source = m.parentElement && m.parentElement.tagName === 'PICTURE'
            ? m.parentElement.querySelector('source')
            : null;
        if (source) {
            source.srcset = t.dataset.webp || src;
        }
        m.src = src;
    }
    document.querySelectorAll('.dk-product-thumbs .product-thumb').forEach(x => x.classList.remove('active'));
    t.classList.add('active');
});
</script>
<script>
$(function () {
    var $rail = $('.nx-slider');
    if (!$rail.length || $rail.hasClass('owl-loaded')) {
        return;
    }
    $rail.owlCarousel({
        rtl: true,
        nav: true,
        dots: false,
        margin: 0,
        loop: false,
        navText: ['<i class="fas fa-chevron-right"></i>', '<i class="fas fa-chevron-left"></i>'],
        responsive: {
            0: { items: 2 },
            768: { items: 3 },
            992: { items: 4 },
            1200: { items: 5 }
        }
    });
});
</script>
@endsection
