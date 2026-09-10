{{--
    ریل افقی کارت‌های محصول.

    اسکرول افقی بومی مرورگر است، نه owl carousel. owl بعد از بارگذاری،
    فرزندهای ریل را در چند لایه‌ی تازه بازچینی می‌کرد و مرورگر همان لحظه یک
    layout shift بزرگ روی کل صفحه ثبت می‌کرد؛ تنها منبع CLS صفحه‌ی اصلی
    همین بود. حالا چیدمان از همان اولین رندر نهایی است.

    ورودی‌ها:
      $railProducts — مجموعه‌ی محصول‌ها
      $railEager    — چند کارت اول با loading=eager بیایند (پیش‌فرض ۰).
                      فقط برای ریلی که در نخستین نمای صفحه دیده می‌شود؛
                      تصویر LCP نباید lazy باشد.
--}}
<div class="nx-rail">
    <div class="nx-rail-track">
        @foreach($railProducts as $product)
            @include('product.product_card', ['product' => $product, 'eager' => $loop->index < ($railEager ?? 0)])
        @endforeach
    </div>

    {{-- دکمه‌ها از همان ابتدا در HTML هستند و absolute جانمایی می‌شوند، پس نه
         منتظر جاوااسکریپت می‌مانند و نه جای چیزی را عوض می‌کنند. --}}
    <button type="button" class="nx-rail-nav nx-rail-prev is-disabled" aria-label="قطعات قبلی">
        <i class="fas fa-chevron-right" aria-hidden="true"></i>
    </button>
    <button type="button" class="nx-rail-nav nx-rail-next" aria-label="قطعات بعدی">
        <i class="fas fa-chevron-left" aria-hidden="true"></i>
    </button>
</div>
