@extends('layout.layout', ['title' => 'اعلان‌ها | ناظر یدک', 'robots' => seo_robots_tag(false, true), 'noBaseSchema' => true])
@section('main_content')
<main class="nx-account">
    <div class="nx-wrap">
        <nav class="nx-breadcrumb" aria-label="مسیر صفحه">
            <a href="/">ناظر یدک</a>
            <i class="fas fa-chevron-left"></i>
            <b>اعلان‌ها</b>
        </nav>

        <div class="nx-account-layout">
            @include('layout.sidebar', ['menu' => 'notifs'])

            <div class="nx-account-main">
                <section class="nx-card">
                    <div class="nx-card-head">
                        <h2><i class="fas fa-bell"></i> اعلان‌ها</h2>
                        @if($notifications->total() > 0)
                            <span class="nx-order-date">{{ toPersianNumbers($notifications->total(), false) }} اعلان</span>
                        @endif
                    </div>

                    @if($notifications->count())
                        <div class="nx-notifs">
                            @foreach($notifications as $notification)
                                <a href="{{ $notification->url ?: '/profile/orders' }}"
                                   class="nx-notif {{ $notification->isUnread() ? 'is-unread' : '' }}">
                                    <span class="nx-notif-icon">
                                        <i class="fas {{ $notification->icon ?: 'fa-bell' }}"></i>
                                    </span>
                                    <span class="nx-notif-body">
                                        <b>{{ $notification->title }}</b>
                                        @if($notification->body)
                                            <p>{{ $notification->body }}</p>
                                        @endif
                                    </span>
                                    <span class="nx-notif-time">{{ gregorian_to_jalali2($notification->created_at) }}</span>
                                </a>
                            @endforeach
                        </div>

                        @if($notifications->hasPages())
                            <div class="nx-panel-body d-flex justify-content-center">
                                {{ $notifications->links() }}
                            </div>
                        @endif
                    @else
                        <div class="nx-empty-state">
                            <i class="fas fa-bell-slash"></i>
                            <p>هنوز اعلانی ندارید. وضعیت سفارش‌هایتان اینجا نمایش داده می‌شود.</p>
                            <a href="/shop" class="nx-btn-ghost"><i class="fas fa-store"></i> رفتن به فروشگاه</a>
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>
</main>
@endsection
