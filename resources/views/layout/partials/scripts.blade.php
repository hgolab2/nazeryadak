{{--
    اسکریپت‌های پایه‌ی سایت.

    ترتیب اجرا با حالت جداگانه یکی است، پس اسکریپت‌های inline که بعد از این
    می‌آیند همچنان به jQuery و Swal دسترسی دارند.
--}}
@if(asset_bundle_is_fresh('js'))
<script src="{{ asset_v(config('assets.js.bundle')) }}"></script>
@else
    @foreach(config('assets.js.sources', []) as $script)
<script src="{{ asset_v($script) }}"></script>
    @endforeach
@endif
