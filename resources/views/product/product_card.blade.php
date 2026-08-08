@php
    $discountPercent = $product->discountPercent();
    $hasDiscount = $discountPercent > 0;
@endphp
<div class="item">
    <div class="card custom-card position-relative">
        @if($hasDiscount)
            <span class="product-discount-badge">{{ toPersianNumbers(round($discountPercent), false) }}%</span>
        @endif

        <a href="{{$product->url()}}" class="product-thumb">
            <img src="{{$product->image()}}" class="slider-pic lazy-img"
                 onerror="this.onerror=null;this.src='/images/no-image.svg';this.classList.add('is-placeholder');"
                 loading="lazy"
                 alt="{{$product->title}}">
        </a>

        <div class="card-body product-card-body">
            <a href="{{$product->url()}}" class="product-title">{{toPersianNumbers($product->title)}}</a>

            <div class="product-price-row">
                @if($hasDiscount)
                    <del class="product-old-price">{{toPersianNumbers($product->regular_price)}}</del>
                @endif
                <span class="product-price">{{toPersianNumbers($product->price)}} <small>تومان</small></span>
            </div>

            <button class="btn add-cart-btn" data-id="{{ $product->id }}">
                <i class="fa fa-cart-plus me-1"></i>
                افزودن به سبد خرید
            </button>
        </div>
    </div>
</div>
