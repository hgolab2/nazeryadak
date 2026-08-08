@if($model->isEmpty())
<div class="col-12">
    <div class="search-empty text-center">
        <div class="search-empty-icon"><i class="fas fa-search"></i></div>
        <h5>قطعه‌ای با این مشخصات پیدا نشد</h5>
        <p>می‌توانید یکی از این راه‌ها را امتحان کنید:</p>
        <ul class="search-empty-tips">
            <li><i class="fas fa-barcode"></i> جستجو با <strong>کد فنی</strong> قطعه — دقیق‌ترین روش</li>
            <li><i class="fas fa-font"></i> نوشتن نام کوتاه‌تر، مثلاً «لنت» به‌جای «لنت ترمز جلو پژو»</li>
            <li><i class="fas fa-filter"></i> برداشتن فیلترهای دسته‌بندی و خودرو</li>
        </ul>
        <div class="search-empty-actions">
            <a href="/shop" class="btn"><i class="fas fa-store me-1"></i> همه‌ی قطعات</a>
            <a href="tel:09127471631" class="btn btn-outline"><i class="fas fa-headset me-1"></i> پرسیدن از کارشناس</a>
        </div>
    </div>
</div>
@endif

@foreach ($model as $product)
@php
    $discountPercent = $product->discountPercent();
    $hasDiscount = $discountPercent > 0;
@endphp
<div class="col-lg-4 col-md-6 col-6 mb-3">
    <div class="card custom-card shop-card position-relative h-100">

        <span class="heart-icon itemFavorite_{{ $product->id }}">
            <i class="{{ count($product->favorites) > 0 ? 'fas' : 'far' }} fa-heart favorite-icon"
               onclick="addFavorite({{ $product->id }})"></i>
        </span>

        @if($hasDiscount)
            <span class="product-discount-badge">{{ toPersianNumbers(round($discountPercent), false) }}%</span>
        @endif

        <a href="{{ $product->url() }}" class="product-thumb">
            <img src="{{ $product->image() }}" class="slider-pic lazy-img"
                 onerror="this.onerror=null;this.src='/images/no-image.svg';this.classList.add('is-placeholder');"
                 loading="lazy"
                 alt="{{ $product->title }}">
        </a>

        <div class="card-body product-card-body">
            <a href="{{ $product->url() }}" class="product-title">{{ $product->title }}</a>

            <div class="product-meta">
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
                @if($hasDiscount)
                    <del class="product-old-price">{{ toPersianNumbers($product->regular_price) }}</del>
                @endif
                <span class="product-price">{{ toPersianNumbers($product->price) }} <small>تومان</small></span>
            </div>

            <button class="btn add-cart-btn" data-id="{{ $product->id }}">
                <i class="fa fa-cart-plus me-1"></i>
                افزودن به سبد خرید
            </button>
        </div>
    </div>
</div>
@endforeach
