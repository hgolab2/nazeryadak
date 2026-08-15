{{-- نمودار تاریخچه‌ی قیمت — SVG خالص، بدون کتابخانه‌ی جاوااسکریپت.

     ورودی: $priceHistory = [['price' => int تومان, 'date' => 'رشته‌ی تاریخ شمسی'], ...]
     از قدیم به جدید. کنترلر تضمین می‌کند که حداقل دو نقطه دارد.

     محور زمان از چپ به راست است (قدیمی‌ترین سمت چپ) و برچسب قیمت سمت راست؛
     همان چیدمانی که کاربر ایرانی از نمودار قیمت انتظار دارد. --}}

@php
    $points = $priceHistory;
    $count  = count($points);
    $prices = array_column($points, 'price');

    $minPrice = min($prices);
    $maxPrice = max($prices);
    $first    = $prices[0];
    $last     = $prices[$count - 1];

    // مختصات بوم. با viewBox، نمودار در هر عرضی مقیاس می‌خورد و برای موبایل
    // به CSS جداگانه نیاز ندارد.
    $w = 720; $h = 250;
    $padTop = 16; $padBottom = 38; $padRight = 92; $padLeft = 14;
    $chartW = $w - $padLeft - $padRight;
    $chartH = $h - $padTop - $padBottom;
    $baseline = $padTop + $chartH;

    // وقتی قیمت در کل بازه ثابت مانده، تقسیم بر صفر پیش می‌آید؛ خط را وسط
    // بوم می‌کشیم که همان معنای «تغییری نکرده» را می‌دهد.
    $span = $maxPrice - $minPrice;

    $xy = [];
    foreach ($points as $i => $point) {
        $x = $padLeft + ($count > 1 ? $i * $chartW / ($count - 1) : $chartW / 2);
        $y = $span > 0
            ? $padTop + ($maxPrice - $point['price']) * $chartH / $span
            : $padTop + $chartH / 2;
        $xy[] = ['x' => round($x, 2), 'y' => round($y, 2)] + $point;
    }

    $line = implode(' ', array_map(fn ($p) => $p['x'] . ',' . $p['y'], $xy));
    $area = 'M ' . $xy[0]['x'] . ',' . $baseline
        . ' L ' . implode(' L ', array_map(fn ($p) => $p['x'] . ',' . $p['y'], $xy))
        . ' L ' . $xy[$count - 1]['x'] . ',' . $baseline . ' Z';

    // درصد تغییر از اولین نقطه تا امروز؛ همان عددی که کاربر دنبالش است.
    $changePercent = $first > 0 ? round(($last - $first) / $first * 100) : 0;

    // برچسب فشرده‌ی محور قیمت: عدد کامل در عرض موبایل جا نمی‌شود.
    $shortToman = function (int $toman) {
        if ($toman >= 1000000) {
            $m = $toman / 1000000;
            return toPersianNumbers(fmod($m, 1) == 0 ? (string) (int) $m : number_format($m, 1), false) . ' م';
        }
        if ($toman >= 1000) {
            return toPersianNumbers((string) (int) round($toman / 1000), false) . ' هـ';
        }
        return toPersianNumbers($toman);
    };

    // برچسب تاریخ فقط برای اول، آخر و وسط؛ بیشتر از این روی هم می‌افتد.
    $labelIndexes = [0, $count - 1];
    if ($count >= 5) {
        $labelIndexes[] = intdiv($count - 1, 2);
    }
@endphp

