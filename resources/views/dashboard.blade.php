@extends('layout.layout', [
    'title' => "داشبورد | ناظر یدک"
])
@section('main_content')
<main>
    <div class="container">
        <div class="row mt-3 mb-2">
            <div class="col-12">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/" class="breadcrumb-custom">ناظر یدک</a></li>
                    <li class="breadcrumb-item"><span class="breadcrumb-custom">داشبورد</span></li>
                </ul>
            </div>
        </div>
        <div class="row">
            @include('layout.sidebar', ['menu' => 'dashboard'])
            <div class="col-lg-9">
                {{-- اطلاعات شخصی + علاقه‌مندی --}}
                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <div class="cart-content p-3 h-100">
                            <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                                <i class="fas fa-user-circle" style="color:var(--primary);"></i>
                                <h6 class="mb-0 font-13 fw-bold">اطلاعات شخصی</h6>
                            </div>
                            @if($address)
                            <div class="row font-13" style="line-height:2.2;">
                                <div class="col-6 mb-2">
                                    <span class="text-muted">نام:</span><br>
                                    <span class="fw-bold">{{ $customer->fullname() ?: '—' }}</span>
                                </div>
                                <div class="col-6 mb-2">
                                    <span class="text-muted">تلفن:</span><br>
                                    <span class="fw-bold">{{ $address->receiver_phone ?? '—' }}</span>
                                </div>
                                <div class="col-12">
                                    <span class="text-muted">آدرس:</span><br>
                                    <span>{{ $address->province?->name }} — {{ $address->city }} — {{ $address->address_line }}</span>
                                </div>
                            </div>
                            @else
                            <p class="font-13 text-muted text-center py-3">اطلاعات آدرس ثبت نشده است.</p>
                            @endif
                            <a href="/profile/info" class="btn btn-sm mt-3 font-12" style="border:1px solid var(--primary); color:var(--primary); border-radius:var(--radius-sm);">
                                <i class="fa fa-edit me-1"></i> ویرایش اطلاعات
                            </a>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <div class="cart-content p-3 h-100">
                            <div class="d-flex align-items-center justify-content-between pb-2 mb-3 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-heart" style="color:var(--danger);"></i>
                                    <h6 class="mb-0 font-13 fw-bold">علاقه‌مندی‌ها</h6>
                                </div>
                                <a href="/favorite" class="font-12" style="color:var(--primary);">مشاهده همه</a>
                            </div>
                            @forelse ($favorites as $product)
                            <div class="d-flex align-items-center gap-2 py-2 {{ !$loop->last ? 'border-bottom' : '' }}" id="fav{{ $product->id }}">
                                <a href="{{ $product->url() }}">
                                    @if($product->image())
                                    <img src="{{ $product->image() }}" style="width:50px; height:50px; object-fit:contain; border-radius:6px;" alt="">
                                    @else
                                    <div style="width:50px; height:50px; background:var(--bg-body); border-radius:6px; display:flex; align-items:center; justify-content:center;">
                                        <i class="fas fa-image" style="color:#ddd;"></i>
                                    </div>
                                    @endif
                                </a>
                                <div class="flex-grow-1">
                                    <a href="{{ $product->url() }}" class="font-12 d-block" style="color:var(--text-dark); line-height:1.7;">{{ $product->title }}</a>
                                </div>
                            </div>
                            @empty
                            <p class="font-13 text-muted text-center py-3">هنوز قطعه‌ای به علاقه‌مندی‌ها اضافه نکرده‌اید.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- آخرین سفارش‌ها --}}
                <div class="cart-content p-3">
                    <div class="d-flex align-items-center justify-content-between pb-2 mb-3 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-box" style="color:var(--accent);"></i>
                            <h6 class="mb-0 font-13 fw-bold">آخرین سفارش‌ها</h6>
                        </div>
                        <a href="/profile/orders" class="font-12" style="color:var(--primary);">مشاهده همه</a>
                    </div>
                    @if($orders->count() > 0)
                    <div class="table-responsive">
                        <table class="text-center table table-bordered font-13 mb-0" style="border-color:var(--border-color);">
                            <thead style="background:var(--primary); color:#fff;">
                                <tr>
                                    <td class="py-2">شماره</td>
                                    <td class="py-2">تاریخ</td>
                                    <td class="py-2">مبلغ</td>
                                    <td class="py-2">ارسال</td>
                                    <td class="py-2">وضعیت</td>
                                    <td class="py-2">جزئیات</td>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach ($orders as $order)
                                <tr>
                                    <td class="py-2">{{ $order->id }}</td>
                                    <td class="py-2">{{ gregorian_to_jalali2($order->created_at) }}</td>
                                    <td class="py-2">{{ toPersianNumbers(number_format($order->total_price)) }}</td>
                                    <td class="py-2">{{ toPersianNumbers(number_format($order->shipping_price)) }}</td>
                                    <td class="py-2"><span class="badge" style="background:var(--primary-lighter); color:var(--primary);">{{ $order->status() }}</span></td>
                                    <td class="py-2"><a href="/profile/orderDetail/{{ $order->id }}" style="color:var(--primary);"><i class="fa fa-eye"></i></a></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <p class="font-13 text-muted text-center py-3">هنوز سفارشی ثبت نکرده‌اید.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
