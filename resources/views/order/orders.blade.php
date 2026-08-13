@extends('layout.layout', ['title' => 'سفارش‌های من | ناظر یدک', 'robots' => seo_robots_tag(false, true), 'noBaseSchema' => true])
@section('main_content')
<main class="nx-account">
    <div class="nx-wrap">
        <nav class="nx-breadcrumb" aria-label="مسیر صفحه">
            <a href="/">ناظر یدک</a>
            <i class="fas fa-chevron-left"></i>
            <b>سفارش‌های من</b>
        </nav>

        <div class="nx-account-layout">
            @include('layout.sidebar', ['menu' => 'orders'])

            <div class="nx-account-main">
                <section class="nx-card">
                    <div class="nx-card-head">
                        <h2><i class="fas fa-box"></i> سفارش‌های من</h2>
                        @if($orders->total() > 0)
                            <span class="nx-order-date">{{ toPersianNumbers($orders->total(), false) }} سفارش</span>
                        @endif
                    </div>

                    @if($orders->count() > 0)
                        <div class="nx-orders">
                            @foreach ($orders as $order)
                                @include('order.order_card', ['order' => $order])
                            @endforeach
                        </div>

                        @if($orders->hasPages())
                            <div class="nx-panel-body d-flex justify-content-center">
                                {{ $orders->links() }}
                            </div>
                        @endif
                    @else
                        <div class="nx-empty-state">
                            <i class="fas fa-box-open"></i>
                            <p>هنوز سفارشی ثبت نکرده‌اید.</p>
                            <a href="/shop" class="nx-btn-red"><i class="fas fa-store"></i> رفتن به فروشگاه</a>
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>
</main>
@endsection
