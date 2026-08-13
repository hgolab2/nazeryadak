@php
    $fa = fn($s) => urldecode($s);
    $discountPercent = $product->discountPercent();
    $hasDiscount = $discountPercent > 0;
    $images = $product->relationLoaded('images') ? $product->images : collect();
    $mainImage = $images->count() ? $images->first()->path : $product->image();
    $galleryCount = max($images->count(), 1);
    $cardStock = (int) $product->stock;
@endphp
<div class="item">
    <article class="card custom-card dk-product-card position-relative">
        @if($hasDiscount)
            <span class="product-discount-badge">{{ toPersianNumbers(round($discountPercent), false) }}%</span>
        @endif

        <a href="{{$product->url()}}" class="product-thumb dk-product-thumb">
            @php $cardWebp = webp_variant($mainImage); @endphp
            <picture>
                @if($cardWebp)<source srcset="{{ $cardWebp }}" type="image/webp">@endif
                <img src="{{$mainImage}}" class="slider-pic lazy-img"
                     onerror="this.onerror=null;this.src='/images/no-image.svg';this.classList.add('is-placeholder');"
                     loading="lazy" decoding="async" width="300" height="300"
                     alt="{{ $product->title }}{{ $product->car_model ? ' مناسب ' . $product->car_model : '' }} - خرید از ناظر یدک">
            </picture>
            @if($galleryCount > 1)
                <span class="dk-gallery-count"><i class="far fa-images"></i> {{ toPersianNumbers($galleryCount, false) }}</span>
            @endif
        </a>

        <div class="card-body product-card-body">
            <a href="{{$product->url()}}" class="product-title">{{toPersianNumbers($product->title)}}</a>

            <div class="product-card-meta">
                @if($product->sku)
                    <span><i class="fas fa-barcode"></i> <bdi>{{ $product->sku }}</bdi></span>
                @endif
                @if($product->car_model)
                    <span><i class="fas fa-car"></i> {{ Str::limit($product->car_model, 28) }}</span>
                @endif
                @if($product->short_description)
                    <p>{{ Str::limit(strip_tags($product->short_description), 78) }}</p>
                @endif
            </div>

            <div class="product-price-row">
                @if($product->compareAtPrice())
                    <del class="product-old-price">{{toPersianNumbers($product->compareAtPrice())}}</del>
                @endif
                <span class="product-price">{{toPersianNumbers($product->price)}} <small>{{ $fa('%D8%AA%D9%88%D9%85%D8%A7%D9%86') }}</small></span>
            </div>

            @if($cardStock > 0)
                <button class="btn add-cart-btn" data-id="{{ $product->id }}">
                    <i class="fa fa-cart-plus me-1"></i>
                    {{ $fa('%D8%A7%D9%81%D8%B2%D9%88%D8%AF%D9%86%20%D8%A8%D9%87%20%D8%B3%D8%A8%D8%AF%20%D8%AE%D8%B1%DB%8C%D8%AF') }}
                </button>
            @else
                {{-- کارت ناموجود دکمه‌ی فعال ندارد؛ قبلا کلیک به خطای «محصول یافت نشد» می‌رسید --}}
                <button class="btn" disabled style="background:#e3e3e3; color:#8a8a8a; border:none; cursor:not-allowed;">
                    <i class="fas fa-ban me-1"></i> ناموجود
                </button>
            @endif
        </div>
    </article>
</div>
