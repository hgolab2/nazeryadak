@php
    /* --- سئوی صفحه‌ی فهرست محصولات --- */
    $shopPage = max(1, (int) request('page', 1));
    $shopCarModel = trim((string) request('car_model'));
    // دسته‌بندی از مسیر آدرس می‌آید (/shop/{slug})، نه از query string.
    $shopCategorySlug = trim((string) ($categorySlug ?? ''));
    $shopCategoryLabel = null;
    foreach (\App\Enums\ProductCategory::cases() as $shopCase) {
        if ($shopCase->slug() === $shopCategorySlug) { $shopCategoryLabel = $shopCase->label(); break; }
    }

    // عنوان به ترتیب اولویت: دسته‌بندی ← مدل خودرو ← عبارت جستجو ← عمومی
    if ($shopCategoryLabel) {
        $shopTitle = 'خرید ' . $shopCategoryLabel . ' | قطعات اصلی ایساکو';
        $shopDescription = seo_description('خرید ' . $shopCategoryLabel . ' اصل و باکیفیت در ناظر یدک؛ قیمت روز، ضمانت اصالت کالا، بررسی کد فنی و ارسال سریع به سراسر ایران.');
    } elseif ($shopCarModel) {
        $shopTitle = 'قطعات ' . $shopCarModel . ' | خرید لوازم یدکی ' . $shopCarModel;
        $shopDescription = seo_description('لیست کامل قطعات یدکی ' . $shopCarModel . ' شامل قطعات موتوری، مصرفی، برقی، ترمز و بدنه با ضمانت اصالت و ارسال سراسر کشور از ناظر یدک.');
    } elseif ($title) {
        $shopTitle = 'خرید ' . $title . ' | فروشگاه لوازم یدکی ناظر یدک';
        $shopDescription = seo_description('جستجو و خرید ' . $title . ' در فروشگاه ناظر یدک؛ قطعات اصلی خودرو با امکان بررسی کد فنی، قیمت روز، ضمانت اصالت و ارسال سراسر کشور.');
    } else {
        $shopTitle = 'خرید لوازم یدکی خودرو | قطعات اصلی ایساکو در ناظر یدک';
        $shopDescription = 'خرید آنلاین لوازم یدکی خودرو با تمرکز ویژه بر قطعات اصلی ایساکو؛ قطعات موتوری، مصرفی، برقی، بدنه، جلوبندی و ترمز با قیمت روز و ارسال سراسر کشور از ناظر یدک.';
    }

    if ($shopPage > 1) {
        $shopTitle = $shopTitle . ' - صفحه ' . $shopPage;
    }

    /* صفحه‌ی دسته‌بندی آدرس اختصاصی خودش را دارد؛ canonical باید همان باشد
       نه /shop، وگرنه گوگل این صفحه را نسخه‌ی تکراری فروشگاه می‌بیند. */
    $shopCanonical = $shopCategorySlug
        ? seo_canonical('/shop/' . $shopCategorySlug)
        : seo_canonical('/shop');

    // مسیر راهنما
    $shopCrumbs = [['name' => 'ناظر یدک', 'url' => seo_url()], ['name' => 'فروشگاه لوازم یدکی', 'url' => seo_url('/shop')]];
    if ($shopCategoryLabel) {
        $shopCrumbs[] = ['name' => $shopCategoryLabel, 'url' => null];
    } elseif ($shopCarModel) {
        $shopCrumbs[] = ['name' => 'قطعات ' . $shopCarModel, 'url' => null];
    } elseif ($title) {
        $shopCrumbs[] = ['name' => $title, 'url' => null];
    }

    // فهرست محصولات این صفحه برای ItemList
    $shopItems = collect($model->items() ?? [])->map(fn($p) => [
        'name'  => $p->title,
        'url'   => seo_url($p->url()),
        'image' => seo_image_url($p->image()),
    ])->all();

    // صفحه‌بندی: prev/next برای کشف بهتر و canonical خودارجاع در هر صفحه
    $shopPrev = $model->currentPage() > 1 ? $model->previousPageUrl() : null;
    $shopNext = $model->hasMorePages() ? $model->nextPageUrl() : null;

    /* جستجوهای آزاد و ترکیب فیلترها محتوای تکراری تولید می‌کنند؛ فقط
       دسته‌بندی، مدل خودرو و صفحه‌بندی اجازه‌ی ایندکس دارند. */
    $shopRobots = seo_robots_tag(!seo_should_noindex() && $model->total() > 0);
