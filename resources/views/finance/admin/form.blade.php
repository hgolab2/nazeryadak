@extends('layout.managmentLayout', [
    'title' => $model ? 'ویرایش تراکنش' : 'ثبت تراکنش',
    'menu'  => 'finance',
])

@section('main_content')
@php
    $type = old('type', $model->type ?? request('type', 'expense'));
    $type = isset(\App\Models\FinanceTransaction::TYPES[$type]) ? $type : 'expense';
    $occurredOn = old('occurred_on', $model ? \Morilog\Jalali\Jalalian::fromCarbon($model->occurred_on)->format('Y/m/d') : \Morilog\Jalali\Jalalian::now()->format('Y/m/d'));
@endphp

<nav class="mb-2 pt-md-3" aria-label="Breadcrumb">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="/dashboardAdmin">داشبورد</a></li>
        <li class="breadcrumb-item"><a href="/admin/finance">حسابداری</a></li>
        <li class="breadcrumb-item active">{{ $model ? 'ویرایش تراکنش' : 'ثبت تراکنش' }}</li>
    </ol>
</nav>

<h1 class="h4 mb-3">{{ $model ? 'ویرایش تراکنش' : 'ثبت تراکنش' }}</h1>

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $model ? '/admin/finance/' . $model->id : '/admin/finance' }}" class="card shadow-sm" style="max-width:760px;">
    @csrf
    @if($model) @method('put') @endif
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label fw-bold">نوع</label>
                <div class="d-flex gap-2">
                    @foreach(\App\Models\FinanceTransaction::TYPES as $key => $label)
                        <label class="btn btn-sm flex-grow-1 {{ $key === 'expense' ? 'btn-outline-danger' : 'btn-outline-success' }} type-btn {{ $type === $key ? 'active' : '' }}">
                            <input type="radio" name="type" value="{{ $key }}" class="d-none" {{ $type === $key ? 'checked' : '' }}> {{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="col-md-6">
                <label class="form-label fw-bold">دسته</label>
                @foreach(\App\Models\FinanceTransaction::CATEGORIES as $typeKey => $list)
                    <select name="category" class="form-select category-select" data-type="{{ $typeKey }}" {{ $type === $typeKey ? '' : 'disabled hidden' }}>
                        @foreach($list as $key => $label)
                            <option value="{{ $key }}" {{ old('category', $model->category ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                @endforeach
            </div>

            <div class="col-md-8">
                <label class="form-label fw-bold">عنوان</label>
                <input type="text" name="title" class="form-control" required maxlength="190"
                       value="{{ old('title', $model->title ?? '') }}"
                       placeholder="مثلا: هزینه سرور شهریور، پیامک ۱۰۰۰ عددی، پست سفارش ۱۲">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold">مبلغ (تومان)</label>
                <input type="text" name="amount" class="form-control" required inputmode="numeric" dir="ltr"
                       value="{{ old('amount', $model ? number_format($model->amount) : '') }}">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold">تاریخ (شمسی)</label>
                <input type="text" name="occurred_on" class="form-control" required dir="ltr" value="{{ $occurredOn }}" placeholder="۱۴۰۵/۰۶/۲۰">
            </div>

            <div class="col-md-4">
                <label class="form-label fw-bold">شماره سفارش <span class="text-muted fw-normal">(اختیاری)</span></label>
                <input type="text" name="order_id" class="form-control" inputmode="numeric" dir="ltr"
                       value="{{ old('order_id', $model->order_id ?? ($order->id ?? '')) }}" placeholder="مثلا 12">
                <div class="form-text">اگر هزینه بابت یک سفارش خاص است (پست، بسته‌بندی)، از سود همان سفارش کم می‌شود.</div>
            </div>

            <div class="col-md-4">
                @if($order)
                    <label class="form-label fw-bold">سفارش</label>
                    <div class="form-control bg-light" style="font-size:.85rem;">
                        <a href="/admin/order/show/{{ $order->id }}">#{{ $order->id }}</a>
                        — {{ $order->customer?->fullName() ?: '—' }}
                        — {{ number_format((int) $order->total_price) }} تومان
                    </div>
                @endif
            </div>

            <div class="col-12">
                <label class="form-label fw-bold">توضیح <span class="text-muted fw-normal">(اختیاری)</span></label>
                <textarea name="note" class="form-control" rows="2" maxlength="2000">{{ old('note', $model->note ?? '') }}</textarea>
            </div>
        </div>
    </div>
    <div class="card-footer d-flex gap-2">
        <button class="btn btn-primary"><i class="fa fa-save me-1"></i> {{ $model ? 'ذخیره تغییرات' : 'ثبت' }}</button>
        <a href="{{ $order ? '/admin/order/show/' . $order->id : '/admin/finance' }}" class="btn btn-light">بازگشت</a>
    </div>
</form>
@endsection

@section('js')
<script>
    // با عوض شدن نوع، فهرست دسته‌های همان نوع نشان داده می‌شود؛ فقط یکی
    // از دو select فعال است تا مقدار درست ارسال شود
    $('.type-btn input').on('change', function () {
        var type = this.value;
        $('.type-btn').removeClass('active');
        $(this).closest('label').addClass('active');
        $('.category-select').each(function () {
            var mine = $(this).data('type') === type;
            $(this).prop('disabled', !mine).prop('hidden', !mine);
        });
    });
</script>
@endsection
