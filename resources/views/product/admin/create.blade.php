@extends('layout.managmentLayout', [
    'title' => !empty($model) ? 'ویرایش محصول' : 'ثبت محصول جدید',
])

@section('main_content')
<nav class="mb-3 pt-md-3" aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">خانه</a></li>
        <li class="breadcrumb-item active">{{ !empty($model) ? 'ویرایش محصول' : 'ثبت محصول جدید' }}</li>
    </ol>
</nav>

<div class="mb-4"><h2 class="h5 mb-0">{{ !empty($model) ? 'ویرایش محصول' : 'ثبت محصول جدید' }}</h2></div>

{{-- بدون این بخش، فرم پس از خطای اعتبارسنجی بدون هیچ توضیحی دوباره نمایش داده می‌شد --}}
@if(isset($errors) && $errors->any())
<div class="alert alert-danger">
    <p class="mb-2 fw-bold"><i class="fa fa-exclamation-triangle me-1"></i> ثبت انجام نشد:</p>
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" enctype="multipart/form-data" action="{{ !empty($model) ? url('/admin/product/update/'.$model->id) : url('/admin/product/store') }}">
    @csrf
    @if(!empty($model)) @method('put') @endif

    <section class="card card-body shadow-sm p-4 mb-4">
        <h6 class="border-bottom pb-2 mb-3">اطلاعات اصلی قطعه</h6>
        <div class="row">
            <div class="col-sm-6 mb-3">
                <label class="form-label">عنوان قطعه *</label>
                <input id="productTitle" type="text" name="title" class="form-control" required value="{{ $model->title ?? '' }}">
            </div>
            @php
                use App\Enums\ProductCategory;
                // مقدار انتخاب‌شده از کنترلر می‌آید (ستون محصول یا جدول واسط) و با
                // == مقایسه می‌شود؛ مقایسه‌ی === با مقدار رشته‌ای دیتابیس هیچ‌وقت درست نمی‌شد
                $currentCategoryId = old('category_id', $selectedCategoryId ?? ($model->category_id ?? null));
            @endphp
            <div class="col-sm-6 mb-3">
                <label class="form-label">دسته‌بندی</label>
                <select name="category_id" class="form-control">
                    <option value="">انتخاب کنید</option>
                    @foreach(ProductCategory::cases() as $category)
                        <option value="{{ $category->value }}" {{ $currentCategoryId == $category->value ? 'selected' : '' }}>{{ $category->label() }}</option>
                    @endforeach
                </select>
                <small class="text-muted">تعیین دسته باعث می‌شود قطعه در فیلتر دسته‌بندی فروشگاه دیده شود.</small>
            </div>
            <div class="col-sm-4 mb-3">
                <label>خودرو مناسب</label>
                <input id="productCarModel" type="text" name="car_model" class="form-control" value="{{ $model->car_model ?? '' }}" placeholder="مثلا: پژو 206، سمند">
            </div>
            <div class="col-sm-4 mb-3">
                <label>SKU</label>
                <input id="productSku" type="text" name="sku" class="form-control" value="{{ $model->sku ?? '' }}">
            </div>
            <div class="col-sm-4 mb-3">
                <label>موجودی (تعداد) *</label>
                {{-- موجودی خالی یعنی محصول در فروشگاه دیده می‌شود ولی به سبد اضافه نمی‌شود --}}
                <input type="number" name="stock" min="0" required class="form-control" value="{{ old('stock', $model->stock ?? 0) }}">
                <small class="text-muted">با مقدار صفر، محصول «ناموجود» نمایش داده می‌شود.</small>
            </div>
        </div>

        <h6 class="border-bottom pb-2 mb-3 mt-3">قیمت، تخفیف و فروش ویژه</h6>
        <div class="row">
            <div class="col-sm-3 mb-3">
                <label>قیمت خرید (تومان)</label>
                <input type="number" name="regular_price" class="form-control" value="{{ $model->regular_price ?? '' }}">
                <small class="text-muted">فقط برای محاسبه‌ی سود؛ به مشتری نمایش داده نمی‌شود.</small>
            </div>
            <div class="col-sm-3 mb-3">
                <label>قیمت فروش (تومان)</label>
                <input type="number" name="price" class="form-control" value="{{ $model->price ?? '' }}">
            </div>
            <div class="col-sm-3 mb-3">
                <label>درصد تخفیف</label>
                <input type="number" name="discount_percent" min="0" max="100" class="form-control" value="{{ $model->discount_percent ?? 0 }}">
                <small class="text-muted">روی «قیمت فروش» اعمال می‌شود و قیمت پیش از تخفیف، خط‌خورده به مشتری نشان داده می‌شود.</small>
            </div>
            <div class="col-sm-3 mb-3">
                <label>فروش ویژه</label>
                <select name="is_special_offer" class="form-control">
                    <option value="0" {{ ($model->is_special_offer ?? 0)==0?'selected':'' }}>خیر</option>
                    <option value="1" {{ ($model->is_special_offer ?? 0)==1?'selected':'' }}>بله</option>
                </select>
            </div>
        </div>

        <h6 class="border-bottom pb-2 mb-3 mt-3">وضعیت و توضیحات</h6>
        <div class="row">
            <div class="col-sm-3 mb-3">
                <label>وضعیت</label>
                <select name="is_active" class="form-control">
                    <option value="1" {{ ($model->is_active ?? 1)==1?'selected':'' }}>فعال</option>
                    <option value="0" {{ ($model->is_active ?? 1)==0?'selected':'' }}>غیرفعال</option>
                </select>
            </div>
            <div class="col-sm-3 mb-3">
                <label>وزن (گرم)</label>
                <input type="number" name="weight" class="form-control" value="{{ $model->weight ?? '' }}">
            </div>
            <div class="col-sm-12 mb-3">
                <label>توضیح کوتاه کارت محصول</label>
                <textarea name="short_description" rows="3" class="form-control">{{ $model->short_description ?? '' }}</textarea>
            </div>
            <div class="col-sm-12 mb-3">
                <label>توضیحات قطعه</label>
                <textarea id="productDescription" name="description" rows="5" class="form-control">{{ $model->description ?? '' }}</textarea>
            </div>
            <div class="col-sm-6 mb-3">
                <label>تصویر قطعه</label>
                <input type="file" name="file" class="form-control">
                @if(!empty($model) && $model->hasImage())
                    <div class="mt-2 d-flex align-items-end gap-3">
                        <img src="{{ $model->image() }}" style="max-width:200px; border-radius:8px;">
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteImage({{ $model->id }})"><i class="fa fa-trash me-1"></i> حذف تصویر</button>
                    </div>
                @endif
            </div>
        </div>
    </section>

    @php
        /* پیش‌نمایش و مقادیر پیش‌فرض دقیقا از همان متدهایی می‌آیند که صفحه‌ی
           محصول استفاده می‌کند، تا آنچه مدیر اینجا می‌بیند همان چیزی باشد که
           در نتایج گوگل ظاهر می‌شود. */
        $seoAutoTitle = !empty($model) ? $model->autoSeoTitle() : '';
        $seoAutoDescription = !empty($model) ? $model->autoSeoDescription() : '';
        $seoProductUrl = !empty($model) ? seo_url($model->url()) : seo_url('/product/…');
        $seoHasImage = !empty($model) && $model->hasImage();
    @endphp

    <section class="card card-body shadow-sm p-4 mb-4">
        <h6 class="border-bottom pb-2 mb-3"><i class="fas fa-chart-line me-1"></i> سئوی محصول</h6>
        <p class="text-muted small">
            هر فیلدی را خالی بگذارید، همان مقدار خودکارِ فعلی صفحه‌ی محصول استفاده می‌شود.
        </p>
        <div class="row">
            <div class="col-lg-7">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">کلمه کلیدی اصلی</label>
                        <input id="seoFocusKeyword" type="text" name="focus_keyword" class="form-control"
                               value="{{ old('focus_keyword', $model->focus_keyword ?? '') }}" placeholder="مثلا: لنت ترمز پژو 206">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">وضعیت ایندکس</label>
                        <div class="d-flex gap-3 pt-2">
                            <label class="form-check">
                                <input type="hidden" name="robots_index" value="0">
                                <input class="form-check-input" type="checkbox" name="robots_index" value="1" {{ old('robots_index', $model->robots_index ?? 1) ? 'checked' : '' }}> Index
                            </label>
                            <label class="form-check">
                                <input type="hidden" name="robots_follow" value="0">
                                <input class="form-check-input" type="checkbox" name="robots_follow" value="1" {{ old('robots_follow', $model->robots_follow ?? 1) ? 'checked' : '' }}> Follow
                            </label>
                        </div>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">عنوان سئو</label>
                        <input id="seoTitle" type="text" name="seo_title" class="form-control"
                               value="{{ old('seo_title', $model->seo_title ?? '') }}" placeholder="{{ $seoAutoTitle }}">
                        <div id="seoTitleCount" class="text-muted" style="font-size:12px;margin-top:4px"></div>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">توضیحات متا</label>
                        <textarea id="seoDescription" name="seo_description" rows="3" class="form-control" placeholder="{{ $seoAutoDescription }}">{{ old('seo_description', $model->seo_description ?? '') }}</textarea>
                        <div id="seoDescriptionCount" class="text-muted" style="font-size:12px;margin-top:4px"></div>
                    </div>
                    <div class="col-12 mb-3">
                        <label class="form-label">Canonical URL</label>
                        <input id="seoCanonical" type="url" name="canonical_url" class="form-control" dir="ltr"
                               value="{{ old('canonical_url', $model->canonical_url ?? '') }}" placeholder="{{ $seoProductUrl }}">
                        {{-- عمدا پیش‌فرض خالی است: اگر آدرس فعلی ذخیره شود، با تغییر بعدیِ
                             نام یا کد فنی، اسلاگ عوض می‌شود و کانونیکال به آدرس مرده اشاره می‌کند. --}}
                        <small class="text-muted">فقط وقتی پر کنید که این محصول باید به صفحه‌ی دیگری کانونیکال شود.</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="border rounded bg-white mb-3">
                    <div class="d-flex align-items-center gap-3 p-3 border-bottom">
                        <div id="seoScoreRing" style="width:56px;height:56px;border-radius:50%;display:grid;place-items:center;background:conic-gradient(#dc3545 0deg,#edf2f7 0deg);font-weight:800">0</div>
                        <div>
                            <strong id="seoScoreText">نیاز به تکمیل</strong>
                            <div class="text-muted small">تحلیل زنده مشابه Rank Math</div>
                        </div>
                    </div>
                    <div id="seoChecks" class="p-3" style="display:grid;grid-template-columns:1fr;gap:8px"></div>
                </div>
                <div class="border rounded bg-white p-3" dir="ltr" style="text-align:left">
                    <div id="snippetTitle" style="color:#1a0dab;font-size:18px;line-height:1.4"></div>
                    <div id="snippetUrl" style="color:#006621;font-size:13px;word-break:break-all">{{ $seoProductUrl }}</div>
                    <div id="snippetDesc" style="color:#4d5156;font-size:13px;line-height:1.6"></div>
                </div>
            </div>
        </div>
    </section>

    <button type="submit" class="btn btn-primary btn-lg">{{ !empty($model) ? 'ویرایش محصول' : 'ثبت محصول' }}</button>
