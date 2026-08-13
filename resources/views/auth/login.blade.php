@extends('layout.layout', [
    'title' => 'ورود و ثبت‌نام | ناظر یدک',
    'metaDescription' => 'ورود و ثبت‌نام در فروشگاه ناظر یدک با شماره موبایل؛ با کد پیامکی یا رمز عبور، برای ثبت سفارش و پیگیری خرید لوازم یدکی.',
    'robots' => seo_robots_tag(false, true),
    'noBaseSchema' => true,
])

@section('main_content')
<main class="nx-auth">
    <div class="nx-auth-card">
        <div class="nx-auth-head">
            <span class="nx-auth-logo"><i class="fas fa-user"></i></span>
            <h1 id="authTitle">ورود به ناظر یدک</h1>
            <p id="authSubtitle">برای ورود یا ثبت‌نام، شماره موبایل خود را وارد کنید</p>
        </div>

        <p class="nx-auth-error" id="authError" hidden></p>
        <p class="nx-auth-dev" id="authDevCode" hidden></p>

        {{-- گام ۱: شماره موبایل. سرور تصمیم می‌گیرد گام بعد رمز باشد یا کد. --}}
        <section class="nx-auth-step" data-step="mobile">
            <div class="nx-auth-field">
                <label for="mobile">شماره موبایل</label>
                {{-- inputmode و autocomplete: روی موبایل کیبورد عددی باز می‌شود
                     و شماره‌ی ذخیره‌شده‌ی دستگاه پیشنهاد داده می‌شود --}}
                <input type="tel" id="mobile" class="is-num" placeholder="۰۹۱۲۰۰۰۰۰۰۰"
                       inputmode="numeric" autocomplete="tel" enterkeyhint="next"
                       maxlength="13" autofocus>
            </div>

            <button type="button" class="nx-auth-submit" id="btnContinue">
                ادامه <i class="fas fa-arrow-left"></i>
            </button>
        </section>

        {{-- گام ۲-الف: رمز عبور — فقط برای شماره‌ای که رمز ثبت کرده است --}}
        <section class="nx-auth-step" data-step="password" hidden>
            <div class="nx-auth-identity">
                <span class="js-identity"></span>
                <button type="button" class="js-edit-mobile"><i class="fas fa-pen"></i> تغییر شماره</button>
            </div>

            <div class="nx-auth-field nx-auth-pass">
                <label for="password">رمز عبور</label>
                <input type="password" id="password" autocomplete="current-password" enterkeyhint="go">
                <button type="button" class="nx-auth-eye" id="togglePassword" aria-label="نمایش رمز عبور">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            <button type="button" class="nx-auth-submit" id="btnPassword">
                <i class="fas fa-right-to-bracket"></i> ورود
            </button>

            <button type="button" class="nx-auth-alt" id="btnUseOtp">
                <i class="fas fa-comment-sms"></i> ورود با کد یکبارمصرف
            </button>

            <a class="nx-auth-link" href="/password/forgot">رمز عبور خود را فراموش کرده‌اید؟</a>
        </section>

        {{-- گام ۲-ب: کد پیامکی --}}
        <section class="nx-auth-step" data-step="otp" hidden>
            <div class="nx-auth-identity">
                <span class="js-identity"></span>
                <button type="button" class="js-edit-mobile"><i class="fas fa-pen"></i> تغییر شماره</button>
            </div>

            <div class="nx-auth-field">
                <label for="otp">کد تأیید پیامک‌شده</label>
                {{-- one-time-code باعث می‌شود iOS و اندروید کد را مستقیم از
                     پیامک پیشنهاد بدهند و کاربر بین دو برنامه جابه‌جا نشود --}}
                <input type="text" id="otp" class="is-num is-code" placeholder="——————"
                       inputmode="numeric" autocomplete="one-time-code" enterkeyhint="go"
                       maxlength="6" pattern="[0-9۰-۹]*">
            </div>

            <button type="button" class="nx-auth-submit" id="btnVerify">
                <i class="fas fa-check"></i> تأیید و ورود
            </button>

            {{-- بدون شمارش معکوس، کاربر پیام «تعداد درخواست زیاد است» می‌گیرد
                 و نمی‌داند چقدر باید صبر کند --}}
            <div class="nx-auth-resend" id="resendWrap">
                <span id="resendTimer">ارسال مجدد کد تا <b id="resendSeconds">۶۰</b> ثانیه دیگر</span>
                <button type="button" id="btnResend" hidden><i class="fas fa-redo"></i> ارسال مجدد کد</button>
            </div>

            <button type="button" class="nx-auth-alt" id="btnUsePassword" hidden>
                <i class="fas fa-lock"></i> ورود با رمز عبور
            </button>
        </section>

        {{-- گام ۳: تکمیل ثبت‌نام. جای درستِ گرفتن رمز همین‌جاست؛ کاربر تازه
             حساب دارد و می‌فهمد رمز به چه دردی می‌خورد. --}}
        <section class="nx-auth-step" data-step="profile" hidden>
            <div class="nx-auth-field">
                <label for="first_name">نام</label>
                <input type="text" id="first_name" autocomplete="given-name" maxlength="100" enterkeyhint="next">
            </div>

            <div class="nx-auth-field">
                <label for="last_name">نام خانوادگی</label>
                <input type="text" id="last_name" autocomplete="family-name" maxlength="100" enterkeyhint="next">
            </div>

            <div class="nx-auth-field nx-auth-pass">
                <label for="new_password">رمز عبور <span style="font-weight:400;">(اختیاری)</span></label>
                <input type="password" id="new_password" autocomplete="new-password" minlength="6"
                       placeholder="حداقل ۶ کاراکتر">
                <button type="button" class="nx-auth-eye" id="toggleNewPassword" aria-label="نمایش رمز عبور">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            <div class="nx-auth-field" id="newPasswordConfirmWrap" hidden>
                <label for="new_password_confirmation">تکرار رمز عبور</label>
                <input type="password" id="new_password_confirmation" autocomplete="new-password"
                       minlength="6" enterkeyhint="go">
            </div>

            <button type="button" class="nx-auth-submit" id="btnCompleteProfile">
                <i class="fas fa-check"></i> ثبت و ورود به حساب
            </button>

            <button type="button" class="nx-auth-link" id="btnSkipProfile">فعلا رد شو</button>
        </section>

        <div class="nx-auth-foot">
            <i class="fas fa-shield-halved"></i>
            <span id="authFootNote">ورود شما امن است و شماره‌تان نزد ما محفوظ می‌ماند.</span>
            <br>
            <a href="/order-tracking">پیگیری سفارش بدون ورود</a>
        </div>
    </div>
