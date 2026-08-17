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
