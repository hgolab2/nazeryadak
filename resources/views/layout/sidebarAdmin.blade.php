<div class="admin-menu-title">منوی اصلی</div>
<a href="/dashboardAdmin" class="admin-menu-item {{ ($menu ?? '') == 'dashboard' ? 'active' : '' }}">
    <i class="fas fa-tachometer-alt"></i> داشبورد
</a>

<div class="admin-menu-title">مدیریت فروشگاه</div>
<a href="/admin/product/list" class="admin-menu-item {{ ($menu ?? '') == 'product/list' ? 'active' : '' }}">
    <i class="fas fa-box"></i> محصولات
</a>
<a href="/admin/product/create" class="admin-menu-item {{ ($menu ?? '') == 'product/create' ? 'active' : '' }}">
    <i class="fas fa-plus-circle"></i> افزودن محصول
</a>
<a href="/admin/import" class="admin-menu-item {{ ($menu ?? '') == 'import' ? 'active' : '' }}">
    <i class="fas fa-file-import"></i> بارگذاری فایل انبار
</a>
<a href="/admin/order/list" class="admin-menu-item {{ ($menu ?? '') == 'order/list' ? 'active' : '' }}">
    <i class="fas fa-shopping-cart"></i> سفارشات
</a>
@php $pendingReceipts = pendingPaymentReceiptsCount(); @endphp
<a href="/admin/payment/list" class="admin-menu-item {{ ($menu ?? '') == 'payment/list' ? 'active' : '' }}">
    <i class="fas fa-money-check-dollar"></i> پرداخت‌ها
    @if($pendingReceipts > 0)
        {{-- رسید ثبت‌شده‌ای منتظر تأیید است؛ باید از هر صفحه‌ی پنل دیده شود --}}
        <span class="badge bg-danger" style="margin-right:6px;">{{ $pendingReceipts }}</span>
    @endif
</a>
<a href="/admin/customer/list" class="admin-menu-item {{ ($menu ?? '') == 'customer/list' ? 'active' : '' }}">
    <i class="fas fa-users"></i> مشتریان
</a>

<div class="admin-menu-title">محتوا</div>
<a href="/admin/article/list" class="admin-menu-item {{ ($menu ?? '') == 'article/list' ? 'active' : '' }}">
    <i class="fas fa-newspaper"></i> مدیریت بلاگ
</a>
<a href="/admin/article/create" class="admin-menu-item {{ ($menu ?? '') == 'article/create' ? 'active' : '' }}">
    <i class="fas fa-pen"></i> مطلب جدید
</a>

<a href="/admin/advertisement/list" class="admin-menu-item {{ ($menu ?? '') == 'advertisement/list' ? 'active' : '' }}">
    <i class="fas fa-images"></i> تبلیغات و بنر
</a>

<div class="admin-menu-title">سئو</div>
<a href="/admin/seo/health" class="admin-menu-item {{ ($menu ?? '') == 'seo/health' ? 'active' : '' }}">
    <i class="fas fa-heart-pulse"></i> سلامت سئو
</a>
<a href="/admin/seo/terms" class="admin-menu-item {{ ($menu ?? '') == 'seo/terms' ? 'active' : '' }}">
    <i class="fas fa-signs-post"></i> صفحات فرود
</a>
@php $pendingReviews = pendingProductReviewsCount(); @endphp
<a href="/admin/seo/reviews" class="admin-menu-item {{ ($menu ?? '') == 'seo/reviews' ? 'active' : '' }}">
    <i class="fas fa-star"></i> نظرات محصولات
    @if($pendingReviews > 0)
        {{-- نظر تأییدنشده روی صفحه‌ی محصول دیده نمی‌شود و وارد امتیاز گوگل هم نمی‌شود --}}
        <span class="badge bg-danger" style="margin-right:6px;">{{ $pendingReviews }}</span>
    @endif
</a>
<a href="/admin/seo/redirects" class="admin-menu-item {{ ($menu ?? '') == 'seo/redirects' ? 'active' : '' }}">
    <i class="fas fa-exchange-alt"></i> مدیریت ریدایرکت
</a>
<a href="/admin/seo/404" class="admin-menu-item {{ ($menu ?? '') == 'seo/404' ? 'active' : '' }}">
    <i class="fas fa-unlink"></i> خطاهای ۴۰۴
</a>
<a href="/admin/seo/settings" class="admin-menu-item {{ ($menu ?? '') == 'seo/settings' ? 'active' : '' }}">
    <i class="fas fa-sliders-h"></i> تنظیمات سئو
</a>

<div class="admin-menu-title">تنظیمات</div>
<a href="/admin/settings" class="admin-menu-item {{ ($menu ?? '') == 'settings' ? 'active' : '' }}">
    <i class="fas fa-truck"></i> تنظیمات ارسال
</a>
<a href="/admin/user/list" class="admin-menu-item {{ ($menu ?? '') == 'user/list' ? 'active' : '' }}">
    <i class="fas fa-user-cog"></i> کاربران سیستم
</a>

<div style="padding:15px 18px; margin-top:20px; border-top:1px solid rgba(255,255,255,0.08);">
    <a href="/" target="_blank" class="admin-menu-item" style="padding:8px 0; color:rgba(255,255,255,0.5); font-size:0.75rem;">
        <i class="fas fa-external-link-alt"></i> مشاهده سایت
    </a>
    <a href="/logout" class="admin-menu-item" style="padding:8px 0; color:#e57373;">
        <i class="fas fa-sign-out-alt"></i> خروج
    </a>
</div>
