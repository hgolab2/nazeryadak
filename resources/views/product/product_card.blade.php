<div class="item">
    <div class="card border-0 custom-card mt-3 position-relative">
        <a href="{{$product->url()}}" class="d-block w-100">
            <img src="{{$product->image()}}" class="slider-pic lazy-img"
                 @if(!$product->hasImage()) data-fetch="/product/fetch-image/{{$product->id}}" @endif
                 alt="{{$product->title}}">
        </a>
        <div class="card-body">
            <a href="{{$product->url()}}" class="product-title">{{toPersianNumbers($product->title)}}</a>

            <div class="d-flex justify-content-between align-items-center px-3 py-3">
                <span class="badge text-white rounded-pill font-13" style="height: 25px; background: var(--accent);">
                    {{$product->discountPercent()}}%
                </span>

                <span class="font-13">
                    {{toPersianNumbers($product->price)}} تومان
                    <br>
                    <del class="text-muted font-13">{{toPersianNumbers($product->regular_price)}}</del>
                </span>
            </div>

            <div class="d-flex justify-content-center">
                <button class="btn add-cart-btn m-0" data-id="{{ $product->id }}">
                    <i class="fa fa-cart-plus me-1"></i>
                    افزودن به سبد خرید
                </button>
            </div>

        </div>
    </div>
</div>
