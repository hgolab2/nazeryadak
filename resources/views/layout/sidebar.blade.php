<div class="col-lg-3">
    <div class="cart-content p-0">
        <div class="sidebar-user-header">
            <div class="sidebar-user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div>
                <span class="sidebar-user-name">
                    {{ Auth::guard('customer')->user()->fullName() != '' ? Auth::guard('customer')->user()->fullName() : Auth::guard('customer')->user()->phone }}
                </span>
            </div>
        </div>
        <ul class="list-unstyled mb-0">
            <li>
                <a href="/dashboard" class="d-flex align-items-center gap-2 px-3 py-3 font-13 {{ $menu == 'dashboard' ? 'fw-bold' : '' }}"
                   style="{{ $menu == 'dashboard' ? 'color:var(--primary); background:var(--primary-lighter); border-right:3px solid var(--primary);' : 'color:var(--text-dark);' }}">
                    <i class="fas fa-tachometer-alt" style="width:20px; text-align:center;"></i> داشبورد
                </a>
            </li>
            <li style="border-top:1px solid var(--border-color);">
                <a href="/profile/orders" class="d-flex align-items-center gap-2 px-3 py-3 font-13 {{ $menu == 'orders' ? 'fw-bold' : '' }}"
                   style="{{ $menu == 'orders' ? 'color:var(--primary); background:var(--primary-lighter); border-right:3px solid var(--primary);' : 'color:var(--text-dark);' }}">
                    <i class="fas fa-box" style="width:20px; text-align:center;"></i> سفارش‌های من
                </a>
            </li>
            <li style="border-top:1px solid var(--border-color);">
                <a href="/favorite" class="d-flex align-items-center gap-2 px-3 py-3 font-13 {{ $menu == 'favourits' ? 'fw-bold' : '' }}"
                   style="{{ $menu == 'favourits' ? 'color:var(--primary); background:var(--primary-lighter); border-right:3px solid var(--primary);' : 'color:var(--text-dark);' }}">
                    <i class="fas fa-heart" style="width:20px; text-align:center;"></i> علاقه‌مندی‌ها
                </a>
            </li>
            <li style="border-top:1px solid var(--border-color);">
                <a href="/profile/info" class="d-flex align-items-center gap-2 px-3 py-3 font-13 {{ $menu == 'info' ? 'fw-bold' : '' }}"
                   style="{{ $menu == 'info' ? 'color:var(--primary); background:var(--primary-lighter); border-right:3px solid var(--primary);' : 'color:var(--text-dark);' }}">
                    <i class="fas fa-user-edit" style="width:20px; text-align:center;"></i> اطلاعات حساب
                </a>
            </li>
            <li style="border-top:1px solid var(--border-color);">
                <a href="/logout" class="d-flex align-items-center gap-2 px-3 py-3 font-13" style="color:var(--danger);">
                    <i class="fas fa-sign-out-alt" style="width:20px; text-align:center;"></i> خروج
                </a>
            </li>
        </ul>
    </div>
</div>
