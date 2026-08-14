@php
    $fa = fn($s) => urldecode($s);
    $productUrl = seo_url($model->url());
    $productImage = str_starts_with($model->image(), 'http') ? $model->image() : seo_url($model->image());
    /* عنوان، توضیحات متا، کانونیکال و robots از مدل خوانده می‌شوند: اگر مدیر
       در تب «سئوی محصول» مقداری وارد کرده باشد همان می‌آید، وگرنه نسخه‌ی
       خودکار از روی نام، کد فنی و مدل خودرو ساخته می‌شود. */
    $productDescription = $model->seoDescription();
    // کانونیکال ممکن است دستی به صفحه‌ی دیگری اشاره کند؛ اسکیما و @id باید
    // روی آدرس واقعی همین صفحه بمانند.
    $productCanonical = $model->seoCanonical();
    $galleryImages = $model->images->count() ? $model->images : collect([(object) ['path' => $model->image(), 'alt' => $model->title]]);

    /* --- سئوی صفحه محصول --- */
    $productInStock = !isset($model->stock) || $model->stock === null || (int) $model->stock > 0;

    // موجودی قابل خرید؛ محصول بدون stock ثبت‌شده عملا قابل افزودن به سبد نیست
    $stock = (int) $model->stock;

    /* قطعات دسته‌ی «شاسی و بدنه» قیمت ثابت ندارند: به‌جای مبلغ، دعوت به تماس
       نشان داده می‌شود و در سبد و فاکتور هم به‌صورت «استعلام تلفنی» می‌آید. */
    $contactPrice = $model->isContactPrice();

    // عنوان: «خرید {نام قطعه} + کد فنی» تا کوئری‌های کد فنی هم پوشش داده شود.
    $productTitle = $model->seoTitle();

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
        $model->focus_keyword,
        $model->title,
        $model->sku,
        $model->isaco_code,
        $model->car_model ? $fa('%D9%82%D8%B7%D8%B9%D8%A7%D8%AA%20') . $model->car_model : null,
        seo_default_keywords(),
    ])), ', '),
    'canonical' => $productCanonical,
    'robots' => $model->seoRobotsTag(),
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
                    <button type="button" onclick="addFavorite({{ $model->id }})" class="itemFavorite_{{ $model->id }}"><i class="{{ is_favorite_product($model->id) ? 'fas' : 'far' }} fa-heart"></i></button>
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
                    <div class="dk-detail-price is-contact-price" style="font-size:1.05rem;">
                        <x-contact-price-link class="nx-contact-call" :show-phone="true" />
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
                    <div class="dk-detail-price">
                        <span id="dk-unit-price">{{ toPersianNumbers($model->price) }}</span>
                        <small>{{ $fa('%D8%AA%D9%88%D9%85%D8%A7%D9%86') }}</small>
                        <span class="dk-price-mode" id="dk-price-mode" hidden>قیمت عمده</span>
                    </div>
                @endif

                {{-- فروش عمده در سه سطر کوتاه: تعداد و قیمت، فاصله تا آستانه،
                     دکمه. مشتری این صفحه سر کار است و متن بلند را نمی‌خواند،
                     پس فقط عددهایی می‌ماند که برای تصمیم لازم است --}}
                @if(!$contactPrice && $model->hasWholesale())
                    @php
                        $wsQty   = $model->wholesaleMinQty();
                        $wsPrice = $model->wholesalePrice();
                    @endphp
                    <div class="dk-wholesale" id="dk-wholesale"
                         data-min-qty="{{ $wsQty }}"
                         data-price="{{ $wsPrice }}"
                         data-retail="{{ (int) $model->price }}">
                        <div class="dk-wholesale-head">
                            <i class="fas fa-boxes-stacked"></i>
                            <b>خرید عمده</b>
                            <span class="dk-wholesale-off">{{ toPersianNumbers($model->wholesaleDiscountPercent(), false) }}٪ ارزان‌تر</span>
                            {{-- فقط وقتی تعداد آستانه واقعا فاکتور را به مبلغ ارسال رایگان
                                 می‌رساند؛ با تعداد دستیِ کمتر، این وعده درست نیست --}}
                            @if($model->wholesaleReachesFreeShipping())
                                <span class="dk-wholesale-ship"><i class="fas fa-truck-fast"></i> ارسال رایگان</span>
                            @endif
                        </div>
                        <p class="dk-wholesale-main">
                            از <b>{{ toPersianNumbers($wsQty, false) }} عدد</b>:
                            هر عدد <b>{{ toPersianNumbers($wsPrice) }}</b> تومان
                        </p>
                        @if($stock >= $wsQty)
                            {{-- فاصله تا آستانه با تعدادِ انتخاب‌شده جلو می‌رود، وگرنه
                                 کاربر باید خودش عددها را کم و زیاد کند --}}
                            <div class="dk-wholesale-progress">
                                <span class="dk-wholesale-remain" id="dk-ws-remain">
                                    <i class="fas fa-arrow-up-long"></i>
                                    <span class="js-ws-remain-text">{{ toPersianNumbers($wsQty - 1, false) }} عدد دیگر</span>
                                </span>
                                <button type="button" class="dk-wholesale-jump js-ws-jump" data-qty="{{ $wsQty }}">
                                    انتخاب {{ toPersianNumbers($wsQty, false) }} عدد
                                </button>
                            </div>
                        @elseif($stock > 0)
                            <p class="dk-wholesale-warn">
                                <i class="fas fa-circle-info"></i>
                                موجودی {{ toPersianNumbers($stock, false) }} عدد — برای عمده تماس بگیرید
                            </p>
                        @endif
                    </div>
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

        {{-- نظرات کاربران.
             دو کارکرد همزمان: محتوای یکتا برای صفحه‌ای که اغلب فقط مشخصات
             فنی دارد، و منبع aggregateRating که ستاره‌ی نتیجه‌ی گوگل را
             فعال می‌کند. --}}
        <section class="nx-card" id="reviews">
            <div class="nx-card-head">
                <h2><i class="fas fa-star"></i> نظرات کاربران درباره {{ $model->title }}</h2>
                @if($model->rating_count)
                    <span class="dk-rating-summary">
                        <b>{{ toPersianNumbers(number_format((float) $model->rating_avg, 1)) }}</b>
                        از ۵ — {{ toPersianNumbers($model->rating_count) }} نظر
                    </span>
                @endif
            </div>

            <div class="dk-reviews">
                @if(session('review_notice'))
                    <div class="dk-review-notice">{{ session('review_notice') }}</div>
                @endif

                @if($model->approvedReviews->count())
                    @foreach($model->approvedReviews as $review)
                        <article class="dk-review">
                            <div class="dk-review-head">
                                <b>{{ $review->name }}</b>
                                @if($review->is_buyer)<span class="dk-review-buyer">خریدار</span>@endif
                                <span class="dk-review-stars" aria-label="{{ $review->rating }} از ۵">
                                    @for($i = 1; $i <= 5; $i++)<i class="fa{{ $i <= $review->rating ? 's' : 'r' }} fa-star"></i>@endfor
                                </span>
                            </div>
                            @if($review->title)<div class="dk-review-title">{{ $review->title }}</div>@endif
                            <p class="dk-review-body">{{ $review->comment }}</p>
                        </article>
                    @endforeach
                @else
                    <p class="dk-review-empty">هنوز نظری برای این قطعه ثبت نشده است. اولین نفر باشید.</p>
                @endif

                <form method="POST" action="/product/{{ $model->id }}/review" class="dk-review-form">
                    @csrf
                    <h3>ثبت نظر و امتیاز</h3>

                    @if($errors->any())
                        <div class="dk-review-errors">
                            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                        </div>
                    @endif

                    <div class="dk-review-stars-input">
                        <span>امتیاز شما:</span>
                        @for($i = 5; $i >= 1; $i--)
                            <input type="radio" name="rating" id="rating-{{ $i }}" value="{{ $i }}" {{ (int) old('rating') === $i ? 'checked' : '' }} required>
                            <label for="rating-{{ $i }}" title="{{ $i }} ستاره"><i class="fas fa-star"></i></label>
                        @endfor
                    </div>

                    <div class="dk-review-fields">
                        <input type="text" name="name" placeholder="نام شما" value="{{ old('name', auth('customer')->user()->name ?? '') }}" maxlength="100">
                        <input type="text" name="title" placeholder="عنوان نظر (اختیاری)" value="{{ old('title') }}" maxlength="255">
                    </div>
                    <textarea name="comment" rows="4" placeholder="تجربه‌ی خود از این قطعه را بنویسید؛ کیفیت، تناسب با خودرو و نصب." maxlength="2000" required>{{ old('comment') }}</textarea>
                    <button type="submit" class="btn btn-info">ثبت نظر</button>
                    <small>نظر شما پس از تأیید مدیر نمایش داده می‌شود.</small>
                </form>
            </div>
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

