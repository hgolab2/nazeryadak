@extends('layout.layout', [
    'title' => 'سبد خرید | ناظر یدک',
    'robots' => seo_robots_tag(false, true),
    'noBaseSchema' => true,
    'bodyClass' => count(session('cart', [])) ? 'has-actionbar' : '',
])
@section('main_content')
<main>
    <div class="container">
        <div class="row mt-3 mb-2">
            <div class="col-12">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/" class="breadcrumb-custom">ناظر یدک</a></li>
                    <li class="breadcrumb-item"><a href="/shop" class="breadcrumb-custom">فروشگاه</a></li>
                    <li class="breadcrumb-item"><span class="breadcrumb-custom">سبد خرید</span></li>
                </ul>
            </div>
        </div>

        @php
            $cart = session('cart', []);
            // آستانه‌ها از همان تنظیماتی خوانده می‌شوند که هزینه‌ی واقعی ارسال را
            // حساب می‌کند؛ قبلا اینجا عدد ثابت نوشته شده بود و با محاسبه‌ی نهایی
            // ده برابر اختلاف داشت
            $shippingRules = getShippingRules();
            $cartSum = array_sum(array_map(fn($i) => ($i['price'] ?? 0) * ($i['quantity'] ?? 1), $cart));
            // قطعات بدنه و شاسی مبلغ اعلامی ندارند و در جمع کل حساب نمی‌شوند؛
            // مبلغشان تلفنی اعلام و به فاکتور نهایی اضافه می‌شود.
            $hasContactPriceItem = collect($cart)->contains(fn($i) => !empty($i['contact_price']));
        @endphp

        @if(count($cart) == 0)
            {{-- سبد خالی --}}
            <div class="cart-content text-center py-5">
                <div style="width:100px; height:100px; background:var(--primary-lighter); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
                    <i class="fas fa-shopping-cart" style="font-size:2.5rem; color:var(--primary); opacity:.5;"></i>
                </div>
                <h1 style="font-weight:700; margin-bottom:8px; font-size:1.1rem;">سبد خرید شما خالی است</h1>
                <p class="font-13 text-muted mb-4">می‌توانید از فروشگاه قطعات مورد نیاز خودرو خود را انتخاب کنید.</p>
                <a href="/shop" class="btn btn-info px-4">
                    <i class="fas fa-store me-1"></i>
                    رفتن به فروشگاه
                </a>
            </div>
        @else
            <div class="row">
                {{-- لیست آیتم‌ها --}}
                <div class="col-lg-8">
                    <div class="cart-content">
                        <div class="d-flex align-items-center gap-2 px-3 pb-3 mb-2 border-bottom">
                            <i class="fas fa-shopping-cart" style="color:var(--primary);"></i>
                            <h1 class="mb-0" style="font-size:1rem; font-weight:700;">
                                سبد خرید شما
                                <span class="font-12 text-muted fw-normal">
                                    ({{ toPersianNumbers(count($cart)) }} قطعه)
                                </span>
                            </h1>
                        </div>

                        @foreach($cart as $id => $item)
                        @php $itemContactPrice = !empty($item['contact_price']); @endphp
                        <div class="shopping-cart-item" data-id="{{ $id }}">
                            <div class="row align-items-center">
                                {{-- تصویر --}}
                                <div class="col-lg-2 col-3">
                                    <a href="{{ $item['url'] ?? '#' }}" class="d-block">
                                        @if(!empty($item['image']))
                                        <img src="{{ $item['image'] }}" class="img-fluid" style="border-radius:var(--radius-sm); max-height:100px; object-fit:contain; display:block; margin:0 auto;" alt="{{ $item['title'] ?? '' }}">
                                        @else
                                        <div class="d-flex align-items-center justify-content-center" style="height:80px; background:var(--bg-body); border-radius:var(--radius-sm);">
                                            <i class="fas fa-image" style="font-size:1.5rem; color:#ddd;"></i>
                                        </div>
                                        @endif
                                    </a>
                                </div>
                                {{-- عنوان و قیمت واحد --}}
                                <div class="col-lg-4 col-9">
                                    <a href="{{ $item['url'] ?? '#' }}" class="shopping-cart-title d-block mb-1" style="line-height:1.8;">{{ $item['title'] ?? 'بدون عنوان' }}</a>
                                    <span class="font-12 text-muted">
                                        <i class="fas fa-store me-1"></i> فروشنده: ناظر یدک
                                    </span>
                                </div>
                                {{-- تعداد --}}
                                <div class="col-lg-3 col-6 mt-2 mt-lg-0">
                                    <div class="d-flex align-items-center gap-1">
                                        <button class="cart-qty-plus btn-qty" type="button" data-id="{{ $id }}" style="background:var(--primary); color:#fff; border:none; border-radius:6px; width:32px; height:32px; font-size:1rem; cursor:pointer;">+</button>
                                        <input type="text" name="qty" class="qty form-control text-center" value="{{ $item['quantity'] ?? 1 }}" readonly style="width:42px; height:32px; border-radius:6px; font-size:.9rem;">
                                        <button class="cart-qty-minus btn-qty" type="button" data-id="{{ $id }}" style="background:var(--border-color); color:var(--text-dark); border:none; border-radius:6px; width:32px; height:32px; font-size:1rem; cursor:pointer;">−</button>
                                    </div>
                                    @if($itemContactPrice)
                                        <p class="font-12 mt-1 mb-0">
                                            <x-contact-price-link class="nx-contact-call"
                                                                  label="قیمت واحد: استعلام تلفنی"
                                                                  :show-phone="true" />
                                        </p>
                                    @else
                                        <p class="font-12 text-muted mt-1 mb-0">قیمت واحد:
                                            <span class="item-unit-price">{{ toPersianNumbers(number_format($item['price'] ?? 0)) }}</span> تومان
                                        </p>

                                        {{-- وضعیت عمده‌ی همین ردیف: یا اعمال شده، یا فاصله‌ی
                                             باقی‌مانده تا اعمال‌شدنش گفته می‌شود --}}
                                        @if(!empty($item['wholesale_min_qty']))
                                            @php
                                                $wsMin  = (int) $item['wholesale_min_qty'];
                                                $wsLeft = max(0, $wsMin - (int) ($item['quantity'] ?? 1));
                                            @endphp
                                            <p class="font-12 mt-1 mb-0 item-wholesale-note"
                                               data-min-qty="{{ $wsMin }}"
                                               data-price="{{ (int) $item['wholesale_price'] }}"
                                               style="color:{{ $wsLeft ? '#8a6100' : '#17a566' }};">
                                                @if($wsLeft)
                                                    <i class="fas fa-boxes-stacked me-1"></i>
                                                    {{ toPersianNumbers($wsLeft, false) }} عدد دیگر تا قیمت عمده
                                                    ({{ toPersianNumbers(number_format((int) $item['wholesale_price'])) }} تومان)
                                                @else
                                                    <i class="fas fa-check-circle me-1"></i> قیمت عمده اعمال شد
                                                @endif
                                            </p>
                                        @endif
                                    @endif
                                </div>
                                {{-- قیمت کل و حذف --}}
                                <div class="col-lg-3 col-6 mt-2 mt-lg-0 text-end">
                                    @if($itemContactPrice)
                                        <p class="mb-1" style="font-size:.85rem;">
                                            <x-contact-price-link class="nx-contact-call is-contact-price" />
                                        </p>
                                    @else
                                        <p class="mb-1" style="font-weight:700; color:var(--primary); font-size:.95rem;">
                                            <span class="item-subtotal">{{ toPersianNumbers(number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1))) }}</span>
                                            <small class="font-12 fw-normal">تومان</small>
                                        </p>
                                    @endif
                                    <button class="delete-item btn btn-sm px-2 py-1" data-id="{{ $id }}" style="border:1px solid var(--border-color); color:var(--text-light); border-radius:6px; font-size:.78rem; cursor:pointer;">
                                        <i class="fa fa-trash me-1"></i> حذف
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    {{-- لینک بازگشت --}}
                    <div class="mt-2 mb-4">
                        <a href="/shop" class="font-13" style="color:var(--primary);">
                            <i class="fas fa-arrow-right me-1"></i> ادامه خرید
                        </a>
                    </div>
                </div>

                {{-- خلاصه سفارش --}}
                <div class="col-lg-4">
                    <div class="cart-content" style="position:sticky; top:20px;">
                        <div class="d-flex align-items-center gap-2 px-2 pb-3 mb-3 border-bottom">
                            <i class="fas fa-receipt" style="color:var(--primary);"></i>
                            <h6 class="mb-0" style="font-weight:700; font-size:.95rem;">خلاصه سفارش</h6>
                        </div>

                        <div class="d-flex justify-content-between py-2 px-2 font-13">
                            <span class="text-muted">
                                تعداد قطعات
                                (<span class="cart-total-count">{{ toPersianNumbers(array_sum(array_map(fn($i) => $i['quantity'] ?? 1, $cart))) }}</span>)
                            </span>
                            <span style="font-weight:600;">
                                {{-- واحد بیرون از span است تا به‌روزرسانی ای‌جکس «تومان تومان» ننویسد --}}
                                <span class="cart-total-sum">{{ toPersianNumbers(number_format($cartSum)) }}</span> تومان
                            </span>
                        </div>

                        <div class="d-flex justify-content-between py-2 px-2 font-13 border-bottom">
                            <span class="text-muted"><i class="fas fa-truck me-1"></i> هزینه ارسال</span>
                            <span class="font-12" style="color:var(--accent);">محاسبه پس از ثبت آدرس</span>
                        </div>
                        <div class="font-12 text-muted px-2 py-1" style="line-height:1.9;">
                            <p class="mb-1">
                                <i class="fas fa-info-circle me-1" style="color:var(--primary);"></i>
                                {{ $shippingRules['local_province_name'] }}: سفارش بالای {{ toPersianNumbers($shippingRules['local_free_threshold']) }} تومان ارسال رایگان،
                                در غیر این صورت {{ toPersianNumbers($shippingRules['local_shipping_cost']) }} تومان (پیک).
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-truck-moving me-1" style="color:var(--accent);"></i>
                                سایر شهرها: بالای {{ toPersianNumbers($shippingRules['national_free_threshold']) }} تومان رایگان؛
                                زیر این مبلغ با تیپاکس ارسال می‌شود و <b>کرایه هنگام تحویل از گیرنده</b> دریافت می‌شود
                                (این مبلغ در فاکتور اینترنتی نیست).
                            </p>
                            @if($cartSum > 0 && $cartSum < $shippingRules['local_free_threshold'])
                            <p class="mb-0 mt-1" style="color:var(--primary);">
                                <i class="fas fa-gift me-1"></i>
                                تا ارسال رایگان در {{ $shippingRules['local_province_name'] }} {{ toPersianNumbers($shippingRules['local_free_threshold'] - $cartSum) }} تومان باقی مانده است.
                            </p>
                            @endif
                        </div>

                        @if($hasContactPriceItem)
                        {{-- قطعه‌ی استعلامی در سبد هست؛ کاربر باید بداند جمع زیر کامل نیست --}}
                        <div class="font-12 px-2 py-2" style="line-height:1.9; background:#fff8e1; border-radius:var(--radius-sm); color:#7a5c00;">
                            <i class="fas fa-phone-alt me-1"></i>
                            سبد شما قطعه‌ی <b>بدنه و شاسی</b> دارد که قیمتش روی سایت اعلام نمی‌شود؛
                            مبلغ آن در جمع زیر نیامده و پس از ثبت سفارش، کارشناسان ما آن را تلفنی به شما اعلام می‌کنند.
                        </div>
                        @endif

                        <div class="d-flex justify-content-between py-3 px-2">
                            <span style="font-weight:700; font-size:.95rem;">جمع کالاها <small class="font-12 fw-normal text-muted d-block">(بدون هزینه ارسال{{ $hasContactPriceItem ? ' و قطعات استعلامی' : '' }})</small></span>
                            <span style="font-weight:700; font-size:1.1rem; color:var(--primary);">
                                <span class="cart-total-sum">
                                    {{ toPersianNumbers(number_format(array_sum(array_map(fn($i) => ($i['price'] ?? 0) * ($i['quantity'] ?? 1), $cart)))) }}
                                </span>
                                <small class="font-12 fw-normal">تومان</small>
                            </span>
                        </div>

                        <a href="/order/shopping" class="btn add-cart-btn2 text-center d-block" style="font-size:.95rem; font-weight:600;">
                            <i class="fas fa-check-circle me-1"></i>
                            ادامه و ثبت سفارش
                        </a>

                        <p class="font-12 text-muted mt-3 text-center" style="line-height:1.8;">
                            <i class="fas fa-info-circle me-1"></i>
                            کالاهای سبد رزرو نشده‌اند؛ برای ثبت نهایی، مراحل بعدی را تکمیل کنید.
                        </p>

                        {{-- نشان‌های اطمینان --}}
                        <div class="border-top pt-3 mt-2">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                @if(onlinePaymentEnabled())
                                    <i class="fas fa-shield-alt font-12" style="color:var(--success);"></i>
                                    <span class="font-12 text-muted">پرداخت امن و رمزنگاری شده</span>
                                @else
                                    {{-- پرداخت آنلاین خاموش است؛ وعده‌ی «پرداخت امن» گمراه‌کننده می‌شد --}}
                                    <i class="fas fa-headset font-12" style="color:var(--success);"></i>
                                    <span class="font-12 text-muted">تأیید سفارش و هماهنگی پرداخت، تلفنی توسط کارشناس</span>
                                @endif
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-certificate font-12" style="color:var(--primary);"></i>
                                <span class="font-12 text-muted">ضمانت اصالت قطعات</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</main>

