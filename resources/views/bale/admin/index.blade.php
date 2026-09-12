@extends('layout.managmentLayout', [
    'title' => 'اتصال بله',
    'menu'  => 'bale',
])

@section('main_content')
@php
    $webhookSet = $webhook && ! empty($webhook['url']);
    $webhookOk  = $webhookSet && $webhook['url'] === $webhookUrl;
@endphp
<style>
    .bale-kv { display:flex; justify-content:space-between; gap:10px; padding:8px 0; border-bottom:1px dashed #eee; font-size:.85rem; }
    .bale-kv:last-child { border-bottom:0; }
    .bale-kv code { direction:ltr; display:inline-block; font-size:.78rem; word-break:break-all; }
</style>

<nav class="mb-2 pt-md-3" aria-label="Breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="/dashboardAdmin">داشبورد</a></li>
        <li class="breadcrumb-item active">اتصال بله</li>
    </ol>
</nav>
<h1 class="h4 mb-3">اتصال بله</h1>

@if($problem)
    <div class="alert alert-danger">
        <i class="fa fa-exclamation-triangle me-1"></i>
        <b>هیچ پیامی به بله نمی‌رود:</b> {{ $problem }}
        <div class="mt-2" style="font-size:.82rem;">
            توکن و شناسه‌ی چت در تنظیمات محیطی سرور (.env یا پنل هاست) قرار می‌گیرند، نه در این پنل.
            بعد از تغییر، اگر روی سرور <code>config:cache</code> فعال است، باید دوباره اجرا شود.
        </div>
    </div>
@else
    <div class="alert alert-success">
        <i class="fa fa-check-circle me-1"></i> توکن و مقصد تنظیم شده‌اند. برای اطمینان از اتصال، یک پیام آزمایشی بفرستید.
    </div>
@endif

<div class="row g-3">
    {{-- وضعیت اتصال --}}
    <div class="col-lg-6">
        <div class="admin-card h-100">
            <div class="admin-card-title"><i class="fas fa-plug"></i> وضعیت اتصال</div>
            <div class="bale-kv"><span>اطلاع‌رسانی</span><b>{{ config('bale.enabled') ? 'روشن' : 'خاموش' }}</b></div>
            <div class="bale-kv"><span>توکن ربات</span><b>{{ config('bale.token') ? 'تنظیم شده' : 'خالی' }}</b></div>
            <div class="bale-kv"><span>مقصد پیام‌ها (BALE_CHAT_ID)</span><b>{{ $chatIds ? implode('، ', $chatIds) : 'خالی' }}</b></div>
            <div class="bale-kv"><span>مدیران مجاز ربات</span><b>{{ $adminIds ? implode('، ', $adminIds) : '—' }}</b></div>
            <div class="bale-kv"><span>ارسال بعد از پاسخ به کاربر</span><b>{{ config('bale.defer') ? 'بله' : 'خیر' }}</b></div>
            <div class="bale-kv">
                <span>رویدادهای فعال</span>
                <span style="font-size:.75rem; text-align:left;">{{ count($enabledEvents) }} از {{ count($events) }}
                    @if(! $problem && count($enabledEvents) < count($events))
                        <div class="text-muted">غیرفعال: {{ implode('، ', array_map(fn ($e) => $events[$e][1], array_diff(array_keys($events), $enabledEvents))) }}</div>
                    @endif
                </span>
            </div>

            <form method="POST" action="/admin/bale/test" class="mt-3">
                @csrf
                <button class="btn btn-primary btn-sm" {{ $problem ? 'disabled' : '' }}>
                    <i class="fa fa-paper-plane me-1"></i> ارسال پیام آزمایشی
                </button>
            </form>
        </div>
    </div>

    {{-- ربات دوطرفه --}}
    <div class="col-lg-6">
        <div class="admin-card h-100">
            <div class="admin-card-title"><i class="fas fa-robot"></i> ربات دوطرفه (تغییر وضعیت و گزارش از داخل بله)</div>
            <p style="font-size:.82rem; color:#555;">
                با ثبت وبهوک، زیر هر پیام سفارش دکمه‌های وضعیت می‌آید و مدیر بازاریابی بدون باز کردن پنل سفارش را جلو می‌برد.
                همچنین با فرستادن «آمار»، «سود»، «باز» یا شماره‌ی سفارش به ربات، گزارش می‌گیرد.
            </p>
            <div class="bale-kv">
                <span>وبهوک</span>
                <b>
                    @if($webhook === null)
                        <span class="text-muted">نامعلوم</span>
                    @elseif($webhookOk)
                        <span class="text-success">ثبت شده ✓</span>
                    @elseif($webhookSet)
                        <span class="text-warning">روی آدرس دیگری ثبت شده</span>
                    @else
                        <span class="text-danger">ثبت نشده</span>
                    @endif
                </b>
            </div>
            @if($webhookSet)
                <div class="bale-kv"><span>آدرس فعلی</span><code>{{ $webhook['url'] }}</code></div>
            @endif
            <div class="bale-kv"><span>آدرس مورد انتظار</span><code>{{ $webhookUrl }}</code></div>
            @if(! empty($webhook['last_error_message']))
                <div class="bale-kv text-danger"><span>آخرین خطای بله</span><span style="font-size:.78rem;">{{ $webhook['last_error_message'] }}</span></div>
            @endif
            @if(isset($webhook['pending_update_count']) && $webhook['pending_update_count'] > 0)
                <div class="bale-kv"><span>رویدادهای در صف</span><b>{{ $webhook['pending_update_count'] }}</b></div>
            @endif

            @if(! str_starts_with($webhookUrl, 'https://'))
                <div class="alert alert-warning py-2 mt-2" style="font-size:.78rem;">
                    آدرس سایت (APP_URL) با https شروع نمی‌شود؛ بله فقط به آدرس عمومی https پیام می‌فرستد.
                    در محیط محلی به‌جای وبهوک از <code>php artisan bale:poll</code> استفاده کنید.
                </div>
            @endif

            <div class="d-flex gap-2 mt-3">
                <form method="POST" action="/admin/bale/webhook">
                    @csrf
                    <button class="btn btn-success btn-sm" {{ $problem ? 'disabled' : '' }}><i class="fa fa-link me-1"></i> ثبت وبهوک</button>
                </form>
                @if($webhookSet)
                    <form method="POST" action="/admin/bale/webhook" onsubmit="return confirm('وبهوک حذف شود؟ دکمه‌های زیر پیام‌ها دیگر کار نمی‌کنند.')">
                        @csrf
                        <input type="hidden" name="action" value="remove">
                        <button class="btn btn-outline-danger btn-sm"><i class="fa fa-unlink me-1"></i> حذف وبهوک</button>
                    </form>
                @endif
            </div>
            <div class="text-muted mt-2" style="font-size:.75rem;">
                برای این‌که کس دیگری جز مقصدهای بالا بتواند از ربات استفاده کند، شناسه‌ی عددی‌اش را در BALE_ADMIN_IDS بگذارید
                (هر کس به ربات پیام بدهد، شناسه‌اش را در پاسخ می‌بیند).
            </div>
        </div>
    </div>
</div>

{{-- سفارش‌های ارسال‌نشده --}}
<div class="admin-card mt-3">
    <div class="admin-card-title">
        <i class="fas fa-redo"></i> سفارش‌هایی که به بله نرسیده‌اند
        <span class="badge {{ $pendingOrders->count() > 0 ? 'bg-danger' : 'bg-success' }}">{{ $pendingOrders->count() }}</span>
        <form method="POST" action="/admin/bale/resend" style="margin-right:auto;">
            @csrf
            <button class="btn btn-sm btn-warning" {{ $problem || $pendingOrders->isEmpty() ? 'disabled' : '' }}>
                <i class="fa fa-paper-plane me-1"></i> ارسال دوباره‌ی همه
            </button>
        </form>
    </div>
    <p style="font-size:.8rem; color:#666;">
        سفارشی که هنگام ثبت، به هر دلیل (قطعی بله، توکن تنظیم‌نشده) پیامش نرسیده باشد این‌جا می‌ماند؛ ۳۰ روز اخیر بررسی می‌شود.
        سفارش‌های «در انتظار پرداخت» (مشتری هنوز به درگاه نرفته) شمرده نمی‌شوند.
    </p>
    @if($pendingOrders->isEmpty())
        <div class="text-success" style="font-size:.85rem;"><i class="fa fa-check-circle me-1"></i> همه‌ی سفارش‌های اخیر به بله رسیده‌اند.</div>
    @else
        <div class="table-responsive">
            <table class="table admin-table mb-0">
                <thead><tr><th>#</th><th>مشتری</th><th>وضعیت</th><th>مبلغ</th><th>تاریخ</th></tr></thead>
                <tbody>
                @foreach($pendingOrders as $order)
                    <tr>
                        <td><a href="/admin/order/show/{{ $order->id }}" class="fw-bold">{{ $order->id }}</a></td>
                        <td>{{ $order->customer?->fullName() ?: $order->customer?->phone ?: '—' }}</td>
                        <td><span class="badge {{ $order->statusBadgeClass() }}">{{ $order->status() }}</span></td>
                        <td>{{ number_format((int) $order->total_price) }}</td>
                        <td style="font-size:.78rem; color:#777;">{{ toPersianDate($order->created_at) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
