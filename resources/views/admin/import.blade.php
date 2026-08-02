@extends('layout.managmentLayout', [
    'title' => 'بارگذاری فایل انبار',
    'menu' => 'import'
])

@section('main_content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h5 style="font-weight:700; margin:0;"><i class="fas fa-file-import me-2" style="color:var(--admin-primary);"></i> بارگذاری فایل انبار (ISACO)</h5>
        <p style="font-size:0.8rem; color:#777; margin:5px 0 0;">فایل XLS/HTML صادرشده از سامانه ایساکو را بارگذاری کنید</p>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success d-flex align-items-center gap-2" style="font-size:0.85rem; border-radius:10px;">
    <i class="fas fa-check-circle" style="font-size:1.2rem;"></i>
    <div>{!! session('success') !!}</div>
</div>
@endif

@if(session('error'))
<div class="alert alert-danger d-flex align-items-center gap-2" style="font-size:0.85rem; border-radius:10px;">
    <i class="fas fa-exclamation-circle" style="font-size:1.2rem;"></i>
    <div>{{ session('error') }}</div>
</div>
@endif

<div class="row g-3">
    <div class="col-lg-7">
        <div class="admin-card">
            <div class="admin-card-title">
                <i class="fas fa-upload"></i> بارگذاری فایل
            </div>

            <form method="POST" action="{{ route('admin.import') }}" enctype="multipart/form-data" id="importForm">
                @csrf

                <div class="upload-zone" id="uploadZone">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>فایل XLS را اینجا رها کنید یا کلیک کنید</p>
                    <span>فرمت‌های مجاز: .xls — حداکثر ۲۰ مگابایت</span>
                    <input type="file" name="file" id="fileInput" accept=".xls,.xlsx,.html" required>
                </div>

                <div class="selected-file d-none" id="selectedFile">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:42px; height:42px; background:#e8f5e9; border-radius:8px; display:flex; align-items:center; justify-content:center;">
                            <i class="fas fa-file-excel" style="color:#2e7d32; font-size:1.2rem;"></i>
                        </div>
                        <div>
                            <div id="fileName" style="font-size:0.85rem; font-weight:600;"></div>
                            <div id="fileSize" style="font-size:0.75rem; color:#999;"></div>
                        </div>
                        <button type="button" class="btn btn-sm ms-auto" id="removeFile" style="color:#c62828;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <div class="mt-3 p-3" style="background:#fff8e1; border-radius:8px; border:1px solid #ffe082;">
                    <p style="font-size:0.82rem; color:#e65100; margin:0;">
                        <i class="fas fa-info-circle me-1"></i>
                        <strong>توجه:</strong> محصولاتی که کد کالا (SKU) مشابه داشته باشند بروزرسانی می‌شوند. محصولات جدید اضافه می‌شوند. دسته‌بندی بر اساس «مدل خودرو» ایجاد می‌شود.
                    </p>
                </div>

                <button type="submit" class="btn w-100 mt-3" id="submitBtn"
                        style="background:var(--admin-primary); color:#fff; border-radius:8px; padding:12px; font-size:0.9rem; font-weight:600;">
                    <i class="fas fa-database me-1"></i> شروع بارگذاری و پردازش
                </button>
            </form>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="admin-card">
            <div class="admin-card-title">
                <i class="fas fa-info-circle"></i> راهنما
            </div>
            <div style="font-size:0.82rem; line-height:2.2; color:#555;">
                <p><strong>ستون‌های مورد انتظار در فایل:</strong></p>
                <table class="table table-sm" style="font-size:0.78rem;">
                    <tr><td style="width:30%;"><i class="fas fa-barcode me-1" style="color:var(--admin-primary);"></i> کد کالا</td><td>کد محصول (SKU)</td></tr>
                    <tr><td><i class="fas fa-tag me-1" style="color:var(--admin-primary);"></i> شرح کالا</td><td>عنوان محصول</td></tr>
                    <tr><td><i class="fas fa-car me-1" style="color:var(--admin-primary);"></i> مدل خودرو</td><td>دسته‌بندی خودکار</td></tr>
                    <tr><td><i class="fas fa-cubes me-1" style="color:var(--admin-primary);"></i> موجودی</td><td>تعداد در انبار</td></tr>
                    <tr><td><i class="fas fa-money-bill me-1" style="color:var(--admin-primary);"></i> قیمت فروش</td><td>قیمت نمایشی</td></tr>
                    <tr><td><i class="fas fa-calculator me-1" style="color:var(--admin-primary);"></i> قیمت میانگین</td><td>قیمت قبل تخفیف</td></tr>
                </table>
            </div>
        </div>

        @if($lastImport)
        <div class="admin-card">
            <div class="admin-card-title">
                <i class="fas fa-history"></i> آخرین بارگذاری
            </div>
            <div style="font-size:0.82rem; line-height:2.2;">
                <div class="d-flex justify-content-between"><span class="text-muted">فایل:</span> <span>{{ $lastImport->file_name }}</span></div>
                <div class="d-flex justify-content-between"><span class="text-muted">تاریخ:</span> <span>{{ gregorian_to_jalali2($lastImport->created_at) }}</span></div>
                <div class="d-flex justify-content-between"><span class="text-muted">محصول جدید:</span> <span style="color:#2e7d32; font-weight:700;">{{ number_format($lastImport->imported) }}</span></div>
                <div class="d-flex justify-content-between"><span class="text-muted">بروزرسانی:</span> <span style="color:#1565c0; font-weight:700;">{{ number_format($lastImport->updated) }}</span></div>
                <div class="d-flex justify-content-between"><span class="text-muted">مجموع:</span> <span style="font-weight:700;">{{ number_format($lastImport->total) }}</span></div>
            </div>
        </div>
        @endif
    </div>
</div>

<style>
.upload-zone {
    border: 2px dashed #c5cfe0;
    border-radius: 12px;
    padding: 40px 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    position: relative;
    background: #f8fafd;
}
.upload-zone:hover, .upload-zone.dragover {
    border-color: var(--admin-primary);
    background: #e8f1fa;
}
.upload-zone i {
    font-size: 2.5rem;
    color: #b0bec5;
    margin-bottom: 10px;
    display: block;
}
.upload-zone p {
    font-size: 0.9rem;
    color: #555;
    margin: 5px 0;
    font-weight: 600;
}
.upload-zone span {
    font-size: 0.75rem;
    color: #999;
}
.upload-zone input[type="file"] {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    opacity: 0;
    cursor: pointer;
}
.selected-file {
    padding: 12px 15px;
    background: #f0f7f0;
    border-radius: 8px;
    border: 1px solid #c8e6c9;
    margin-top: 12px;
}
</style>
@endsection

@section('js')
<script>
const zone = document.getElementById('uploadZone');
const input = document.getElementById('fileInput');
const selected = document.getElementById('selectedFile');
const nameEl = document.getElementById('fileName');
const sizeEl = document.getElementById('fileSize');

['dragenter','dragover'].forEach(e => zone.addEventListener(e, () => zone.classList.add('dragover')));
['dragleave','drop'].forEach(e => zone.addEventListener(e, () => zone.classList.remove('dragover')));

input.addEventListener('change', function() {
    if (this.files.length > 0) {
        const f = this.files[0];
        nameEl.textContent = f.name;
        sizeEl.textContent = (f.size / 1024 / 1024).toFixed(2) + ' مگابایت';
        zone.classList.add('d-none');
        selected.classList.remove('d-none');
    }
});

document.getElementById('removeFile').addEventListener('click', function() {
    input.value = '';
    zone.classList.remove('d-none');
    selected.classList.add('d-none');
});

document.getElementById('importForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> در حال پردازش... لطفا صبر کنید';
});
</script>
@endsection