@if(count($cart) > 0)
{{-- نوار چسبان موبایل: مبلغ و دکمه‌ی ادامه بدون اسکرول تا انتهای فهرست کالاها --}}
<div class="mobile-actionbar" role="region" aria-label="ادامه‌ی خرید">
    <div class="mobile-actionbar__info">
        <span class="mobile-actionbar__label">جمع کالاها</span>
        <span class="mobile-actionbar__price">
            <span class="cart-total-sum">{{ toPersianNumbers(number_format($cartSum)) }}</span>
            <small>تومان</small>
        </span>
    </div>
    <a href="/order/shopping" class="mobile-actionbar__btn">
        <i class="fas fa-check-circle"></i> ادامه و ثبت سفارش
    </a>
</div>
@endif
@endsection
@section('js')
<script>
    var csrf = '{{ csrf_token() }}';
    function toPersianNumbers(str) {
        if (str === null || str === undefined) return '';
        str = String(str);
        const persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        return str.replace(/\d/g, d => persian[d]);
    }
    function numberWithCommas(x) {
        if (x === null || x === undefined) return '0';
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    function updateTotals(cart_total = 0, cart_count = 0){
        // واحد «تومان» در خود قالب کنار این span آمده است
        $('.cart-total-sum').text(toPersianNumbers(numberWithCommas(Number(cart_total))));
        $('.cart-total-count').text(toPersianNumbers(cart_count));
    }
    $(document).on('click', '.delete-item', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'حذف از سبد خرید',
            text: "آیا از حذف این قطعه مطمئنید؟",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'var(--primary)',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'بله، حذف شود',
            cancelButtonText: 'انصراف'
        }).then(result => {
            if(result.value){
                $.post("/cart/remove", { id: id, _token: csrf }, function(res){
                    if(res.status === 'success'){
                        $('.shopping-cart-item[data-id="'+id+'"]').fadeOut(300, function(){ $(this).remove(); });
                        updateTotals(res.cart_total, res.items_count);
                        toast.fire({ icon:'success', title:'قطعه از سبد حذف شد' });
                        if(res.cart_count == 0) setTimeout(()=> location.reload(), 500);
                    }
                }).fail(function(){
                    Swal.fire({ icon:'error', text:'خطا در حذف' });
                });
            }
        });
    });
    /* قیمت واحد با تعداد عوض می‌شود (عمده/تکی)، پس بعد از هر کم و زیاد کردن
       باید هم قیمت واحد و هم یادداشت عمده‌ی همان ردیف تازه‌سازی شود */
    function applyRowUpdate(id, res) {
        var row = $('.shopping-cart-item[data-id="'+id+'"]');
        row.find('.qty').val(res.item_quantity);
        row.find('.item-subtotal').text(toPersianNumbers(numberWithCommas(res.item_subtotal)));
        row.find('.item-unit-price').text(toPersianNumbers(numberWithCommas(res.item_unit_price)));

        var note = row.find('.item-wholesale-note');
        if (note.length) {
            var minQty = parseInt(note.data('min-qty'), 10) || 0;
            var wsPrice = parseInt(note.data('price'), 10) || 0;
            var left = Math.max(0, minQty - (parseInt(res.item_quantity, 10) || 0));

            if (left) {
                note.css('color', '#8a6100').html(
                    '<i class="fas fa-boxes-stacked me-1"></i> ' +
                    toPersianNumbers(left) + ' عدد دیگر تا قیمت عمده (' +
                    toPersianNumbers(numberWithCommas(wsPrice)) + ' تومان)'
                );
            } else {
                note.css('color', '#17a566').html('<i class="fas fa-check-circle me-1"></i> قیمت عمده اعمال شد');
            }
        }

        updateTotals(res.cart_total, res.items_count);
    }

    $(document).on('click', '.cart-qty-plus', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        $.post("/cart/increase", { id: id, _token: csrf }, function(res) {
            if(res.status === 'success') applyRowUpdate(id, res);
        });
    });
    $(document).on('click', '.cart-qty-minus', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        $.post("/cart/decrease", { id: id, _token: csrf }, function(res) {
            if(res.status === 'success') applyRowUpdate(id, res);
        });
    });
</script>
@endsection