</main>
@endsection

@section('js')
<script>
(function () {
    const state = { mobile: '', hasPassword: false, isNew: false, timerId: null, redirect: '/dashboard' };

    const $card    = $('.nx-auth-card');
    const $error   = $('#authError');
    const $dev     = $('#authDevCode');
    const $title   = $('#authTitle');
    const $sub     = $('#authSubtitle');
    const csrf     = '{{ csrf_token() }}';

    const faDigits = v => String(v).replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
    const enDigits = v => String(v)
        .replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d))
        .replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d));

    function showError(message) {
        $error.text(message).prop('hidden', false);
    }

    function clearError() {
        $error.prop('hidden', true);
    }

    /* خطای اعتبارسنجی لاراول در message می‌آید؛ بقیه‌ی خطاها در همان کلید. */
    function errorOf(xhr, fallback) {
        const body = xhr.responseJSON || {};
        if (body.message) return body.message;
        if (body.errors) return Object.values(body.errors)[0][0];
        return fallback;
    }

    /* دکمه‌ی در حال ارسال؛ متن اصلی نگه داشته می‌شود تا بعد برگردد. */
    function busy($btn, on) {
        if (on) {
            $btn.data('label', $btn.html())
                .prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin"></i> لطفا صبر کنید');
        } else {
            $btn.prop('disabled', false).html($btn.data('label'));
        }
    }

    const TEXTS = {
        mobile:   ['ورود به ناظر یدک', 'برای ورود یا ثبت‌نام، شماره موبایل خود را وارد کنید'],
        password: ['خوش آمدید', 'رمز عبور حساب خود را وارد کنید'],
        otp:      ['تأیید شماره موبایل', 'کد ۶ رقمی پیامک‌شده را وارد کنید'],
        signup:   ['ثبت‌نام در ناظر یدک', 'کد ۶ رقمی پیامک‌شده را وارد کنید تا حساب شما ساخته شود'],
        profile:  ['تکمیل حساب', 'نام خود را وارد کنید. اگر رمز عبور بگذارید، دفعه‌ی بعد بدون منتظر ماندن برای پیامک وارد می‌شوید.'],
    };

    function goStep(name) {
        clearError();
        $card.find('.nx-auth-step').prop('hidden', true);
        $card.find('[data-step="' + name + '"]').prop('hidden', false);

        const key = (name === 'otp' && state.isNew) ? 'signup' : name;
        $title.text(TEXTS[key][0]);
        $sub.text(TEXTS[key][1]);

        $('.js-identity').text(faDigits(state.mobile));

        if (name === 'mobile') {
            stopTimer();
            $('#mobile').trigger('focus');
        } else if (name === 'password') {
            $('#password').val('').trigger('focus');
        } else if (name === 'profile') {
            stopTimer();
            showDevCode(null);
            $('#first_name').trigger('focus');
        } else {
            // در گام کد، گزینه‌ی برگشت به رمز فقط وقتی معنا دارد که رمزی ثبت شده باشد
            $('#btnUsePassword').prop('hidden', !state.hasPassword);
            $('#otp').val('').trigger('focus');
        }
    }

    /* ---------- شمارش معکوس ارسال مجدد ---------- */

    function stopTimer() {
        clearInterval(state.timerId);
        state.timerId = null;
    }

    function startTimer(seconds) {
        stopTimer();
        $('#btnResend').prop('hidden', true);
        $('#resendTimer').prop('hidden', false);

        let remaining = seconds;
        $('#resendSeconds').text(faDigits(remaining));

        state.timerId = setInterval(function () {
            if (--remaining <= 0) {
                stopTimer();
                $('#resendTimer').prop('hidden', true);
                $('#btnResend').prop('hidden', false);
                return;
            }
            $('#resendSeconds').text(faDigits(remaining));
        }, 1000);
    }

    /* کد در محیط توسعه، وقتی درگاه پیامک تنظیم نشده است */
    function showDevCode(code) {
        if (!code) { $dev.prop('hidden', true); return; }
        $dev.html('کد پیامکی (فقط محیط توسعه): <b>' + code + '</b>').prop('hidden', false);
    }

    /* ---------- گام ۱ ---------- */

    $('#btnContinue').on('click', function () {
        const $btn = $(this);
        const mobile = enDigits($('#mobile').val()).replace(/[^\d+]/g, '');

        if (!mobile) { showError('شماره موبایل را وارد کنید.'); return; }

        busy($btn, true);
        $.post('/auth/check', { _token: csrf, mobile: mobile })
            .done(function (res) {
                state.mobile      = mobile;
                state.hasPassword = res.step === 'password';
                state.isNew       = !!res.is_new;

                if (res.step === 'password') {
                    goStep('password');
                    if (res.greeting) $sub.text(res.greeting + ' عزیز، رمز عبور خود را وارد کنید');
                } else {
                    goStep('otp');
                    startTimer(res.wait || 60);
                    showDevCode(res.dev_code);
                }
            })
            .fail(function (xhr) {
                showError(errorOf(xhr, 'ارتباط برقرار نشد. دوباره تلاش کنید.'));
            })
            .always(function () { busy($btn, false); });
    });

    /* ---------- گام رمز عبور ---------- */

    $('#btnPassword').on('click', function () {
        const $btn = $(this);
        const password = $('#password').val();

        if (!password) { showError('رمز عبور را وارد کنید.'); return; }

        busy($btn, true);
        $.post('/auth/login-password', { _token: csrf, mobile: state.mobile, password: password })
            .done(function (res) { window.location = res.redirect || '/dashboard'; })
            .fail(function (xhr) {
                busy($btn, false);
                showError(errorOf(xhr, 'ورود انجام نشد.'));

                // اگر رمز روی این حساب حذف شده باشد، سرور مسیر کد را پیشنهاد می‌دهد
                if (xhr.responseJSON && xhr.responseJSON.step === 'otp') {
                    state.hasPassword = false;
                    requestOtp($btn);
                }
            });
    });

    $('#togglePassword').on('click', function () {
        const $input = $('#password');
        const show = $input.attr('type') === 'password';
        $input.attr('type', show ? 'text' : 'password');
        $(this).find('i').attr('class', show ? 'fas fa-eye-slash' : 'fas fa-eye');
        $input.trigger('focus');
    });

    /* ---------- گام کد پیامکی ---------- */

    function requestOtp($btn) {
        busy($btn, true);
        $.post('/auth/send-otp', { _token: csrf, mobile: state.mobile })
            .done(function (res) {
                state.isNew = !!res.is_new;
                goStep('otp');
                startTimer(res.wait || 60);
                showDevCode(res.dev_code);
            })
            .fail(function (xhr) {
                goStep('otp');
                showError(errorOf(xhr, 'ارسال کد انجام نشد.'));
                const wait = (xhr.responseJSON || {}).wait;
                if (wait) startTimer(wait);
            })
            .always(function () { busy($btn, false); });
    }

    $('#btnUseOtp').on('click', function () { requestOtp($(this)); });
    $('#btnResend').on('click', function () { requestOtp($(this)); });

    $('#btnUsePassword').on('click', function () {
        stopTimer();
        goStep('password');
    });

    $('#btnVerify').on('click', function () {
        const $btn = $(this);
        if ($btn.prop('disabled')) return;

        const code = enDigits($('#otp').val()).replace(/\D/g, '');
        if (code.length !== 6) { showError('کد ۶ رقمی را کامل وارد کنید.'); return; }

        busy($btn, true);
        $.post('/auth/verify-otp', { _token: csrf, mobile: state.mobile, otp: code })
            .done(function (res) {
                state.redirect = res.redirect || '/dashboard';

                // حساب تازه هنوز نام ندارد؛ به‌جای پرتاب به داشبوردِ خالی،
                // یک گام کوتاه برای تکمیل حساب نشان داده می‌شود
                if (res.step === 'profile') {
                    busy($btn, false);
                    goStep('profile');
                    return;
                }

                window.location = state.redirect;
            })
            .fail(function (xhr) {
                busy($btn, false);
                showError(errorOf(xhr, 'کد وارد شده درست نیست.'));
                $('#otp').val('').trigger('focus');
            });
    });

    /* ---------- گام تکمیل حساب ---------- */

    // تکرار رمز فقط وقتی معنا دارد که کاربر رمزی تایپ کرده باشد
    $('#new_password').on('input', function () {
        $('#newPasswordConfirmWrap').prop('hidden', $(this).val().length === 0);
    });

    $('#toggleNewPassword').on('click', function () {
        const $input = $('#new_password');
        const show = $input.attr('type') === 'password';
        $input.attr('type', show ? 'text' : 'password');
        $(this).find('i').attr('class', show ? 'fas fa-eye-slash' : 'fas fa-eye');
        $input.trigger('focus');
    });

    $('#btnCompleteProfile').on('click', function () {
        const $btn = $(this);
        const password = $('#new_password').val();
        const confirm  = $('#new_password_confirmation').val();

        if (!$('#first_name').val().trim() || !$('#last_name').val().trim()) {
            showError('نام و نام خانوادگی را وارد کنید.');
            return;
        }
        if (password && password.length < 6) {
            showError('رمز عبور باید حداقل ۶ کاراکتر باشد.');
            return;
        }
        if (password && password !== confirm) {
            showError('تکرار رمز عبور با رمز یکسان نیست.');
            return;
        }

        busy($btn, true);
        $.post('/auth/complete-profile', {
            _token: csrf,
            first_name: $('#first_name').val().trim(),
            last_name: $('#last_name').val().trim(),
            password: password,
            password_confirmation: confirm
        })
            .done(function (res) { window.location = res.redirect || state.redirect; })
            .fail(function (xhr) {
                busy($btn, false);
                showError(errorOf(xhr, 'ثبت اطلاعات انجام نشد.'));
            });
    });

    $('#btnSkipProfile').on('click', function () { window.location = state.redirect; });

    // با کامل شدن ۶ رقم (تایپ دستی یا تکمیل خودکار از پیامک) خودبه‌خود ارسال می‌شود
    $('#otp').on('input', function () {
        if (enDigits($(this).val()).replace(/\D/g, '').length === 6) $('#btnVerify').trigger('click');
    });

    /* ---------- برگشت به گام شماره ---------- */

    $('.js-edit-mobile').on('click', function () {
        state.hasPassword = false;
        state.isNew = false;
        showDevCode(null);
        goStep('mobile');
    });

    /* ---------- کلید Enter ---------- */

    $('#mobile').on('keyup',   e => { if (e.key === 'Enter') $('#btnContinue').trigger('click'); });
    $('#password').on('keyup', e => { if (e.key === 'Enter') $('#btnPassword').trigger('click'); });
    $('#otp').on('keyup',      e => { if (e.key === 'Enter') $('#btnVerify').trigger('click'); });
})();
</script>
@endsection
