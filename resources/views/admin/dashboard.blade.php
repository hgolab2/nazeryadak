@extends('layout.managmentLayout', [
    'title' => 'داشبورد',
    'menu' => 'dashboard'
])

@section('main_content')
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
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:#e3f2fd; color:#1565c0;">
                <i class="fas fa-box"></i>
            </div>
            <div>
                <div class="stat-value">{{ number_format($totalProducts) }}</div>
                <div class="stat-label">کل محصولات</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fff3e0; color:#e65100;">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div>
                <div class="stat-value">{{ number_format($totalOrders) }}</div>
                <div class="stat-label">کل سفارشات</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:#e8f5e9; color:#2e7d32;">
                <i class="fas fa-users"></i>
            </div>
            <div>
                <div class="stat-value">{{ number_format($totalCustomers) }}</div>
                <div class="stat-label">کل مشتریان</div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-icon" style="background:#fce4ec; color:#c62828;">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div>
                <div class="stat-value" style="font-size:1.1rem;">{{ number_format($totalRevenue) }}</div>
                <div class="stat-label">درآمد (تومان)</div>
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
                            <th>مبلغ</th>
                            <th>وضعیت</th>
                            <th>تاریخ</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentOrders as $order)
                        <tr>
                            <td>{{ $order->id }}</td>
                            <td>{{ $order->customer?->fullName() ?: $order->customer?->phone ?? '—' }}</td>
                            <td>{{ number_format($order->total_price) }} <small class="text-muted">تومان</small></td>
                            <td>
                                @php
                                    $statusColors = [
                                        'pending' => 'background:#fff3e0; color:#e65100;',
                                        'paid' => 'background:#e8f5e9; color:#2e7d32;',
                                        'processing' => 'background:#e3f2fd; color:#1565c0;',
                                        'shipped' => 'background:#f3e5f5; color:#7b1fa2;',
                                        'delivered' => 'background:#e8f5e9; color:#2e7d32;',
                                        'failed' => 'background:#fce4ec; color:#c62828;',
                                        'canceled' => 'background:#f5f5f5; color:#616161;',
                                    ];
                                    $sColor = $statusColors[$order->status] ?? 'background:#f5f5f5; color:#616161;';
                                @endphp
                                <span class="badge-status" style="{{ $sColor }}">{{ $order->status() }}</span>
                            </td>
                            <td style="font-size:0.75rem; color:#999;">{{ gregorian_to_jalali2($order->created_at) }}</td>
                            <td>
                                <a href="/admin/order/edit/{{ $order->id }}" style="color:var(--admin-primary);">
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
            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid #f0f0f0;">
                <div class="d-flex align-items-center gap-2">
                    <span style="width:8px; height:8px; border-radius:50%; background:#e65100;"></span>
                    <span style="font-size:0.82rem;">در انتظار پرداخت</span>
                </div>
                <span style="font-size:0.9rem; font-weight:700;">{{ $pendingOrders }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid #f0f0f0;">
                <div class="d-flex align-items-center gap-2">
                    <span style="width:8px; height:8px; border-radius:50%; background:#2e7d32;"></span>
                    <span style="font-size:0.82rem;">پرداخت شده</span>
                </div>
                <span style="font-size:0.9rem; font-weight:700;">{{ $paidOrders }}</span>
            </div>
            <div class="d-flex justify-content-between align-items-center py-2">
                <div class="d-flex align-items-center gap-2">
                    <span style="width:8px; height:8px; border-radius:50%; background:#1565c0;"></span>
                    <span style="font-size:0.82rem;">کل سفارشات</span>
                </div>
                <span style="font-size:0.9rem; font-weight:700;">{{ $totalOrders }}</span>
            </div>
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
