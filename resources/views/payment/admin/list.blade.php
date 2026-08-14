@extends('layout.managmentLayout', [
    'title' => 'مدیریت پرداخت‌ها',
    'menu' => 'payment/list',
])

@section('main_content')
<style>
    .pay-table td{vertical-align:middle; font-size:0.85rem}
    .pay-table th{font-size:0.82rem; white-space:nowrap}
    .pay-ltr{direction:ltr; text-align:center; font-family:consolas,monospace}
    .pay-thumb{width:52px; height:52px; object-fit:cover; border-radius:8px; border:1px solid #e6e6e6}
    .pay-detail{background:#fafbff}
    .pay-tab{border:1px solid #dee2e6; border-radius:8px; padding:6px 14px; font-size:0.82rem; color:#555; text-decoration:none}
    .pay-tab.active{background:#0d6efd; border-color:#0d6efd; color:#fff}
</style>

<nav class="mb-3 pt-md-3" aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">خانه</a></li>
        <li class="breadcrumb-item active">مدیریت پرداخت‌ها</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <h1 class="h4 mb-0">
        پرداخت‌ها و رسیدها
        @if($counts['pending'] > 0)
            <span class="badge bg-warning text-dark align-middle">{{ $counts['pending'] }} منتظر بررسی</span>
        @endif
    </h1>
    <a href="/admin/order/list" class="btn btn-outline-primary btn-sm">
        <i class="fas fa-shopping-cart me-1"></i> مدیریت سفارشات
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">
        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif

<section class="card card-body shadow-sm p-3 mb-3">
    <form method="GET" action="/admin/payment/list">
        <div class="d-flex gap-2 mb-3 flex-wrap">
            @foreach(['manual' => 'رسیدهای دستی', 'online' => 'درگاه اینترنتی', 'all' => 'همه'] as $key => $label)
                <a href="/admin/payment/list?type={{ $key }}" class="pay-tab {{ $type === $key ? 'active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
        <input type="hidden" name="type" value="{{ $type }}">
        <div class="row">
            <div class="col-md-3 mb-2">
                <label class="form-label" style="font-size:0.82rem;">وضعیت</label>
                <select name="status" class="form-control">
                    <option value="">همه</option>
                    @foreach(\App\Models\Payment::STATUSES as $key => $label)
                        <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-2">
                <label class="form-label" style="font-size:0.82rem;">کد سفارش</label>
                <input type="text" name="order_id" class="form-control" value="{{ request('order_id') }}">
            </div>
            <div class="col-md-3 mb-2">
                <label class="form-label" style="font-size:0.82rem;">موبایل مشتری</label>
                <input type="text" name="phone" class="form-control" dir="ltr" value="{{ request('phone') }}">
            </div>
            <div class="col-md-3 mb-2 d-flex align-items-end gap-2">
                <button class="btn btn-primary flex-grow-1"><i class="fas fa-search me-1"></i> جستجو</button>
                <a href="/admin/payment/list" class="btn btn-light border">حذف فیلتر</a>
            </div>
        </div>
    </form>
</section>

<div class="d-flex gap-3 mb-3 flex-wrap" style="font-size:0.82rem;">
    <span class="badge bg-warning text-dark">در انتظار بررسی: {{ $counts['pending'] }}</span>
    <span class="badge bg-success">تأییدشده: {{ $counts['paid'] }}</span>
    <span class="badge bg-danger">ردشده: {{ $counts['rejected'] }}</span>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-bordered table-hover mb-0 pay-table">
            <thead class="table-primary">
            <tr>
                <th class="text-center">سفارش</th>
                <th class="text-center">مشتری</th>
                <th class="text-center">مبلغ (تومان)</th>
                <th class="text-center">روش</th>
                <th class="text-center">شماره پیگیری</th>
                <th class="text-center">تاریخ پرداخت</th>
                <th class="text-center">رسید</th>
                <th class="text-center">وضعیت</th>
                <th class="text-center">عملیات</th>
            </tr>
            </thead>
            <tbody>
            @forelse($model as $payment)
                @php $order = $payment->order; @endphp
                <tr>
                    <td class="text-center">
                        @if($order)
                            <a href="/admin/order/edit/{{ $order->id }}">#{{ $order->id }}</a>
                            <div class="text-muted" style="font-size:0.72rem;">{{ $order->status() }}</div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        {{ $order?->customer?->fullName() ?: '—' }}
                        <div class="pay-ltr text-muted" style="font-size:0.75rem;">{{ $order?->customer?->phone }}</div>
                    </td>
                    <td class="text-center fw-bold">
                        {{ number_format((int) $payment->amount) }}
                        @if($order && (int) $payment->amount !== (int) $order->total_price)
                            {{-- مبلغ اعلامی مشتری با مبلغ سفارش نمی‌خواند؛ باید به چشم مدیر بیاید.
                                 مقایسه با total_price است چون همان مبلغی است که
                                 بعد از تخفیف و با هزینه‌ی ارسال باید واریز شود. --}}
                            <div class="text-danger" style="font-size:0.72rem;">
                                مبلغ سفارش: {{ number_format((int) $order->total_price) }}
                                @if($order->hasDiscount())
                                    <span class="d-block">(با {{ number_format((int) $order->discount_amount) }} تومان تخفیف {{ $order->discount_code }})</span>
                                @endif
                            </div>
                        @endif
                    </td>
                    <td class="text-center">
                        {{ $payment->methodLabel() }}
                        @if($payment->card_last4)
                            <div class="pay-ltr text-muted" style="font-size:0.72rem;">**** {{ $payment->card_last4 }}</div>
                        @endif
                    </td>
                    <td class="text-center pay-ltr">{{ $payment->reference ?: ($payment->ref_id ?: '—') }}</td>
                    <td class="text-center">
                        {{ $payment->paid_at ? toPersianDate($payment->paid_at) : '—' }}
                        <div class="text-muted" style="font-size:0.72rem;">
                            ثبت: {{ $payment->created_at ? toPersianDate($payment->created_at) : '—' }}
                        </div>
                    </td>
                    <td class="text-center">
                        @if($payment->receiptUrl())
                            <a href="{{ $payment->receiptUrl() }}" target="_blank" rel="noopener">
                                <img src="{{ $payment->receiptUrl() }}" alt="رسید پرداخت" class="pay-thumb" loading="lazy">
                            </a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $payment->statusBadgeClass() }}">{{ $payment->statusLabel() }}</span>
                        @if($payment->reviewed_at)
                            <div class="text-muted" style="font-size:0.7rem;">
                                {{ $payment->reviewer?->name ?? 'مدیر' }} — {{ toPersianDate($payment->reviewed_at) }}
                            </div>
                        @endif
                    </td>
                    <td class="text-center" style="min-width:150px;">
                        @if($payment->isAwaitingReview())
                            <form method="POST" action="/admin/payment/{{ $payment->id }}/approve" class="d-inline"
                                  onsubmit="return confirm('پرداخت سفارش #{{ $payment->order_id }} تأیید و وضعیت سفارش «پرداخت شده» شود؟');">
                                @csrf
                                <button class="btn btn-success btn-sm mb-1"><i class="fas fa-check me-1"></i> تأیید</button>
                            </form>
                            <button class="btn btn-outline-danger btn-sm mb-1" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#reject-{{ $payment->id }}">
                                <i class="fas fa-times me-1"></i> رد
                            </button>
                        @else
                            <span class="text-muted" style="font-size:0.75rem;">بررسی شده</span>
                        @endif
                        @if($payment->customer_note || $payment->admin_note)
                            <button class="btn btn-light border btn-sm mb-1" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#note-{{ $payment->id }}">
                                <i class="fas fa-comment-dots"></i>
                            </button>
                        @endif
                    </td>
                </tr>

                @if($payment->customer_note || $payment->admin_note)
                    <tr class="collapse pay-detail" id="note-{{ $payment->id }}">
                        <td colspan="9">
                            @if($payment->customer_note)
                                <div class="mb-1"><b>توضیح مشتری:</b> {{ $payment->customer_note }}</div>
                            @endif
                            @if($payment->payer_name)
                                <div class="mb-1"><b>پرداخت‌کننده:</b> {{ $payment->payer_name }}</div>
                            @endif
                            @if($payment->admin_note)
                                <div><b>یادداشت مدیر:</b> {{ $payment->admin_note }}</div>
                            @endif
                        </td>
                    </tr>
                @endif

                @if($payment->isAwaitingReview())
                    <tr class="collapse pay-detail" id="reject-{{ $payment->id }}">
                        <td colspan="9">
                            <form method="POST" action="/admin/payment/{{ $payment->id }}/reject" class="row g-2 align-items-end">
                                @csrf
                                <div class="col-md-9">
                                    <label class="form-label" style="font-size:0.82rem;">دلیل رد کردن (برای مشتری پیامک می‌شود) *</label>
                                    <input type="text" name="admin_note" class="form-control" required maxlength="500"
                                           placeholder="مثلا: مبلغ واریزی با مبلغ سفارش نمی‌خواند / رسید ناخوانا است">
                                </div>
                                <div class="col-md-3">
                                    <button class="btn btn-danger w-100"><i class="fas fa-times me-1"></i> ثبت رد</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-4">پرداختی با این فیلترها پیدا نشد.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($model->hasPages())
    <div class="mt-3 d-flex justify-content-center">
        {{ $model->links() }}
    </div>
@endif
@endsection
