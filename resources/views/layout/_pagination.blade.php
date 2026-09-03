@php
    /*
    | صفحه‌بندی سمت سرور، با لینک واقعی.
    |
    | تا پیش از این شماره‌ی صفحه‌ها را فقط جاوااسکریپت می‌ساخت و خروجی‌اش
    | «<a pn="2">» بود — بدون href. برای مرورگر کار می‌کرد، ولی برای خزنده
    | اصلا لینک نبود: گوگل از هر صفحه‌ی دسته‌بندی یا مدل خودرو فقط ۱۲ محصول
    | اول را می‌دید و هیچ مسیری به صفحه‌ی دوم به بعد نداشت (rel=next در هد
    | فقط یک راهنماست، نه مسیر خزش).
    |
    | حالا همین فهرست با href واقعی رندر می‌شود. جاوااسکریپت فقط بعد از
    | فیلتر ایجکسی محتوای این <ul> را جایگزین می‌کند، پس رفتار کاربر عوض
    | نمی‌شود.
    */
    $current = (int) $paginator->currentPage();
    $last    = (int) $paginator->lastPage();
    $window  = 2;

    $numbers = [];
    if ($paginator->hasPages()) {
        $numbers = array_merge([1, $last], range(max(1, $current - $window), min($last, $current + $window)));
        $numbers = array_values(array_unique(array_filter($numbers, fn ($p) => $p >= 1 && $p <= $last)));
        sort($numbers);
    }

    /* صفحه‌ی اول آدرسِ بدون «page» دارد — دقیقا همان چیزی که canonical اعلام
       می‌کند. لینک‌دادن به «?page=1» یک نسخه‌ی دوم از همان صفحه می‌سازد. */
    $linkFor = function (int $number) use ($paginator) {
        $url = $paginator->url($number);
        if ($number > 1) {
            return $url;
        }

        [$path, $queryString] = array_pad(explode('?', $url, 2), 2, '');
        parse_str($queryString, $params);
        unset($params['page']);

        return $params ? $path . '?' . http_build_query($params) : $path;
    };
@endphp
<ul class="{{ $paginationClass ?? 'custom-pagination nx-pagination' }}" aria-label="صفحه‌بندی نتایج" id="pagination">
    @if($paginator->hasPages())
        @if($current > 1)
            <li class="page-item">
                <a href="{{ $linkFor($current - 1) }}" rel="prev" aria-label="صفحه قبل"><i class="fa fa-angle-right align-middle"></i></a>
            </li>
        @endif

        @php $previous = 0; @endphp
        @foreach($numbers as $number)
            @if($previous && $number - $previous > 1)
                <li class="page-item disabled"><span>…</span></li>
            @endif
            <li class="page-item {{ $number === $current ? 'active' : '' }}">
                <a href="{{ $linkFor($number) }}" @if($number === $current) aria-current="page" @endif>{{ toPersianNumbers($number, false) }}</a>
            </li>
            @php $previous = $number; @endphp
        @endforeach

        @if($current < $last)
            <li class="page-item">
                <a href="{{ $linkFor($current + 1) }}" rel="next" aria-label="صفحه بعد"><i class="fa fa-angle-left align-middle"></i></a>
            </li>
        @endif
    @endif
</ul>
