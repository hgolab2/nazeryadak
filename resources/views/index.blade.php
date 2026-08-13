@php
    $fa = fn($s) => urldecode($s);
@endphp
@extends('layout.layout', [
    'title' => $fa('%D9%86%D8%A7%D8%B8%D8%B1%20%DB%8C%D8%AF%DA%A9%20%7C%20%D9%81%D8%B1%D9%88%D8%B4%DA%AF%D8%A7%D9%87%20%D9%82%D8%B7%D8%B9%D8%A7%D8%AA%20%D9%88%20%D9%84%D9%88%D8%A7%D8%B2%D9%85%20%DB%8C%D8%AF%DA%A9%DB%8C'),
    'metaDescription' => $fa('%D9%86%D8%A7%D8%B8%D8%B1%20%DB%8C%D8%AF%DA%A9%20%D9%81%D8%B1%D9%88%D8%B4%DA%AF%D8%A7%D9%87%20%D8%AA%D8%AE%D8%B5%D8%B5%DB%8C%20%D9%82%D8%B7%D8%B9%D8%A7%D8%AA%20%D8%AE%D9%88%D8%AF%D8%B1%D9%88%20%D8%A8%D8%A7%20%D8%AA%D9%85%D8%B1%DA%A9%D8%B2%20%D8%A8%D8%B1%20%D9%82%D8%B7%D8%B9%D8%A7%D8%AA%20%D8%A7%D8%B5%D9%84%DB%8C%20%D8%A7%DB%8C%D8%B3%D8%A7%DA%A9%D9%88%20%D8%A7%D8%B3%D8%AA.'),
    'keywords' => seo_default_keywords(),
    'canonical' => seo_url(),
    'ogImage' => seo_url('/assets/images/logo.png'),
    'schema' => [seo_store_schema(), ['@context' => 'https://schema.org', '@type' => 'WebSite', '@id' => seo_url('#website'), 'url' => seo_url(), 'name' => seo_site_name(), 'inLanguage' => 'fa-IR', 'potentialAction' => ['@type' => 'SearchAction', 'target' => seo_url('/shop?title={search_term_string}'), 'query-input' => 'required name=search_term_string']]],
])
@section('main_content')
<main class="dk-home">
    <section class="dk-hero-wrap">
        <div class="container">
            <div class="dk-hero-grid">
                <section class="dk-main-slider" aria-label="{{ $fa('%D8%A7%D8%B3%D9%84%D8%A7%DB%8C%D8%AF%D8%B1%20%D8%AA%D8%A8%D9%84%DB%8C%D8%BA%D8%A7%D8%AA') }}">
                    <div class="owl-carousel owl-theme home-ad-carousel">
                        @if(isset($advertisements) && $advertisements->count())
                            @foreach($advertisements as $ad)
                                @if($ad->media)
                                    <a href="{{ $ad->link ?: '#' }}" class="dk-main-slide"><img src="{{ $ad->media->getPath() }}" alt="{{ $ad->title }}"></a>
                                @endif
                            @endforeach
                        @else
                            <a href="/admin/advertisement/create" class="dk-main-slide dk-slide-fallback"><span>{{ $fa('%D9%88%DB%8C%D8%AA%D8%B1%DB%8C%D9%86%20%D9%88%DB%8C%DA%98%D9%87%20%D9%86%D8%A7%D8%B8%D8%B1%20%DB%8C%D8%AF%DA%A9') }}</span><strong>{{ $fa('%D8%A8%D9%86%D8%B1%20%D8%AA%D8%A8%D9%84%DB%8C%D8%BA%D8%A7%D8%AA%DB%8C%20%D8%B5%D9%81%D8%AD%D9%87%20%D8%A7%D9%88%D9%84') }}</strong><small>{{ $fa('%D8%A7%D8%B2%20%D9%85%D8%AF%DB%8C%D8%B1%DB%8C%D8%AA%20%D8%AA%D8%A8%D9%84%DB%8C%D8%BA%D8%A7%D8%AA%D8%8C%20%D8%AC%D8%A7%DB%8C%DA%AF%D8%A7%D9%87%20%D8%A7%D8%B3%D9%84%D8%A7%DB%8C%D8%AF%D8%B1%20%D9%81%D8%B1%D9%88%D8%B4%DA%AF%D8%A7%D9%87%20%D8%B1%D8%A7%20%D8%AB%D8%A8%D8%AA%20%DA%A9%D9%86%DB%8C%D8%AF.') }}</small></a>
                            <a href="/shop" class="dk-main-slide dk-slide-alt"><span>{{ $fa('%D8%AE%D8%B1%DB%8C%D8%AF%20%D9%85%D8%B7%D9%85%D8%A6%D9%86') }}</span><strong>{{ $fa('%D9%82%D8%B7%D8%B9%D8%A7%D8%AA%20%D8%A7%D8%B5%D9%84%DB%8C%20%D8%AE%D9%88%D8%AF%D8%B1%D9%88') }}</strong><small>{{ $fa('%D8%AC%D8%B3%D8%AA%D8%AC%D9%88%20%D8%A8%D8%A7%20%DA%A9%D8%AF%20%D9%81%D9%86%DB%8C%D8%8C%20%D9%86%D8%A7%D9%85%20%D9%82%D8%B7%D8%B9%D9%87%20%DB%8C%D8%A7%20%D9%85%D8%AF%D9%84%20%D8%AE%D9%88%D8%AF%D8%B1%D9%88') }}</small></a>
                        @endif
                    </div>
                </section>
                <aside class="dk-hero-side">
                    <img src="/assets/images/isaco-logo.png" alt="{{ $fa('%D9%84%D9%88%DA%AF%D9%88%DB%8C%20%D8%A7%DB%8C%D8%B3%D8%A7%DA%A9%D9%88') }}">
                    <strong>{{ $fa('%D9%82%D8%B7%D8%B9%D8%A7%D8%AA%20%D8%A7%D8%B5%D9%84%DB%8C%20%D8%A7%DB%8C%D8%B3%D8%A7%DA%A9%D9%88') }}</strong>
                    <p>{{ $fa('%D8%A7%D8%B5%D8%A7%D9%84%D8%AA%D8%8C%20%D9%85%D9%88%D8%AC%D9%88%D8%AF%DB%8C%20%D9%88%20%D8%AE%D8%B1%DB%8C%D8%AF%20%D8%B3%D8%B1%DB%8C%D8%B9') }}</p>
                    <a href="/shop">{{ $fa('%D9%88%D8%B1%D9%88%D8%AF%20%D8%A8%D9%87%20%D9%81%D8%B1%D9%88%D8%B4%DA%AF%D8%A7%D9%87') }}</a>
                </aside>
            </div>
        </div>
    </section>

    <div class="container">
        <section class="dk-quick-icons" aria-label="{{ $fa('%D8%AF%D8%B3%D8%AA%D8%B1%D8%B3%DB%8C%20%D8%B3%D8%B1%DB%8C%D8%B9') }}">
            <a href="/shop?title={{ $fa('%D9%84%D9%86%D8%AA%20%D8%AA%D8%B1%D9%85%D8%B2') }}"><i class="fas fa-compact-disc"></i><span>{{ $fa('%D9%84%D9%86%D8%AA%20%D8%AA%D8%B1%D9%85%D8%B2') }}</span></a>
            <a href="/shop?title={{ $fa('%D9%81%DB%8C%D9%84%D8%AA%D8%B1%20%D8%B1%D9%88%D8%BA%D9%86') }}"><i class="fas fa-oil-can"></i><span>{{ $fa('%D9%81%DB%8C%D9%84%D8%AA%D8%B1%20%D8%B1%D9%88%D8%BA%D9%86') }}</span></a>
            <a href="/shop?title={{ $fa('%D8%AA%D8%B3%D9%85%D9%87%20%D8%AA%D8%A7%DB%8C%D9%85') }}"><i class="fas fa-cogs"></i><span>{{ $fa('%D8%AA%D8%B3%D9%85%D9%87%20%D8%AA%D8%A7%DB%8C%D9%85') }}</span></a>
            <a href="/shop?title={{ $fa('%D8%B3%D9%86%D8%B3%D9%88%D8%B1') }}"><i class="fas fa-bolt"></i><span>{{ $fa('%D8%B3%D9%86%D8%B3%D9%88%D8%B1%D9%87%D8%A7') }}</span></a>
            <a href="/shop?title={{ $fa('%D8%B3%D9%BE%D8%B1') }}"><i class="fas fa-car-side"></i><span>{{ $fa('%D8%A8%D8%AF%D9%86%D9%87') }}</span></a>
            <a href="/shop?title={{ $fa('%DA%86%D8%B1%D8%A7%D8%BA') }}"><i class="fas fa-lightbulb"></i><span>{{ $fa('%DA%86%D8%B1%D8%A7%D8%BA') }}</span></a>
        </section>

        @if(isset($specialProducts) && $specialProducts->count())
        <section class="dk-amazing-section">
            <div class="dk-amazing-head"><strong>{{ $fa('%D9%81%D8%B1%D9%88%D8%B4%20%D9%88%DB%8C%DA%98%D9%87') }}</strong><span>{{ $fa('%D9%BE%DB%8C%D8%B4%D9%86%D9%87%D8%A7%D8%AF%D9%87%D8%A7%DB%8C%20%D8%B4%DA%AF%D9%81%D8%AA%E2%80%8C%D8%A7%D9%86%DA%AF%DB%8C%D8%B2%20%D8%A7%D9%85%D8%B1%D9%88%D8%B2') }}</span><a href="/shop">{{ $fa('%D9%87%D9%85%D9%87') }}</a></div>
            <div class="owl-carousel owl-theme custom-product-slider dk-amazing-slider">@foreach($specialProducts as $product) @include('product.product_card', ['product' => $product]) @endforeach</div>
        </section>
        @endif

        <section class="dk-product-section"><div class="dk-section-head"><h2>{{ $fa('%D9%BE%DB%8C%D8%B4%D9%86%D9%87%D8%A7%D8%AF%20%D9%88%DB%8C%DA%98%D9%87%20%D9%82%D8%B7%D8%B9%D8%A7%D8%AA%20%D8%AE%D9%88%D8%AF%D8%B1%D9%88%20%D9%88%20%D8%A7%DB%8C%D8%B3%D8%A7%DA%A9%D9%88') }}</h2><a href="/shop">{{ $fa('%D9%85%D8%B4%D8%A7%D9%87%D8%AF%D9%87%20%D9%87%D9%85%D9%87') }}</a></div><div class="owl-carousel owl-theme custom-product-slider">@foreach($products as $product) @include('product.product_card', ['product' => $product]) @endforeach</div></section>

        @if(isset($carCategories) && $carCategories->count() > 0)
        <section class="dk-car-section"><div class="dk-section-head"><h2>{{ $fa('%D8%AE%D8%B1%DB%8C%D8%AF%20%D8%A8%D8%B1%20%D8%A7%D8%B3%D8%A7%D8%B3%20%D8%AE%D9%88%D8%AF%D8%B1%D9%88') }}</h2><a href="/shop">{{ $fa('%D9%87%D9%85%D9%87') }}</a></div><div class="home-car-grid">@foreach($carCategories as $cat)<a href="/shop?car_model={{ urlencode($cat->name) }}" class="home-car-card">@if($cat->image)<img src="{{ $cat->image }}" alt="{{ $cat->name }}">@else<span class="home-car-icon"><i class="fas fa-car"></i></span>@endif<strong>{{ $cat->name }}</strong><span>{{ number_format($cat->products_count) }} {{ $fa('%D9%82%D8%B7%D8%B9%D9%87') }}</span></a>@endforeach</div></section>
        @endif

        <section class="dk-help-strip"><div><strong>{{ $fa('%D9%82%D8%B7%D8%B9%D9%87%20%D9%85%D9%86%D8%A7%D8%B3%D8%A8%20%D8%B1%D8%A7%20%D9%BE%DB%8C%D8%AF%D8%A7%20%D9%86%D9%85%DB%8C%E2%80%8C%DA%A9%D9%86%DB%8C%D8%AF%D8%9F') }}</strong><span>{{ $fa('%D8%A8%D8%A7%20%DA%A9%D8%AF%20%D9%81%D9%86%DB%8C%20%DB%8C%D8%A7%20%D9%85%D8%AF%D9%84%20%D8%AE%D9%88%D8%AF%D8%B1%D9%88%20%D8%B1%D8%A7%D9%87%D9%86%D9%85%D8%A7%DB%8C%DB%8C%20%D9%85%DB%8C%E2%80%8C%D8%B4%D9%88%DB%8C%D8%AF.') }}</span></div><a href="/contact-us">{{ $fa('%D8%AA%D9%85%D8%A7%D8%B3%20%D8%A8%D8%A7%20%DA%A9%D8%A7%D8%B1%D8%B4%D9%86%D8%A7%D8%B3') }}</a></section>

        <section class="dk-blog-section"><div class="dk-section-head"><h2>{{ $fa('%D8%A2%D8%AE%D8%B1%DB%8C%D9%86%20%D9%85%D8%B7%D8%A7%D9%84%D8%A8%20%D8%B1%D8%A7%D9%87%D9%86%D9%85%D8%A7%DB%8C%20%D8%AE%D8%B1%DB%8C%D8%AF') }}</h2><a href="/blog">{{ $fa('%D9%87%D9%85%D9%87') }}</a></div><div class="row g-3">@foreach ($articles as $article)<div class="col-6 col-lg-3"><a href="{{$article->getUrl()}}" class="d-block h-100 text-decoration-none"><div class="blog-card-new">@if($article->image && $article->images)<img title="{{$article->titr}}" src="{{$article->images->getPath()}}" alt="{{$article->titr}}">@else<div class="blog-card-fallback"><i class="fas fa-cogs"></i></div>@endif<div class="p-3"><h3>{{$article->titr}}</h3>@if($article->sutitr)<p>{{ Str::limit($article->sutitr, 72) }}</p>@endif</div></div></a></div>@endforeach</div></section>
    </div>
</main>
@endsection
@section('js')
<script>
$(function(){if($('.home-ad-carousel').length){$('.home-ad-carousel').owlCarousel({rtl:true,items:1,loop:true,dots:true,nav:true,autoplay:true,autoplayTimeout:4500,autoplayHoverPause:true});}});
</script>
@endsection