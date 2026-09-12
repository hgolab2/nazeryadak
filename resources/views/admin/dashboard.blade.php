@extends('layout.managmentLayout', [
    'title' => 'داشبورد',
    'menu' => 'dashboard'
])

@section('main_content')
@php
    $profitClass = fn (int $value) => $value >= 0 ? 'profit-pos' : 'profit-neg';
@endphp
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h5 style="font-weight:700; margin:0;">داشبورد</h5>
        <p style="font-size:0.8rem; color:#777; margin:5px 0 0;">خلاصه وضعیت فروشگاه ناظر یدک</p>
    </div>
    <span style="font-size:0.78rem; color:#999;">
        <i class="fas fa-calendar-alt me-1"></i>
        {{ gregorian_to_jalali2(now()) }}
    </span>
</div>

{{-- آمار کلی --}}
<div class="row g-3 mb-3">
    <div class="col-xl-3 col-sm-6">
        <a href="/admin/product/list" class="stat-card" style="color:inherit;">
            <div class="stat-icon" style="background:#e3f2fd; color:#1565c0;">
                <i class="fas fa-box"></i>
            </div>
            <div>
                <div class="stat-value">{{ number_format($snapshot['products']) }}</div>
                <div class="stat-label">کل محصولات
                    @if($snapshot['products_nostock'] > 0)
                        <span class="text-danger">— {{ number_format($snapshot['products_nostock']) }} ناموجود</span>
                    @endif
                </div>
            </div>
        </a>
    </div>
    <div class="col-xl-3 col-sm-6">
        <a href="/admin/order/list" class="stat-card" style="color:inherit;">
            <div class="stat-icon" style="background:#fff3e0; color:#e65100;">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div>
                <div class="stat-value">{{ number_format($snapshot['orders']) }}</div>
                <div class="stat-label">کل سفارشات
                    @if($snapshot['orders_open'] > 0)
                        <span class="text-warning fw-bold">— {{ $snapshot['orders_open'] }} در انتظار اقدام</span>
                    @endif
                </div>
            </div>
        </a>
    </div>
    <div class="col-xl-3 col-sm-6">
        <a href="/admin/customer/list" class="stat-card" style="color:inherit;">
            <div class="stat-icon" style="background:#e8f5e9; color:#2e7d32;">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <div class="stat-value">{{ number_format($snapshot['customers']) }}</div>
                <div class="stat-label">کل مشتریان</div>
            </div>
        </a>
    </div>
    <div class="col-xl-3 col-sm-6">
        <a href="/admin/finance" class="stat-card" style="color:inherit;">
            <div class="stat-icon" style="background:#fce4ec; color:#c62828;">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div>
                <div class="stat-value" style="font-size:1.1rem;">{{ number_format($allTime['sales']) }}</div>
                <div class="stat-label">کل فروش (تومان)</div>
            </div>
        </a>
    </div>
</div>

{{-- سود و زیان: امروز و ماه جاری. اعداد از FinanceReport می‌آیند؛ همان
     منبع صفحه‌ی حسابداری و ربات بله. --}}
