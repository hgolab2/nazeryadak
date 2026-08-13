@extends('layout.layout', ['title' => 'اطلاعات حساب | ناظر یدک', 'robots' => seo_robots_tag(false, true), 'noBaseSchema' => true])
@section('main_content')
<main class="nx-account">
    <div class="nx-wrap">
        <nav class="nx-breadcrumb" aria-label="مسیر صفحه">
            <a href="/">ناظر یدک</a>
            <i class="fas fa-chevron-left"></i>
            <b>اطلاعات حساب</b>
        </nav>

        <div class="nx-account-layout">
            @include('layout.sidebar', ['menu' => 'info'])

            <div class="nx-account-main">
                @if(session('success'))
                    <div class="nx-card" style="margin-bottom:16px;">
                        <div class="nx-panel-body" style="color:#17a566; font-size:13px; font-weight:700;">
                            <i class="fas fa-circle-check"></i> {{ session('success') }}
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('customer.profile.update') }}">
                    @csrf
                    @method('PUT')

                    <section class="nx-card">
                        <div class="nx-card-head">
                            <h2><i class="fas fa-id-card"></i> مشخصات فردی</h2>
                        </div>
                        <div class="nx-form">
                            <div>
                                <label for="first_name">نام</label>
                                <input id="first_name" type="text" name="first_name" value="{{ $customer->first_name }}">
                            </div>
                            <div>
                                <label for="last_name">نام خانوادگی</label>
                                <input id="last_name" type="text" name="last_name" value="{{ $customer->last_name }}">
                            </div>
                            <div class="is-wide">
                                <label for="receiver_phone">تلفن تحویل‌گیرنده</label>
                                <input id="receiver_phone" type="text" name="receiver_phone" inputmode="numeric"
                                       value="{{ $customer->address?->receiver_phone }}">
                            </div>
                        </div>
                    </section>

                    <section class="nx-card" style="margin-top:16px;">
                        <div class="nx-card-head">
                            <h2><i class="fas fa-location-dot"></i> آدرس تحویل</h2>
                        </div>
                        <div class="nx-form">
                            <div>
                                <label for="province_id">استان</label>
                                <select id="province_id" name="province_id" required>
                                    @foreach ($provinces as $province)
                                        <option value="{{ $province->id }}" {{ optional($customer->address)->province_id == $province->id ? 'selected' : '' }}>
                                            {{ $province->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="city">شهر</label>
                                <input id="city" type="text" name="city" value="{{ $customer->address?->city }}" required>
                            </div>
                            <div>
                                <label for="postal_code">کد پستی</label>
                                <input id="postal_code" type="text" name="postal_code" inputmode="numeric"
                                       value="{{ $customer->address?->postal_code }}">
                            </div>
                            <div class="is-wide">
                                <label for="address_line">آدرس کامل</label>
                                <textarea id="address_line" name="address_line" rows="3" required>{{ $customer->address?->address_line }}</textarea>
                            </div>
                            <div class="is-wide" style="display:flex; justify-content:flex-end;">
                                <button type="submit" class="nx-btn-red">
                                    <i class="fas fa-check"></i> ذخیره اطلاعات
                                </button>
                            </div>
                        </div>
                    </section>
                </form>
            </div>
        </div>
    </div>
</main>
@endsection
