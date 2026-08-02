@extends('layout.managmentLayout', [
    'title' => 'مدیریت کاربران',
    'menu' => 'user/list'
])
@section('main_content')
<div class="d-flex align-items-center justify-content-between mb-3">
    <h5 style="font-weight:700; margin:0;"><i class="fas fa-user-cog me-2" style="color:var(--admin-primary);"></i> مدیریت کاربران سیستم</h5>
    <a href="/admin/user/create" class="btn btn-sm" style="background:var(--admin-primary); color:#fff; border-radius:8px; font-size:0.82rem; padding:8px 16px;">
        <i class="fas fa-plus me-1"></i> کاربر جدید
    </a>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>نام</th>
                    <th>نام خانوادگی</th>
                    <th>نام کاربری</th>
                    <th>نقش</th>
                    <th>عملیات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($model as $user)
                <tr>
                    <td>{{ $user->user_id }}</td>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->family }}</td>
                    <td><code style="font-size:0.8rem;">{{ $user->username }}</code></td>
                    <td>{{ $user->role?->title ?? '—' }}</td>
                    <td>
                        <a href="/admin/user/edit/{{ $user->user_id }}" class="btn btn-sm" style="background:#e3f2fd; color:#1565c0; border:none; font-size:0.75rem;">
                            <i class="fas fa-edit"></i> ویرایش
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">کاربری یافت نشد</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
