@extends('layout.layout', ['title' => 'صفحه پیدا نشد | ناظر یدک', 'metaDescription' => 'صفحه‌ای که دنبال آن بودید پیدا نشد. قطعه مورد نظرتان را در فروشگاه ناظر یدک جستجو کنید.', 'robots' => seo_robots_tag(false, true), 'noBaseSchema' => true])
@section('main_content')
<main>
    <div class="container">
        <div class="cart-content text-center py-5 my-3">
            <div style="width:100px; height:100px; background:var(--primary-lighter); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 20px;">
                <span style="font-size:2.5rem; font-weight:900; color:var(--primary);">۴۰۴</span>
            </div>
            {{-- عنوان صفحه باید h1 باشد، نه h4: تنها صفحه‌ی سایت بود که هیچ h1
                 نداشت و ابزارهای سئو آن را «صفحه بدون عنوان اصلی» گزارش می‌کردند. --}}
            <h1 class="fw-bold mb-2" style="font-size:1.25rem;">صفحه مورد نظر پیدا نشد</h1>
            <p class="font-13 text-muted mb-4">صفحه‌ای که به دنبال آن بودید وجود ندارد یا حذف شده است.</p>

            {{-- صفحه‌ی ۴۰۴ تا حالا بن‌بست بود: دو دکمه و تمام. کسی که از نتیجه‌ی
                 گوگل روی یک آدرس قدیمی آمده، اینجا یا قطعه‌اش را پیدا می‌کند یا
                 سایت را می‌بندد. جعبه‌ی جستجو و دسته‌بندی‌ها همان مسیر نجات‌اند. --}}
            <form method="get" action="/shop" class="mx-auto mb-4" style="max-width:460px;">
                <div class="search-box-header">
                    <input type="search" name="title" aria-label="جستجو در محصولات"
                           placeholder="نام قطعه، خودرو یا کد فنی را بنویسید...">
                    <button type="submit"><i class="fa fa-search"></i> جستجو</button>
                </div>
            </form>

            <div class="d-flex justify-content-center gap-3 mb-4">
                <a href="/" class="btn btn-info px-4 font-13">
                    <i class="fas fa-home me-1"></i> صفحه اصلی
                </a>
                <a href="/shop" class="btn font-13 px-4" style="border:1px solid var(--primary); color:var(--primary); border-radius:var(--radius-sm);">
                    <i class="fas fa-store me-1"></i> فروشگاه قطعات
                </a>
            </div>

            <nav aria-label="دسته‌بندی قطعات" class="mx-auto" style="max-width:760px;">
                <p class="font-13 text-muted mb-2">یا از دسته‌بندی‌ها شروع کنید:</p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    @foreach(\App\Enums\ProductCategory::cases() as $notFoundCategory)
                        <a href="/shop/{{ rawurlencode($notFoundCategory->slug()) }}"
                           class="font-12"
                           style="display:inline-block; padding:7px 12px; border:1px solid var(--border-color,#e3e8ef); border-radius:999px; color:var(--text-dark,#333); text-decoration:none;">
                            {{ $notFoundCategory->label() }}
                        </a>
                    @endforeach
                </div>
            </nav>
        </div>
    </div>
</main>
@endsection
