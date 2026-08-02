@extends('layout.managmentLayout', [
    'title' => isset($model) ? 'ویرایش کاربر' : 'کاربر جدید',
    'menu' => isset($model) ? 'user/edit' : 'user/create'
])
@section('main_content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 style="font-weight:700; margin:0;">
        <i class="fas fa-user-{{ isset($model) ? 'edit' : 'plus' }} me-2" style="color:var(--admin-primary);"></i>
        {{ isset($model) ? 'ویرایش کاربر: ' . $model->fullname() : 'ایجاد کاربر جدید' }}
    </h5>
    <a href="/admin/user/list" class="btn btn-sm" style="background:var(--admin-bg); border:1px solid #dde4ec; font-size:0.82rem; padding:8px 16px;">
        <i class="fas fa-arrow-right me-1"></i> بازگشت
    </a>
</div>

<div class="admin-card">
    <form method="POST" action="{{ isset($model) ? '/admin/user/update/' . $model->user_id : '/admin/user/store' }}">
        @csrf
        @if(isset($model))
            @method('PUT')
        @endif

        <div class="row">
            <div class="col-md-4 mb-3">
                <label style="font-size:0.82rem; font-weight:600; color:#555;">نام</label>
                <input type="text" name="name" class="form-control" value="{{ $model->name ?? '' }}" required
                       style="border-radius:8px; font-size:0.88rem; padding:10px 14px;">
            </div>
            <div class="col-md-4 mb-3">
                <label style="font-size:0.82rem; font-weight:600; color:#555;">نام خانوادگی</label>
                <input type="text" name="family" class="form-control" value="{{ $model->family ?? '' }}"
                       style="border-radius:8px; font-size:0.88rem; padding:10px 14px;">
            </div>
            <div class="col-md-4 mb-3">
                <label style="font-size:0.82rem; font-weight:600; color:#555;">نام کاربری</label>
                <input type="text" name="username" class="form-control" value="{{ $model->username ?? '' }}" required
                       style="border-radius:8px; font-size:0.88rem; padding:10px 14px; direction:ltr;">
            </div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3">
                <label style="font-size:0.82rem; font-weight:600; color:#555;">رمز عبور {{ isset($model) ? '(خالی = بدون تغییر)' : '' }}</label>
                <input type="password" name="password" class="form-control" {{ isset($model) ? '' : 'required' }}
                       style="border-radius:8px; font-size:0.88rem; padding:10px 14px; direction:ltr;">
            </div>
            <div class="col-md-4 mb-3">
                <label style="font-size:0.82rem; font-weight:600; color:#555;">نقش</label>
                <select name="role_id" class="form-select" required style="border-radius:8px; font-size:0.88rem; padding:10px 14px;">
                    <option value="">انتخاب کنید</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->role_id }}" {{ (isset($model) && $model->role_id == $role->role_id) ? 'selected' : '' }}>
                            {{ $role->title }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label style="font-size:0.82rem; font-weight:600; color:#555;">سایت</label>
                <select name="site_id" class="form-select" required style="border-radius:8px; font-size:0.88rem; padding:10px 14px;">
                    @foreach($sites as $site)
                        <option value="{{ $site->id ?? $site->siteid ?? 1 }}" {{ (isset($model) && $model->site_id == ($site->id ?? $site->siteid ?? 1)) ? 'selected' : '' }}>
                            {{ $site->title ?? $site->name ?? 'سایت ' . ($site->id ?? $site->siteid ?? 1) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="text-end mt-3">
            <button type="submit" class="btn" style="background:var(--admin-primary); color:#fff; border-radius:8px; font-size:0.88rem; padding:10px 24px;">
                <i class="fas fa-check me-1"></i> {{ isset($model) ? 'بروزرسانی' : 'ذخیره' }}
            </button>
        </div>
    </form>
</div>
@endsection
