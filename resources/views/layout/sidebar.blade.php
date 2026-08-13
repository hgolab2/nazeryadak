@php
    $accountUser = Auth::guard('customer')->user();
    $accountName = $accountUser && $accountUser->fullName() !== '' ? $accountUser->fullName() : ($accountUser->phone ?? '');
    $unreadNotifications = \App\Models\CustomerNotification::unreadCountFor($accountUser->id ?? null);

    $accountLinks = [
        ['menu' => 'dashboard',  'url' => '/dashboard',             'icon' => 'fa-gauge-high', 'label' => 'داشبورد'],
        ['menu' => 'orders',     'url' => '/profile/orders',        'icon' => 'fa-box',        'label' => 'سفارش‌های من'],
        ['menu' => 'notifs',     'url' => '/profile/notifications', 'icon' => 'fa-bell',       'label' => 'اعلان‌ها', 'badge' => $unreadNotifications],
        ['menu' => 'favourits',  'url' => '/favorite',              'icon' => 'fa-heart',      'label' => 'علاقه‌مندی‌ها'],
        ['menu' => 'info',       'url' => '/profile/info',          'icon' => 'fa-user-pen',   'label' => 'اطلاعات حساب'],
    ];
@endphp

<aside class="nx-account-side">
    <div class="nx-account-user">
        <span class="nx-account-avatar"><i class="fas fa-user"></i></span>
        <div>
            <b>{{ $accountName }}</b>
            <span>حساب کاربری</span>
        </div>
    </div>

    <nav class="nx-account-nav" aria-label="منوی حساب کاربری">
        @foreach($accountLinks as $link)
            <a href="{{ $link['url'] }}" class="{{ ($menu ?? '') === $link['menu'] ? 'is-active' : '' }}">
                <i class="fas {{ $link['icon'] }}"></i>
                {{ $link['label'] }}
                @if(!empty($link['badge']))
                    <span class="nx-account-badge">{{ toPersianNumbers($link['badge'], false) }}</span>
                @endif
            </a>
        @endforeach
        <a href="/logout" class="is-logout"><i class="fas fa-arrow-right-from-bracket"></i> خروج</a>
    </nav>
</aside>
