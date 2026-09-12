@extends('layout.managmentLayout', [
    'title' => 'کلیدواژه‌های هدف',
    'menu' => 'seo/keywords',
])

@section('main_content')
<style>
.path-cell{direction:ltr;text-align:left;font-family:consolas,monospace;font-size:12px;word-break:break-all}
.kw-table td{vertical-align:middle;font-size:13px}
.kw-check{display:inline-block;width:22px;height:22px;line-height:22px;text-align:center;border-radius:50%;font-size:11px;font-weight:700}
.kw-check.ok{background:#d1e7dd;color:#0f5132}
.kw-check.bad{background:#f8d7da;color:#842029}
.kw-check.na{background:#e9ecef;color:#6c757d}
.kw-score{font-weight:700;min-width:36px;display:inline-block;text-align:center;border-radius:4px;padding:2px 6px}
.kw-score.good{background:#d1e7dd;color:#0f5132}
.kw-score.mid{background:#fff3cd;color:#664d03}
.kw-score.low{background:#f8d7da;color:#842029}
.kw-stat{border:1px solid #e5e7eb;border-radius:8px;padding:10px 14px;background:#fff;min-width:120px}
.kw-stat b{font-size:20px;display:block}
.kw-page-preview{font-size:11px;color:#6c757d;max-width:320px;white-space:normal}
.kw-edit-row{display:none}
tr.editing + .kw-edit-row{display:table-row}
.kw-edit-row td{background:#f8f9fa}
</style>

<nav class="mb-3 pt-md-3" aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">خانه</a></li>
        <li class="breadcrumb-item active">کلیدواژه‌های هدف</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <h1 class="h4 mb-0">کلیدواژه‌های هدف</h1>
    <div class="d-flex gap-2">
        <a href="/admin/seo/searches" class="btn btn-outline-primary btn-sm"><i class="fas fa-magnifying-glass me-1"></i> جستجوهای کاربران</a>
        <a href="/admin/seo/gsc" class="btn btn-outline-primary btn-sm"><i class="fab fa-google me-1"></i> سرچ کنسول</a>
        <form method="POST" action="/admin/seo/keywords/seed" onsubmit="return confirm('برای هر «قطعه × خودرو»، «نوع قطعه» و «مدل خودرو» که به اندازه‌ی کافی محصول دارد یک عبارت هدف ساخته می‌شود. عبارت‌های موجود دست نمی‌خورند. ادامه؟')">
            @csrf
            <button class="btn btn-outline-success btn-sm" title="از قطعات و خودروهای موجود در انبار"><i class="fas fa-wand-magic-sparkles me-1"></i> فهرست اولیه از ساختار سایت</button>
        </form>
        @if($stats['total'])
        <form method="POST" action="/admin/seo/keywords/check">
            @csrf
            <button class="btn btn-outline-secondary btn-sm" onclick="this.disabled=true;this.innerText='در حال بررسی…';this.form.submit()"><i class="fas fa-rotate me-1"></i> بررسی همه‌ی صفحات</button>
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

<div class="alert alert-light border">
    هر عبارت مهم دقیقا <b>یک</b> صفحه‌ی هدف دارد. بعد از تعیین صفحه، سیستم آن را می‌خواند و می‌گوید عبارت در
    <b>عنوان (title)</b>، <b>تیتر (H1)</b> و <b>توضیحات (description)</b> هست یا نه. اگر سرچ کنسول وصل باشد، رتبه و کلیک واقعی گوگل هم کنار هر عبارت می‌نشیند.
    عبارت‌های جدید را از <a href="/admin/seo/searches">جستجوهای کاربران</a> و <a href="/admin/seo/gsc">سرچ کنسول</a> با یک کلیک اضافه کنید.
</div>

<div class="d-flex gap-2 flex-wrap mb-4">
    <div class="kw-stat"><small class="text-muted">کل عبارت‌ها</small><b>{{ $stats['total'] }}</b></div>
    <div class="kw-stat"><small class="text-muted">بدون صفحه‌ی هدف</small><b class="{{ $stats['no_target'] ? 'text-danger' : '' }}">{{ $stats['no_target'] }}</b></div>
    <div class="kw-stat"><small class="text-muted">بررسی‌نشده</small><b>{{ $stats['unchecked'] }}</b></div>
    <div class="kw-stat"><small class="text-muted">صفحه‌ی ضعیف</small><b class="{{ $stats['weak'] ? 'text-warning' : '' }}">{{ $stats['weak'] }}</b></div>
    <div class="kw-stat"><small class="text-muted">صفحه‌ی اول گوگل</small><b class="text-success">{{ $stats['top10'] }}</b></div>
    <div class="kw-stat"><small class="text-muted">رتبه ۱۱ تا ۲۰</small><b class="text-primary">{{ $stats['striking'] }}</b></div>
</div>

<section class="card card-body shadow-sm p-4 mb-4" id="add">
    <h6 class="border-bottom pb-2 mb-3">افزودن عبارت هدف</h6>
    <form method="POST" action="/admin/seo/keywords">
        @csrf
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">عبارت *</label>
                <input type="text" name="keyword" class="form-control" required value="{{ old('keyword', $prefill['keyword']) }}" placeholder="مثلا: لنت ترمز جلو پژو 206">
                <small class="text-muted">همان‌طور که مشتری در گوگل می‌نویسد.</small>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">صفحه‌ی هدف</label>
                <input type="text" name="target_url" class="form-control path-cell" value="{{ old('target_url', $prefill['target']) }}" placeholder="/part/لنت-ترمز/پژو-206">
                <small class="text-muted">مسیر نسبی یا آدرس کامل. خالی = بعدا.</small>
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">اولویت</label>
                <select name="priority" class="form-control">
                    @foreach(\App\Models\SeoKeyword::PRIORITIES as $value => $label)
                        <option value="{{ $value }}" {{ (string) old('priority', 2) === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 mb-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus me-1"></i> افزودن</button>
            </div>
        </div>
    </form>
</section>

<section class="card card-body shadow-sm p-4">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h6 class="mb-0">{{ $model->total() }} عبارت</h6>
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="q" class="form-control form-control-sm" value="{{ request('q') }}" placeholder="جستجو">
            <select name="sort" class="form-control form-control-sm">
                <option value="priority" {{ $sort === 'priority' ? 'selected' : '' }}>اولویت</option>
                <option value="problems" {{ $sort === 'problems' ? 'selected' : '' }}>مشکل‌دارها اول</option>
                <option value="impressions" {{ $sort === 'impressions' ? 'selected' : '' }}>بیشترین نمایش گوگل</option>
                <option value="clicks" {{ $sort === 'clicks' ? 'selected' : '' }}>بیشترین کلیک گوگل</option>
                <option value="position" {{ $sort === 'position' ? 'selected' : '' }}>بهترین رتبه</option>
            </select>
            <button class="btn btn-sm btn-outline-secondary">اعمال</button>
        </form>
    </div>

    @if($model->count())
    <div class="table-responsive">
        <table class="table table-sm align-middle kw-table">
            <thead>
                <tr>
                    <th>عبارت</th>
                    <th>صفحه‌ی هدف</th>
                    <th class="text-center" title="عبارت در title هست؟">title</th>
                    <th class="text-center" title="عبارت در H1 هست؟">H1</th>
                    <th class="text-center" title="عبارت در meta description هست؟">desc</th>
                    <th class="text-center" title="تعداد تکرار در متن صفحه">متن</th>
                    <th class="text-center">نمره</th>
                    <th class="text-center" title="رتبه‌ی میانگین در گوگل">رتبه</th>
                    <th class="text-center" title="نمایش / کلیک در گوگل">نمایش / کلیک</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($model as $kw)
                @php
                    $score = $kw->score();
                    $scoreClass = $score === null ? '' : ($score >= 80 ? 'good' : ($score >= 50 ? 'mid' : 'low'));
                    $check = fn ($v) => $v === null ? '<span class="kw-check na">؟</span>' : ($v ? '<span class="kw-check ok">✓</span>' : '<span class="kw-check bad">✗</span>');
                @endphp
                <tr id="kw-{{ $kw->id }}">
                    <td>
                        <span class="badge {{ $kw->priority == 1 ? 'bg-danger' : ($kw->priority == 2 ? 'bg-primary' : 'bg-secondary') }}" title="اولویت">{{ \App\Models\SeoKeyword::PRIORITIES[$kw->priority] ?? '' }}</span>
                        <b>{{ $kw->keyword }}</b>
                        @if($kw->note)<div class="text-muted" style="font-size:11px">{{ $kw->note }}</div>@endif
                    </td>
                    <td>
                        @if($kw->target_url)
                            <a href="{{ $kw->target_url }}" target="_blank" class="path-cell">{{ $kw->target_url }}</a>
                            @if($kw->checked_at)
                                <div class="kw-page-preview">
                                    @if($kw->http_status !== 200)
                                        <span class="text-danger">HTTP {{ $kw->http_status ?: 'خطا' }}</span>
                                    @elseif($kw->is_indexable === false)
                                        <span class="text-danger">noindex / canonical به جای دیگر</span>
                                    @else
                                        <span title="title">{{ \Illuminate\Support\Str::limit($kw->page_title, 90) }}</span>
                                    @endif
                                </div>
                            @endif
                            @if($kw->hasCannibalization())
                                <div class="text-warning" style="font-size:11px" title="گوگل صفحه‌ی دیگری را برای این عبارت نشان می‌دهد">
                                    <i class="fas fa-triangle-exclamation"></i> گوگل نشان می‌دهد: <span class="path-cell">{{ $kw->gsc_top_page }}</span>
                                </div>
                            @endif
                        @else
                            <span class="text-danger">تعیین نشده</span>
                        @endif
                    </td>
                    <td class="text-center">{!! $check($kw->in_title) !!}</td>
                    <td class="text-center">{!! $check($kw->in_h1) !!}</td>
                    <td class="text-center">{!! $check($kw->in_description) !!}</td>
                    <td class="text-center">{{ $kw->body_hits ?? '—' }}</td>
                    <td class="text-center">
                        @if($score === null)<span class="text-muted">—</span>@else<span class="kw-score {{ $scoreClass }}">{{ $score }}</span>@endif
                    </td>
                    <td class="text-center">
                        @if($kw->gsc_position !== null)
                            <b class="{{ $kw->gsc_position <= 10 ? 'text-success' : ($kw->gsc_position <= 20 ? 'text-primary' : '') }}">{{ number_format($kw->gsc_position, 1) }}</b>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-center text-nowrap">
                        @if($kw->gsc_impressions !== null)
                            {{ number_format($kw->gsc_impressions) }} / {{ number_format($kw->gsc_clicks) }}
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="this.closest('tr').classList.toggle('editing')" title="ویرایش"><i class="fas fa-pen"></i></button>
                        @if($kw->target_url)
                        <form method="POST" action="/admin/seo/keywords/{{ $kw->id }}/check" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-outline-primary" title="بررسی دوباره‌ی صفحه"><i class="fas fa-rotate"></i></button>
                        </form>
                        @endif
                        <form method="POST" action="/admin/seo/keywords/{{ $kw->id }}" class="d-inline" onsubmit="return confirm('حذف شود؟')">
                            @csrf @method('delete')
                            <button class="btn btn-sm btn-outline-danger" title="حذف"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <tr class="kw-edit-row">
                    <td colspan="10">
                        <form method="POST" action="/admin/seo/keywords/{{ $kw->id }}" class="row g-2 align-items-end">
                            @csrf @method('put')
                            <div class="col-md-5">
                                <label class="form-label mb-1" style="font-size:12px">صفحه‌ی هدف</label>
                                <input type="text" name="target_url" class="form-control form-control-sm path-cell" value="{{ $kw->target_url }}" placeholder="/part/…">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label mb-1" style="font-size:12px">اولویت</label>
                                <select name="priority" class="form-control form-control-sm">
                                    @foreach(\App\Models\SeoKeyword::PRIORITIES as $value => $label)
                                        <option value="{{ $value }}" {{ $kw->priority == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label mb-1" style="font-size:12px">یادداشت</label>
                                <input type="text" name="note" class="form-control form-control-sm" value="{{ $kw->note }}">
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-sm btn-primary w-100">ذخیره</button>
                            </div>
                            @if($kw->checked_at && $kw->http_status === 200)
                            <div class="col-12 text-muted" style="font-size:11px">
                                <div><b>title:</b> {{ $kw->page_title ?: '—' }}</div>
                                <div><b>H1:</b> {{ $kw->page_h1 ?: '—' }}</div>
                                <div><b>description:</b> {{ $kw->page_description ?: '—' }}</div>
                                <div>آخرین بررسی: {{ $kw->checked_at->diffForHumans() }}</div>
                            </div>
                            @endif
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $model->links() }}</div>
    @else
        <p class="text-muted mb-0">هنوز عبارتی ثبت نشده. از <a href="/admin/seo/searches">جستجوهای کاربران</a> یا <a href="/admin/seo/gsc">سرچ کنسول</a> شروع کنید، یا بالا دستی اضافه کنید.</p>
    @endif
</section>
@endsection
