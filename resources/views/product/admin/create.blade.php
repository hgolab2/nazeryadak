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
                <input type="text" name="title" class="form-control" required value="{{ $model->title ?? '' }}">
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
                <input type="text" name="car_model" class="form-control" value="{{ $model->car_model ?? '' }}" placeholder="مثلا: پژو 206، سمند">
            </div>
            <div class="col-sm-4 mb-3">
                <label>SKU</label>
                <input type="text" name="sku" class="form-control" value="{{ $model->sku ?? '' }}">
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
                <textarea name="description" rows="5" class="form-control">{{ $model->description ?? '' }}</textarea>
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

    <button type="submit" class="btn btn-primary btn-lg">{{ !empty($model) ? 'ویرایش محصول' : 'ثبت محصول' }}</button>
</form>

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