<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="admin-card mb-0 h-100">
            <div class="admin-card-title">
                <i class="fas fa-chart-line"></i> ماه {{ $monthLabel }}
                <a href="/admin/finance" style="margin-right:auto; font-size:0.75rem; color:var(--admin-primary);">حسابداری</a>
            </div>
            <div class="row g-2 text-center">
                <div class="col-4">
                    <div style="font-size:0.72rem; color:#777;">فروش</div>
                    <div style="font-weight:700;">{{ number_format($month['sales']) }}</div>
                    <div style="font-size:0.7rem; color:#999;">{{ $month['orders'] }} سفارش / {{ $month['items'] }} قطعه</div>
                </div>
                <div class="col-4">
                    <div style="font-size:0.72rem; color:#777;">هزینه‌ها</div>
                    <div style="font-weight:700;">{{ number_format($month['cogs'] + $month['order_expenses'] + $month['general_expenses']) }}</div>
                    <div style="font-size:0.7rem; color:#999;">خرید کالا {{ number_format($month['cogs']) }}</div>
                </div>
                <div class="col-4">
                    <div style="font-size:0.72rem; color:#777;">سود خالص</div>
                    <div style="font-weight:700;" class="{{ $profitClass($month['net_profit']) }}">{{ number_format($month['net_profit']) }}</div>
                    <div style="font-size:0.7rem; color:#999;">{{ $month['margin'] }}٪ حاشیه</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="admin-card mb-0 h-100">
            <div class="admin-card-title">
                <i class="fas fa-sun"></i> امروز
            </div>
            <div class="row g-2 text-center">
                <div class="col-4">
                    <div style="font-size:0.72rem; color:#777;">فروش</div>
                    <div style="font-weight:700;">{{ number_format($today['sales']) }}</div>
                    <div style="font-size:0.7rem; color:#999;">{{ $today['orders'] }} سفارش / {{ $today['items'] }} قطعه</div>
                </div>
                <div class="col-4">
                    <div style="font-size:0.72rem; color:#777;">سود خالص</div>
                    <div style="font-weight:700;" class="{{ $profitClass($today['net_profit']) }}">{{ number_format($today['net_profit']) }}</div>
                </div>
                <div class="col-4">
                    <div style="font-size:0.72rem; color:#777;">سود کل از ابتدا</div>
                    <div style="font-weight:700;" class="{{ $profitClass($allTime['net_profit']) }}">{{ number_format($allTime['net_profit']) }}</div>
                    <div style="font-size:0.7rem; color:#999;">{{ $allTime['margin'] }}٪ حاشیه</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- آخرین سفارشات --}}
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="admin-card-title">
                <i class="fas fa-clock"></i> آخرین سفارشات
                <a href="/admin/order/list" style="margin-right:auto; font-size:0.75rem; color:var(--admin-primary);">مشاهده همه</a>
            </div>
            @if($recentOrders->count() > 0)
            <div class="table-responsive">
                <table class="table admin-table mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>مشتری</th>
                            <th>مبلغ پرداختی</th>
                            <th>سود</th>
                            <th>وضعیت</th>
                            <th>تاریخ</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentOrders as $order)
                        <tr>
                            {{-- شماره و نام مشتری هر دو به صفحه‌ی سفارش می‌روند؛ قبلا فقط
                                 آیکن چشم لینک بود و پیدا کردنش سخت --}}
                            <td>
                                <a href="/admin/order/show/{{ $order->id }}" class="fw-bold">{{ $order->id }}</a>
                            </td>
                            <td>
                                <a href="/admin/order/show/{{ $order->id }}" style="color:inherit;">
                                    {{ $order->customer?->fullName() ?: $order->customer?->phone ?? '—' }}
                                </a>
                            </td>
                            <td>{{ number_format($order->total_price) }} <small class="text-muted">تومان</small></td>
                            <td class="{{ $profitClass($order->netProfit()) }}" style="font-size:0.78rem;">
                                {{ number_format($order->netProfit()) }}
                            </td>
                            <td>
                                @include('order.admin._status', ['order' => $order])
                                @if($order->pendingReceipt)
                                    <a href="/admin/payment/list?status=pending&order_id={{ $order->id }}"
                                       class="badge bg-warning text-dark d-block mt-1 text-decoration-none">
                                        <i class="fa fa-receipt me-1"></i>رسید در انتظار بررسی
                                    </a>
                                @endif
                            </td>
                            <td style="font-size:0.75rem; color:#999;">{{ $order->created_at != null ? toPersianDate($order->created_at) : '' }}</td>
                            <td>
                                <a href="/admin/order/show/{{ $order->id }}" style="color:var(--admin-primary);" title="مشاهده سفارش">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-4">
                <i class="fas fa-inbox" style="font-size:2rem; color:#ddd;"></i>
                <p style="font-size:0.85rem; color:#999; margin-top:10px;">هنوز سفارشی ثبت نشده</p>
            </div>
            @endif
        </div>
    </div>

    {{-- خلاصه وضعیت --}}
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="admin-card-title">
                <i class="fas fa-chart-pie"></i> وضعیت سفارشات
            </div>
            @php
                $dots = [
                    'pending' => '#f9a825', 'awaiting_call' => '#e65100', 'paid' => '#2e7d32',
                    'processing' => '#1565c0', 'shipped' => '#0288d1', 'delivered' => '#1b5e20',
                    'canceled' => '#c62828', 'returned' => '#333', 'failed' => '#ad1457',
                ];
            @endphp
            @foreach($statuses as $key => $label)
                @php $count = (int) ($snapshot['status_counts'][$key] ?? 0); @endphp
                <a href="/admin/order/list?status={{ $key }}" class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid #f0f0f0; color:inherit;">
                    <div class="d-flex align-items-center gap-2">
                        <span style="width:8px; height:8px; border-radius:50%; background:{{ $dots[$key] ?? '#999' }};"></span>
                        <span style="font-size:0.82rem;">{{ $label }}</span>
                    </div>
                    <span style="font-size:0.9rem; font-weight:700; {{ $count === 0 ? 'color:#bbb;' : '' }}">{{ $count }}</span>
                </a>
            @endforeach
            @if($snapshot['pending_receipts'] > 0)
                <a href="/admin/payment/list?status=pending" class="d-flex justify-content-between align-items-center py-2 text-warning">
                    <span style="font-size:0.82rem;"><i class="fa fa-receipt me-1"></i> رسید در انتظار بررسی</span>
                    <span style="font-size:0.9rem; font-weight:700;">{{ $snapshot['pending_receipts'] }}</span>
                </a>
            @endif
        </div>

        <div class="admin-card">
            <div class="admin-card-title">
                <i class="fas fa-bolt"></i> دسترسی سریع
            </div>
            <div class="d-grid gap-2">
                <a href="/admin/product/create" class="btn btn-sm" style="background:var(--admin-bg); border:1px solid #dde4ec; text-align:right; font-size:0.82rem; padding:10px 12px;">
                    <i class="fas fa-plus me-2" style="color:var(--admin-primary);"></i> افزودن محصول جدید
                </a>
                <a href="/admin/order/list" class="btn btn-sm" style="background:var(--admin-bg); border:1px solid #dde4ec; text-align:right; font-size:0.82rem; padding:10px 12px;">
                    <i class="fas fa-list me-2" style="color:var(--admin-accent);"></i> مدیریت سفارشات
                </a>
                <a href="/admin/finance/create" class="btn btn-sm" style="background:var(--admin-bg); border:1px solid #dde4ec; text-align:right; font-size:0.82rem; padding:10px 12px;">
                    <i class="fas fa-file-invoice-dollar me-2" style="color:#c62828;"></i> ثبت هزینه / درآمد
                </a>
                <a href="/admin/customer/list" class="btn btn-sm" style="background:var(--admin-bg); border:1px solid #dde4ec; text-align:right; font-size:0.82rem; padding:10px 12px;">
                    <i class="fas fa-users me-2" style="color:#2e7d32;"></i> لیست مشتریان
                </a>
                <a href="/" target="_blank" class="btn btn-sm" style="background:var(--admin-bg); border:1px solid #dde4ec; text-align:right; font-size:0.82rem; padding:10px 12px;">
                    <i class="fas fa-globe me-2" style="color:#7b1fa2;"></i> مشاهده سایت
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
