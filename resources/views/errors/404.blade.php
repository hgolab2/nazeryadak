@extends('layout.layout', ['title' => 'صفحه پیدا نشد | ناظر یدک'])
@section('main_content')
<main>
    <div class="container">
        <div class="cart-content text-center py-5 my-3">
            <div style="width:100px; height:100px; background:var(--primary-lighter); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
                <span style="font-size:2.5rem; font-weight:900; color:var(--primary);">۴۰۴</span>
            </div>
            <h4 class="fw-bold mb-2">صفحه مورد نظر پیدا نشد</h4>
            <p class="font-13 text-muted mb-4">صفحه‌ای که به دنبال آن بودید وجود ندارد یا حذف شده است.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="/" class="btn btn-info px-4 font-13">
                    <i class="fas fa-home me-1"></i> صفحه اصلی
                </a>
                <a href="/shop" class="btn font-13 px-4" style="border:1px solid var(--primary); color:var(--primary); border-radius:var(--radius-sm);">
                    <i class="fas fa-store me-1"></i> فروشگاه قطعات
                </a>
            </div>
        </div>
    </div>
</main>
@endsection
