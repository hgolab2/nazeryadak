@if($model->isEmpty())
<div class="nx-empty">
    <div class="nx-empty-icon"><i class="fas fa-search"></i></div>
    <h3>قطعه‌ای با این مشخصات پیدا نشد</h3>
    <p>می‌توانید یکی از این راه‌ها را امتحان کنید:</p>
    <ul class="nx-empty-tips">
        <li><i class="fas fa-barcode"></i> جستجو با <strong>کد فنی</strong> قطعه — دقیق‌ترین روش</li>
        <li><i class="fas fa-font"></i> نوشتن نام کوتاه‌تر، مثلاً «لنت» به‌جای «لنت ترمز جلو پژو»</li>
        <li><i class="fas fa-filter"></i> برداشتن فیلترهای دسته‌بندی و خودرو</li>
    </ul>
    <div class="nx-empty-actions">
        <a href="/shop" class="nx-btn"><i class="fas fa-store"></i> همه‌ی قطعات</a>
        <a href="tel:09127471631" class="nx-btn nx-btn-ghost"><i class="fas fa-headset"></i> پرسیدن از کارشناس</a>
    </div>
</div>
@endif

@foreach ($model as $product)
@php
    $discountPercent = $product->discountPercent();
    $hasDiscount = $discountPercent > 0;
    $cardStock = (int) $product->stock;
@endphp
<article class="nx-gcard">
    @if($hasDiscount)
        <span class="product-discount-badge">{{ toPersianNumbers(round($discountPercent), false) }}%</span>
    @endif

    <span class="nx-gcard-fav itemFavorite_{{ $product->id }}">
        <i class="{{ count($product->favorites) > 0 ? 'fas' : 'far' }} fa-heart favorite-icon"
           onclick="addFavorite({{ $product->id }})"></i>
    </span>

    <a href="{{ $product->url() }}" class="nx-gcard-thumb">
        @php $listImage = $product->image(); $listWebp = webp_variant($listImage); @endphp
        <picture>
            @if($listWebp)<source srcset="{{ $listWebp }}" type="image/webp">@endif
            <img src="{{ $listImage }}" class="slider-pic lazy-img"
                 onerror="this.onerror=null;this.src='/images/no-image.svg';this.classList.add('is-placeholder');"
                 loading="lazy" decoding="async" width="300" height="300"
                 alt="{{ $product->title }}{{ $product->car_model ? ' مناسب ' . $product->car_model : '' }} - خرید از ناظر یدک">
        </picture>
    </a>

    <div class="nx-gcard-body">
        <a href="{{ $product->url() }}" class="product-title">{{ $product->title }}</a>

        <div class="nx-gcard-meta">
            @if($product->sku)
                {{-- کد فنی عمداً با ارقام انگلیسی و چپ‌به‌راست نمایش داده می‌شود
                     چون خریدار آن را با کد روی قطعه مقایسه یا کپی می‌کند --}}
                <span class="product-sku" title="کد فنی قطعه">
                    <i class="fas fa-barcode"></i> <bdi>{{ $product->sku }}</bdi>
                </span>
            @endif
            @if($product->car_model)
                <span class="product-car" title="خودرو مناسب">
                    <i class="fas fa-car"></i> {{ Str::limit($product->car_model, 22) }}
                </span>
            @endif
        </div>

        <div class="product-price-row">
            @if($product->compareAtPrice())
                <del class="product-old-price">{{ toPersianNumbers($product->compareAtPrice()) }}</del>
            @endif
            <span class="product-price">{{ toPersianNumbers($product->price) }} <small>تومان</small></span>
        </div>

        @if($cardStock > 0)
            <button class="btn add-cart-btn" data-id="{{ $product->id }}">
                <i class="fa fa-cart-plus me-1"></i>
                افزودن به سبد خرید
            </button>
        @else
            {{-- کارت ناموجود دکمه‌ی فعال ندارد؛ قبلا کلیک به خطای «محصول یافت نشد» می‌رسید --}}
            <button class="btn" disabled style="background:#e3e3e3; color:#8a8a8a; border:none; cursor:not-allowed;">
                <i class="fas fa-ban me-1"></i> ناموجود
            </button>
        @endif
    </div>
</article>
@endforeach
