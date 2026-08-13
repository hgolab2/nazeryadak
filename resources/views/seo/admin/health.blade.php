@extends('layout.managmentLayout', [
    'title' => 'سلامت سئو',
    'menu' => 'seo/health',
])

@section('main_content')
<style>
.health-tile{border:1px solid #e3e8ef;border-radius:10px;padding:14px 16px;background:#fff;height:100%}
.health-tile b{display:block;font-size:1.6rem;line-height:1.2}
.health-tile span{font-size:.8rem;color:#6c757d}
.health-bar{height:6px;border-radius:99px;background:#edf2f7;margin-top:8px;overflow:hidden}
.health-bar i{display:block;height:100%;background:#dc3545}
</style>

<nav class="mb-3 pt-md-3" aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">خانه</a></li>
        <li class="breadcrumb-item active">سلامت سئو</li>
    </ol>
</nav>

<h1 class="h4 mb-3">سلامت سئو</h1>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

<div class="row g-3 mb-4">
    @foreach($issues as $key => $meta)
    @php $pct = $totalActive ? round(($counts[$key] / $totalActive) * 100) : 0; @endphp
    <div class="col-md-4 col-lg">
        <a href="/admin/seo/health?issue={{ $key }}" class="text-decoration-none text-dark">
            <div class="health-tile {{ $issue === $key ? 'border-primary' : '' }}">
                <b class="{{ $counts[$key] ? 'text-danger' : 'text-success' }}">{{ toPersianNumbers($counts[$key]) }}</b>
                <span>{{ $meta['label'] }}</span>
                <div class="health-bar"><i style="width:{{ $pct }}%"></i></div>
                <span style="font-size:.72rem">{{ toPersianNumbers($pct) }}٪ از {{ toPersianNumbers($totalActive) }} محصول فعال</span>
            </div>
        </a>
    </div>
    @endforeach
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="health-tile d-flex align-items-center justify-content-between">
            <div>
                <b class="{{ $reviewCount ? 'text-warning' : 'text-success' }}">{{ toPersianNumbers($reviewCount) }}</b>
                <span>نظر در انتظار تأیید</span>
            </div>
            <a href="/admin/seo/reviews" class="btn btn-sm btn-outline-primary">بررسی</a>
        </div>
    </div>
    <div class="col-md-6">
        <div class="health-tile d-flex align-items-center justify-content-between">
            <div>
                <b>{{ toPersianNumbers($termCount) }}</b>
                <span>صفحه‌ی فرود با متن اختصاصی</span>
            </div>
            <a href="/admin/seo/terms" class="btn btn-sm btn-outline-primary">مدیریت</a>
        </div>
    </div>
</div>

<section class="card card-body shadow-sm p-4">
    <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
        <h6 class="mb-0">{{ $issues[$issue]['label'] }} — {{ toPersianNumbers($model->total()) }} محصول</h6>
        <form method="GET" class="d-flex gap-2">
            <input type="hidden" name="issue" value="{{ $issue }}">
            <input type="text" name="q" class="form-control form-control-sm" value="{{ request('q') }}" placeholder="نام یا کد فنی">
            <button class="btn btn-sm btn-outline-secondary">جستجو</button>
        </form>
    </div>
    <p class="text-muted small">{{ $issues[$issue]['hint'] }}</p>

    @if($model->count())
    <form method="POST" action="/admin/seo/health/generate">
        @csrf
        @if($issue === 'no_description')
        <div class="alert alert-warning d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span>
                برای محصولات انتخاب‌شده پیش‌نویس توضیحات ساخته می‌شود (از روی نام، کد فنی، دسته و خودرو).
                <strong>متن تولیدشده باید بازبینی شود.</strong>
                برای اجرای انبوه: <code>php artisan products:describe</code>
            </span>
            <button type="submit" class="btn btn-sm btn-warning"><i class="fas fa-magic me-1"></i> ساخت پیش‌نویس</button>
        </div>
        @endif

        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        @if($issue === 'no_description')<th style="width:32px"><input type="checkbox" id="checkAll" class="form-check-input"></th>@endif
                        <th>محصول</th><th>کد فنی</th><th>خودرو</th><th>وضعیت</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($model as $product)
                    <tr>
                        @if($issue === 'no_description')
                        <td><input type="checkbox" name="ids[]" value="{{ $product->id }}" class="form-check-input row-check" checked></td>
                        @endif
                        <td style="max-width:320px"><a href="{{ $product->url() }}" target="_blank" style="font-size:.85rem">{{ $product->title }}</a></td>
                        <td dir="ltr" style="font-size:12px">{{ $product->sku ?: '—' }}</td>
                        <td style="font-size:12px">{{ $product->car_model ?: '—' }}</td>
                        <td>
                            @if($product->rating_count)<span class="badge bg-warning text-dark">{{ number_format((float) $product->rating_avg, 1) }}★</span>@endif
                            @if(!$product->robots_index)<span class="badge bg-secondary">noindex</span>@endif
                        </td>
                        <td class="text-end">
                            <a href="/admin/product/edit/{{ $product->id }}" class="btn btn-sm btn-primary"><i class="fas fa-pen"></i> ویرایش</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </form>
    <div class="mt-3">{{ $model->links() }}</div>
    @else
        <p class="text-muted mb-0">محصولی با این ایراد پیدا نشد.</p>
    @endif
</section>

<script>
(function () {
    var all = document.getElementById('checkAll');
    if (!all) return;
    all.checked = true;
    all.addEventListener('change', function () {
        document.querySelectorAll('.row-check').forEach(function (el) { el.checked = all.checked; });
    });
})();
</script>
@endsection
