@props([
    'src',
    'alt' => '',
    'width' => null,
    'height' => null,
    'lazy' => true,
    'priority' => false,
])

@php
    /*
    | تصویر با نسخه‌ی WebP.
    |
    | اگر فایل .webp کنار تصویر اصلی موجود باشد، مرورگرهای مدرن آن را
    | می‌گیرند (تا ۷۰٪ سبک‌تر) و بقیه به همان jpg/png برمی‌گردند.
    | width/height همیشه ست می‌شوند تا چیدمان صفحه هنگام بارگذاری نپرد (CLS).
    */
    $webp = webp_variant($src);
@endphp

@if($webp)
<picture>
    <source srcset="{{ $webp }}" type="image/webp">
    <img src="{{ $src }}"
         alt="{{ $alt }}"
         @if($width) width="{{ $width }}" @endif
         @if($height) height="{{ $height }}" @endif
         @if($priority) fetchpriority="high" @elseif($lazy) loading="lazy" @endif
         decoding="async"
         {{ $attributes }}>
</picture>
@else
<img src="{{ $src }}"
     alt="{{ $alt }}"
     @if($width) width="{{ $width }}" @endif
     @if($height) height="{{ $height }}" @endif
     @if($priority) fetchpriority="high" @elseif($lazy) loading="lazy" @endif
     decoding="async"
     {{ $attributes }}>
@endif
