@extends('layout.managmentLayout', [
    'title' => 'مدیریت پیامک‌ها',
    'menu' => 'sms/list',
])

@section('main_content')
<style>
    .sms-table td{vertical-align:middle; font-size:0.85rem}
    .sms-table th{font-size:0.82rem; white-space:nowrap}
    .sms-ltr{direction:ltr; text-align:center; font-family:consolas,monospace}
    .sms-text{max-width:420px; white-space:pre-wrap; word-break:break-word; font-size:0.82rem}
</style>

<nav class="mb-3 pt-md-3" aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">خانه</a></li>
        <li class="breadcrumb-item active">مدیریت پیامک‌ها</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <h1 class="h4 mb-0">پیامک‌های ارسال‌شده</h1>
    <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#sms-send">
        <i class="fas fa-paper-plane me-1"></i> ارسال پیامک دستی
    </button>
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

@if(! $gatewayReady)
    {{-- بدون این دو مقدار هیچ پیامکی از سایت بیرون نمی‌رود؛ حتی کد ورود مشتری --}}
    <div class="alert alert-warning">
        <i class="fas fa-triangle-exclamation me-1"></i>
        درگاه پیامک تنظیم نشده است. تا وقتی <code>SMS_USERNAME</code> و <code>SMS_PASSWORD</code> در فایل
        <code>.env</code> پر نشوند، هیچ پیامکی (از جمله کد ورود مشتریان) ارسال نمی‌شود.
    </div>
@endif

<div class="collapse @if(old('mobile') || $errors->any()) show @endif mb-3" id="sms-send">
    <section class="card card-body shadow-sm p-3">
        <form method="POST" action="/admin/sms/send" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-3">
                <label class="form-label" style="font-size:0.82rem;">شماره موبایل *</label>
                <input type="text" name="mobile" class="form-control sms-ltr" dir="ltr" maxlength="11"
                       placeholder="09xxxxxxxxx" value="{{ old('mobile') }}" required>
            </div>
            <div class="col-md-7">
                <label class="form-label" style="font-size:0.82rem;">متن پیامک * <span class="text-muted">(حداکثر ۵۰۰ کاراکتر)</span></label>
                <textarea name="text" class="form-control" rows="2" maxlength="500" required>{{ old('text') }}</textarea>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" @disabled(! $gatewayReady)>
                    <i class="fas fa-paper-plane me-1"></i> ارسال
                </button>
            </div>
        </form>
    </section>
</div>

<section class="card card-body shadow-sm p-3 mb-3">
    <form method="GET" action="/admin/sms">
        <div class="row">
            <div class="col-md-3 mb-2">
                <label class="form-label" style="font-size:0.82rem;">شماره موبایل</label>
                <input type="text" name="mobile" class="form-control" dir="ltr" value="{{ request('mobile') }}">
            </div>
            <div class="col-md-3 mb-2">
                <label class="form-label" style="font-size:0.82rem;">متن پیامک</label>
                <input type="text" name="text" class="form-control" value="{{ request('text') }}">
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label" style="font-size:0.82rem;">وضعیت</label>
                <select name="status" class="form-control">
                    <option value="">همه</option>
                    <option value="sent" {{ $status === 'sent' ? 'selected' : '' }}>ارسال‌شده</option>
                    <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>ناموفق</option>
                </select>
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label" style="font-size:0.82rem;">بازه</label>
                <select name="days" class="form-control">
                    <option value="0">همه</option>
                    @foreach([1 => 'یک روز اخیر', 7 => '۷ روز اخیر', 30 => '۳۰ روز اخیر', 90 => '۹۰ روز اخیر'] as $key => $label)
                        <option value="{{ $key }}" {{ (int) $days === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 mb-2 d-flex align-items-end gap-2">
                <button class="btn btn-primary flex-grow-1"><i class="fas fa-search me-1"></i> جستجو</button>
                <a href="/admin/sms" class="btn btn-light border">حذف فیلتر</a>
            </div>
        </div>
    </form>
</section>

<div class="d-flex gap-3 mb-3 flex-wrap" style="font-size:0.82rem;">
    <span class="badge bg-primary">کل: {{ number_format($counts['total']) }}</span>
    <span class="badge bg-info text-dark">امروز: {{ number_format($counts['today']) }}</span>
    <span class="badge bg-secondary">۷ روز اخیر: {{ number_format($counts['week']) }}</span>
    <span class="badge bg-danger">ناموفق: {{ number_format($counts['failed']) }}</span>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-bordered table-hover mb-0 sms-table">
            <thead class="table-primary">
            <tr>
                <th class="text-center">#</th>
                <th class="text-center">شماره</th>
                <th class="text-center">گیرنده</th>
                <th class="text-center">متن</th>
                <th class="text-center">تاریخ</th>
                <th class="text-center">وضعیت</th>
                <th class="text-center">پاسخ درگاه</th>
                <th class="text-center">عملیات</th>
            </tr>
            </thead>
            <tbody>
            @forelse($model as $sms)
                @php $customer = $customers[$sms->mobile] ?? null; @endphp
                <tr id="sms-row-{{ $sms->id }}">
                    <td class="text-center">{{ $sms->id }}</td>
                    <td class="text-center sms-ltr">{{ $sms->mobile }}</td>
                    <td class="text-center">
                        @if($customer)
                            <a href="/admin/customer/edit/{{ $customer->id }}">{{ $customer->fullName() ?: 'مشتری #'.$customer->id }}</a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="sms-text">{{ $sms->text }}</td>
                    <td class="text-center">{{ $sms->created_at ? toPersianDate($sms->created_at) : '—' }}</td>
                    <td class="text-center">
                        <span class="badge {{ $sms->statusBadgeClass() }}">{{ $sms->statusLabel() }}</span>
                    </td>
                    <td class="text-center sms-ltr text-muted" style="font-size:0.75rem;">{{ $sms->udh ?: '—' }}</td>
                    <td class="text-center">
                        <button class="btn btn-outline-danger btn-sm" type="button" onclick="deleteSms({{ $sms->id }})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">پیامکی با این فیلترها پیدا نشد.</td>
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

@section('js')
<script>
function deleteSms(id) {
    if (!confirm('این پیامک از لاگ حذف شود؟')) return;

    fetch('/admin/sms/' + id, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(function (res) { return res.json(); })
    .then(function (data) {
        if (data.success) {
            var row = document.getElementById('sms-row-' + id);
            if (row) row.remove();
        } else {
            alert(data.message || 'حذف انجام نشد');
        }
    })
    .catch(function () { alert('خطا در ارتباط با سرور'); });
}
</script>
@endsection
