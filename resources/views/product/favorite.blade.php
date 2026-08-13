@extends('layout.layout', ['title' => 'علاقه‌مندی‌ها | ناظر یدک', 'robots' => seo_robots_tag(false, true), 'noBaseSchema' => true])
@section('main_content')
<main class="nx-account">
    <div class="nx-wrap">
        <nav class="nx-breadcrumb" aria-label="مسیر صفحه">
            <a href="/">ناظر یدک</a>
            <i class="fas fa-chevron-left"></i>
            <b>علاقه‌مندی‌ها</b>
        </nav>

        <div class="nx-account-layout">
            @include('layout.sidebar', ['menu' => 'favourits'])

            <div class="nx-account-main">
                <section class="nx-card">
                    <div class="nx-card-head">
                        <h2><i class="fas fa-heart"></i> قطعات مورد علاقه</h2>
                        @if($products->count() > 0)
                            <span class="nx-order-date">{{ toPersianNumbers($products->count(), false) }} قطعه</span>
                        @endif
                    </div>

                    @if($products->count() > 0)
                        <div class="nx-items">
                            @foreach ($products as $product)
                                <div class="nx-item" id="fav{{ $product->id }}">
                                    <a href="{{ $product->url() }}" class="nx-item-thumb">
                                        <img src="{{ $product->image() }}" alt="{{ $product->title }}" loading="lazy"
                                             onerror="this.onerror=null;this.src='/images/no-image.svg';">
                                    </a>
                                    <div class="nx-item-info">
                                        <a href="{{ $product->url() }}">{{ $product->title }}</a>
                                        <div class="nx-item-meta">
                                            @if($product->sku)<bdi>{{ $product->sku }}</bdi>@endif
                                            @if($product->car_model) — {{ $product->car_model }} @endif
                                        </div>
                                        <div style="display:flex; gap:8px; margin-top:10px; flex-wrap:wrap;">
                                            <button class="nx-btn-red add-cart-btn3" data-id="{{ $product->id }}"
                                                    style="height:34px; padding:0 14px; font-size:12px;">
                                                <i class="fa fa-cart-plus"></i> سبد خرید
                                            </button>
                                            <button class="nx-btn-ghost" onclick="removeFavorite({{ $product->id }})"
                                                    style="height:34px; padding:0 14px; font-size:12px;">
                                                <i class="fa fa-trash"></i> حذف
                                            </button>
                                        </div>
                                    </div>
                                    @if($product->isContactPrice())
                                        {{-- قطعات بدنه و شاسی قیمت اعلامی ندارند --}}
                                        <div class="nx-item-price" style="font-size:12px; color:var(--accent, #ef394e);">
                                            <i class="fas fa-phone-alt me-1"></i>{{ contactPriceLabel() }}
                                        </div>
                                    @elseif($product->price)
                                        <div class="nx-item-price">
                                            {{ toPersianNumbers(number_format($product->price)) }}
                                            <small style="font-size:11px; color:var(--nx-muted,#81858b);">تومان</small>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="nx-empty-state">
                            <i class="fas fa-heart"></i>
                            <p>هنوز قطعه‌ای به علاقه‌مندی‌ها اضافه نکرده‌اید.</p>
                            <a href="/shop" class="nx-btn-red"><i class="fas fa-store"></i> رفتن به فروشگاه</a>
                        </div>
                    @endif
                </section>
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
            Swal.fire({ icon: 'error', text: 'خطا در افزودن به سبد خرید', confirmButtonColor: '#ef4056' });
        }
    });
});
</script>
@endsection