@endphp
@extends('layout.layout', [
    'title' => $shopTitle,
    'metaDescription' => $shopDescription,
    'keywords' => trim(implode(', ', array_filter([$shopCategoryLabel, $shopCarModel ? 'قطعات ' . $shopCarModel : null, $title, seo_default_keywords()])), ', '),
    'canonical' => $shopCanonical,
    'robots' => $shopRobots,
    'prevPage' => $shopPrev,
    'nextPage' => $shopNext,
    'ogType' => 'website',
    'schema' => [
        seo_webpage_schema($shopTitle, $shopDescription, $shopCanonical, 'CollectionPage'),
        seo_breadcrumb_schema($shopCrumbs),
        seo_itemlist_schema($shopTitle, $shopItems, $shopCanonical),
    ],
])
@section('main_content')
<main class="nx-home nx-shop">
    <div class="nx-wrap">

        <nav class="nx-breadcrumb" aria-label="مسیر صفحه">
            <a href="/">ناظر یدک</a>
            <i class="fas fa-chevron-left"></i>
            <a href="/shop">فروشگاه قطعات</a>
            @if($title)
                <i class="fas fa-chevron-left"></i>
                <b>{{ $title }}</b>
            @endif
        </nav>

        <div class="nx-shop-layout">

            {{-- سایدبار فیلترها: در موبایل بعد از محصولات می‌آید تا کاربر
                 مجبور نباشد از روی فیلترها رد شود --}}
            <aside class="nx-shop-side">
                <section class="nx-card nx-side-card">
                    <div class="nx-card-head">
                        <h2><i class="fas fa-search"></i> جستجو در قطعات</h2>
                    </div>
                    <div class="nx-side-body">
                        <div class="nx-field">
                            <i class="fas fa-search"></i>
                            <input type="search"
                                id="search_title"
                                value="{{ $title }}"
                                placeholder="نام قطعه یا کد فنی...">
                        </div>
                        <label class="nx-field-label"><i class="fas fa-car"></i> خودرو مناسب</label>
                        <div class="nx-field nx-field-select">
                            <select id="search_car_model">
                                <option value="">همه خودروها</option>
                                @foreach($carCategories as $carCategory)
                                    <option value="{{ $carCategory->id }}" {{ (string) ($carModel ?? '') === (string) $carCategory->id || (string) ($carModel ?? '') === (string) $carCategory->name ? 'selected' : '' }}>{{ $carCategory->name }}</option>
                                @endforeach
                            </select>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                    </div>
                </section>

                <section class="nx-card nx-side-card">
                    <div class="nx-card-head">
                        <h2><i class="fas fa-filter"></i> دسته‌بندی قطعات</h2>
                    </div>
                    <div class="nx-side-body">
                        <ul class="nx-filter-list">
                            @foreach($categories as $cat)
                                <li>
                                    <label class="nx-filter-item">
                                        <input type="checkbox"
                                            class="category-filter"
                                            value="{{ $cat->value }}"
                                            {{ in_array($cat->value, $selectedCategoryIds ?? []) ? 'checked' : '' }}>
                                        <i class="fas {{ $cat->icon() }}"></i>
                                        <span>{{ $cat->label() }}</span>
                                        <b>{{ toPersianNumbers($categoryCounts[$cat->value] ?? 0) }}</b>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </section>

                <a href="/contact-us" class="nx-banner nx-banner-support nx-side-banner">
                    <i class="fas fa-headset"></i>
                    <span>
                        <strong>قطعه‌تان را پیدا نکردید؟</strong>
                        <p>با کد فنی یا مدل خودرو راهنمایی می‌شوید</p>
                        <span class="nx-banner-cta">تماس با کارشناس <i class="fas fa-chevron-left"></i></span>
                    </span>
                </a>
            </aside>

            {{-- نتایج --}}
            <div class="nx-shop-main">
                {{-- H1 پویا: موضوع دقیق همین صفحه (دسته، مدل خودرو یا جستجو)
                     را به گوگل اعلام می‌کند، نه یک عنوان ثابت برای همه. --}}
                <h1 class="nx-page-title">
                    @if($shopCategoryLabel)
                        خرید {{ $shopCategoryLabel }}
                    @elseif($shopCarModel)
                        قطعات یدکی {{ $shopCarModel }}
                    @elseif($title)
                        نتایج جستجوی «{{ $title }}»
                    @else
                        فروشگاه لوازم یدکی خودرو و قطعات ایساکو
                    @endif
                </h1>

                <section class="nx-card">
                    <div class="nx-card-head nx-results-head">
                        <h2><i class="fas fa-th-large"></i> <span id="total-count-text">{{ toPersianNumbers($totalCount) }} قطعه یافت شد</span></h2>
                        <div class="nx-sort">
                            <span>مرتب‌سازی:</span>
                            <div class="nx-field nx-field-select nx-sort-field">
                                <select id="sort-select">
                                    <option value="id-desc">جدیدترین</option>
                                    <option value="price-asc">ارزان‌ترین</option>
                                    <option value="price-desc">گران‌ترین</option>
                                </select>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                        </div>
                    </div>

                    <div class="nx-grid" id="content-wrapper">
                        @include('product.list_type', compact('model'))
                    </div>

                    <ul class="custom-pagination nx-pagination" aria-label="Pagination" id="pagination"></ul>
                </section>
            </div>

        </div>
    </div>
</main>
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
    // ارقام شمارنده باید مثل بقیه‌ی صفحه فارسی بماند
    function toFaDigits(value) {
        return String(value).replace(/\d/g, function (d) {
            return String.fromCharCode(1776 + Number(d));
        });
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
                $("#total-count-text").text(toFaDigits(Number(data.totalCount).toLocaleString('en-US')) + ' قطعه یافت شد');
                var result = Paging(pagin, 12, data.totalCount, "myClass", "myDisableClass");
                $("#pagination").html(result);
                $('html, body').animate({ scrollTop: $('#content-wrapper').offset().top - 90 }, 500);
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
        pagin = 1;
        loadMoreData(1, createQuery());
    });
    $('#search_title').on('keyup', function () {
        clearTimeout(window.searchTimer);
        window.searchTimer = setTimeout(function () {
            pagin = 1;
            loadMoreData(1, createQuery());
        }, 500);
    });
    $('#search_car_model').on('change', function () {
        pagin = 1;
        loadMoreData(1, createQuery());
    });
    $('#sort-select').on('change', function () {
        pagin = 1;
        loadMoreData(1, createQuery());
    });
</script>
@endsection
