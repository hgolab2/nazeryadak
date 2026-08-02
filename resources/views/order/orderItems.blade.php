@extends('layout.layout', ['title' => 'جزئیات سفارش #' . $order->id . ' | ناظر یدک'])
@section('main_content')
<main>
    <div class="container">
        <div class="row mt-3 mb-2">
            <div class="col-12">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/" class="breadcrumb-custom">ناظر یدک</a></li>
                    <li class="breadcrumb-item"><a href="/profile/orders" class="breadcrumb-custom">سفارش‌ها</a></li>
                    <li class="breadcrumb-item"><span class="breadcrumb-custom">سفارش #{{ $order->id }}</span></li>
                </ul>
            </div>
        </div>
        <div class="row">
            @include('layout.sidebar', ['menu' => 'orders'])
            <div class="col-lg-9">
                <div class="cart-content p-3 mb-3">
                    <div class="d-flex align-items-center justify-content-between pb-2 mb-3 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-file-invoice" style="color:var(--primary);"></i>
                            <h6 class="mb-0 font-13 fw-bold">جزئیات سفارش <span class="badge" style="background:var(--primary-lighter); color:var(--primary);">#{{ $order->id }}</span></h6>
                        </div>
                        <span class="badge py-2 px-3" style="background:var(--primary-lighter); color:var(--primary);">{{ $order->status() }}</span>
                    </div>

                    <div class="row font-13" style="line-height:2.3;">
                        <div class="col-md-6">
                            <p class="mb-1"><i class="fas fa-user me-2 text-muted" style="width:18px;"></i> تحویل‌گیرنده: <strong>{{ $order->id }}</strong></p>
                            <p class="mb-1"><i class="fas fa-phone me-2 text-muted" style="width:18px;"></i> شماره تماس: *******0912</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><i class="fas fa-truck me-2 text-muted" style="width:18px;"></i> نحوه ارسال: پست پیشتاز</p>
                            <p class="mb-1"><i class="fas fa-money-bill me-2 text-muted" style="width:18px;"></i> هزینه ارسال: {{ toPersianNumbers(number_format($order->shipping_price)) }} تومان</p>
                        </div>
                    </div>
                </div>

                <div class="cart-content p-3">
                    <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                        <i class="fas fa-cogs" style="color:var(--accent);"></i>
                        <h6 class="mb-0 font-13 fw-bold">قطعات سفارش</h6>
                    </div>
                    <div class="row">
                        @foreach ($order->items as $item)
                        <div class="col-lg-3 col-md-4 col-6 mb-3">
                            <a href="{{ $item->product?->url() }}" class="d-block">
                                <div class="card custom-card text-center p-2">
                                    @if($item->product?->image())
                                    <img src="{{ $item->product?->image() }}" class="slider-pic" alt="">
                                    @else
                                    <div class="d-flex align-items-center justify-content-center" style="height:100px;"><i class="fas fa-image" style="font-size:2rem; color:#ddd;"></i></div>
                                    @endif
                                    <div class="card-body py-2">
                                        <p class="font-12 mb-0" style="line-height:1.7;">{{ $item->product?->title }}</p>
                                    </div>
                                </div>
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