<style>
.dk-rating-summary{font-size:.85rem;color:#666}
.dk-rating-summary b{color:#f5a623;font-size:1.05rem}
.dk-reviews{padding:16px 20px}
.dk-review{border-bottom:1px solid #eef2f7;padding:14px 0}
.dk-review:last-of-type{border-bottom:0}
.dk-review-head{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px}
.dk-review-buyer{background:#e8f7ee;color:#176b3a;border-radius:999px;padding:2px 9px;font-size:11px}
.dk-review-stars{color:#f5a623;font-size:.8rem;margin-right:auto}
.dk-review-title{font-weight:700;font-size:.9rem;margin-bottom:4px}
.dk-review-body{font-size:.87rem;line-height:2;color:#444;margin:0}
.dk-review-empty,.dk-review-notice{font-size:.87rem;color:#666;padding:10px 0}
.dk-review-notice{background:#e8f7ee;color:#176b3a;border-radius:8px;padding:10px 14px;margin-bottom:12px}
.dk-review-errors{background:#fff5f5;color:#842029;border-radius:8px;padding:10px 14px;margin-bottom:12px;font-size:.85rem}
.dk-review-form{border-top:1px solid #eef2f7;margin-top:14px;padding-top:16px}
.dk-review-form h3{font-size:.95rem;font-weight:700;margin-bottom:12px}
.dk-review-form textarea,.dk-review-fields input{width:100%;border:1px solid #e3e8ef;border-radius:8px;padding:10px 12px;font-size:.85rem;font-family:inherit;margin-bottom:10px}
.dk-review-fields{display:grid;grid-template-columns:1fr 1fr;gap:10px}
@media(max-width:600px){.dk-review-fields{grid-template-columns:1fr}}
/* ستاره‌ها با direction:rtl از راست پر می‌شوند؛ انتخاب ۴ باید ۴ ستاره‌ی
   اول را هم روشن کند، به همین دلیل از سلکتور برادرِ بعدی استفاده شده. */
.dk-review-stars-input{display:flex;align-items:center;gap:4px;margin-bottom:12px;font-size:.85rem}
.dk-review-stars-input input{position:absolute;opacity:0;width:0;height:0}
.dk-review-stars-input label{color:#d7dde5;cursor:pointer;font-size:1.25rem;order:1}
.dk-review-stars-input input:checked ~ label{color:#f5a623}
.dk-review-stars-input span{margin-left:8px}
</style>

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
                <span class="mobile-actionbar__price"><span class="js-bar-price">{{ toPersianNumbers($model->price) }}</span> <small>تومان</small></span>
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

    // جعبه‌ی عمده: با رسیدن تعداد به آستانه، قیمتِ نمایش‌داده‌شده همان‌جا عوض
    // می‌شود تا کاربر پیش از رفتن به سبد ببیند چه چیزی نصیبش می‌شود
    var $ws = $('#dk-wholesale');
    var wsMin = $ws.length ? parseInt($ws.data('min-qty'), 10) : 0;
    var wsPrice = $ws.length ? parseInt($ws.data('price'), 10) : 0;
    var retail = $ws.length ? parseInt($ws.data('retail'), 10) : 0;

    // جداکننده‌ی هزارگان همان کاماست تا با خروجی toPersianNumbers یکی باشد
    function money(n) {
        return toFa(String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ','));
    }

    function syncPrice(value) {
        if (!$ws.length) return;
        var isWholesale = value >= wsMin;
        $ws.toggleClass('is-active', isWholesale);
        $('#dk-price-mode').prop('hidden', !isWholesale);
        $('#dk-unit-price').text(money(isWholesale ? wsPrice : retail));
        $('.mobile-actionbar__price .js-bar-price').text(money(isWholesale ? wsPrice : retail));

        // فاصله‌ی تا آستانه به‌جای یک جمله‌ی ثابت، همراه با تعدادِ انتخاب‌شده جلو می‌رود
        var $remain = $('#dk-ws-remain');
        if ($remain.length) {
            $remain.toggleClass('is-done', isWholesale);
            $remain.find('i').attr('class', isWholesale ? 'fas fa-circle-check' : 'fas fa-arrow-up-long');
            $remain.find('.js-ws-remain-text').text(isWholesale
                ? 'قیمت عمده اعمال شد'
                : toFa(wsMin - value) + ' عدد دیگر');
            $('.js-ws-jump').prop('hidden', isWholesale);
        }
    }

    function setQty(value) {
        value = Math.min(Math.max(value, 1), max);
        $qty.val(value);
        $('.js-qty-view').text(toFa(value));
        $('.js-qty-plus').prop('disabled', value >= max).css('opacity', value >= max ? .45 : 1);
        $('.js-qty-minus').prop('disabled', value <= 1).css('opacity', value <= 1 ? .45 : 1);
        syncPrice(value);
    }

    $(document).on('click', '.js-qty-plus', function () { setQty((parseInt($qty.val(), 10) || 1) + 1); });
    $(document).on('click', '.js-qty-minus', function () { setQty((parseInt($qty.val(), 10) || 1) - 1); });
    // پرش مستقیم به آستانه؛ وگرنه کاربر باید چندین بار روی + بزند
    $(document).on('click', '.js-ws-jump', function () { setQty(parseInt($(this).data('qty'), 10) || 1); });
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
