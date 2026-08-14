@props([
    'label' => null,
    'showPhone' => false,
])

@php
    /*
    | برچسب «لطفا تماس بگیرید» قطعات استعلامی، به‌صورت لینک tel:.
    |
    | قبلا این برچسب یک متن ساده بود و کاربر موبایل باید شماره را از جای
    | دیگری پیدا می‌کرد؛ حالا با یک لمس، شماره‌گیر گوشی باز می‌شود.
    | شماره از همان تنظیمات پنل خوانده می‌شود، پس اگر مدیر عوض کند
    | همه‌ی این لینک‌ها با هم عوض می‌شوند.
    */
    $phone        = shopContactPhone();
    $phoneDisplay = shopContactPhoneDisplay();
    $text         = $label ?? contactPriceLabel();
@endphp

<a href="tel:{{ $phone }}"
   rel="nofollow"
   title="تماس با {{ $phoneDisplay }} برای استعلام قیمت"
   aria-label="{{ $text }} — تماس با {{ $phoneDisplay }}"
   {{ $attributes->merge(['class' => 'nx-contact-call']) }}>
    <i class="fas fa-phone-alt" aria-hidden="true"></i>
    <span>{{ $text }}</span>
    @if($showPhone)
        <bdi class="nx-contact-call-num">{{ $phoneDisplay }}</bdi>
    @endif
</a>
