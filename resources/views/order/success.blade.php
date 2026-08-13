@extends('layout.layout', [
    'title' => $paymentStatus === 'paid' ? 'پرداخت موفق | ناظر یدک' : 'پرداخت ناموفق | ناظر یدک',
    'robots' => seo_robots_tag(false, true),
    'noBaseSchema' => true,
])
@section('main_content')
<div class="container">
    <div class="row mt-3 mb-2">
        <div class="col-12">
            <div class="cart-content py-3 px-4 mb-3">
                {{-- در پرداخت ناموفق، مرحله‌ی «اتمام خرید» هنوز طی نشده است؛
                     قبلا همه‌ی مراحل سبز نشان داده می‌شد و کاربر فکر می‌کرد سفارش نهایی شده --}}
                <ul class="checkout-steps mb-0">
                    <li class="is-completed-extra"><a href="javascript:void(0)" class="checkout-steps-active active-link-shopping">اطلاعات ارسال</a></li>
                    <li class="{{ $paymentStatus === 'paid' ? 'is-completed-extra' : 'is-completed' }}"><a href="javascript:void(0)" class="checkout-steps-active active-link-shopping">پرداخت</a></li>
                    <li class="is-active">
                        <a href="javascript:void(0)" class="{{ $paymentStatus === 'paid' ? 'checkout-steps-active active-link-shopping' : 'checkout-steps-item active-link' }}">اتمام خرید</a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
<main>
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="cart-content p-4">
                    {{-- وضعیت پرداخت --}}
                    <div class="text-center py-4">
                        @if($paymentStatus === 'paid')
                        <div style="width:80px; height:80px; background:#e8f5e9; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 15px;">
                            <i class="fas fa-check" style="font-size:2.5rem; color:var(--success);"></i>
                        </div>
                        <h5 class="fw-bold mb-2" style="color:var(--success);">پرداخت با موفقیت انجام شد</h5>
                        <p class="font-13 text-muted">سفارش <span class="badge" style="background:var(--primary-lighter); color:var(--primary);">DKC-{{ $order->id }}</span> ثبت شد و در حال پردازش است.</p>
                        @else
                        <div style="width:80px; height:80px; background:#ffebee; border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 15px;">
                            <i class="fas fa-times" style="font-size:2.5rem; color:var(--danger);"></i>
                        </div>
                        <h5 class="fw-bold mb-2" style="color:var(--danger);">پرداخت ناموفق بود</h5>
                        <p class="font-13 text-muted">در فرآیند پرداخت مشکلی پیش آمد. در صورت کسر وجه، حداکثر تا ۷۲ ساعت بازگردانده می‌شود.</p>
                        @endif
                    </div>

                    {{-- اطلاعات سفارش --}}
                    <div class="row font-13 mx-0 mt-3" style="background:#f8f9fa; border-radius:var(--radius-sm);">
                        <div class="col-md-6 py-3 {{ $paymentStatus === 'paid' ? '' : 'border-bottom' }}">
                            <span class="text-muted"><i class="fas fa-user me-1"></i> تحویل‌گیرنده:</span><br>
                            <strong>{{ optional($order->customer->address)->receiver_name ?? $order->customer?->fullName() ?? '—' }}</strong>
                        </div>
                        <div class="col-md-6 py-3">
                            <span class="text-muted"><i class="fas fa-phone me-1"></i> شماره تماس:</span><br>
                            <strong>{{ optional($order->customer->address)->receiver_phone ?? $order->customer?->phone ?? '—' }}</strong>
                        </div>
                        {{-- total_price همان مبلغی است که از کارت کسر می‌شود؛
                             قبلا final_price نمایش داده می‌شد که هزینه‌ی ارسال را کم داشت --}}
                        <div class="col-md-6 py-3 border-top">
                            <span class="text-muted"><i class="fas fa-money-bill me-1"></i> مبلغ پرداختی:</span><br>
                            <strong style="color:var(--primary);">{{ number_format($order->total_price) }} تومان</strong>
                            @if($order->shipping_price > 0)
                                <span class="d-block font-12 text-muted mt-1">
                                    شامل {{ number_format($order->shipping_price) }} تومان هزینه ارسال
                                </span>
                            @endif
                        </div>
                        <div class="col-md-6 py-3 border-top">
                            <span class="text-muted"><i class="fas fa-info-circle me-1"></i> وضعیت:</span><br>
                            <span class="badge py-1 px-2" style="background:{{ $paymentStatus === 'paid' ? '#e8f5e9' : '#ffebee' }}; color:{{ $paymentStatus === 'paid' ? 'var(--success)' : 'var(--danger)' }};">
                                {{ $order->status() }}
                            </span>
                        </div>
                        <div class="col-12 py-3 border-top">
                            <span class="text-muted"><i class="fas fa-map-marker-alt me-1"></i> آدرس:</span><br>
                            {{ optional($order->customer->address)->address_line ?? '—' }}
                        </div>
                    </div>

                    {{-- دکمه‌ها --}}
                    <div class="text-center mt-4 d-flex justify-content-center gap-3 flex-wrap">
                        @if($paymentStatus === 'paid')
                        <a href="/profile/orders" class="btn btn-info px-4 font-13">
                            <i class="fas fa-box me-1"></i> پیگیری سفارش
                        </a>
                        <a href="/shop" class="btn font-13 px-4" style="border:1px solid var(--primary); color:var(--primary); border-radius:var(--radius-sm);">
                            <i class="fas fa-store me-1"></i> ادامه خرید
                        </a>
                        @else
                        <a href="/order/payment/{{ $order->id }}" class="btn font-13 px-4" style="background:linear-gradient(135deg, var(--accent), var(--accent-light)); color:#fff; border:none; border-radius:var(--radius-sm);">
                            <i class="fas fa-redo me-1"></i> تلاش مجدد پرداخت
                        </a>
                        <a href="/contact-us" class="btn font-13 px-4" style="border:1px solid var(--border-color); color:var(--text-dark); border-radius:var(--radius-sm);">
                            <i class="fas fa-headset me-1"></i> تماس با پشتیبانی
                        </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
