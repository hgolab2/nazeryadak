{{--
    فایل‌های ظاهری سایت.

    اگر باندل ساخته و به‌روز باشد یک درخواست می‌رود، وگرنه همان تگ‌های
    جداگانه‌ی قبلی. فهرست و ترتیب در config/assets.php است.

    ?v= از زمان تغییر خود فایل می‌آید؛ بدون آن، هدر immutable در .htaccess
    نسخه‌ی قدیمی را تا یک سال در مرورگر کاربر نگه می‌داشت.
--}}
@if(asset_bundle_is_fresh('css'))
    <link rel="stylesheet" href="{{ asset_v(config('assets.css.bundle')) }}">
@else
    @foreach(config('assets.css.sources', []) as $stylesheet)
    <link rel="stylesheet" href="{{ asset_v($stylesheet) }}">
    @endforeach
@endif

{{--
    اعلان‌های @font-face فونت‌آوسام، جدا و غیرمسدودکننده.

    فایل fa-solid-900.woff2 حدود ۸۰ کیلوبایت است. وقتی داخل باندل اصلی بود،
    مرورگر همان لحظه‌ی رندر کشفش می‌کرد و هم‌زمان با تصویر اصلی صفحه دانلودش
    می‌کرد. قاعده‌های ابعاد و چیدمان آیکن‌ها در باندل مانده‌اند، پس هیچ عنصری
    بعدا جابه‌جا نمی‌شود؛ فقط خودِ شکل آیکن کمی دیرتر ظاهر می‌شود.
--}}
<link rel="stylesheet" href="{{ asset_v('/assets/fontawesome/css/fa-fonts.css') }}" media="print" onload="this.media='all';this.onload=null">
<noscript><link rel="stylesheet" href="{{ asset_v('/assets/fontawesome/css/fa-fonts.css') }}"></noscript>
