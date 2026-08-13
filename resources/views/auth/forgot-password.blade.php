@extends('layout.layout', [
    'title' => 'بازیابی رمز عبور | ناظر یدک',
    'metaDescription' => 'بازیابی رمز عبور حساب کاربری ناظر یدک با کد پیامکی؛ شماره موبایل خود را وارد کنید و رمز تازه بسازید.',
    'robots' => seo_robots_tag(false, true),
    'noBaseSchema' => true,
])

@section('main_content')
<main class="nx-auth">
    <div class="nx-auth-card">
        <div class="nx-auth-head">
            <span class="nx-auth-logo"><i class="fas fa-key"></i></span>
            <h1 id="authTitle">بازیابی رمز عبور</h1>
            <p id="authSubtitle">شماره موبایل حساب خود را وارد کنید تا کد تأیید بفرستیم</p>
        </div>

        <p class="nx-auth-error" id="authError" hidden></p>
        <p class="nx-auth-dev" id="authDevCode" hidden></p>

        {{-- گام ۱: شماره موبایل --}}
        <section class="nx-auth-step" data-step="mobile">
            <div class="nx-auth-field">
                <label for="mobile">شماره موبایل</label>
                <input type="tel" id="mobile" class="is-num" placeholder="۰۹۱۲۰۰۰۰۰۰۰"
                       inputmode="numeric" autocomplete="tel" enterkeyhint="next"
                       maxlength="13" autofocus>
            </div>

            <button type="button" class="nx-auth-submit" id="btnSend">
                <i class="fas fa-paper-plane"></i> ارسال کد تأیید
            </button>

            <a class="nx-auth-link" href="/login">بازگشت به صفحه‌ی ورود</a>
        </section>

        {{-- گام ۲: کد تأیید --}}
        <section class="nx-auth-step" data-step="otp" hidden>
            <div class="nx-auth-identity">
                <span class="js-identity"></span>
                <button type="button" class="js-edit-mobile"><i class="fas fa-pen"></i> تغییر شماره</button>
            </div>

            <div class="nx-auth-field">
                <label for="otp">کد تأیید پیامک‌شده</label>
                <input type="text" id="otp" class="is-num is-code" placeholder="——————"
                       inputmode="numeric" autocomplete="one-time-code" enterkeyhint="go"
                       maxlength="6" pattern="[0-9۰-۹]*">
            </div>

            <button type="button" class="nx-auth-submit" id="btnVerify">
                <i class="fas fa-check"></i> تأیید کد
            </button>

            <div class="nx-auth-resend" id="resendWrap">
                <span id="resendTimer">ارسال مجدد کد تا <b id="resendSeconds">۶۰</b> ثانیه دیگر</span>
                <button type="button" id="btnResend" hidden><i class="fas fa-redo"></i> ارسال مجدد کد</button>
            </div>
        </section>

        {{-- گام ۳: رمز تازه --}}
        <section class="nx-auth-step" data-step="reset" hidden>
            <div class="nx-auth-field nx-auth-pass">
                <label for="password">رمز عبور جدید</label>
                <input type="password" id="password" autocomplete="new-password" minlength="6">
                <button type="button" class="nx-auth-eye" id="togglePassword" aria-label="نمایش رمز عبور">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            <div class="nx-auth-field">
                <label for="password_confirmation">تکرار رمز عبور جدید</label>
                <input type="password" id="password_confirmation" autocomplete="new-password"
                       minlength="6" enterkeyhint="go">
            </div>

            <button type="button" class="nx-auth-submit" id="btnReset">
                <i class="fas fa-check"></i> ثبت رمز و ورود
            </button>
        </section>

        <div class="nx-auth-foot">
            <i class="fas fa-shield-halved"></i>
            رمز جدید حداقل ۶ کاراکتر باشد. پس از ثبت، وارد حساب می‌شوید.
        </div>
    </div>
</main>
@endsection

