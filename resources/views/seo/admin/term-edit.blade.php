@extends('layout.managmentLayout', [
    'title' => 'ویرایش صفحه‌ی فرود',
    'menu' => 'seo/terms',
])

@section('main_content')
<nav class="mb-3 pt-md-3" aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">خانه</a></li>
        <li class="breadcrumb-item"><a href="/admin/seo/terms">صفحات فرود سئو</a></li>
        <li class="breadcrumb-item active">{{ $autoName }}</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">{{ $autoName }}</h1>
    <a href="{{ $targetUrl }}" target="_blank" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-external-link-alt me-1"></i> مشاهده صفحه
    </a>
</div>

@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

<form method="POST" action="/admin/seo/terms">
    @csrf
    <input type="hidden" name="type" value="{{ $term->type }}">
    <input type="hidden" name="slug" value="{{ $term->slug }}">

    <section class="card card-body shadow-sm p-4 mb-4">
        <h6 class="border-bottom pb-2 mb-3">متن صفحه</h6>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">نام داخلی</label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $term->name ?: $autoName) }}">
                <small class="text-muted">فقط برای شناسایی در همین فهرست؛ در سایت دیده نمی‌شود.</small>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label">تیتر صفحه (H1)</label>
                <input id="termHeading" type="text" name="heading" class="form-control" value="{{ old('heading', $term->heading) }}" placeholder="{{ $autoName }}">
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">متن معرفی (بالای فهرست محصولات)</label>
                <textarea name="intro" rows="6" class="form-control" placeholder="مثلا: توضیح کوتاه درباره‌ی قطعات این خودرو، کدهای فنی رایج، و نکاتی که خریدار باید بداند.">{{ old('intro', $term->intro) }}</textarea>
                <small class="text-muted">HTML مجاز است (پاراگراف، تیتر h2/h3، لیست). مهم‌ترین فیلد این صفحه از نظر سئو.</small>
            </div>
            <div class="col-12 mb-3">
                <label class="form-label">متن تکمیلی (پایین فهرست محصولات)</label>
                <textarea name="body" rows="6" class="form-control" placeholder="مثلا: پرسش‌های پرتکرار، راهنمای انتخاب قطعه، شرایط ارسال و ضمانت.">{{ old('body', $term->body) }}</textarea>
            </div>
        </div>
    </section>

    <section class="card card-body shadow-sm p-4 mb-4">
        <h6 class="border-bottom pb-2 mb-3">سئو</h6>
        <div class="row">
            <div class="col-lg-7">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">کلمه کلیدی اصلی</label>
                        <input type="text" name="focus_keyword" class="form-control" value="{{ old('focus_keyword', $term->focus_keyword) }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">وضعیت</label>
                        <div class="d-flex gap-3 pt-2">
                            <label class="form-check">
                                <input type="hidden" name="robots_index" value="0">
                                <input class="form-check-input" type="checkbox" name="robots_index" value="1" {{ old('robots_index', $term->robots_index ?? 1) ? 'checked' : '' }}> Index
                            </label>
                            <label class="form-check">
                                <input type="hidden" name="is_active" value="0">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', $term->is_active ?? 1) ? 'checked' : '' }}> فعال
                            </label>
                        </div>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">عنوان سئو</label>
                        <input id="termSeoTitle" type="text" name="seo_title" class="form-control" value="{{ old('seo_title', $term->seo_title) }}">
                        <div id="termTitleCount" class="text-muted" style="font-size:12px;margin-top:4px"></div>
                        <small class="text-muted">خالی بگذارید تا عنوان خودکار صفحه استفاده شود.</small>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">توضیحات متا</label>
                        <textarea id="termSeoDescription" name="seo_description" rows="3" class="form-control">{{ old('seo_description', $term->seo_description) }}</textarea>
                        <div id="termDescCount" class="text-muted" style="font-size:12px;margin-top:4px"></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="border rounded bg-white p-3" dir="ltr" style="text-align:left">
                    <div id="termSnippetTitle" style="color:#1a0dab;font-size:18px;line-height:1.4"></div>
                    <div style="color:#006621;font-size:13px;word-break:break-all">{{ seo_url($targetUrl) }}</div>
                    <div id="termSnippetDesc" style="color:#4d5156;font-size:13px;line-height:1.6"></div>
                </div>
            </div>
        </div>
    </section>

    <div class="d-flex gap-2 mb-4">
        <button type="submit" class="btn btn-primary btn-lg">ذخیره</button>
        <a href="/admin/seo/terms" class="btn btn-light btn-lg">بازگشت</a>
        @if($term->exists)
        <button type="submit" form="term-delete" class="btn btn-outline-danger btn-lg ms-auto" onclick="return confirm('متن این صفحه حذف شود؟')">حذف متن</button>
        @endif
    </div>
</form>

@if($term->exists)
<form id="term-delete" method="POST" action="/admin/seo/terms/{{ $term->id }}">@csrf @method('delete')</form>
@endif

<script>
(function () {
    var AUTO = @json($autoName);

    function val(id) { var el = document.getElementById(id); return el ? (el.value || '').trim() : ''; }

    function update() {
        var title = val('termSeoTitle') || val('termHeading') || AUTO;
        var desc = val('termSeoDescription');
        document.getElementById('termTitleCount').textContent = title.length + ' کاراکتر' + (title.length > 60 ? ' — بلندتر از حد گوگل' : '');
        document.getElementById('termDescCount').textContent = desc.length + ' کاراکتر' + (desc && (desc.length < 120 || desc.length > 160) ? ' — بازه‌ی پیشنهادی ۱۲۰ تا ۱۶۰' : '');
        document.getElementById('termSnippetTitle').textContent = title;
        document.getElementById('termSnippetDesc').textContent = desc || 'توضیحات متا';
    }

    ['termSeoTitle', 'termSeoDescription', 'termHeading'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('input', update);
    });

    update();
})();
</script>
@endsection
