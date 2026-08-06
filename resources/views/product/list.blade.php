@php
    $shopTitle = $title ? ('خرید ' . $title . ' | فروشگاه لوازم یدکی ناظر یدک') : 'خرید لوازم یدکی خودرو | قطعات اصلی ایساکو در ناظر یدک';
    $shopDescription = $title ? seo_description('جستجو و خرید ' . $title . ' در فروشگاه ناظر یدک؛ قطعات اصلی خودرو با امکان بررسی کد فنی، قیمت روز، ضمانت اصالت و ارسال سراسر کشور.') : 'خرید آنلاین لوازم یدکی خودرو با تمرکز ویژه بر قطعات اصلی ایساکو؛ قطعات موتوری، مصرفی، برقی، بدنه، جلوبندی و ترمز با قیمت روز و ارسال سراسر کشور از ناظر یدک.';
@endphp
@extends('layout.layout', [
    'title' => $shopTitle,
    'metaDescription' => $shopDescription,
    'keywords' => seo_default_keywords(),
    'canonical' => seo_url('/shop'),
    'schema' => [seo_store_schema(), ['@context' => 'https://schema.org', '@type' => 'CollectionPage', 'name' => $shopTitle, 'description' => $shopDescription, 'url' => seo_url('/shop')], seo_breadcrumb_schema([['name' => 'ناظر یدک', 'url' => seo_url()], ['name' => 'فروشگاه لوازم یدکی', 'url' => seo_url('/shop')]])],
])
@section('main_content')
<div class="container">
    <div class="row mt-3">
        <div class="col-12">
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="/" class="breadcrumb-custom">ناظر یدک</a></li>
                <li class="breadcrumb-item"><a href="/shop" class="breadcrumb-custom">فروشگاه قطعات</a></li>
            </ul>
        </div>
    </div>
</div>
<main>
    <div class="container">
        <div class="row">
            {{-- سایدبار --}}
            <div class="col-lg-3">
                <div class="custom-sidebar mb-3">
                    <div class="d-flex align-items-center gap-2 border-bottom pb-2 mb-2">
                        <i class="fas fa-filter" style="color: var(--primary);"></i>
                        <p class="font-13 m-0 fw-bold">دسته‌بندی قطعات</p>
                    </div>
                    <ul class="ps-0 mb-0">
                        @foreach($categories as $cat)
                            <li class="mt-1">
                                <label class="font-12 d-flex align-items-center gap-2 py-1 px-2 rounded category-filter-label">
                                    <input type="checkbox"
                                        class="form-check-input category-filter m-0"
                                        value="{{ $cat->value }}"
                                        {{ in_array($cat->value, $selectedCategoryIds ?? []) ? 'checked' : '' }}>
                                    <i class="fas {{ $cat->icon() }}" style="color: var(--primary); width: 18px; text-align: center; font-size: 0.8rem;"></i>
                                    {{ $cat->label() }}
                                    <span class="badge bg-light text-muted ms-auto" style="font-size: 0.7rem;">{{ $categoryCounts[$cat->value] ?? 0 }}</span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="custom-sidebar">
                    <div class="d-flex align-items-center gap-2 border-bottom pb-2 mb-3">
                        <i class="fas fa-search" style="color: var(--primary);"></i>
                        <p class="font-13 m-0 fw-bold">جستجو در قطعات</p>
                    </div>
                    <div class="position-relative mb-3">
                        <input type="search"
                            id="search_title"
                            class="form-control"
                            value="{{$title}}"
                            placeholder="نام قطعه یا کد فنی...">
                    </div>
                    <div class="d-flex align-items-center gap-2 border-bottom pb-2 mb-3">
                        <i class="fas fa-car" style="color: var(--primary);"></i>
                        <p class="font-13 m-0 fw-bold">خودرو مناسب</p>
                    </div>
                    <div class="position-relative">
                        <input type="search"
                            id="search_car_model"
                            class="form-control"
                            value="{{ $carModel ?? '' }}"
                            placeholder="مثلاً: پژو 206، سمند...">
                    </div>
                </div>
            </div>

            {{-- محصولات --}}
            <div class="col-lg-9">
                <div class="product-items">
                    <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-3 px-1">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-th-large" style="color: var(--primary);"></i>
                            <span class="font-13 fw-bold" id="total-count-text">{{ $totalCount }} قطعه یافت شد</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="font-12 text-muted">مرتب‌سازی:</span>
                            <select class="form-select form-select-sm" id="sort-select" style="width: auto; font-size: 0.82rem;">
                                <option value="id-desc">جدیدترین</option>
                                <option value="price-asc">ارزان‌ترین</option>
                                <option value="price-desc">گران‌ترین</option>
                            </select>
                        </div>
                    </div>
                    <div class="row" id="content-wrapper">
                        @include('product.list_type', compact('model'))
                    </div>
                    <ul class="custom-pagination mt-4" aria-label="Pagination" id="pagination"></ul>
                </div>
            </div>
        </div>
    </div>
</main>
<style>
.product-title {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.6;
    min-height: calc(1.6em * 2);
}
.category-filter-label {
    cursor: pointer;
    transition: background 0.2s;
}
.category-filter-label:hover {
    background: var(--primary-lighter);
}
</style>
@endsection
@section('js')
<script src="/js/paging.js"></script>
<script>
    var pagin = 1;
    var str="";
    function createQuery()
    {
        let params = [];
        let title = $('#search_title').val();
        if (title && title.length > 0) {
            params.push('title=' + encodeURIComponent(title));
        }
        let carModel = $('#search_car_model').val();
        if (carModel && carModel.length > 0) {
            params.push('car_model=' + encodeURIComponent(carModel));
        }
        let categories = [];
        $('.category-filter:checked').each(function () {
            categories.push($(this).val());
        });
        if (categories.length > 0) {
            params.push('categories=' + categories.join(','));
        }
        let sort = $('#sort-select').val();
        if (sort) {
            let parts = sort.split('-');
            params.push('order=' + parts[0]);
            params.push('orderby=' + parts[1]);
        }
        return params.join('&');
    }
    function loadMoreData(page, type)
    {
        if(page==1){
            $(".tab-content1").html("");
        }
        $.ajax({
                url: `/shop?ajaxi=1&page=${page}&${type}`,
                type: "get",
            })
            .done(function(data) {
                if (data.length == 0) return;
                $("#content-wrapper").html(data.html);
                var result = Paging(pagin, 12, data.totalCount, "myClass", "myDisableClass");
                $("#pagination").html(result);
                $('html, body').animate({ scrollTop: $('#content-wrapper').offset().top - 80 }, 500);
            });
    }
    $(document).ready(function() {
        var result = Paging(1, 12, {{$totalCount}}, "myClass", "myDisableClass");
        $("#pagination").html(result);
        $("#pagination").on("click", "a", function () {
            pagin = $(this).attr("pn");
            if(pagin > 0){
                str = createQuery();
                loadMoreData($(this).attr("pn"), str);
            }
        });
    });
    $('.category-filter').on('change', function () {
        loadMoreData(1, createQuery());
    });
    $('#search_title').on('keyup', function () {
        clearTimeout(window.searchTimer);
        window.searchTimer = setTimeout(function () {
            loadMoreData(1, createQuery());
        }, 500);
    });
    $('#search_car_model').on('keyup', function () {
        clearTimeout(window.carModelTimer);
        window.carModelTimer = setTimeout(function () {
            loadMoreData(1, createQuery());
        }, 500);
    });
    $('#sort-select').on('change', function () {
        loadMoreData(1, createQuery());
    });
</script>
@endsection
