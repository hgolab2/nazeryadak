@foreach ($model as $product)
<div class="col-lg-4 col-md-6 col-sm-6 mb-3">
    <div class="card custom-card position-relative h-100">

        <span class="heart-icon m-2 itemFavorite_{{ $product->id }}" style="position:absolute; top:5px; left:5px; z-index:2;">
            <i class="{{ count($product->favorites) > 0 ? 'fas' : 'far' }} fa-heart favorite-icon"
               onclick="addFavorite({{ $product->id }})" style="font-size: 1.1rem; color: #ccc;"></i>
        </span>

        <a href="{{ $product->url() }}" class="d-block w-100 text-center pt-3 px-2">
            <img src="{{ $product->image() }}" class="slider-pic lazy-img"
                 @if(!$product->hasImage()) data-fetch="/product/fetch-image/{{$product->id}}" @endif
                 alt="{{ $product->title }}">
        </a>

        <div class="card-body d-flex flex-column">
            <a href="{{ $product->url() }}" class="product-title flex-grow-1">
                {{ $product->title }}
            </a>

            <div class="d-flex justify-content-between align-items-center px-1 py-2 mt-2">
                @if($product->discountPercent() > 0)
                    <span class="badge text-white rounded-pill font-13" style="background: var(--accent);">
                        {{ $product->discountPercent() }}%
                    </span>
                @else
                    <span></span>
                @endif

                <span class="font-13 text-end">
                    @if($product->discountPercent() > 0)
                        <strong>{{ toPersianNumbers($product->price) }}</strong> تومان
                        <br>
                        <del class="text-muted" style="font-size: 0.8rem;">
                            {{ toPersianNumbers($product->regular_price) }}
                        </del>
                    @else
                        <strong>{{ toPersianNumbers($product->regular_price) }}</strong> تومان
                    @endif
                </span>
            </div>

            <button class="btn add-cart-btn mt-auto" data-id="{{ $product->id }}">
                <i class="fa fa-cart-plus me-1"></i>
                افزودن به سبد خرید
            </button>
        </div>
    </div>
</div>
@endforeach