@section('js')
<script>
(function () {
    const state = { mobile: '', timerId: null };

    const $card  = $('.nx-auth-card');
    const $error = $('#authError');
    const $dev   = $('#authDevCode');
    const csrf   = '{{ csrf_token() }}';

    const faDigits = v => String(v).replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
    const enDigits = v => String(v)
        .replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d))
        .replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d));

    const showError = m => $error.text(m).prop('hidden', false);
    const clearError = () => $error.prop('hidden', true);

    function errorOf(xhr, fallback) {
        const body = xhr.responseJSON || {};
        if (body.message) return body.message;
        if (body.errors) return Object.values(body.errors)[0][0];
        return fallback;
    }

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
        mobile: ['بازیابی رمز عبور', 'شماره موبایل حساب خود را وارد کنید تا کد تأیید بفرستیم'],
        otp:    ['تأیید شماره موبایل', 'کد ۶ رقمی پیامک‌شده را وارد کنید'],
        reset:  ['رمز عبور تازه', 'رمز جدید خود را دوبار وارد کنید'],
    };

    function goStep(name) {
        clearError();
        $card.find('.nx-auth-step').prop('hidden', true);
        $card.find('[data-step="' + name + '"]').prop('hidden', false);
        $('#authTitle').text(TEXTS[name][0]);
        $('#authSubtitle').text(TEXTS[name][1]);
        $('.js-identity').text(faDigits(state.mobile));

        if (name === 'mobile') { stopTimer(); $('#mobile').trigger('focus'); }
        if (name === 'otp')    { $('#otp').val('').trigger('focus'); }
        if (name === 'reset')  { stopTimer(); $('#password').trigger('focus'); }
    }

    function stopTimer() { clearInterval(state.timerId); state.timerId = null; }

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

    function showDevCode(code) {
        if (!code) { $dev.prop('hidden', true); return; }
        $dev.html('کد پیامکی (فقط محیط توسعه): <b>' + code + '</b>').prop('hidden', false);
    }

    function send($btn) {
        const mobile = state.mobile || enDigits($('#mobile').val()).replace(/[^\d+]/g, '');
        if (!mobile) { showError('شماره موبایل را وارد کنید.'); return; }

        busy($btn, true);
        $.post('/password/forgot/send-otp', { _token: csrf, mobile: mobile })
            .done(function (res) {
                state.mobile = mobile;
                goStep('otp');
                startTimer(res.wait || 60);
                showDevCode(res.dev_code);
            })
            .fail(function (xhr) {
                showError(errorOf(xhr, 'ارسال کد انجام نشد.'));
                const wait = (xhr.responseJSON || {}).wait;
                if (wait && state.mobile) startTimer(wait);
            })
            .always(function () { busy($btn, false); });
    }

    $('#btnSend').on('click', function () { send($(this)); });
    $('#btnResend').on('click', function () { send($(this)); });

    $('#btnVerify').on('click', function () {
        const $btn = $(this);
        if ($btn.prop('disabled')) return;

        const code = enDigits($('#otp').val()).replace(/\D/g, '');
        if (code.length !== 6) { showError('کد ۶ رقمی را کامل وارد کنید.'); return; }

        busy($btn, true);
        $.post('/password/forgot/verify-otp', { _token: csrf, mobile: state.mobile, otp: code })
            .done(function () { goStep('reset'); })
            .fail(function (xhr) {
                showError(errorOf(xhr, 'کد وارد شده درست نیست.'));
                $('#otp').val('').trigger('focus');
            })
            .always(function () { busy($btn, false); });
    });

    $('#otp').on('input', function () {
        if (enDigits($(this).val()).replace(/\D/g, '').length === 6) $('#btnVerify').trigger('click');
    });

    $('#btnReset').on('click', function () {
        const $btn = $(this);
        const password = $('#password').val();
        const confirm  = $('#password_confirmation').val();

        if (password.length < 6)   { showError('رمز عبور باید حداقل ۶ کاراکتر باشد.'); return; }
        if (password !== confirm)  { showError('تکرار رمز عبور با رمز جدید یکسان نیست.'); return; }

        busy($btn, true);
        $.post('/password/forgot/reset', {
            _token: csrf,
            password: password,
            password_confirmation: confirm
        })
            .done(function (res) { window.location = res.redirect || '/dashboard'; })
            .fail(function (xhr) {
                busy($btn, false);
                showError(errorOf(xhr, 'ثبت رمز انجام نشد.'));
                // مهلت تعیین رمز تمام شده؛ باید از اول کد بگیرد
                if ((xhr.responseJSON || {}).step === 'mobile') goStep('mobile');
            });
    });

    $('#togglePassword').on('click', function () {
        const $input = $('#password');
        const show = $input.attr('type') === 'password';
        $input.attr('type', show ? 'text' : 'password');
        $(this).find('i').attr('class', show ? 'fas fa-eye-slash' : 'fas fa-eye');
        $input.trigger('focus');
    });

    $('.js-edit-mobile').on('click', function () {
        state.mobile = '';
        showDevCode(null);
        goStep('mobile');
    });

    $('#mobile').on('keyup', e => { if (e.key === 'Enter') $('#btnSend').trigger('click'); });
    $('#otp').on('keyup', e => { if (e.key === 'Enter') $('#btnVerify').trigger('click'); });
    $('#password_confirmation').on('keyup', e => { if (e.key === 'Enter') $('#btnReset').trigger('click'); });
})();
</script>
@endsection
