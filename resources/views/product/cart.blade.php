@extends('layout.layout', [
    'title' => "سبد خرید | ناظر یدک"
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

        @php $cart = session('cart', []); @endphp

        @if(count($cart) == 0)
            {{-- سبد خالی --}}
            <div class="cart-content text-center py-5">
                <div style="width:100px; height:100px; background:var(--primary-lighter); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
                    <i class="fas fa-shopping-cart" style="font-size:2.5rem; color:var(--primary); opacity:.5;"></i>
                </div>
                <h5 style="font-weight:700; margin-bottom:8px;">سبد خرید شما خالی است</h5>
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
                            <h4 class="mb-0" style="font-size:1rem; font-weight:700;">
                                سبد خرید شما
                                <span class="font-12 text-muted fw-normal">
                                    ({{ toPersianNumbers(count($cart)) }} قطعه)
                                </span>
                            </h4>
                        </div>

                        @foreach($cart as $id => $item)
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
                                    <p class="font-12 text-muted mt-1 mb-0">قیمت واحد: {{ toPersianNumbers(number_format($item['price'] ?? 0)) }} تومان</p>
                                </div>
                                {{-- قیمت کل و حذف --}}
                                <div class="col-lg-3 col-6 mt-2 mt-lg-0 text-end">
                                    <p class="mb-1" style="font-weight:700; color:var(--primary); font-size:.95rem;">
                                        <span class="item-subtotal">{{ toPersianNumbers(number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1))) }}</span>
                                        <small class="font-12 fw-normal">تومان</small>
                                    </p>
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
                            <span class="cart-total-sum" style="font-weight:600;">
                                {{ toPersianNumbers(number_format(array_sum(array_map(fn($i) => ($i['price'] ?? 0) * ($i['quantity'] ?? 1), $cart)))) }} تومان
                            </span>
                        </div>

                        <div class="d-flex justify-content-between py-2 px-2 font-13 border-bottom">
                            <span class="text-muted"><i class="fas fa-truck me-1"></i> هزینه ارسال</span>
                            <span class="font-12" style="color:var(--accent);">محاسبه پس از ثبت آدرس</span>
                        </div>
                        <div class="font-12 text-muted px-2 py-1" style="line-height:1.8;">
                            <i class="fas fa-info-circle me-1" style="color:var(--primary);"></i>
                            قم: سفارش بالای ۵ میلیون تومان ارسال رایگان | سایر شهرها: بالای ۲۰ میلیون رایگان
                        </div>

                        <div class="d-flex justify-content-between py-3 px-2">
                            <span style="font-weight:700; font-size:.95rem;">مبلغ قابل پرداخت</span>
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
                                <i class="fas fa-shield-alt font-12" style="color:var(--success);"></i>
                                <span class="font-12 text-muted">پرداخت امن و رمزنگاری شده</span>
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
        $('.cart-total-sum').text(toPersianNumbers(numberWithCommas(Number(cart_total))) + ' تومان');
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
                        updateTotals(res.cart_total, res.cart_count);
                        toast.fire({ icon:'success', title:'قطعه از سبد حذف شد' });
                        if(res.cart_count == 0) setTimeout(()=> location.reload(), 500);
                    }
                }).fail(function(){
                    Swal.fire({ icon:'error', text:'خطا در حذف' });
                });
            }
        });
    });
    $(document).on('click', '.cart-qty-plus', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        $.post("/cart/increase", { id: id, _token: csrf }, function(res) {
            if(res.status === 'success') {
                var row = $('.shopping-cart-item[data-id="'+id+'"]');
                row.find('.qty').val(res.item_quantity);
                row.find('.item-subtotal').text(toPersianNumbers(numberWithCommas(res.item_subtotal)));
                updateTotals(res.cart_total, res.cart_count);
            }
        });
    });
    $(document).on('click', '.cart-qty-minus', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        $.post("/cart/decrease", { id: id, _token: csrf }, function(res) {
            if(res.status === 'success') {
                var row = $('.shopping-cart-item[data-id="'+id+'"]');
                row.find('.qty').val(res.item_quantity);
                row.find('.item-subtotal').text(toPersianNumbers(numberWithCommas(res.item_subtotal)));
                updateTotals(res.cart_total, res.cart_count);
            }
        });
    });
</script>
@endsection
