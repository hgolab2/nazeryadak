@php
    $editing = ! empty($model);
    /* datetime-local فقط قالب Y-m-d\TH:i را می‌پذیرد؛ مقدار خام دیتابیس
       («Y-m-d H:i:s») در فرم خالی می‌افتد و تاریخ ذخیره‌شده گم می‌شود. */
    $dt = fn ($value) => $value ? $value->format('Y-m-d\TH:i') : '';
@endphp
@extends('layout.managmentLayout', [
    'title' => $editing ? 'ویرایش کد تخفیف' : 'کد تخفیف جدید',
    'menu'  => 'discount/list',
])

@section('main_content')
<nav class="mb-3 pt-md-3" aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">خانه</a></li>
        <li class="breadcrumb-item"><a href="/admin/discount/list">کدهای تخفیف</a></li>
        <li class="breadcrumb-item active">{{ $editing ? 'ویرایش' : 'جدید' }}</li>
    </ol>
</nav>

<h1 class="h4 mb-3">{{ $editing ? 'ویرایش کد تخفیف' : 'کد تخفیف جدید' }}</h1>

@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

<form method="POST" action="{{ $editing ? '/admin/discount/update/' . $model->id : '/admin/discount/store' }}">
    @csrf
    @if($editing) @method('put') @endif

    <section class="card card-body shadow-sm p-4 mb-3">
        <h6 class="border-bottom pb-2 mb-3"><i class="fas fa-tag me-1"></i> قاعده‌ی تخفیف</h6>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">کد تخفیف *</label>
                <input type="text" name="code" class="form-control" required maxlength="40" dir="ltr"
                       style="font-family:monospace; letter-spacing:1px;"
                       value="{{ old('code', $model->code ?? '') }}" placeholder="NOROOZ">
                <small class="text-muted">فقط حروف لاتین، رقم، خط تیره و زیرخط. خودکار به حروف بزرگ تبدیل می‌شود.</small>
            </div>

            <div class="col-md-8 mb-3">
                <label class="form-label fw-bold">عنوان (یادداشت داخلی)</label>
                <input type="text" name="title" class="form-control" maxlength="150"
                       value="{{ old('title', $model->title ?? '') }}" placeholder="جشنواره نوروز ۱۴۰۵">
                <small class="text-muted">به مشتری نشان داده نمی‌شود؛ فقط برای این‌که خودتان یادتان بماند این کد برای چه بوده.</small>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">درصد تخفیف *</label>
                <div class="input-group">
                    <input type="number" name="percent" class="form-control" required min="1" max="100"
                           value="{{ old('percent', $model->percent ?? 10) }}">
                    <span class="input-group-text">٪</span>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">سقف تخفیف (تومان)</label>
                <input type="text" name="max_discount" class="form-control" inputmode="numeric"
                       value="{{ old('max_discount', $model->max_discount ?? '') }}" placeholder="مثلا ۵۰۰۰۰">
                <small class="text-muted">بیشتر از این مبلغ از فاکتور کم نمی‌شود. خالی یعنی بدون سقف.</small>
            </div>

            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">حداقل مبلغ سفارش (تومان)</label>
                <input type="text" name="min_order_amount" class="form-control" inputmode="numeric"
                       value="{{ old('min_order_amount', $model->min_order_amount ?? 0) }}">
                <small class="text-muted">فاکتور کمتر از این مبلغ، کد را نمی‌پذیرد. صفر یعنی بدون حداقل.</small>
            </div>
        </div>

        <div class="alert alert-light border mb-0" style="font-size:13px; line-height:2;">
            <i class="fas fa-circle-info me-1"></i>
            درصد روی <b>مبلغ اقلام</b> حساب می‌شود، نه روی هزینه‌ی ارسال.
            مثال: «۲۰٪ تا سقف ۵۰٬۰۰۰ تومان» روی فاکتور ۲۰۰٬۰۰۰ تومانی ۴۰٬۰۰۰ تومان کم می‌کند،
            ولی روی فاکتور ۱٬۰۰۰٬۰۰۰ تومانی همان ۵۰٬۰۰۰ تومان.
        </div>
    </section>

    <section class="card card-body shadow-sm p-4 mb-3">
        <h6 class="border-bottom pb-2 mb-3"><i class="fas fa-sliders-h me-1"></i> محدودیت‌ها</h6>

        <div class="row">
            <div class="col-md-3 mb-3">
                <label class="form-label fw-bold">سقف کل دفعات استفاده</label>
                <input type="text" name="usage_limit" class="form-control" inputmode="numeric"
                       value="{{ old('usage_limit', $model->usage_limit ?? '') }}" placeholder="نامحدود">
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label fw-bold">سقف استفاده هر مشتری</label>
                <input type="text" name="per_customer_limit" class="form-control" inputmode="numeric"
                       value="{{ old('per_customer_limit', $model->per_customer_limit ?? '') }}" placeholder="نامحدود">
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label fw-bold">شروع اعتبار</label>
                <input type="datetime-local" name="starts_at" class="form-control"
                       value="{{ old('starts_at', $dt($model->starts_at ?? null)) }}">
            </div>

            <div class="col-md-3 mb-3">
                <label class="form-label fw-bold">پایان اعتبار</label>
                <input type="datetime-local" name="expires_at" class="form-control"
                       value="{{ old('expires_at', $dt($model->expires_at ?? null)) }}">
            </div>

            <div class="col-12">
                <div class="form-check form-switch">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active"
                           {{ old('is_active', $model->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold" for="is_active">کد فعال است</label>
                </div>
                <small class="text-muted">کد غیرفعال برای مشتری «معتبر نیست» اعلام می‌شود ولی سفارش‌های قبلی دست‌نخورده می‌مانند.</small>
            </div>
        </div>
    </section>

    @if($editing)
    <div class="alert alert-info">
        <i class="fas fa-chart-simple me-1"></i>
        تا الان <b>{{ $model->usedCount() }}</b> سفارش با این کد ثبت شده است.
    </div>
    @endif

    <button type="submit" class="btn btn-primary btn-lg">
        <i class="fas fa-check me-1"></i> {{ $editing ? 'ذخیره تغییرات' : 'ثبت کد تخفیف' }}
    </button>
    <a href="/admin/discount/list" class="btn btn-link">انصراف</a>
</form>
@endsection
