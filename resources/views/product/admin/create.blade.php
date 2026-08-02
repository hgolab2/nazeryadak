@extends('layout.managmentLayout', [
    'title' => !empty($model) ? 'ویرایش محصول' : 'ثبت محصول جدید',
])

@section('main_content')

<nav class="mb-3 pt-md-3" aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">خانه</a></li>
        <li class="breadcrumb-item active">
            {{ !empty($model) ? 'ویرایش محصول' : 'ثبت محصول جدید' }}
        </li>
    </ol>
</nav>

<div class="mb-4">
    <h2 class="h5 mb-0">
        {{ !empty($model) ? 'ویرایش محصول' : 'ثبت محصول جدید' }}
    </h2>
</div>

<form method="POST" enctype="multipart/form-data"
      action="{{ !empty($model) ? url('/admin/product/update/'.$model->id) : url('/admin/product/store') }}">
    @csrf
    @if(!empty($model)) @method('put') @endif

    <section class="card card-body shadow-sm p-4 mb-4">

        <h6 class="border-bottom pb-2 mb-3">اطلاعات اصلی قطعه</h6>
        <div class="row">

            <div class="col-sm-6 mb-3">
                <label class="form-label">عنوان قطعه *</label>
                <input type="text" name="title" class="form-control" required
                       value="{{ $model->title ?? '' }}">
            </div>

            @php
                use App\Enums\ProductCategory;
            @endphp

            <div class="col-sm-6 mb-3">
                <label class="form-label">دسته‌بندی</label>
                <select name="category_id" class="form-control">
                    <option value="">انتخاب کنید</option>
                    @foreach(ProductCategory::cases() as $category)
                        <option value="{{ $category->value }}"
                            {{ ($model->category_id ?? null) === $category->value ? 'selected' : '' }}>
                            {{ $category->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-sm-3 mb-3">
                <label>خودرو مناسب</label>
                <input type="text" name="car_model" class="form-control" value="{{ $model->car_model ?? '' }}" placeholder="مثلاً: پژو 206، سمند">
            </div>

        </div>

        <h6 class="border-bottom pb-2 mb-3 mt-3">قیمت و موجودی</h6>
        <div class="row">

            <div class="col-sm-3 mb-3">
                <label>قیمت اصلی (تومان)</label>
                <input type="number" name="regular_price" class="form-control"
                       value="{{ $model->regular_price ?? '' }}">
            </div>

            <div class="col-sm-3 mb-3">
                <label>قیمت فروش (تومان)</label>
                <input type="number" name="price" class="form-control"
                       value="{{ $model->price ?? '' }}">
            </div>

            <div class="col-sm-3 mb-3">
                <label>موجودی</label>
                <input type="number" name="stock" class="form-control"
                       value="{{ $model->stock ?? '' }}">
            </div>

            <div class="col-sm-3 mb-3">
                <label>SKU</label>
                <input type="text" name="sku" class="form-control"
                       value="{{ $model->sku ?? '' }}">
            </div>

        </div>

        <h6 class="border-bottom pb-2 mb-3 mt-3">مشخصات فیزیکی</h6>
        <div class="row">

            <div class="col-sm-3 mb-3">
                <label>وزن (گرم)</label>
                <input type="number" name="weight" class="form-control" value="{{ $model->weight ?? '' }}">
            </div>

            <div class="col-sm-3 mb-3">
                <label>طول (سانتی‌متر)</label>
                <input type="number" name="length" class="form-control" value="{{ $model->length ?? '' }}">
            </div>

            <div class="col-sm-3 mb-3">
                <label>عرض (سانتی‌متر)</label>
                <input type="number" name="width" class="form-control" value="{{ $model->width ?? '' }}">
            </div>

            <div class="col-sm-3 mb-3">
                <label>ارتفاع (سانتی‌متر)</label>
                <input type="number" name="height" class="form-control" value="{{ $model->height ?? '' }}">
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
                <label>نوع محصول</label>
                <select name="is_physical" class="form-control">
                    <option value="1" {{ ($model->is_physical ?? 1)==1?'selected':'' }}>فیزیکی</option>
                    <option value="0" {{ ($model->is_physical ?? 1)==0?'selected':'' }}>دیجیتال</option>
                </select>
            </div>

            <div class="col-sm-12 mb-3">
                <label>توضیحات قطعه</label>
                <textarea name="description" rows="5"
                          class="form-control">{{ $model->description ?? '' }}</textarea>
            </div>

            <div class="col-sm-6 mb-3">
                <label>تصویر قطعه</label>
                <input type="file" name="file" class="form-control">
                @if(!empty($model) && $model->hasImage())
                    <div class="mt-2 d-flex align-items-end gap-3">
                        <img src="{{ $model->image() }}" style="max-width:200px; border-radius:8px;">
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteImage({{ $model->id }})">
                            <i class="fa fa-trash me-1"></i> حذف تصویر
                        </button>
                    </div>
                @elseif(!empty($model))
                    <div class="mt-2">
                        <span class="text-muted font-12">تصویری ثبت نشده — با مشاهده محصول توسط کاربران، تصویر از ایساکو دریافت می‌شود.</span>
                    </div>
                @endif
            </div>

        </div>

    </section>

    <button type="submit" class="btn btn-primary btn-lg">
        {{ !empty($model) ? 'ویرایش محصول' : 'ثبت محصول' }}
    </button>

</form>

@if(!empty($model))
<form method="POST" enctype="multipart/form-data"
      action="/admin/product/upload-image/{{ $model->id }}" class="mt-3">
    @csrf
    <section class="card card-body shadow-sm p-4">
        <h6 class="border-bottom pb-2 mb-3">آپلود مستقیم تصویر</h6>
        <div class="row align-items-end">
            <div class="col-sm-8">
                <input type="file" name="file" class="form-control" accept="image/*" required>
            </div>
            <div class="col-sm-4">
                <button type="submit" class="btn btn-success w-100">
                    <i class="fa fa-upload me-1"></i> آپلود تصویر
                </button>
            </div>
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
