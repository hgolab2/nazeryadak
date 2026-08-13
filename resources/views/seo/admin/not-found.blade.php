@extends('layout.managmentLayout', [
    'title' => 'مانیتور خطاهای ۴۰۴',
    'menu' => 'seo/404',
])

@section('main_content')
<style>
.path-cell{direction:ltr;text-align:left;font-family:consolas,monospace;font-size:13px;word-break:break-all}
</style>

<nav class="mb-3 pt-md-3" aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">خانه</a></li>
        <li class="breadcrumb-item active">مانیتور خطاهای ۴۰۴</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">مانیتور خطاهای ۴۰۴</h1>
    <div class="d-flex gap-2">
        <a href="/admin/seo/redirects" class="btn btn-outline-primary"><i class="fas fa-exchange-alt me-1"></i> ریدایرکت‌ها</a>
        @if($model->total())
        <form method="POST" action="/admin/seo/404/clear" onsubmit="return confirm('کل فهرست خطاهای ۴۰۴ پاک شود؟')">
            @csrf @method('delete')
            <button class="btn btn-outline-danger"><i class="fas fa-broom me-1"></i> پاک‌سازی فهرست</button>
        </form>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="alert alert-light border">
    مسیرهایی که کاربر یا خزنده‌ی گوگل سراغشان رفته و صفحه‌ای پیدا نشده است.
    برای هر مسیرِ پرتکرار، با دکمه‌ی «ساخت ریدایرکت» آدرس درست را تعیین کنید تا اعتبار آن لینک از دست نرود.
</div>

<section class="card card-body shadow-sm p-4">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h6 class="mb-0">{{ $model->total() }} مسیر</h6>
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="q" class="form-control form-control-sm" value="{{ request('q') }}" placeholder="جستجوی مسیر">
            <select name="sort" class="form-control form-control-sm">
                <option value="last_seen_at" {{ $sort === 'last_seen_at' ? 'selected' : '' }}>تازه‌ترین</option>
                <option value="hits" {{ $sort === 'hits' ? 'selected' : '' }}>پربازدیدترین</option>
            </select>
            <button class="btn btn-sm btn-outline-secondary">اعمال</button>
        </form>
    </div>

    @if($model->count())
    <div class="table-responsive">
        <table class="table table-sm align-middle">
            <thead>
                <tr>
                    <th>مسیر</th>
                    <th class="text-center">تعداد</th>
                    <th>آخرین بار</th>
                    <th>ارجاع‌دهنده</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($model as $row)
                <tr>
                    <td class="path-cell">{{ $row->path }}</td>
                    <td class="text-center"><span class="badge bg-danger">{{ $row->hits }}</span></td>
                    <td class="text-nowrap">{{ $row->last_seen_at ? $row->last_seen_at->diffForHumans() : '—' }}</td>
                    <td class="path-cell text-muted" style="max-width:260px">{{ $row->referer ?: '—' }}</td>
                    <td class="text-nowrap">
                        <a href="/admin/seo/redirects?source={{ urlencode($row->path) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-exchange-alt me-1"></i> ساخت ریدایرکت
                        </a>
                        <form method="POST" action="/admin/seo/404/{{ $row->id }}" class="d-inline">
                            @csrf @method('delete')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $model->links() }}</div>
    @else
        <p class="text-muted mb-0">خطای ۴۰۴ ثبت نشده است.</p>
    @endif
</section>
@endsection
