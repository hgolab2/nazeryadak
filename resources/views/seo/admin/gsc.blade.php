@extends('layout.managmentLayout', [
    'title' => 'سرچ کنسول',
    'menu' => 'seo/gsc',
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
        <li class="breadcrumb-item active">سرچ کنسول</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <h1 class="h4 mb-0">عبارت‌های گوگل (Search Console)</h1>
    <div class="d-flex gap-2">
        <a href="/admin/seo/keywords" class="btn btn-outline-primary btn-sm"><i class="fas fa-bullseye me-1"></i> کلیدواژه‌های هدف</a>
        @if($stats['configured'])
        <form method="POST" action="/admin/seo/gsc/sync">
            @csrf
            <button class="btn btn-primary btn-sm" onclick="this.disabled=true;this.innerText='در حال دریافت…';this.form.submit()"><i class="fas fa-cloud-arrow-down me-1"></i> دریافت از API</button>
        </form>
        @endif
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
@endif

<div class="row">
    <div class="col-lg-7">
        <div class="alert alert-light border h-100 mb-3">
            <b>این داده از کجا می‌آید؟</b>
            <ol class="mb-2 mt-1 ps-4">
                <li>در <a href="https://search.google.com/search-console" target="_blank">Search Console</a> → <b>Performance</b> → بازه‌ی ۳ ماه → بالا سمت راست <b>Export → Download CSV</b>.</li>
                <li>فایل زیپ را باز کنید و <b>Queries.csv</b> را این‌جا آپلود کنید.</li>
            </ol>
            @if($stats['configured'])
                <div class="text-success"><i class="fas fa-plug me-1"></i> اتصال API فعال است؛ هر شب خودکار دریافت می‌شود.</div>
            @else
                <div class="text-muted" style="font-size:12px">برای دریافت خودکار، SEO_GSC_CREDENTIALS (فایل Service Account) و SEO_GSC_SITE_URL را در .env تنظیم کنید.</div>
            @endif
        </div>
    </div>
    <div class="col-lg-5">
        <section class="card card-body shadow-sm p-3 mb-3">
            <form method="POST" action="/admin/seo/gsc/import" enctype="multipart/form-data">
                @csrf
                <label class="form-label">آپلود Queries.csv</label>
                <div class="d-flex gap-2 flex-wrap">
                    <input type="file" name="csv" accept=".csv,text/csv" class="form-control form-control-sm" required style="max-width:260px">
                    <input type="number" name="days" class="form-control form-control-sm" value="90" min="1" max="480" style="width:90px" title="بازه‌ی گزارش (روز)">
                    <button class="btn btn-sm btn-primary"><i class="fas fa-upload me-1"></i> وارد کردن</button>
                </div>
                <label class="form-check mt-2 mb-0" style="font-size:12px">
                    <input type="hidden" name="replace" value="0">
                    <input class="form-check-input" type="checkbox" name="replace" value="1" checked> داده‌ی قبلی پاک شود (عکس تازه از گزارش)
                </label>
            </form>
        </section>
    </div>
</div>

<div class="d-flex gap-2 flex-wrap mb-4">
    <div class="kw-stat"><small class="text-muted">عبارت</small><b>{{ number_format($stats['terms']) }}</b></div>
    <div class="kw-stat"><small class="text-muted">کلیک</small><b>{{ number_format($stats['clicks']) }}</b></div>
    <div class="kw-stat"><small class="text-muted">نمایش</small><b>{{ number_format($stats['impressions']) }}</b></div>
    <div class="kw-stat"><small class="text-muted">بازه</small><b>{{ $stats['period_days'] }} روز</b></div>
    <div class="kw-stat"><small class="text-muted">آخرین دریافت</small><b style="font-size:14px">{{ $stats['synced_at'] ? \Carbon\Carbon::parse($stats['synced_at'])->diffForHumans() : '—' }}</b></div>
</div>

<section class="card card-body shadow-sm p-4">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <ul class="nav nav-pills flex-wrap">
            <li class="nav-item"><a class="nav-link {{ $view === 'striking' ? 'active' : '' }}" href="?view=striking" title="رتبه ۱۱ تا ۲۰ با نمایش قابل توجه — با کمی کار به صفحه‌ی اول می‌رسند">میوه‌های دم دست</a></li>
            <li class="nav-item"><a class="nav-link {{ $view === 'impressions' ? 'active' : '' }}" href="?view=impressions">بیشترین نمایش</a></li>
            <li class="nav-item"><a class="nav-link {{ $view === 'clicks' ? 'active' : '' }}" href="?view=clicks">بیشترین کلیک</a></li>
            <li class="nav-item"><a class="nav-link {{ $view === 'low_ctr' ? 'active' : '' }}" href="?view=low_ctr" title="صفحه‌ی اول ولی کلیک کم — عنوان و توضیحات جذاب نیست">کلیک کم</a></li>
            <li class="nav-item"><a class="nav-link {{ $view === 'cannibal' ? 'active' : '' }}" href="?view=cannibal" title="چند صفحه برای یک عبارت — رقابت داخلی">رقابت داخلی</a></li>
        </ul>
        <form method="GET" class="d-flex gap-2">
            <input type="hidden" name="view" value="{{ $view }}">
            <input type="text" name="q" class="form-control form-control-sm" value="{{ request('q') }}" placeholder="جستجو در عبارت‌ها">
            <button class="btn btn-sm btn-outline-secondary">اعمال</button>
        </form>
    </div>

    @if($view === 'striking')
        <p class="text-muted" style="font-size:12px">عبارت‌هایی با رتبه‌ی ۱۱ تا ۲۰ و دست‌کم ۲۰ نمایش. این‌ها نزدیک‌ترین به صفحه‌ی اول‌اند؛ اولویت اول برای کار.</p>
    @elseif($view === 'low_ctr')
        <p class="text-muted" style="font-size:12px">عبارت‌هایی که در صفحه‌ی اول هستند ولی کمتر از ۲٪ کلیک می‌گیرند. title و description صفحه‌ی هدف را جذاب‌تر کنید (قیمت، «اصلی»، «ارسال فوری»).</p>
    @elseif($view === 'cannibal')
        <p class="text-muted" style="font-size:12px">یک عبارت که گوگل برایش چند صفحه‌ی مختلف از سایت را نشان داده. یکی را هدف کنید و از بقیه به آن لینک بدهید. (فقط با داده‌ی API دیده می‌شود؛ CSV بعدِ صفحه ندارد.)</p>
    @endif

    @if(count($rows))
    <div class="table-responsive">
        <table class="table table-sm align-middle kw-table">
            <thead>
                <tr>
                    <th>عبارت</th>
                    <th class="text-center">کلیک</th>
                    <th class="text-center">نمایش</th>
                    <th class="text-center">CTR</th>
                    <th class="text-center">رتبه</th>
                    <th>صفحه‌ای که گوگل نشان می‌دهد</th>
                    <th>صفحه‌ی پیشنهادی</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $item)
                @php $row = $item['row']; $ctr = $row->impressions > 0 ? $row->clicks / $row->impressions : 0; @endphp
                <tr>
                    <td>
                        <b>{{ $row->query }}</b>
                        @if($row->pages > 1)<span class="badge bg-warning text-dark" title="چند صفحه برای این عبارت">{{ $row->pages }} صفحه</span>@endif
                    </td>
                    <td class="text-center">{{ number_format($row->clicks) }}</td>
                    <td class="text-center">{{ number_format($row->impressions) }}</td>
                    <td class="text-center">{{ number_format($ctr * 100, 1) }}٪</td>
                    <td class="text-center"><b class="{{ $row->position <= 10 ? 'text-success' : ($row->position <= 20 ? 'text-primary' : '') }}">{{ number_format($row->position, 1) }}</b></td>
                    <td>
                        @if($item['top_page'])
                            <a href="{{ $item['top_page'] }}" target="_blank" class="path-cell">{{ \Illuminate\Support\Str::limit($item['top_page'], 60) }}</a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($item['match'])
                            <a href="{{ $item['match']['url'] }}" target="_blank" class="path-cell">{{ $item['match']['url'] }}</a>
                            @if($item['top_page'] && rtrim(urldecode($item['top_page']), '/') !== rtrim(urldecode($item['match']['url']), '/'))
                                <div class="text-warning" style="font-size:11px"><i class="fas fa-triangle-exclamation"></i> با صفحه‌ی گوگل فرق دارد</div>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        @if($item['tracked'])
                            <a href="/admin/seo/keywords?q={{ urlencode($item['tracked']->keyword) }}" class="btn btn-sm btn-outline-success"><i class="fas fa-check me-1"></i> در فهرست هدف</a>
                        @else
                            @php $target = $item['match']['url'] ?? $item['top_page'] ?? ''; @endphp
                            <a href="/admin/seo/keywords?keyword={{ urlencode($row->query) }}{{ $target ? '&target=' . urlencode($target) : '' }}#add" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i> افزودن به هدف</a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $model->links() }}</div>
    @else
        <p class="text-muted mb-0">
            @if(! $stats['rows'])
                هنوز داده‌ای وارد نشده. فایل Queries.csv را بالا آپلود کنید.
            @else
                عبارتی با این شرط پیدا نشد.
            @endif
        </p>
    @endif
</section>
@endsection
