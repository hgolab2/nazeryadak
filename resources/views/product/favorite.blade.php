@extends('layout.layout', ['title' => 'علاقه‌مندی‌ها | ناظر یدک', 'robots' => seo_robots_tag(false, true), 'noBaseSchema' => true])
@section('main_content')
<main>
    <div class="container">
        <div class="row mt-3 mb-2">
            <div class="col-12">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/" class="breadcrumb-custom">ناظر یدک</a></li>
                    <li class="breadcrumb-item"><span class="breadcrumb-custom">علاقه‌مندی‌ها</span></li>
                </ul>
            </div>
        </div>
        <div class="row">
            @include('layout.sidebar', ['menu' => 'favourits'])
            <div class="col-lg-9">
                <div class="cart-content p-3">
                    <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                        <i class="fas fa-heart" style="color:var(--danger);"></i>
                        <h6 class="mb-0 font-13 fw-bold">قطعات مورد علاقه</h6>
                    </div>
                    @if($products->count() > 0)
                    <div class="row">
                        @foreach ($products as $product)
                        <div class="col-md-6 mb-3" id="fav{{ $product->id }}">
                            <div class="d-flex gap-3 p-3" style="border:1px solid var(--border-color); border-radius:var(--radius-sm);">
                                <a href="{{ $product->url() }}" class="flex-shrink-0">
                                    @if($product->image())
                                    <img src="{{ $product->image() }}" style="width:80px; height:80px; object-fit:contain; border-radius:6px;" alt="">
                                    @else
                                    <div style="width:80px; height:80px; background:var(--bg-body); border-radius:6px; display:flex; align-items:center; justify-content:center;">
                                        <i class="fas fa-image" style="font-size:1.5rem; color:#ddd;"></i>
                                    </div>
                                    @endif
                                </a>
                                <div class="flex-grow-1 d-flex flex-column">
                                    <a href="{{ $product->url() }}" class="font-13 d-block mb-2" style="color:var(--text-dark); line-height:1.8;">
                                        {{ $product->title }}
                                    </a>
                                    <div class="d-flex justify-content-between align-items-center mt-auto">
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-sm add-cart-btn3 px-2 py-1" data-id="{{ $product->id }}" style="background:var(--primary); color:#fff; border:none; border-radius:6px; font-size:.75rem;">
                                                <i class="fa fa-cart-plus me-1"></i> سبد خرید
                                            </button>
                                            <button class="btn btn-sm px-2 py-1" onclick="removeFavorite({{ $product->id }})" style="border:1px solid var(--border-color); color:var(--text-light); border-radius:6px; font-size:.75rem;">
                                                <i class="fa fa-trash me-1"></i> حذف
                                            </button>
                                        </div>
                                        @if($product->price)
                                        <span class="font-12 fw-bold" style="color:var(--primary);">{{ toPersianNumbers(number_format($product->price)) }} <small>تومان</small></span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-5">
                        <div style="width:80px; height:80px; background:var(--primary-lighter); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 15px;">
                            <i class="fas fa-heart" style="font-size:2rem; color:var(--primary); opacity:.4;"></i>
                        </div>
                        <p class="font-13 text-muted mb-3">هنوز قطعه‌ای به علاقه‌مندی‌ها اضافه نکرده‌اید.</p>
                        <a href="/shop" class="btn btn-info px-4 font-13">مشاهده فروشگاه</a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
@section('js')
<script>
function removeFavorite(id) {
    @if(\Auth::guard('customer')->user())
    $.get("/products/favorite/" + id, function (data) {
        if (data.result != 1) {
            $('#fav' + id).fadeOut(300, function () { $(this).remove(); });
            toast.fire({ icon: 'info', title: 'از علاقه‌مندی‌ها حذف شد' });
        }
    });
    @else
        window.location = "/login";
    @endif
}
$(document).on('click', '.add-cart-btn3', function (e) {
    e.preventDefault();
    let productId = $(this).data('id');
    $.ajax({
        url: "{{ route('cart.add') }}",
        type: "POST",
        data: { product_id: productId, _token: "{{ csrf_token() }}" },
        success: function (response) {
            if (response.status === "success") {
                toast.fire({ icon: 'success', title: 'به سبد خرید اضافه شد' });
            }
        },
        error: function () {
            Swal.fire({ icon: 'error', text: 'خطا در افزودن به سبد خرید', confirmButtonColor: 'var(--primary)' });
        }
    });
});
</script>
@endsection
