@extends('layout.managmentLayout', [
    'title' => !empty($model) ? 'ویرایش مشتری' : 'ثبت مشتری جدید',
])

@section('main_content')

<nav class="mb-3 pt-md-3" aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">خانه</a></li>
        <li class="breadcrumb-item"><a href="/admin/customer/list">مشتریان</a></li>
        <li class="breadcrumb-item active">
            {{ !empty($model) ? 'ویرایش مشتری' : 'ثبت مشتری جدید' }}
        </li>
    </ol>
</nav>

<div class="mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <h2 class="h5 mb-0">
        {{ !empty($model) ? 'ویرایش مشتری' : 'ثبت مشتری جدید' }}
    </h2>

    @if(!empty($model))
        {{-- ورود به حساب مشتری برای عیب‌یابی؛ تب تازه باز می‌شود تا پنل مدیریت
             در تب فعلی دست‌نخورده بماند --}}
        <a class="btn btn-outline-dark btn-sm" target="_blank"
           href="{{ url('/admin/customer/'.$model->id.'/impersonate') }}"
           onclick="return confirm('وارد حساب این مشتری می‌شوید. ادامه می‌دهید؟');">
            <i class="fa fa-user-secret me-1"></i> ورود به حساب این مشتری
        </a>
    @endif
</div>

@if(session('success'))
<div class="alert alert-success"><i class="fa fa-check-circle me-1"></i> {{ session('success') }}</div>
@endif

@if(isset($errors) && $errors->any())
<div class="alert alert-danger">
    <p class="mb-2 fw-bold"><i class="fa fa-exclamation-triangle me-1"></i> ثبت انجام نشد:</p>
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST"
      action="{{ !empty($model) ? url('/admin/customer/update/'.$model->id) : url('/admin/customer/store') }}">
    @csrf
    @if(!empty($model)) @method('put') @endif

    <section class="card card-body shadow-sm p-4 mb-4">

        <div class="row">

            {{-- نام --}}
            <div class="col-sm-6 mb-3">
                <label class="form-label">نام *</label>
                <input type="text" name="first_name" class="form-control" required
                       value="{{ old('first_name', $model->first_name ?? '') }}">
            </div>

            {{-- نام خانوادگی --}}
            <div class="col-sm-6 mb-3">
                <label class="form-label">نام خانوادگی *</label>
                <input type="text" name="last_name" class="form-control" required
                       value="{{ old('last_name', $model->last_name ?? '') }}">
            </div>

            {{-- موبایل: نام کاربری مشتری هم همین است --}}
            <div class="col-sm-6 mb-3">
                <label class="form-label">شماره موبایل *</label>
                <input type="text" name="phone" class="form-control" required dir="ltr"
                       placeholder="09120000000"
                       value="{{ old('phone', $model->phone ?? '') }}">
                <small class="text-muted">ورود مشتری با همین شماره انجام می‌شود.</small>
            </div>

            {{-- وضعیت --}}
            <div class="col-sm-6 mb-3">
                <label class="form-label">وضعیت</label>
                <select name="status" class="form-control">
                    <option value="1" {{ old('status', $model->status ?? 1) == 1 ? 'selected' : '' }}>فعال</option>
                    <option value="0" {{ old('status', $model->status ?? 1) == 0 ? 'selected' : '' }}>غیرفعال</option>
                </select>
                <small class="text-muted">مشتری غیرفعال نمی‌تواند وارد حساب شود.</small>
            </div>

            {{-- رمز عبور فقط هنگام ساخت؛ در حالت ویرایش کارت جداگانه دارد --}}
            @if(empty($model))
            <div class="col-sm-6 mb-3">
                <label class="form-label">رمز عبور <small class="text-muted">(اختیاری)</small></label>
                <input type="text" name="password" class="form-control" dir="ltr" autocomplete="off">
                <small class="text-muted">خالی بگذارید تا مشتری با کد پیامکی وارد شود.</small>
            </div>
            @endif

        </div>

    </section>

    <button type="submit" class="btn btn-primary btn-lg">
        {{ !empty($model) ? 'ویرایش مشتری' : 'ثبت مشتری' }}
    </button>

</form>

@if(!empty($model))
<section class="card card-body shadow-sm p-4 mt-4">
    <h3 class="h6 mb-3"><i class="fa fa-lock me-1"></i> رمز عبور مشتری</h3>

    <p class="text-muted small mb-3">
        وضعیت فعلی:
        @if(!empty($model->password))
            <span class="badge bg-success">رمز عبور دارد</span>
            — می‌تواند با رمز یا کد پیامکی وارد شود.
        @else
            <span class="badge bg-secondary">رمز عبور ندارد</span>
            — ورود فقط با کد پیامکی انجام می‌شود.
        @endif
    </p>

    <form method="POST" action="{{ url('/admin/customer/'.$model->id.'/password') }}" class="row g-3 align-items-end">
        @csrf
        <div class="col-sm-4">
            <label class="form-label">رمز عبور جدید</label>
            <input type="text" name="password" class="form-control" dir="ltr" autocomplete="off" minlength="6">
        </div>
        <div class="col-sm-4">
            <label class="form-label">تکرار رمز عبور جدید</label>
            <input type="text" name="password_confirmation" class="form-control" dir="ltr" autocomplete="off" minlength="6">
        </div>
        <div class="col-sm-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-check me-1"></i> ثبت رمز
            </button>
            @if(!empty($model->password))
            <button type="submit" name="action" value="remove" class="btn btn-outline-danger" formnovalidate
                    onclick="return confirm('رمز عبور این مشتری حذف شود؟ پس از آن فقط با کد پیامکی وارد می‌شود.');">
                <i class="fa fa-trash me-1"></i> حذف رمز
            </button>
            @endif
        </div>
    </form>

    <p class="text-muted small mb-0 mt-3">
        مشتری خودش هم می‌تواند از مسیر «فراموشی رمز عبور» با کد پیامکی رمزش را عوض کند.
    </p>
</section>
@endif
@endsection
