@extends('layout.managmentLayout', [
    'title' => 'کدهای تخفیف',
    'menu'  => 'discount/list',
])

@section('main_content')
<style>
.dc-code{font-family:monospace; font-size:14px; letter-spacing:1px; direction:ltr; display:inline-block}
.dc-row td{vertical-align:middle}
</style>

<nav class="mb-3 pt-md-3" aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">خانه</a></li>
        <li class="breadcrumb-item active">کدهای تخفیف</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
    <h1 class="h4 mb-0">کدهای تخفیف</h1>
    <a href="/admin/discount/create" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> کد تخفیف جدید
    </a>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

<div class="alert alert-light border">
    هر کد، درصدی از <strong>مبلغ اقلام فاکتور</strong> را کم می‌کند و بیشتر از سقفی که تعیین کرده‌اید کم نمی‌شود.
    هزینه‌ی ارسال هیچ‌وقت مشمول تخفیف نمی‌شود.
</div>

<form method="GET" class="row g-2 align-items-end mb-3">
    <div class="col-md-5">
        <label class="form-label">جست‌وجو</label>
        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="کد یا عنوان">
    </div>
    <div class="col-md-3">
        <label class="form-label">وضعیت</label>
        <select name="state" class="form-control">
            <option value="">همه</option>
            <option value="active"   {{ request('state') === 'active' ? 'selected' : '' }}>فعال</option>
            <option value="inactive" {{ request('state') === 'inactive' ? 'selected' : '' }}>غیرفعال</option>
        </select>
    </div>
    <div class="col-md-2">
        <button class="btn btn-outline-primary w-100"><i class="fas fa-search me-1"></i> فیلتر</button>
    </div>
</form>

<section class="card card-body shadow-sm p-4">
    @if($model->count())
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>کد</th>
                    <th>قاعده</th>
                    <th>حداقل فاکتور</th>
                    <th>اعتبار</th>
                    <th class="text-center">مصرف</th>
                    <th class="text-center">وضعیت</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($model as $code)
                @php
                    $used  = $code->usedCount();
                    $state = $code->stateLabel();
                    $badge = ['فعال' => 'success', 'غیرفعال' => 'secondary', 'منقضی' => 'danger',
                              'ظرفیت تکمیل' => 'warning', 'زمان‌بندی‌شده' => 'info'][$state] ?? 'secondary';
                @endphp
                <tr class="dc-row">
                    <td>
                        <b class="dc-code">{{ $code->code }}</b>
                        @if($code->title)
                            <div class="text-muted" style="font-size:12px;">{{ $code->title }}</div>
                        @endif
                    </td>
                    <td>{{ $code->ruleLabel() }}</td>
                    <td>
                        @if($code->min_order_amount > 0)
                            {{ number_format($code->min_order_amount) }} تومان
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td style="font-size:12px;">
                        @if($code->starts_at)
                            از {{ toPersianDate($code->starts_at, false, true, 'Y/m/d') }}<br>
                        @endif
                        @if($code->expires_at)
                            تا {{ toPersianDate($code->expires_at, false, true, 'Y/m/d') }}
                        @elseif(! $code->starts_at)
                            <span class="text-muted">بدون محدودیت زمانی</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark">
                            {{ $used }}{{ $code->usage_limit !== null ? ' / ' . $code->usage_limit : '' }}
                        </span>
                    </td>
                    <td class="text-center"><span class="badge bg-{{ $badge }}">{{ $state }}</span></td>
                    <td class="text-end text-nowrap">
                        <a href="/admin/discount/edit/{{ $code->id }}" class="btn btn-sm btn-primary" title="ویرایش">
                            <i class="fas fa-pen"></i>
                        </a>
                        <form method="POST" action="/admin/discount/{{ $code->id }}/toggle" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-outline-secondary" title="{{ $code->is_active ? 'غیرفعال کردن' : 'فعال کردن' }}">
                                <i class="fas fa-{{ $code->is_active ? 'toggle-on' : 'toggle-off' }}"></i>
                            </button>
                        </form>
                        <form method="POST" action="/admin/discount/{{ $code->id }}" class="d-inline"
                              onsubmit="return confirm('کد {{ $code->code }} حذف شود؟')">
                            @csrf @method('delete')
                            <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $model->links() }}
    @else
        <p class="text-muted mb-0">هنوز کد تخفیفی ساخته نشده است.</p>
    @endif
</section>
@endsection
