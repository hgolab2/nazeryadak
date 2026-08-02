@extends('layout.layout', ['title' => 'اطلاعات ارسال | ناظر یدک'])
@section('main_content')
<div class="container">
    <div class="row mt-3 mb-2">
        <div class="col-12">
            <div class="cart-content py-3 px-4 mb-3">
                <ul class="checkout-steps mb-0">
                    <li class="is-completed"><a href="javascript:void(0)" class="checkout-steps-active active-link-shopping">اطلاعات ارسال</a></li>
                    <li class="is-completed"><a href="javascript:void(0)" class="checkout-steps-item active-link">پرداخت</a></li>
                    <li class="is-active"><a href="javascript:void(0)" class="checkout-steps-item active-link">اتمام خرید</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>
<main>
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div class="position-relative">
                    <div class="cart-content p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-map-marker-alt" style="color:var(--primary);"></i>
                                <h6 class="mb-0 font-13 fw-bold">آدرس تحویل سفارش</h6>
                            </div>
                            <button type="button" class="btn btn-sm font-12 px-3" data-bs-toggle="modal" data-bs-target="#change-address-modal"
                                style="border:1px solid var(--primary); color:var(--primary); border-radius:var(--radius-sm);">
                                <i class="fas fa-edit me-1"></i>
                                {{ $address ? 'تغییر آدرس' : 'ثبت آدرس' }}
                            </button>
                            <div class="modal fade" id="change-address-modal">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content" style="border-radius:var(--radius);">
                                        <div class="modal-header" style="background:var(--primary); color:#fff; border-radius:var(--radius) var(--radius) 0 0;">
                                            <h6 class="modal-title font-13"><i class="fas fa-map-marker-alt me-1"></i> ویرایش آدرس</h6>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <form id="addressForm">
                                                @csrf
                                                <div class="row">
                                                    <div class="col-md-6 mb-3">
                                                        <label class="font-12 mb-1">نام و نام خانوادگی</label>
                                                        <input type="text" name="receiver_name" class="form-control" value="{{ $address->receiver_name ?? '' }}" required style="border-radius:var(--radius-sm);">
                                                    </div>
                                                    <div class="col-md-6 mb-3">
                                                        <label class="font-12 mb-1">شماره تماس گیرنده</label>
                                                        <input type="tel" name="receiver_phone" class="form-control" value="{{ $address->receiver_phone ?? '' }}" required style="border-radius:var(--radius-sm);" placeholder="09xxxxxxxxx">
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label class="font-12 mb-1">استان</label>
                                                        <select name="province_id" class="form-select" required style="border-radius:var(--radius-sm);">
                                                            @foreach ($provinces as $province)
                                                                <option value="{{ $province->id }}" {{ $address != null && $address->province_id == $province->id ? "selected" : '' }}>{{ $province->name }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label class="font-12 mb-1">شهر</label>
                                                        <input type="text" name="city" value="{{ $address->city ?? '' }}" class="form-control" required style="border-radius:var(--radius-sm);">
                                                    </div>
                                                    <div class="col-md-4 mb-3">
                                                        <label class="font-12 mb-1">کد پستی</label>
                                                        <input type="text" name="postal_code" class="form-control" value="{{ $address->postal_code ?? '' }}" style="border-radius:var(--radius-sm);">
                                                    </div>
                                                    <div class="col-12 mb-3">
                                                        <label class="font-12 mb-1">آدرس کامل</label>
                                                        <textarea name="address_line" class="form-control" rows="3" required style="border-radius:var(--radius-sm);">{{ $address->address_line ?? '' }}</textarea>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="modal-footer border-top-0 px-4 pb-4">
                                            <button type="button" id="saveAddressBtn" class="btn btn-info px-4 font-13">
                                                <i class="fas fa-check me-1"></i> ثبت آدرس
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="addressPreview">
                            @include('order.address-preview', ['address' => $address ?? null])
                        </div>
                    </div>
                    <div class="checkout-contact-badge"><i class="fa fa-check"></i></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="cart-content p-3" style="position:sticky; top:20px;">
                    <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                        <i class="fas fa-receipt" style="color:var(--primary);"></i>
                        <h6 class="mb-0 font-13 fw-bold">خلاصه سفارش</h6>
                    </div>
                    <div class="d-flex justify-content-between py-2 font-13">
                        <span class="text-muted">مبلغ محصولات</span>
                        <span>{{ number_format($order->final_price) }} تومان</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 font-13 border-bottom">
                        <span class="text-muted"><i class="fas fa-truck me-1"></i> هزینه ارسال</span>
                        <span id="shippingCost">
                            @if(isset($shipping_info))
                                @if($shipping_info['type'] === 'free')
                                    <span class="text-success fw-bold">{{ $shipping_info['label'] }}</span>
                                @elseif($shipping_info['type'] === 'tipax')
                                    <span style="color:var(--accent);">{{ $shipping_info['label'] }}</span>
                                @elseif($shipping_info['type'] === 'unknown')
                                    <span class="text-muted">{{ $shipping_info['label'] }}</span>
                                @else
                                    {{ $shipping_info['label'] }}
                                @endif
                            @else
                                {{ number_format($order->shipping_price) }} تومان
                            @endif
                        </span>
                    </div>
                    <div class="d-flex justify-content-between py-3">
                        <span class="fw-bold" style="font-size:.95rem;">مبلغ قابل پرداخت</span>
                        <span class="fw-bold" style="font-size:1.1rem; color:var(--primary);" id="finalPrice">{{ number_format($order->total_price) }} <small class="font-12 fw-normal">تومان</small></span>
                    </div>
                    @if($address)
                    <a href="/order/payment/{{ $order->id }}" id="proceedPayment" class="btn add-cart-btn2 text-center d-block font-13 fw-bold">
                        <i class="fas fa-arrow-left me-1"></i> ادامه و پرداخت
                    </a>
                    @else
                    <button type="button" class="btn text-center d-block w-100 font-13 fw-bold" disabled
                            style="background:#ccc; color:#fff; border:none; border-radius:var(--radius-sm); padding:12px;">
                        <i class="fas fa-exclamation-circle me-1"></i> ابتدا آدرس تحویل را ثبت کنید
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
@section('js')
<script>
var csrf = '{{ csrf_token() }}';
function recalcShipping() {
    $.post("{{ route('shipping.calc') }}", { _token: csrf, order_id: "{{ $order->id }}" }, function(res){
        if(res.status === 'success'){
            if(res.shipping_type === 'free'){
                $('#shippingCost').html('<span class="text-success fw-bold">' + res.shipping_label + '</span>');
            } else if(res.shipping_type === 'tipax'){
                $('#shippingCost').html('<span style="color:var(--accent);">' + res.shipping_label + '</span>');
            } else {
                $('#shippingCost').text(res.shipping_label);
            }
            $('#finalPrice').html(new Intl.NumberFormat().format(res.total_price) + ' <small class="font-12 fw-normal">تومان</small>');
        }
    });
}
$('.shipping-method-radio').on('change', recalcShipping);
$('#saveAddressBtn').click(function () {
    let btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> در حال ثبت...');
    $.ajax({
        url: "/address/save",
        method: "POST",
        data: $('#addressForm').serialize(),
        success: function (res) {
            btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i> ثبت آدرس');
            if (res.status === 'success') {
                $('#addressPreview').html(res.html);
                $('#change-address-modal').modal('hide');
                toast.fire({ icon: 'success', title: 'آدرس با موفقیت ثبت شد' });
                recalcShipping();
                let payBtn = $('#proceedPayment');
                if (payBtn.length === 0) {
                    location.reload();
                }
            }
        },
        error: function (xhr) {
            btn.prop('disabled', false).html('<i class="fas fa-check me-1"></i> ثبت آدرس');
            let msg = 'خطا در ثبت آدرس';
            if (xhr.responseJSON && xhr.responseJSON.errors) {
                msg = Object.values(xhr.responseJSON.errors).flat().join('\n');
            }
            Swal.fire({ icon: 'error', text: msg });
        }
    });
});
</script>
@endsection
