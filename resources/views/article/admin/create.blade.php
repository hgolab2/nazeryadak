@extends('layout.managmentLayout', [
    'title' => !empty($model) ? 'ویرایش مطلب' : 'ثبت مطلب جدید',
    'menu' => !empty($model) ? 'article/list' : 'article/create',
])

@section('main_content')
@php
    $articleUrl = !empty($model) ? url($model->getUrl()) : url('/blog/preview.html');
    $seoTitle = old('seo_title', $model->seo_title ?? '');
    $seoDescription = old('seo_description', $model->seo_description ?? '');
    $focusKeyword = old('focus_keyword', $model->focus_keyword ?? '');
@endphp

<style>
.tox-tinymce{border-radius:8px!important;border-color:#d9e2ef!important}.seo-panel{border:1px solid #d9e2ef;border-radius:8px;background:#fff}.seo-score{display:flex;align-items:center;gap:12px;padding:14px;border-bottom:1px solid #edf2f7}.seo-score-ring{width:56px;height:56px;border-radius:50%;display:grid;place-items:center;background:conic-gradient(#dc3545 0deg,#edf2f7 0deg);font-weight:800}.seo-checks{padding:14px;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:8px}.seo-check{border:1px solid #edf2f7;border-radius:6px;padding:9px 10px;font-size:13px}.seo-check.good{border-color:#badbcc;background:#f0fff4;color:#176b3a}.seo-check.warn{border-color:#ffe69c;background:#fff9db;color:#7a5b00}.seo-check.bad{border-color:#f5c2c7;background:#fff5f5;color:#842029}.snippet-preview{direction:ltr;text-align:left;border:1px solid #e3e8ef;border-radius:8px;padding:14px;background:#fff}.snippet-title{color:#1a0dab;font-size:18px;line-height:1.4}.snippet-url{color:#006621;font-size:13px;word-break:break-all}.snippet-desc{color:#4d5156;font-size:13px;line-height:1.6}.char-counter{font-size:12px;color:#6c757d;margin-top:4px}.image-preview{max-width:220px;border-radius:8px;border:1px solid #e3e8ef;padding:4px;background:#fff}
</style>

<nav class="mb-3 pt-md-3" aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">خانه</a></li>
        <li class="breadcrumb-item"><a href="/admin/article/list">مدیریت بلاگ</a></li>
        <li class="breadcrumb-item active">{{ !empty($model) ? 'ویرایش مطلب' : 'ثبت مطلب جدید' }}</li>
    </ol>
</nav>

@if($errors->any())
    <div class="alert alert-danger">
        @foreach($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<form method="POST" enctype="multipart/form-data" action="{{ !empty($model) ? url('/admin/article/update/'.$model->articleid) : url('/admin/article/store') }}">
    @csrf
    @if(!empty($model)) @method('put') @endif

    <section class="admin-card">
        <div class="admin-card-title"><i class="fas fa-newspaper"></i> اطلاعات مطلب</div>
        <div class="row">
            <div class="col-md-8 mb-3"><label class="form-label">عنوان *</label><input id="articleTitle" type="text" name="titr" class="form-control" required value="{{ old('titr', $model->titr ?? '') }}"></div>
            <div class="col-md-4 mb-3"><label class="form-label">روتیتر</label><input type="text" name="rutitr" class="form-control" value="{{ old('rutitr', $model->rutitr ?? '') }}"></div>
            <div class="col-md-8 mb-3"><label class="form-label">خلاصه</label><textarea id="articleExcerpt" name="sutitr" rows="3" class="form-control">{{ old('sutitr', $model->sutitr ?? '') }}</textarea></div>
            <div class="col-md-4 mb-3"><label class="form-label">دسته</label><select name="categoryid" class="form-control">@foreach($categories as $category)<option value="{{ $category->categoryid }}" {{ (string) old('categoryid', $selectedCategory ?? 17) === (string) $category->categoryid ? 'selected' : '' }}>{{ $category->title ?: ('دسته ' . $category->categoryid) }}</option>@endforeach</select></div>
            <div class="col-md-4 mb-3"><label class="form-label">تاریخ انتشار</label><input type="datetime-local" name="showdate" class="form-control" value="{{ old('showdate', !empty($model->showdate) ? date('Y-m-d\TH:i', strtotime($model->showdate)) : date('Y-m-d\TH:i')) }}"></div>
            <div class="col-md-4 mb-3"><label class="form-label">وضعیت</label><select name="hidden" class="form-control"><option value="0" {{ (string) old('hidden', $model->hidden ?? 0) === '0' ? 'selected' : '' }}>منتشر شود</option><option value="1" {{ (string) old('hidden', $model->hidden ?? 0) === '1' ? 'selected' : '' }}>مخفی بماند</option></select></div>
            <div class="col-md-4 mb-3"><label class="form-label">تصویر شاخص</label><input id="imageInput" type="file" name="file" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp"></div>
            <div class="col-md-4 mb-3">@if(!empty($model) && $model->images)<img id="imagePreview" src="{{ $model->images->getPath() }}" class="image-preview" alt="{{ $model->titr }}">@else<img id="imagePreview" class="image-preview d-none" alt="preview">@endif</div>
        </div>
    </section>

    <section class="admin-card">
        <div class="admin-card-title"><i class="fas fa-align-right"></i> متن مطلب</div>
        <textarea id="articleText" name="text" rows="18" class="form-control">{{ old('text', $model->text ?? '') }}</textarea>
    </section>

    <section class="admin-card">
        <div class="admin-card-title"><i class="fas fa-chart-line"></i> Rank Math SEO</div>
        <div class="row">
            <div class="col-lg-7">
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label">کلمه کلیدی اصلی</label><input id="focusKeyword" type="text" name="focus_keyword" class="form-control" value="{{ $focusKeyword }}"></div>
                    <div class="col-md-6 mb-3"><label class="form-label">کلمات کلیدی</label><input id="keywords" type="text" name="keywords" class="form-control" value="{{ old('keywords', $model->keywords ?? '') }}"></div>
                    <div class="col-12 mb-3"><label class="form-label">عنوان سئو</label><input id="seoTitle" type="text" name="seo_title" class="form-control" value="{{ $seoTitle }}"><div id="seoTitleCount" class="char-counter"></div></div>
                    <div class="col-12 mb-3"><label class="form-label">توضیحات متا</label><textarea id="seoDescription" name="seo_description" rows="3" class="form-control">{{ $seoDescription }}</textarea><div id="seoDescriptionCount" class="char-counter"></div></div>
                    <div class="col-12 mb-3"><label class="form-label">Canonical URL</label><input id="canonicalUrl" type="url" name="canonical_url" class="form-control" value="{{ old('canonical_url', $model->canonical_url ?? $articleUrl) }}"></div>
                    <div class="col-md-6 mb-3"><label class="form-check"><input type="hidden" name="robots_index" value="0"><input class="form-check-input" type="checkbox" name="robots_index" value="1" {{ old('robots_index', $model->robots_index ?? 1) ? 'checked' : '' }}> Index</label></div>
                    <div class="col-md-6 mb-3"><label class="form-check"><input type="hidden" name="robots_follow" value="0"><input class="form-check-input" type="checkbox" name="robots_follow" value="1" {{ old('robots_follow', $model->robots_follow ?? 1) ? 'checked' : '' }}> Follow</label></div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="seo-panel mb-3"><div class="seo-score"><div id="seoScoreRing" class="seo-score-ring">0</div><div><strong id="seoScoreText">نیاز به تکمیل</strong><div class="text-muted small">تحلیل زنده مشابه Rank Math</div></div></div><div id="seoChecks" class="seo-checks"></div></div>
                <div class="snippet-preview"><div id="snippetTitle" class="snippet-title"></div><div id="snippetUrl" class="snippet-url">{{ $articleUrl }}</div><div id="snippetDesc" class="snippet-desc"></div></div>
            </div>
        </div>
    </section>

    <section class="admin-card"><div class="admin-card-title"><i class="fas fa-link"></i> اطلاعات تکمیلی</div><div class="row"><div class="col-md-6 mb-3"><label class="form-label">منبع</label><input type="text" name="source" class="form-control" value="{{ old('source', $model->source ?? '') }}"></div><div class="col-md-6 mb-3"><label class="form-label">لینک منبع</label><input type="text" name="urlsource" class="form-control" value="{{ old('urlsource', $model->urlsource ?? '') }}"></div></div></section>

    <div class="d-flex gap-2 mb-4"><button type="submit" class="btn btn-primary btn-lg">{{ !empty($model) ? 'ذخیره تغییرات' : 'ثبت مطلب' }}</button><a href="/admin/article/list" class="btn btn-light btn-lg">بازگشت</a></div>
</form>

<script src="/vendor/tinymce/tinymce.min.js"></script>
<script>
(function(){
const form=document.querySelector('form'),articleText=document.getElementById('articleText');
function editorHtml(){const ed=window.tinymce&&tinymce.get('articleText');return ed?ed.getContent():articleText.value;}
function editorText(){const html=editorHtml();const div=document.createElement('div');div.innerHTML=html;return (div.textContent||div.innerText||'').replace(/\s+/g,' ').trim();}
function sync(){const ed=window.tinymce&&tinymce.get('articleText');if(ed){ed.save();}analyze();}
if(window.tinymce){
    tinymce.init({
        selector:'#articleText',
        directionality:'rtl',
        height:520,
        menubar:'file edit view insert format tools table help',
        plugins:'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table wordcount help directionality',
        toolbar:'undo redo | blocks fontfamily fontsize | bold italic underline forecolor backcolor | alignright aligncenter alignleft alignjustify | rtl ltr | bullist numlist outdent indent | link image media table | removeformat code fullscreen preview',
        block_formats:'پاراگراف=p; تیتر 2=h2; تیتر 3=h3; تیتر 4=h4; نقل قول=blockquote',
        branding:false,
        promotion:false,
        convert_urls:false,
        relative_urls:false,
        remove_script_host:false,
        content_style:'body{font-family:tahoma,arial,sans-serif;font-size:15px;line-height:2;direction:rtl;text-align:right} img{max-width:100%;height:auto}',
        setup:function(editor){editor.on('init keyup change input undo redo SetContent',sync);}
    });
}
form.addEventListener('submit',sync);
const imageInput=document.getElementById('imageInput'),imagePreview=document.getElementById('imagePreview'); imageInput.addEventListener('change',()=>{const f=imageInput.files[0];if(!f)return;imagePreview.src=URL.createObjectURL(f);imagePreview.classList.remove('d-none');});
const fields=['articleTitle','articleExcerpt','focusKeyword','seoTitle','seoDescription','keywords','canonicalUrl'].map(id=>document.getElementById(id)); fields.forEach(el=>el&&el.addEventListener('input',analyze));
function val(id){return (document.getElementById(id).value||'').trim();}
function addCheck(list,label,ok,warn){list.push({label,cls:ok?'good':(warn?'warn':'bad')});return ok?14:(warn?7:0)}
function analyze(){const title=val('seoTitle')||val('articleTitle'),desc=val('seoDescription')||val('articleExcerpt'),focus=val('focusKeyword'),body=editorText(),html=editorHtml(),words=body?body.split(' ').length:0;let checks=[],score=0;score+=addCheck(checks,'کلمه کلیدی اصلی وارد شده باشد',focus.length>0,false);score+=addCheck(checks,'کلمه کلیدی در عنوان سئو باشد',focus&&title.includes(focus),focus&&val('articleTitle').includes(focus));score+=addCheck(checks,'کلمه کلیدی در توضیحات متا باشد',focus&&desc.includes(focus),false);score+=addCheck(checks,'طول عنوان سئو بین 35 تا 60 کاراکتر باشد',title.length>=35&&title.length<=60,title.length>0&&title.length<=70);score+=addCheck(checks,'توضیحات متا بین 120 تا 160 کاراکتر باشد',desc.length>=120&&desc.length<=160,desc.length>=80&&desc.length<=180);score+=addCheck(checks,'متن مقاله حداقل 300 کلمه باشد',words>=300,words>=150);score+=addCheck(checks,'مقاله دارای تیتر H2 باشد',/<h2[\s>]/i.test(html),false);document.getElementById('seoTitleCount').textContent=title.length+' کاراکتر';document.getElementById('seoDescriptionCount').textContent=desc.length+' کاراکتر';document.getElementById('snippetTitle').textContent=title||'عنوان سئو';document.getElementById('snippetDesc').textContent=desc||'توضیحات متا';document.getElementById('snippetUrl').textContent=val('canonicalUrl')||'{{ $articleUrl }}';const finalScore=Math.min(100,score);const ring=document.getElementById('seoScoreRing');ring.textContent=finalScore;ring.style.background='conic-gradient('+(finalScore>=80?'#198754':finalScore>=50?'#ffc107':'#dc3545')+' '+(finalScore*3.6)+'deg,#edf2f7 0deg)';document.getElementById('seoScoreText').textContent=finalScore>=80?'سئو خوب':finalScore>=50?'قابل بهبود':'نیاز به تکمیل';document.getElementById('seoChecks').innerHTML=checks.map(c=>'<div class="seo-check '+c.cls+'">'+c.label+'</div>').join('');}
analyze();
})();
</script>
@endsection
