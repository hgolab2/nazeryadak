@extends('layout.layout', ['title' => 'سفارش‌های من | ناظر یدک'])
@section('main_content')
<main>
    <div class="container">
        <div class="row mt-3 mb-2">
            <div class="col-12">
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/" class="breadcrumb-custom">ناظر یدک</a></li>
                    <li class="breadcrumb-item"><span class="breadcrumb-custom">سفارش‌های من</span></li>
                </ul>
            </div>
        </div>
        <div class="row">
            @include('layout.sidebar', ['menu' => 'orders'])
            <div class="col-lg-9">
                <div class="cart-content p-3">
                    <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom">
                        <i class="fas fa-box" style="color:var(--primary);"></i>
                        <h6 class="mb-0 font-13 fw-bold">سفارش‌های من</h6>
                    </div>
                    @if($orders->count() > 0)
                    <div class="table-responsive">
                        <table class="text-center table table-bordered font-13 mb-0" style="border-color:var(--border-color);">
                            <thead style="background:var(--primary); color:#fff;">
                                <tr>
                                    <td class="py-2">شماره سفارش</td>
                                    <td class="py-2">تاریخ</td>
                                    <td class="py-2">مبلغ</td>
                                    <td class="py-2">هزینه ارسال</td>
                                    <td class="py-2">مبلغ نهایی</td>
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
                                <td class="py-2 fw-bold">{{ toPersianNumbers(number_format($order->final_price)) }}</td>
                                <td class="py-2"><span class="badge" style="background:var(--primary-lighter); color:var(--primary);">{{ $order->status() }}</span></td>
                                <td class="py-2">
                                    <a href="/profile/orderDetail/{{ $order->id }}" style="color:var(--primary);" title="مشاهده جزئیات">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 d-flex justify-content-center">
                        {{ $orders->links() }}
                    </div>
                    @else
                    <div class="text-center py-5">
                        <div style="width:80px; height:80px; background:var(--primary-lighter); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 15px;">
                            <i class="fas fa-box-open" style="font-size:2rem; color:var(--primary); opacity:.5;"></i>
                        </div>
                        <p class="font-13 text-muted mb-3">هنوز سفارشی ثبت نکرده‌اید.</p>
                        <a href="/shop" class="btn btn-info px-4 font-13">مشاهده فروشگاه</a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