</form>

<script>
(function () {
    var BRAND = @json(seo_site_name());
    var ISACO = @json($model->isaco_code ?? '');
    var AUTO_DESCRIPTION = @json($seoAutoDescription);
    var PRODUCT_URL = @json($seoProductUrl);
    var HAS_IMAGE = @json($seoHasImage);

    function val(id) {
        var el = document.getElementById(id);
        return el ? (el.value || '').trim() : '';
    }

    /** همان الگوی autoSeoTitle در مدل Product، برای پیش‌نمایش زنده. */
    function autoTitle() {
        var text = ('خرید ' + val('productTitle') + (ISACO ? ' کد ' + ISACO : '')).trim();
        if (!text || text.indexOf(BRAND) !== -1) return text;
        return text + ' | ' + BRAND;
    }

    function addCheck(list, label, ok, warn) {
        list.push({ label: label, cls: ok ? 'good' : (warn ? 'warn' : 'bad') });
        return ok ? 10 : (warn ? 5 : 0);
    }

    function analyze() {
        var title = val('seoTitle') || autoTitle();
        var desc = val('seoDescription') || AUTO_DESCRIPTION;
        var focus = val('seoFocusKeyword');
        var body = val('productDescription');
        var words = body ? body.split(/\s+/).filter(Boolean).length : 0;
        var checks = [], score = 0;

        score += addCheck(checks, 'کلمه کلیدی اصلی وارد شده باشد', focus.length > 0, false);
        score += addCheck(checks, 'کلمه کلیدی در عنوان سئو باشد', !!focus && title.indexOf(focus) !== -1, !!focus && val('productTitle').indexOf(focus) !== -1);
        score += addCheck(checks, 'کلمه کلیدی در توضیحات متا باشد', !!focus && desc.indexOf(focus) !== -1, false);
        score += addCheck(checks, 'کلمه کلیدی در توضیحات قطعه باشد', !!focus && body.indexOf(focus) !== -1, false);
        score += addCheck(checks, 'طول عنوان سئو بین ۳۵ تا ۶۰ کاراکتر باشد', title.length >= 35 && title.length <= 60, title.length > 0 && title.length <= 70);
        score += addCheck(checks, 'توضیحات متا بین ۱۲۰ تا ۱۶۰ کاراکتر باشد', desc.length >= 120 && desc.length <= 160, desc.length >= 80 && desc.length <= 180);
        score += addCheck(checks, 'توضیحات قطعه حداقل ۱۰۰ کلمه باشد', words >= 100, words >= 50);
        score += addCheck(checks, 'کد فنی (SKU) وارد شده باشد', val('productSku').length > 0, false);
        score += addCheck(checks, 'خودرو مناسب مشخص شده باشد', val('productCarModel').length > 0, false);
        score += addCheck(checks, 'تصویر برای محصول ثبت شده باشد', HAS_IMAGE, false);

        document.getElementById('seoTitleCount').textContent = title.length + ' کاراکتر';
        document.getElementById('seoDescriptionCount').textContent = desc.length + ' کاراکتر';
        document.getElementById('snippetTitle').textContent = title || 'عنوان سئو';
        document.getElementById('snippetDesc').textContent = desc || 'توضیحات متا';
        document.getElementById('snippetUrl').textContent = val('seoCanonical') || PRODUCT_URL;

        var finalScore = Math.min(100, score);
        var ring = document.getElementById('seoScoreRing');
        var color = finalScore >= 80 ? '#198754' : (finalScore >= 50 ? '#ffc107' : '#dc3545');
        ring.textContent = finalScore;
        ring.style.background = 'conic-gradient(' + color + ' ' + (finalScore * 3.6) + 'deg,#edf2f7 0deg)';
        document.getElementById('seoScoreText').textContent = finalScore >= 80 ? 'سئو خوب' : (finalScore >= 50 ? 'قابل بهبود' : 'نیاز به تکمیل');

        document.getElementById('seoChecks').innerHTML = checks.map(function (c) {
            var style = c.cls === 'good' ? 'border-color:#badbcc;background:#f0fff4;color:#176b3a'
                      : c.cls === 'warn' ? 'border-color:#ffe69c;background:#fff9db;color:#7a5b00'
                      : 'border-color:#f5c2c7;background:#fff5f5;color:#842029';
            return '<div style="border:1px solid #edf2f7;border-radius:6px;padding:9px 10px;font-size:13px;' + style + '">' + c.label + '</div>';
        }).join('');
    }

    ['productTitle', 'productSku', 'productCarModel', 'productDescription',
     'seoFocusKeyword', 'seoTitle', 'seoDescription', 'seoCanonical'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('input', analyze);
    });

    analyze();
})();
</script>

