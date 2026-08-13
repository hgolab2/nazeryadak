@php
    $fa = fn($s) => urldecode($s);
    $productUrl = seo_url($model->url());
    $productImage = str_starts_with($model->image(), 'http') ? $model->image() : seo_url($model->image());
    $productDescription = seo_description(($model->description ?: ($model->title . ' ' . $fa('%D9%85%D9%86%D8%A7%D8%B3%D8%A8%20%D8%AE%D8%B1%DB%8C%D8%AF%20%D8%A7%D8%B2%20%D9%81%D8%B1%D9%88%D8%B4%DA%AF%D8%A7%D9%87%20%D9%86%D8%A7%D8%B8%D8%B1%20%DB%8C%D8%AF%DA%A9'))) . ($model->sku ? ' ' . $model->sku : ''));
    $galleryImages = $model->images->count() ? $model->images : collect([(object) ['path' => $model->image(), 'alt' => $model->title]]);
@endphp
@extends('layout.layout', [
    'title' => $fa('%D8%AE%D8%B1%DB%8C%D8%AF%20') . $model->title . ' | ' . $fa('%D9%86%D8%A7%D8%B8%D8%B1%20%DB%8C%D8%AF%DA%A9'),
    'metaDescription' => $productDescription,
    'keywords' => seo_default_keywords(),
    'canonical' => $productUrl,
    'ogImage' => $productImage,
    'ogType' => 'product',
    'schema' => [seo_store_schema()],
])
@section('main_content')
<main class="product-show-page">
    <div class="container">
        <nav class="dk-product-breadcrumb"><a href="/">{{ $fa('%D9%86%D8%A7%D8%B8%D8%B1%20%DB%8C%D8%AF%DA%A9') }}</a><span>/</span><a href="/shop">{{ $fa('%D9%81%D8%B1%D9%88%D8%B4%DA%AF%D8%A7%D9%87') }}</a><span>/</span><b>{{ $model->title }}</b></nav>

        <section class="product-content product-hero-panel dk-product-detail">
            <div class="dk-product-gallery">
                <div class="dk-product-actions">
                    <button type="button" onclick="addFavorite({{ $model->id }})" class="itemFavorite_{{ $model->id }}"><i class="far fa-heart"></i></button>
                    <button type="button" data-bs-toggle="modal" data-bs-target="#share-modal"><i class="fa fa-share-alt"></i></button>
                </div>
                <div class="dk-product-main-image"><img src="{{ $galleryImages->first()->path }}" id="product-main-image" @if(!$model->hasImage()) data-fetch="/product/fetch-image/{{$model->id}}" @endif alt="{{$model->title}}"></div>
                @if($galleryImages->count() > 1)
                <div class="dk-product-thumbs">
                    @foreach($galleryImages as $image)
                        <button type="button" class="product-thumb" data-src="{{ $image->path }}"><img src="{{ $image->path }}" alt="{{ $image->alt ?? $model->title }}"></button>
                    @endforeach
                </div>
                @endif
            </div>

            <div class="dk-product-info">
                <h1>{{ $model->title }}</h1>
                @if($model->short_description)<p class="dk-short-desc">{{ $model->short_description }}</p>@endif
                <h2>{{ $fa('%D9%88%DB%8C%DA%98%DA%AF%DB%8C%E2%80%8C%D9%87%D8%A7') }}</h2>
                <ul>
                    @if($model->sku)<li><span>{{ $fa('%DA%A9%D8%AF%20%D9%81%D9%86%DB%8C') }}</span><bdi>{{ $model->sku }}</bdi></li>@endif
                    @if($model->car_model)<li><span>{{ $fa('%D8%AE%D9%88%D8%AF%D8%B1%D9%88%DB%8C%20%D9%85%D9%86%D8%A7%D8%B3%D8%A8') }}</span>{{ $model->car_model }}</li>@endif
                    @if($model->weight)<li><span>{{ $fa('%D9%88%D8%B2%D9%86') }}</span>{{ $model->weight }} {{ $fa('%DA%AF%D8%B1%D9%85') }}</li>@endif
                    @if($model->width && $model->height)<li><span>{{ $fa('%D8%A7%D8%A8%D8%B9%D8%A7%D8%AF') }}</span>{{ $model->width }} x {{ $model->height }}</li>@endif
                </ul>
                <div class="dk-assurance"><span><i class="fas fa-shield-alt"></i>{{ $fa('%D8%B6%D9%85%D8%A7%D9%86%D8%AA%20%D8%A7%D8%B5%D8%A7%D9%84%D8%AA') }}</span><span><i class="fas fa-truck"></i>{{ $fa('%D8%A7%D8%B1%D8%B3%D8%A7%D9%84%20%D8%B3%D8%B1%D8%A7%D8%B3%D8%B1%20%DA%A9%D8%B4%D9%88%D8%B1') }}</span></div>
            </div>

            <aside class="add-cart-box dk-buy-box">
                <div class="product-seller-row"><span><i class="fas fa-store"></i>{{ $fa('%D9%81%D8%B1%D9%88%D8%B4%D9%86%D8%AF%D9%87') }}</span><b>{{ $fa('%D9%86%D8%A7%D8%B8%D8%B1%20%DB%8C%D8%AF%DA%A9') }}</b></div>
                <div class="product-seller-row"><span><i class="fas fa-check-circle"></i>{{ $fa('%D9%88%D8%B6%D8%B9%DB%8C%D8%AA') }}</span><b>{{ $fa('%D9%85%D9%88%D8%AC%D9%88%D8%AF') }}</b></div>
                @if($model->discountPercent() > 0)<del>{{ toPersianNumbers($model->regular_price) }} {{ $fa('%D8%AA%D9%88%D9%85%D8%A7%D9%86') }}</del><span class="dk-detail-discount">{{ $model->discountPercent() }}%</span>@endif
                <div class="dk-detail-price">{{ toPersianNumbers($model->price) }} <small>{{ $fa('%D8%AA%D9%88%D9%85%D8%A7%D9%86') }}</small></div>
                <button type="button" class="btn add-cart-btn" data-id="{{ $model->id }}"><i class="fa fa-cart-plus me-1"></i>{{ $fa('%D8%A7%D9%81%D8%B2%D9%88%D8%AF%D9%86%20%D8%A8%D9%87%20%D8%B3%D8%A8%D8%AF%20%D8%AE%D8%B1%DB%8C%D8%AF') }}</button>
            </aside>
        </section>

        <section class="product-tab-content dk-detail-tabs"><h2>{{ $fa('%D8%AA%D9%88%D8%B6%DB%8C%D8%AD%D8%A7%D8%AA%20%D9%85%D8%AD%D8%B5%D9%88%D9%84') }}</h2>@if($model->description)<div class="product-description-html">{!! $model->description !!}</div>@else<p>{{ $fa('%D8%AA%D9%88%D8%B6%DB%8C%D8%AD%D8%A7%D8%AA%DB%8C%20%D8%A8%D8%B1%D8%A7%DB%8C%20%D8%A7%DB%8C%D9%86%20%D9%85%D8%AD%D8%B5%D9%88%D9%84%20%D8%AB%D8%A8%D8%AA%20%D9%86%D8%B4%D8%AF%D9%87%20%D8%A7%D8%B3%D8%AA.') }}</p>@endif</section>

        @if(isset($products) && $products->count())<section class="dk-product-section"><div class="dk-section-head"><h2>{{ $fa('%D9%82%D8%B7%D8%B9%D8%A7%D8%AA%20%D9%85%D8%B1%D8%AA%D8%A8%D8%B7') }}</h2><a href="/shop">{{ $fa('%D9%87%D9%85%D9%87') }}</a></div><div class="owl-carousel owl-theme custom-product-slider">@foreach($products as $product) @include('product.product_card', ['product' => $product]) @endforeach</div></section>@endif
    </div>
</main>
<div class="modal fade" id="share-modal"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><p class="modal-title font-13">{{ $fa('%D8%A7%D8%B4%D8%AA%D8%B1%D8%A7%DA%A9%E2%80%8C%DA%AF%D8%B0%D8%A7%D8%B1%DB%8C') }}</p><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><button type="button" class="btn btn-share" onclick="navigator.clipboard.writeText(window.location.href)">{{ $fa('%DA%A9%D9%BE%DB%8C%20%D9%84%DB%8C%D9%86%DA%A9') }}</button></div></div></div></div>
@endsection
@section('js')
<script>document.addEventListener('click',function(e){const t=e.target.closest('.product-thumb');if(!t)return;const m=document.getElementById('product-main-image');if(m)m.src=t.dataset.src;document.querySelectorAll('.product-thumb').forEach(x=>x.classList.remove('active'));t.classList.add('active');});</script>
@endsection
