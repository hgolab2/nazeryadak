@php $currentUser = Auth::user(); @endphp
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'پنل مدیریت' }} | ناظر یدک</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/ico" href="/favicon.ico"/>
    <link rel="stylesheet" href="/assets/css/bootstrap.rtl.css">
    <link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
    {{-- اعلان‌های @font-face برای سرعتِ صفحات سایت از all.min.css جدا شده‌اند؛
         پنل فقط قاعده‌ی آیکن‌ها را داشت و فونتش را نه، و همه‌ی آیکن‌ها خالی
         می‌ماندند. fa6-aliases هم نام‌های نسخه‌ی ۶ را روی فونت ۵ می‌نشاند. --}}
    <link rel="stylesheet" href="/assets/fontawesome/css/fa-fonts.css">
    <link rel="stylesheet" href="/assets/fontawesome/css/fa6-aliases.css">
    <style>
        @font-face {
            font-family: vazir-fa-med;
            src: url('/assets/font/IRANSans/IRANSansWeb(FaNum).woff') format('woff');
        }
        * { font-family: vazir-fa-med, Tahoma, sans-serif; outline: none !important; }
        :root {
            --admin-primary: #00457c;
            --admin-primary-light: #0066b3;
            --admin-dark: #002f5b;
            --admin-accent: #f57c00;
            --admin-bg: #f0f4f8;
            --admin-sidebar: #0d1b2a;
            --admin-sidebar-hover: #1b2d44;
            --admin-text: #1a1a2e;
        }
        body { background: var(--admin-bg); overflow-x: hidden; color: var(--admin-text); }
        a { text-decoration: none; }

        /* --- Topbar --- */
        .admin-topbar {
            background: #fff;
            height: 60px;
            border-bottom: 1px solid #dde4ec;
            position: fixed;
            top: 0;
            right: 0;
            left: 0;
            z-index: 1030;
            display: flex;
            align-items: center;
            padding: 0 20px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.04);
        }
        .admin-topbar-brand { display:flex; align-items:center; gap:10px; }
        .admin-topbar-brand img { height: 32px; }
        .admin-topbar-brand span { font-size:0.85rem; font-weight:700; color:var(--admin-primary); }
        .admin-topbar-toggle {
            background:none; border:none; font-size:1.2rem; color:var(--admin-text);
            cursor:pointer; padding:8px; margin-left:10px; display:none;
        }
        @media(max-width:991px) { .admin-topbar-toggle { display:block; } }
        .admin-topbar-user {
            margin-right:auto; display:flex; align-items:center; gap:8px;
            font-size:0.82rem; color:#555;
        }
        .admin-topbar-user i { color:var(--admin-primary); }

        /* --- Sidebar --- */
        .admin-sidebar {
            position: fixed;
            top: 60px;
            right: 0;
            bottom: 0;
            width: 240px;
            background: var(--admin-sidebar);
            overflow-y: auto;
            z-index: 1020;
            transition: transform 0.3s ease;
            padding-top: 10px;
        }
        .admin-sidebar::-webkit-scrollbar { width:4px; }
        .admin-sidebar::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.15); border-radius:4px; }

        .admin-sidebar-user {
            padding:18px 18px 15px;
            border-bottom:1px solid rgba(255,255,255,0.08);
            display:flex; align-items:center; gap:10px;
            margin-bottom:8px;
        }
        .admin-sidebar-user-avatar {
            width:38px; height:38px; border-radius:50%;
            background:linear-gradient(135deg, var(--admin-primary), var(--admin-primary-light));
            display:flex; align-items:center; justify-content:center;
        }
        .admin-sidebar-user-avatar i { color:#fff; font-size:0.9rem; }
        .admin-sidebar-user-name { color:#fff; font-size:0.82rem; font-weight:600; }
        .admin-sidebar-user-role { color:rgba(255,255,255,0.5); font-size:0.72rem; }

        .admin-menu-title {
            font-size:0.7rem; color:rgba(255,255,255,0.35); padding:15px 18px 6px;
            text-transform:uppercase; letter-spacing:1px; font-weight:600;
        }
        .admin-menu-item {
            display:flex; align-items:center; gap:10px;
            padding:10px 18px; color:rgba(255,255,255,0.7);
            font-size:0.82rem; transition:all 0.2s; border-right:3px solid transparent;
        }
        .admin-menu-item i { width:18px; text-align:center; font-size:0.82rem; }
        .admin-menu-item:hover {
            background:var(--admin-sidebar-hover); color:#fff;
            border-right-color:var(--admin-accent);
        }
        .admin-menu-item.active {
            background:var(--admin-sidebar-hover); color:#fff;
            border-right-color:var(--admin-accent);
        }
        .admin-menu-item .badge {
            margin-right:auto; font-size:0.65rem; padding:3px 7px;
        }

        /* --- Main content --- */
        .admin-content {
            margin-top: 60px;
            margin-right: 240px;
            padding: 20px;
            min-height: calc(100vh - 60px);
        }

        @media(max-width:991px) {
            .admin-sidebar { transform:translateX(100%); }
            .admin-sidebar.show { transform:translateX(0); }
            .admin-content { margin-right:0; }
            .admin-sidebar-overlay {
                display:none; position:fixed; top:60px; left:0; right:0; bottom:0;
                background:rgba(0,0,0,0.4); z-index:1019;
            }
            .admin-sidebar-overlay.show { display:block; }
        }

        /* --- Cards --- */
        .admin-card {
            background:#fff; border-radius:10px; border:1px solid #dde4ec;
            box-shadow:0 1px 3px rgba(0,47,91,0.06); padding:20px; margin-bottom:20px;
        }
        .admin-card-title {
            font-size:0.9rem; font-weight:700; color:var(--admin-text);
            display:flex; align-items:center; gap:8px;
            padding-bottom:12px; margin-bottom:15px; border-bottom:1px solid #eee;
        }
        .admin-card-title i { color:var(--admin-primary); }

        /* --- Stat cards --- */
        .stat-card {
            background:#fff; border-radius:10px; border:1px solid #dde4ec;
            padding:20px; display:flex; align-items:center; gap:15px;
            box-shadow:0 1px 3px rgba(0,47,91,0.06); transition:all 0.3s;
        }
        .stat-card:hover { transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,47,91,0.1); }
        .stat-icon {
            width:50px; height:50px; border-radius:10px;
            display:flex; align-items:center; justify-content:center; font-size:1.3rem;
        }
        .stat-value { font-size:1.4rem; font-weight:700; color:var(--admin-text); line-height:1.2; }
        .stat-label { font-size:0.78rem; color:#777; margin-top:2px; }

        /* --- Table --- */
        .admin-table { font-size:0.82rem; }
        .admin-table thead th {
            background:var(--admin-primary); color:#fff; font-weight:600;
            padding:10px 12px; border:none; font-size:0.8rem;
        }
        .admin-table tbody td { padding:10px 12px; vertical-align:middle; }
        .admin-table tbody tr:hover { background:#f8f9fa; }

        .badge-status {
            font-size:0.72rem; padding:4px 10px; border-radius:50px; font-weight:600;
        }

        /* --- منوی کشویی وضعیت سفارش (لیست، داشبورد، مشاهده) ---
           رنگ نوار کناری همان رنگ نشان وضعیت است تا بدون خواندن متن هم
           وضعیت از یک نگاه معلوم باشد. */
        .order-status-select {
            font-size:0.78rem; padding:4px 26px 4px 8px; min-width:150px;
            border-radius:6px; border:1px solid #dde4ec; border-right-width:4px;
            background-color:#fff; cursor:pointer;
        }
        .order-status-select:disabled { opacity:.6; cursor:wait; }
        .order-status-select.st-pending, .order-status-select.st-awaiting_call { border-right-color:#f9a825; }
        .order-status-select.st-paid, .order-status-select.st-delivered { border-right-color:#2e7d32; }
        .order-status-select.st-processing, .order-status-select.st-shipped { border-right-color:#1565c0; }
        .order-status-select.st-canceled, .order-status-select.st-failed { border-right-color:#c62828; }
        .order-status-select.st-returned { border-right-color:#333; }

        /* --- پیام کوتاه گوشه‌ی صفحه --- */
        .admin-toast {
            position:fixed; bottom:20px; left:20px; z-index:2000; min-width:220px; max-width:360px;
            padding:12px 16px; border-radius:8px; color:#fff; font-size:0.82rem;
            box-shadow:0 4px 14px rgba(0,0,0,.18); opacity:0; transform:translateY(10px);
            transition:all .25s;
        }
        .admin-toast.show { opacity:1; transform:none; }
        .admin-toast.success { background:#2e7d32; }
        .admin-toast.error { background:#c62828; }

        .profit-pos { color:#2e7d32; }
        .profit-neg { color:#c62828; }
    </style>
    @yield('head')
</head>
<body>
    {{-- Topbar --}}
    <div class="admin-topbar">
        <button class="admin-topbar-toggle" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <div class="admin-topbar-brand">
            <img src="/assets/images/logo.png" alt="">
            <span>پنل مدیریت</span>
        </div>
        <div class="admin-topbar-user">
            @if($currentUser)
            <i class="fas fa-user-circle"></i>
            <span>{{ $currentUser->fullname() }}</span>
            <a href="/logout" style="color:var(--admin-accent); font-size:0.78rem; margin-right:10px;">
                <i class="fas fa-sign-out-alt"></i> خروج
            </a>
            @endif
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="admin-sidebar-overlay" id="sidebarOverlay"></div>
    <aside class="admin-sidebar" id="adminSidebar">
        @if($currentUser)
        <div class="admin-sidebar-user">
            <div class="admin-sidebar-user-avatar"><i class="fas fa-user"></i></div>
            <div>
                <div class="admin-sidebar-user-name">{{ $currentUser->fullname() }}</div>
                <div class="admin-sidebar-user-role">مدیر سیستم</div>
            </div>
        </div>
        @endif

        @include('layout.sidebarAdmin', ['menu' => $menu ?? ''])
    </aside>

    {{-- Content --}}
    <div class="admin-content">
        {{-- پیام‌های بعد از ذخیره/حذف؛ یک‌جا برای همه‌ی صفحه‌ها تا هر صفحه
             خودش تکرارش نکند. صفحه‌ای که خودش نشان می‌دهد، دوبار دیده می‌شود
             و باید نسخه‌ی خودش را بردارد. --}}
        @if(session('success') && ! View::hasSection('own_flash'))
            <div class="alert alert-success" style="border-radius:10px; font-size:0.85rem;">
                <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            </div>
        @endif
        @if(session('error') && ! View::hasSection('own_flash'))
            <div class="alert alert-danger" style="border-radius:10px; font-size:0.85rem;">
                <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
            </div>
        @endif

        @yield('main_content')
    </div>

    <script src="/assets/js/jquery.min.js"></script>
    <script src="/assets/js/bootstrap.bundle.min.js"></script>
    <script>
    $('#sidebarToggle').on('click', function() {
        $('#adminSidebar').toggleClass('show');
        $('#sidebarOverlay').toggleClass('show');
    });
    $('#sidebarOverlay').on('click', function() {
        $('#adminSidebar').removeClass('show');
        $(this).removeClass('show');
    });

    /* پیام کوتاه گوشه‌ی صفحه */
    function adminToast(message, type) {
        var el = $('<div class="admin-toast ' + (type || 'success') + '"></div>').text(message).appendTo('body');
        setTimeout(function () { el.addClass('show'); }, 10);
        setTimeout(function () { el.removeClass('show'); setTimeout(function () { el.remove(); }, 300); }, 3500);
    }

    /* ── تغییر وضعیت سفارش از منوی کشویی ─────────────────────────────
       یک رفتار برای لیست، داشبورد و صفحه‌ی مشاهده. delegate است چون لیست
       سفارش‌ها با ajax جایگزین می‌شود. */
    var ADMIN_CSRF = '{{ csrf_token() }}';
    $(document).on('change', '.order-status-select', function () {
        var el = $(this), id = el.data('order'), status = el.val(), prev = el.data('current');
        if (status === prev) return;

        var label = el.find('option:selected').text().trim();
        if (!confirm('وضعیت سفارش #' + id + ' به «' + label + '» تغییر کند؟\nمشتری پیامک تغییر وضعیت می‌گیرد.')) {
            el.val(prev);
            return;
        }

        el.prop('disabled', true);
        $.ajax({
            url: '/admin/order/' + id + '/status',
            type: 'PUT',
            data: { _token: ADMIN_CSRF, status: status },
            dataType: 'json'
        }).done(function (r) {
            el.data('current', r.status).attr('data-current', r.status);
            el.removeClass(function (i, c) { return (c.match(/\bst-\S+/g) || []).join(' '); }).addClass('st-' + r.status);
            // نشان‌های وضعیتِ همین سفارش در صفحه (مثلا سربرگ صفحه‌ی مشاهده)
            $('.order-status-badge[data-order="' + id + '"]').attr('class', 'badge order-status-badge ' + r.badge).attr('data-order', id).text(r.label);
            adminToast(r.message, 'success');
        }).fail(function (xhr) {
            el.val(prev);
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'تغییر وضعیت انجام نشد.';
            adminToast(msg, 'error');
        }).always(function () {
            el.prop('disabled', false);
        });
    });
    </script>
    @yield('js')
</body>
</html>
