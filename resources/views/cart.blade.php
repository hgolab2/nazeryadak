@extends('layout.layout', [
    'title' => "سبد خرید"
])
@section('main_content')

<main><!-- start main -->
    <div class="container">
        <div class="row">

            <div class="col-lg-9"><!-- start cart content -->
                <div class="cart-content">
                    <div class="title">
                        <h4> سبد خرید </h4>
                    </div>

                    @if(empty($cart) || count($cart) == 0)
                        <p class="text-center my-5">سبد خرید شما خالی است.</p>
                    @else

                        @foreach($cart as $id => $item)
                        <div class="row shopping-cart-item" data-id="{{ $id }}"><!-- start shopping cart item -->
                            <div class="col-lg-2 col-md-3">
                               <a href="{{ $item['url'] }}" class="d-block">
                                   <img src="{{ $item['image'] }}" class="img-fluid mb-3">
                               </a>
                            </div>
                            <div class="col-lg-5 col-md-6">
                                <a href="{{ $item['url'] }}" class="shopping-cart-title">{{ $item['title'] }}</a>
                                <p class="shopping-cart-detail"> گارانتی : {{ $item['warranty'] ?? '—' }} </p>
                                <p class="shopping-cart-detail">قیمت واحد : {{ toPersianNumbers(number_format($item['price'])) }} تومان</p>
                            </div>
                            <div class="col-lg-3 col-md-3">
                                <div class="button-container d-flex justify-content-start align-items-start mb-3">
                                    <button class="cart-qty-plus btn-qty" type="button" data-id="{{ $id }}" value="+">+</button>
                                    <input type="text" name="qty" min="0" class="qty form-control text-center" value="{{ $item['qty'] }}" readonly>
                                    <button class="cart-qty-minus btn-qty" type="button" data-id="{{ $id }}" value="-">-</button>
                                </div>
                                <p class="shopping-cart-detail">قیمت کل : <span class="item-subtotal">{{ toPersianNumbers(number_format($item['price'] * $item['qty'])) }}</span> تومان</p>
                            </div>
                            <div class="col-lg-2 col-md-12 d-flex justify-content-center align-items-center">
                                <i class="fa fa-trash delete-icon delete-item" data-id="{{ $id }}"></i>
                            </div>
                        </div><!-- end shopping cart item -->
                        @endforeach

                    @endif

                </div>
            </div><!-- end cart content -->

            <div class="col-lg-3"><!-- start cart box -->
                <div class="cart-content">
                    <div class="product-seller-row">
                        <span>فروشنده :</span>
                        <span>ناظر یدک</span>
                    </div>
                    <div class="product-seller-row">
                        <span> مبلغ کل ({{ toPersianNumbers($qty ?? 0) }} کالا) :</span>
                        <span id="cart-total">{{ toPersianNumbers(number_format($total ?? 0)) }} تومان </span>
                    </div>
                    <div class="product-seller-row">
                        <span>هزینه ارسال :</span>
                        <span>وابسته به آدرس</span>
                    </div>
                    <div class="product-seller-row">
                        <span>نیک کلاب :</span>
                        <span id="nikclub"> {{ toPersianNumbers((($qty ?? 0) * 75)) }}+ امتیاز </span>
                    </div>
                    <a href="/checkout" class="btn add-cart-btn">ادامه و ثبت سفارش</a>
                    <p class="font-12 text-muted mt-3 line-height text-center"> کالاهای موجود در سبد شما ثبت و رزرو نشده‌اند، برای ثبت سفارش مراحل بعدی را تکمیل کنید. </p>
                </div>
            </div><!-- end cart box -->

        </div>
    </div>
</main><!-- end main -->

@endsection

@section('js')
<script>
    // CSRF token for ajax
    var csrf = '{{ csrf_token() }}';

    // Helper: convert english digits to persian digits
    function toPersianNumbers(str) {
        if (str === null || str === undefined) return '';
        str = String(str);
        const persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        return str.replace(/\d/g, function(d){ return persian[d]; });
    }

    // Helper: format number with thousands separator (en)
    function numberWithCommas(x) {
        if (x === null || x === undefined) return '0';
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // Update cart total and nikclub display
    function updateTotals(cart_total, cart_count) {
        $('#cart-total').text(toPersianNumbers(numberWithCommas(cart_total)) + ' تومان');
        $('#nikclub').text(toPersianNumbers(numberWithCommas(cart_count * 75)) + '+ امتیاز');
    }

    // Increase qty
    $(document).on('click', '.cart-qty-plus', function (e) {
        e.preventDefault();
        var id = $(this).data('id');

        $.post("/cart/increase", { id: id, _token: csrf }, function(res) {
            if(res.status === 'success') {
                // update qty input
                var row = $('.shopping-cart-item[data-id="'+id+'"]');
                row.find('.qty').val(res.item_qty);
                // update item subtotal
                row.find('.item-subtotal').text(toPersianNumbers(numberWithCommas(res.item_subtotal)));
                // update cart total
                updateTotals(res.cart_total.replace(/,/g,''), res.cart_count);
            }
        }).fail(function(){
            Swal.fire({ icon: 'error', text: 'خطا در بروزرسانی تعداد' });
        });
    });

    // Decrease qty
    $(document).on('click', '.cart-qty-minus', function (e) {
        e.preventDefault();
        var id = $(this).data('id');

        $.post("/cart/decrease", { id: id, _token: csrf }, function(res) {
            if(res.status === 'success') {
                var row = $('.shopping-cart-item[data-id="'+id+'"]');
                row.find('.qty').val(res.item_qty);
                row.find('.item-subtotal').text(toPersianNumbers(numberWithCommas(res.item_subtotal)));
                updateTotals(res.cart_total.replace(/,/g,''), res.cart_count);
            }
        }).fail(function(){
            Swal.fire({ icon: 'error', text: 'خطا در بروزرسانی تعداد' });
        });
    });

    // Delete item
    $(document).on('click', '.delete-item', function (e) {
        e.preventDefault();
        var id = $(this).data('id');

        Swal.fire({
            title: 'آیا مطمئن هستید؟',
            text: "این آیتم از سبد حذف خواهد شد.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'بله حذف کن',
            cancelButtonText: 'انصراف'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post("/cart/delete", { id: id, _token: csrf }, function(res) {
                    if(res.status === 'success') {
                        // remove row from DOM
                        $('.shopping-cart-item[data-id="'+id+'"]').remove();
                        updateTotals(res.cart_total.replace(/,/g,''), res.cart_count);
                        Swal.fire({ icon: 'success', text: 'آیتم حذف شد', timer: 1000, showConfirmButton:false });
                    }
                }).fail(function(){
                    Swal.fire({ icon: 'error', text: 'خطا در حذف آیتم' });
                });
            }
        });
    });

    // on document ready ensure totals are correct (in case server formatting)
    $(function(){
        // nothing else needed — values are rendered server-side initially
    });
</script>
@endsection
