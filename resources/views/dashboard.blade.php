@extends('layout.layout', ['title' => 'داشبورد | ناظر یدک', 'robots' => seo_robots_tag(false, true), 'noBaseSchema' => true])
@section('main_content')
<main class="nx-account">
    <div class="nx-wrap">
        <nav class="nx-breadcrumb" aria-label="مسیر صفحه">
            <a href="/">ناظر یدک</a>
            <i class="fas fa-chevron-left"></i>
            <b>داشبورد</b>
        </nav>

        <div class="nx-account-layout">
            @include('layout.sidebar', ['menu' => 'dashboard'])

            <div class="nx-account-main">
                {{-- کسی که با کد پیامکی ثبت‌نام کرده ممکن است اصلا نداند رمز
                     عبور هم می‌تواند داشته باشد؛ این یادآوری فقط تا وقتی
                     می‌ماند که رمز تعیین نشده باشد --}}
                @if(empty($customer->password))
                <section class="nx-card" style="margin-bottom:16px;">
                    <div class="nx-panel-body" style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                        <span style="font-size:12.5px; line-height:2; color:var(--nx-muted,#81858b);">
                            <i class="fas fa-lock" style="color:var(--nx-red,#ef4056);"></i>
                            برای این حساب رمز عبور تعیین نشده و ورود فقط با کد پیامکی انجام می‌شود.
                        </span>
                        <a href="/profile/password" class="nx-btn-red">
                            <i class="fas fa-plus"></i> تعیین رمز عبور
                        </a>
                    </div>
                </section>
                @endif

                <div class="nx-account-grid">
                    {{-- اطلاعات شخصی --}}
                    <section class="nx-card">
                        <div class="nx-card-head">
                            <h2><i class="fas fa-user"></i> اطلاعات شخصی</h2>
                            <a href="/profile/info">ویرایش <i class="fas fa-chevron-left"></i></a>
                        </div>
                        @if($address)
                            <ul class="nx-datalist">
                                <li><span>نام</span> {{ $customer->fullname() ?: '—' }}</li>
                                <li><span>تلفن</span> <bdi>{{ toPersianNumbers($address->receiver_phone ?? '—') }}</bdi></li>
                                <li class="is-wide">
                                    <span>آدرس</span>
                                    <span style="color:var(--nx-ink,#23254e); text-align:left;">
                                        {{ $address->province?->name }} — {{ $address->city }} — {{ $address->address_line }}
                                    </span>
                                </li>
                            </ul>
                        @else
                            <div class="nx-empty-state">
                                <i class="fas fa-location-dot"></i>
                                <p>هنوز آدرسی ثبت نکرده‌اید.</p>
                                <a href="/profile/info" class="nx-btn-red"><i class="fas fa-plus"></i> ثبت آدرس</a>
                            </div>
                        @endif
                    </section>

                    {{-- علاقه‌مندی‌ها --}}
                    <section class="nx-card">
                        <div class="nx-card-head">
                            <h2><i class="fas fa-heart"></i> علاقه‌مندی‌ها</h2>
                            <a href="/favorite">مشاهده همه <i class="fas fa-chevron-left"></i></a>
                        </div>
                        @if($favorites->count())
                            <div class="nx-items">
                                @foreach ($favorites as $product)
                                    <div class="nx-item" id="fav{{ $product->id }}">
                                        <a href="{{ $product->url() }}" class="nx-item-thumb">
                                            <img src="{{ $product->image() }}" alt="{{ $product->title }}" loading="lazy"
                                                 onerror="this.onerror=null;this.src='/images/no-image.svg';">
                                        </a>
                                        <div class="nx-item-info">
                                            <a href="{{ $product->url() }}">{{ $product->title }}</a>
                                            <div class="nx-item-meta">{{ toPersianNumbers(number_format($product->price)) }} تومان</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="nx-empty-state">
                                <i class="fas fa-heart"></i>
                                <p>هنوز قطعه‌ای به علاقه‌مندی‌ها اضافه نکرده‌اید.</p>
                                <a href="/shop" class="nx-btn-ghost"><i class="fas fa-store"></i> دیدن قطعات</a>
                            </div>
                        @endif
                    </section>
                </div>

                {{-- آخرین سفارش‌ها --}}
                <section class="nx-card" style="margin-top:16px;">
                    <div class="nx-card-head">
                        <h2><i class="fas fa-box"></i> آخرین سفارش‌ها</h2>
                        <a href="/profile/orders">مشاهده همه <i class="fas fa-chevron-left"></i></a>
                    </div>
                    @if($orders->count() > 0)
                        <div class="nx-orders">
                            @foreach ($orders as $order)
                                @include('order.order_card', ['order' => $order])
                            @endforeach
                        </div>
                    @else
                        <div class="nx-empty-state">
                            <i class="fas fa-box-open"></i>
                            <p>هنوز سفارشی ثبت نکرده‌اید.</p>
                            <a href="/shop" class="nx-btn-red"><i class="fas fa-store"></i> رفتن به فروشگاه</a>
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>
</main>
@endsection
