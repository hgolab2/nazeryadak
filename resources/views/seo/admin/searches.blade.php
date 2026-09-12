@extends('layout.managmentLayout', [
    'title' => 'جستجوهای کاربران',
    'menu' => 'seo/searches',
])

@section('main_content')
<style>
.path-cell{direction:ltr;text-align:left;font-family:consolas,monospace;font-size:12px;word-break:break-all}
.kw-table td{vertical-align:middle;font-size:13px}
.kw-stat{border:1px solid #e5e7eb;border-radius:8px;padding:10px 14px;background:#fff;min-width:120px}
.kw-stat b{font-size:20px;display:block}
</style>

<nav class="mb-3 pt-md-3" aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">خانه</a></li>
        <li class="breadcrumb-item active">جستجوهای کاربران</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <h1 class="h4 mb-0">جستجوهای کاربران داخل سایت</h1>
    <a href="/admin/seo/keywords" class="btn btn-outline-primary btn-sm"><i class="fas fa-bullseye me-1"></i> کلیدواژه‌های هدف</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="alert alert-light border">
    مشتری‌ها داخل سایت همان چیزی را می‌نویسند که در گوگل هم می‌نویسند. <b>پرتکرارها</b> عبارت‌هایی‌اند که باید برایشان صفحه‌ی قوی داشته باشید؛
    <b>بی‌نتیجه‌ها</b> یعنی قطعه‌ای که ندارید یا صفحه‌ای که ساخته نشده. ستون «صفحه‌ی پیشنهادی» می‌گوید هر عبارت طبق ساختار سایت باید به کجا برسد.
</div>

<div class="d-flex gap-2 flex-wrap mb-4">
    <div class="kw-stat"><small class="text-muted">عبارت نتیجه‌دار</small><b>{{ number_format($stats['popular']) }}</b></div>
    <div class="kw-stat"><small class="text-muted">عبارت بی‌نتیجه</small><b class="{{ $stats['empty'] ? 'text-danger' : '' }}">{{ number_format($stats['empty']) }}</b></div>
    <div class="kw-stat"><small class="text-muted">کل جستجوها</small><b>{{ number_format($stats['hits']) }}</b></div>
    <div class="kw-stat"><small class="text-muted">از تاریخ</small><b style="font-size:14px">{{ $stats['since'] ? \Carbon\Carbon::parse($stats['since'])->diffForHumans() : '—' }}</b></div>
</div>

<section class="card card-body shadow-sm p-4">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <ul class="nav nav-pills">
            <li class="nav-item"><a class="nav-link {{ $tab === 'popular' ? 'active' : '' }}" href="?tab=popular">پرتکرار</a></li>
            <li class="nav-item"><a class="nav-link {{ $tab === 'empty' ? 'active' : '' }}" href="?tab=empty">بی‌نتیجه <span class="badge bg-light text-dark">{{ number_format($stats['empty']) }}</span></a></li>
        </ul>
        <form method="GET" class="d-flex gap-2">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <input type="text" name="q" class="form-control form-control-sm" value="{{ request('q') }}" placeholder="جستجو در عبارت‌ها">
            <button class="btn btn-sm btn-outline-secondary">اعمال</button>
        </form>
    </div>

    @if(count($rows))
    <div class="table-responsive">
        <table class="table table-sm align-middle kw-table">
            <thead>
                <tr>
                    <th>عبارت</th>
                    <th class="text-center">تعداد جستجو</th>
                    <th class="text-center">نتیجه</th>
                    <th>آخرین بار</th>
                    <th>صفحه‌ی پیشنهادی</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $item)
                @php $row = $item['row']; @endphp
                <tr>
                    <td><b>{{ $row->display_term }}</b></td>
                    <td class="text-center"><span class="badge bg-primary">{{ $row->hits }}</span></td>
                    <td class="text-center">
                        @if($row->results_count > 0)
                            <a href="/shop?title={{ urlencode($row->display_term) }}" target="_blank">{{ $row->results_count }} محصول</a>
                        @else
                            <span class="badge bg-danger">هیچ</span>
                        @endif
                    </td>
                    <td class="text-nowrap text-muted">{{ $row->last_searched_at ? $row->last_searched_at->diffForHumans() : '—' }}</td>
                    <td>
                        @if($item['match'])
                            <a href="{{ $item['match']['url'] }}" target="_blank" class="path-cell">{{ $item['match']['url'] }}</a>
                            <div class="text-muted" style="font-size:11px">{{ $item['match']['label'] }}</div>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        @if($item['tracked'])
                            <a href="/admin/seo/keywords?q={{ urlencode($item['tracked']->keyword) }}" class="btn btn-sm btn-outline-success"><i class="fas fa-check me-1"></i> در فهرست هدف</a>
                        @else
                            <a href="/admin/seo/keywords?keyword={{ urlencode($row->display_term) }}{{ $item['match'] ? '&target=' . urlencode($item['match']['url']) : '' }}#add" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i> افزودن به هدف</a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $model->links() }}</div>
    @else
        <p class="text-muted mb-0">هنوز جستجویی ثبت نشده است.</p>
    @endif
</section>
@endsection
