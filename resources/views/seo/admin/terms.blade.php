@extends('layout.managmentLayout', [
    'title' => 'صفحات فرود سئو',
    'menu' => 'seo/terms',
])

@section('main_content')
<style>
.term-state{font-size:12px}
.term-row td{vertical-align:middle}
</style>

<nav class="mb-3 pt-md-3" aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">خانه</a></li>
        <li class="breadcrumb-item active">صفحات فرود سئو</li>
    </ol>
</nav>

<h1 class="h4 mb-3">صفحات فرود سئو</h1>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

<div class="alert alert-light border">
    عنوان، توضیحات متا و <strong>متن معرفی</strong> صفحات دسته‌بندی و مدل خودرو را اینجا تعیین کنید.
    صفحه‌ای که فقط فهرست فیلترشده‌ی محصولات است، از نظر گوگل نسخه‌ی تکراری فروشگاه به حساب می‌آید؛
    متن یکتا همان چیزی است که آن را یک صفحه‌ی مستقل می‌کند.
</div>

@php
    $badge = fn($term) => $term
        ? '<span class="badge bg-success term-state">متن دارد</span>'
        : '<span class="badge bg-secondary term-state">متن ندارد</span>';
@endphp

<section class="card card-body shadow-sm p-4 mb-4">
    <h6 class="border-bottom pb-2 mb-3"><i class="fas fa-layer-group me-1"></i> دسته‌بندی‌ها ({{ count($categories) }})</h6>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead><tr><th>دسته</th><th>آدرس</th><th>وضعیت</th><th></th></tr></thead>
            <tbody>
                @foreach($categories as $row)
                <tr class="term-row">
                    <td>{{ $row['name'] }}</td>
                    <td><a href="{{ $row['url'] }}" target="_blank" dir="ltr" style="font-size:12px">{{ $row['url'] }}</a></td>
                    <td>{!! $badge($row['term']) !!}</td>
                    <td class="text-end">
                        <a href="/admin/seo/terms/edit?type=category&slug={{ urlencode($row['slug']) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-pen"></i> ویرایش
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

<section class="card card-body shadow-sm p-4 mb-4">
    <h6 class="border-bottom pb-2 mb-3"><i class="fas fa-car me-1"></i> مدل‌های خودرو ({{ count($cars) }})</h6>
    <p class="text-muted small">فهرست از ستون «خودرو مناسب» محصولات ساخته می‌شود؛ مدل تازه خودبه‌خود اینجا و در نقشه‌ی سایت ظاهر می‌شود.</p>
    <div class="table-responsive">
        <table class="table table-sm">
            <thead><tr><th>خودرو</th><th class="text-center">تعداد قطعه</th><th>آدرس</th><th>وضعیت</th><th></th></tr></thead>
            <tbody>
                @foreach($cars as $row)
                <tr class="term-row">
                    <td>{{ $row['name'] }}</td>
                    <td class="text-center"><span class="badge bg-light text-dark">{{ $row['count'] }}</span></td>
                    <td><a href="{{ $row['url'] }}" target="_blank" dir="ltr" style="font-size:12px">{{ $row['url'] }}</a></td>
                    <td>{!! $badge($row['term']) !!}</td>
                    <td class="text-end">
                        <a href="/admin/seo/terms/edit?type=car&slug={{ urlencode($row['slug']) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-pen"></i> ویرایش
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>

<section class="card card-body shadow-sm p-4">
    <h6 class="border-bottom pb-2 mb-3"><i class="fas fa-object-group me-1"></i> صفحات ترکیبی دسته × خودرو</h6>
    <p class="text-muted small">
        مثل «لنت ترمز پژو ۲۰۶». همه‌ی ترکیب‌ها ({{ count($carOptions) * count($categoryCases) }} حالت) از قبل کار می‌کنند و در نقشه‌ی سایت هستند؛
        اینجا فقط آن‌هایی فهرست می‌شوند که متن اختصاصی گرفته‌اند.
    </p>

    <form method="GET" action="/admin/seo/terms/edit" class="row g-2 align-items-end mb-3">
        <input type="hidden" name="type" value="car_category">
        <div class="col-md-4">
            <label class="form-label">خودرو</label>
            <select name="car" class="form-control" required>
                @foreach($carOptions as $slug => $car)
                    <option value="{{ $slug }}">{{ $car['name'] }} ({{ $car['count'] }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label">دسته‌بندی</label>
            <select name="category" class="form-control" required>
                @foreach($categoryCases as $case)
                    <option value="{{ $case->slug() }}">{{ $case->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <button class="btn btn-outline-primary w-100"><i class="fas fa-plus me-1"></i> نوشتن متن برای این ترکیب</button>
        </div>
    </form>

    @if($combos->count())
    <div class="table-responsive">
        <table class="table table-sm">
            <thead><tr><th>عنوان</th><th>آدرس</th><th></th></tr></thead>
            <tbody>
                @foreach($combos as $term)
                <tr class="term-row">
                    <td>{{ $term->name }}</td>
                    <td><a href="/car/{{ $term->slug }}" target="_blank" dir="ltr" style="font-size:12px">/car/{{ $term->slug }}</a></td>
                    <td class="text-end text-nowrap">
                        <a href="/admin/seo/terms/edit?type=car_category&slug={{ urlencode($term->slug) }}" class="btn btn-sm btn-primary"><i class="fas fa-pen"></i></a>
                        <form method="POST" action="/admin/seo/terms/{{ $term->id }}" class="d-inline" onsubmit="return confirm('متن این صفحه حذف شود؟')">
                            @csrf @method('delete')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
        <p class="text-muted mb-0">هنوز برای هیچ ترکیبی متن اختصاصی نوشته نشده است.</p>
    @endif
</section>
@endsection
