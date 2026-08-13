@extends('layout.managmentLayout', [
    'title' => 'مدیریت ریدایرکت',
    'menu' => 'seo/redirects',
])

@section('main_content')
<style>
.redirect-table td{vertical-align:middle}
.redirect-table input,.redirect-table select{min-width:120px}
.path-cell{direction:ltr;text-align:left;font-family:consolas,monospace;font-size:13px}
</style>

<nav class="mb-3 pt-md-3" aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">خانه</a></li>
        <li class="breadcrumb-item active">مدیریت ریدایرکت</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">مدیریت ریدایرکت</h1>
    <a href="/admin/seo/404" class="btn btn-outline-primary"><i class="fas fa-unlink me-1"></i> خطاهای ۴۰۴</a>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
    </div>
@endif

<section class="card card-body shadow-sm p-4 mb-4">
    <h6 class="border-bottom pb-2 mb-3">ریدایرکت جدید</h6>
    <form method="POST" action="/admin/seo/redirects">
        @csrf
        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label">مسیر مبدا *</label>
                <input type="text" name="source_path" class="form-control path-cell" required
                       value="{{ old('source_path', $prefillSource ?? '') }}" placeholder="/product/12/old-slug">
                <small class="text-muted">فقط مسیر، بدون دامنه. کوئری‌استرینگ در تطبیق نادیده گرفته می‌شود.</small>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label">مسیر مقصد</label>
                <input type="text" name="target_path" class="form-control path-cell"
                       value="{{ old('target_path') }}" placeholder="/shop یا https://...">
                <small class="text-muted">برای «حذف دائمی (410)» خالی بگذارید.</small>
            </div>
            <div class="col-md-2 mb-3">
                <label class="form-label">نوع</label>
                <select name="status_code" class="form-control">
                    @foreach(\App\Models\Redirect::STATUS_CODES as $code => $label)
                        <option value="{{ $code }}" {{ (string) old('status_code', 301) === (string) $code ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 mb-3 d-flex align-items-end">
                <label class="form-check mb-2">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}>
                    فعال
                </label>
            </div>
            <div class="col-md-10 mb-3">
                <label class="form-label">یادداشت</label>
                <input type="text" name="note" class="form-control" value="{{ old('note') }}" placeholder="مثلا: اسلاگ محصول عوض شد">
            </div>
            <div class="col-md-2 mb-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus me-1"></i> ثبت</button>
            </div>
        </div>
    </form>
</section>

<section class="card card-body shadow-sm p-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h6 class="mb-0">قواعد ثبت‌شده ({{ $model->total() }})</h6>
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="q" class="form-control form-control-sm" value="{{ request('q') }}" placeholder="جستجوی مسیر">
            <button class="btn btn-sm btn-outline-secondary">جستجو</button>
        </form>
    </div>

    @if($model->count())
    <div class="table-responsive">
        <table class="table table-sm align-middle redirect-table">
            <thead>
                <tr>
                    <th>مبدا</th>
                    <th>مقصد</th>
                    <th>نوع</th>
                    <th>فعال</th>
                    <th>یادداشت</th>
                    <th class="text-center">برخورد</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($model as $redirect)
                <tr>
                    {{-- ورودی‌ها با ویژگی form به فرمِ بیرون از جدول وصل می‌شوند؛
                         قرار دادن <form> داخل <tr> در HTML معتبر نیست. --}}
                    <td><input type="text" name="source_path" form="redirect-{{ $redirect->id }}" class="form-control form-control-sm path-cell" value="{{ $redirect->source_path }}"></td>
                    <td><input type="text" name="target_path" form="redirect-{{ $redirect->id }}" class="form-control form-control-sm path-cell" value="{{ $redirect->target_path }}"></td>
                    <td>
                        <select name="status_code" form="redirect-{{ $redirect->id }}" class="form-control form-control-sm">
                            @foreach(\App\Models\Redirect::STATUS_CODES as $code => $label)
                                <option value="{{ $code }}" {{ $redirect->status_code === $code ? 'selected' : '' }}>{{ $code }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="text-center">
                        <input type="hidden" name="is_active" value="0" form="redirect-{{ $redirect->id }}">
                        <input type="checkbox" name="is_active" value="1" form="redirect-{{ $redirect->id }}" class="form-check-input" {{ $redirect->is_active ? 'checked' : '' }}>
                    </td>
                    <td><input type="text" name="note" form="redirect-{{ $redirect->id }}" class="form-control form-control-sm" value="{{ $redirect->note }}"></td>
                    <td class="text-center">
                        <span class="badge bg-secondary">{{ $redirect->hits }}</span>
                        @if($redirect->last_hit_at)
                            <div class="text-muted" style="font-size:11px">{{ $redirect->last_hit_at->diffForHumans() }}</div>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        <button type="submit" form="redirect-{{ $redirect->id }}" class="btn btn-sm btn-success"><i class="fas fa-save"></i></button>
                        <button type="submit" form="redirect-delete-{{ $redirect->id }}" class="btn btn-sm btn-outline-danger" onclick="return confirm('این ریدایرکت حذف شود؟')"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @foreach($model as $redirect)
        <form id="redirect-{{ $redirect->id }}" method="POST" action="/admin/seo/redirects/{{ $redirect->id }}">@csrf @method('put')</form>
        <form id="redirect-delete-{{ $redirect->id }}" method="POST" action="/admin/seo/redirects/{{ $redirect->id }}">@csrf @method('delete')</form>
    @endforeach

    <div class="mt-3">{{ $model->links() }}</div>
    @else
        <p class="text-muted mb-0">هنوز ریدایرکتی ثبت نشده است.</p>
    @endif
</section>
@endsection
