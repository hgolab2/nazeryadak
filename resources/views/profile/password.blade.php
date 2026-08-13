@extends('layout.layout', ['title' => 'رمز عبور | ناظر یدک', 'robots' => seo_robots_tag(false, true), 'noBaseSchema' => true])
@section('main_content')
<main class="nx-account">
    <div class="nx-wrap">
        <nav class="nx-breadcrumb" aria-label="مسیر صفحه">
            <a href="/">ناظر یدک</a>
            <i class="fas fa-chevron-left"></i>
            <b>رمز عبور</b>
        </nav>

        <div class="nx-account-layout">
            @include('layout.sidebar', ['menu' => 'password'])

            <div class="nx-account-main">
                @if(session('success'))
                    <div class="nx-card" style="margin-bottom:16px;">
                        <div class="nx-panel-body" style="color:#17a566; font-size:13px; font-weight:700;">
                            <i class="fas fa-circle-check"></i> {{ session('success') }}
                        </div>
                    </div>
                @endif

                @if($errors->any())
                    <div class="nx-card" style="margin-bottom:16px;">
                        <div class="nx-panel-body" style="color:#c62828; font-size:13px;">
                            @foreach($errors->all() as $error)
                                <div><i class="fas fa-circle-exclamation"></i> {{ $error }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <section class="nx-card">
                    <div class="nx-card-head">
                        <h2><i class="fas fa-lock"></i> {{ $hasPassword ? 'تغییر رمز عبور' : 'تعیین رمز عبور' }}</h2>
                    </div>

                    <div class="nx-panel-body" style="padding-bottom:0; color:var(--nx-muted,#81858b); font-size:12.5px; line-height:2;">
                        ورود به حساب همیشه با کد پیامکی ممکن است. با تعیین رمز عبور، می‌توانید بدون منتظر ماندن برای پیامک هم وارد شوید.
                    </div>

                    <form method="POST" action="{{ route('profile.password.update') }}">
                        @csrf
                        @method('PUT')
                        <div class="nx-form">
                            @if($hasPassword)
                                <div class="is-wide">
                                    <label for="current_password">رمز عبور فعلی</label>
                                    <input id="current_password" type="password" name="current_password"
                                           autocomplete="current-password" required>
                                </div>
                            @endif
                            <div>
                                <label for="password">رمز عبور جدید</label>
                                <input id="password" type="password" name="password"
                                       autocomplete="new-password" minlength="6" required>
                            </div>
                            <div>
                                <label for="password_confirmation">تکرار رمز عبور جدید</label>
                                <input id="password_confirmation" type="password" name="password_confirmation"
                                       autocomplete="new-password" minlength="6" required>
                            </div>
                            <div class="is-wide" style="display:flex; justify-content:flex-end; gap:10px; flex-wrap:wrap;">
                                <button type="submit" class="nx-btn-red">
                                    <i class="fas fa-check"></i> {{ $hasPassword ? 'تغییر رمز عبور' : 'ثبت رمز عبور' }}
                                </button>
                            </div>
                        </div>
                    </form>
                </section>

                @if($hasPassword)
                    <section class="nx-card" style="margin-top:16px;">
                        <div class="nx-card-head">
                            <h2><i class="fas fa-unlock"></i> حذف رمز عبور</h2>
                        </div>
                        <div class="nx-panel-body" style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                            <span style="color:var(--nx-muted,#81858b); font-size:12.5px; line-height:2;">
                                با حذف رمز، ورود فقط از طریق کد پیامکی انجام می‌شود.
                            </span>
                            <form method="POST" action="{{ route('profile.password.destroy') }}"
                                  onsubmit="return confirm('رمز عبور حذف شود؟ پس از آن فقط با کد پیامکی می‌توانید وارد شوید.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="nx-btn-ghost">
                                    <i class="fas fa-trash"></i> حذف رمز عبور
                                </button>
                            </form>
                        </div>
                    </section>
                @endif
            </div>
        </div>
    </div>
</main>
@endsection