@if(!empty($model) && $model->images->count())
<section class="card card-body shadow-sm p-4 mb-4">
    <h6 class="border-bottom pb-2 mb-3">گالری تصاویر محصول</h6>
    <div class="d-flex flex-wrap gap-3">
        @foreach($model->images as $image)
            <div class="border rounded p-2 text-center" style="width:150px;">
                <img src="{{ $image->path }}" style="width:100%;height:96px;object-fit:contain;">
                @if($image->is_primary)
                    <span class="badge bg-success mt-2 d-block">تصویر اصلی</span>
                @else
                    <form method="POST" action="/admin/product/{{ $model->id }}/image/{{ $image->id }}/primary" class="mt-2">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">اصلی شود</button>
                    </form>
                @endif
            </div>
        @endforeach
    </div>
</section>
@endif

@if(!empty($model))
<form method="POST" enctype="multipart/form-data" action="/admin/product/upload-image/{{ $model->id }}" class="mt-3">
    @csrf
    <section class="card card-body shadow-sm p-4">
        <h6 class="border-bottom pb-2 mb-3">آپلود مستقیم تصویر</h6>
        <div class="row align-items-end">
            <div class="col-sm-8"><input type="file" name="file" class="form-control" accept="image/*" required></div>
            <div class="col-sm-4"><button type="submit" class="btn btn-success w-100"><i class="fa fa-upload me-1"></i> آپلود تصویر</button></div>
        </div>
    </section>
</form>
@endif
@endsection

@if(!empty($model))
@section('js')
<script>
function deleteImage(productId) {
    if (!confirm('آیا از حذف تصویر اطمینان دارید؟')) return;
    fetch('/admin/product/delete-image/' + productId, {
        method: 'DELETE',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
    }).then(function() { location.reload(); });
}
</script>
@endsection
@endif