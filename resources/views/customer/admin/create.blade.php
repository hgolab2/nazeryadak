@extends('layout.managmentLayout', [
    'title' => !empty($model) ? 'ویرایش مشتری' : 'ثبت مشتری جدید',
])

@section('main_content')

<nav class="mb-3 pt-md-3" aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">خانه</a></li>
        <li class="breadcrumb-item active">
            {{ !empty($model) ? 'ویرایش مشتری' : 'ثبت مشتری جدید' }}
        </li>
    </ol>
</nav>

<div class="mb-4">
    <h2 class="h5 mb-0">
        {{ !empty($model) ? 'ویرایش مشتری' : 'ثبت مشتری جدید' }}
    </h2>
</div>

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

            {{-- موبایل --}}
            <div class="col-sm-6 mb-3">
                <label class="form-label">شماره موبایل *</label>
                <input type="text" name="phone" class="form-control" required
                       value="{{ old('phone', $model->phone ?? '') }}">
            </div>

            {{-- ایمیل
            <div class="col-sm-6 mb-3">
                <label class="form-label">ایمیل</label>
                <input type="email" name="email" class="form-control"
                       value="{{ old('email', $model->email ?? '') }}">
            </div>
            --}}

            {{-- رمز عبور
            <div class="col-sm-6 mb-3">
                <label class="form-label">
                    رمز عبور
                    @if(!empty($model))
                        <small class="text-muted">(در صورت خالی بودن تغییر نمی‌کند)</small>
                    @endif
                </label>
                <input type="password" name="password" class="form-control">
            </div>
            --}}

            {{-- وضعیت --}}
            <div class="col-sm-6 mb-3">
                <label class="form-label">وضعیت</label>
                <select name="status" class="form-control">
                    <option value="1" {{ ($model->status ?? 1) == 1 ? 'selected' : '' }}>فعال</option>
                    <option value="0" {{ ($model->status ?? 1) == 0 ? 'selected' : '' }}>غیرفعال</option>
                </select>
            </div>

        </div>

    </section>

    <button type="submit" class="btn btn-primary btn-lg">
        {{ !empty($model) ? 'ویرایش مشتری' : 'ثبت مشتری' }}
    </button>

</form>
@endsection