<section class="nx-card nx-price-chart" id="price-history">
    <div class="nx-card-head">
        <h2><i class="fas fa-chart-line"></i> تاریخچه قیمت</h2>
        @if($changePercent != 0)
            <span class="nx-pc-change {{ $changePercent > 0 ? 'is-up' : 'is-down' }}">
                <i class="fas fa-arrow-{{ $changePercent > 0 ? 'up' : 'down' }}"></i>
                {{ toPersianNumbers(abs($changePercent), false) }}٪
                {{ $changePercent > 0 ? 'گران‌تر' : 'ارزان‌تر' }}
            </span>
        @else
            <span class="nx-pc-change is-flat">بدون تغییر</span>
        @endif
    </div>

    <div class="nx-pc-body">
        <div class="nx-pc-stats">
            <div class="nx-pc-stat">
                <span>کمترین قیمت</span>
                <b class="is-low">{{ toPersianNumbers($minPrice) }}</b>
            </div>
            <div class="nx-pc-stat">
                <span>بیشترین قیمت</span>
                <b class="is-high">{{ toPersianNumbers($maxPrice) }}</b>
            </div>
            <div class="nx-pc-stat">
                <span>قیمت فعلی</span>
                <b>{{ toPersianNumbers($last) }}</b>
            </div>
        </div>

        <div class="nx-pc-canvas">
            {{-- preserveAspectRatio پیش‌فرض می‌ماند: با «none» دایره‌ها بیضی و
                 متن‌ها کشیده می‌شدند. مقیاس‌شدن با width:100% در CSS انجام می‌شود. --}}
            <svg viewBox="0 0 {{ $w }} {{ $h }}"
                 role="img" aria-label="نمودار تغییرات قیمت این محصول در {{ toPersianNumbers($count, false) }} نقطه">
                <defs>
                    {{-- رنگ از CSS می‌آید نه از ویژگی stop-color؛ var() داخل
                         ویژگی‌های SVG در همه‌ی مرورگرها کار نمی‌کند. --}}
                    <linearGradient id="nxPcFill" x1="0" y1="0" x2="0" y2="1">
                        <stop class="nx-pc-stop-top" offset="0%"/>
                        <stop class="nx-pc-stop-bottom" offset="100%"/>
                    </linearGradient>
                </defs>

                {{-- سه خط راهنما: بیشترین، میانه و کمترین قیمت --}}
                @foreach([0, 0.5, 1] as $step)
                    @php
                        $gy    = $padTop + $chartH * $step;
                        $value = (int) round($maxPrice - $span * $step);
                    @endphp
                    <line class="nx-pc-grid" x1="{{ $padLeft }}" y1="{{ $gy }}"
                          x2="{{ $padLeft + $chartW }}" y2="{{ $gy }}"/>
                    <text class="nx-pc-ylabel" x="{{ $padLeft + $chartW + 10 }}" y="{{ $gy + 4 }}">
                        {{ $shortToman($value) }}
                    </text>
                @endforeach

                <path class="nx-pc-area" d="{{ $area }}" fill="url(#nxPcFill)"/>
                <polyline class="nx-pc-line" points="{{ $line }}"/>

                @foreach($xy as $i => $p)
                    <g class="nx-pc-point">
                        <title>{{ $p['date'] }} — {{ toPersianNumbers($p['price']) }} تومان</title>
                        {{-- دایره‌ی نامرئیِ بزرگ‌تر، تا لمس روی موبایل هم tooltip را بیاورد --}}
                        <circle class="nx-pc-hit" cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="14"/>
                        <circle class="nx-pc-dot" cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="3.5"/>
                    </g>
                @endforeach

                @foreach($labelIndexes as $i)
                    @php
                        // برچسب اول و آخر به لبه‌ی بوم چسبیده‌اند؛ لنگرشان عوض
                        // می‌شود وگرنه نصف متن بیرون می‌افتد.
                        $anchor = $i === 0 ? 'start' : ($i === $count - 1 ? 'end' : 'middle');
                    @endphp
                    <text class="nx-pc-xlabel" x="{{ $xy[$i]['x'] }}" y="{{ $baseline + 24 }}"
                          text-anchor="{{ $anchor }}">{{ $xy[$i]['date'] }}</text>
                @endforeach
            </svg>
        </div>

        <p class="nx-pc-note">
            <i class="fas fa-circle-info"></i>
            قیمت‌ها به تومان است و با هر به‌روزرسانی لیست قیمت ثبت می‌شود.
            برای دیدن قیمت هر نقطه، نشانگر را روی آن نگه دارید.
        </p>
    </div>
</section>
