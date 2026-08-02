@extends('layout.layout', [
    'title' => "ورود | ناظر یدک"
])
@section('main_content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-11 col-sm-8 col-md-6 col-lg-4" style="margin: 60px auto;">
            <div class="cart-content p-4">
                <div class="text-center mb-4">
                    <div style="width:70px; height:70px; background:var(--primary-lighter); border-radius:50%; display:flex; align-items:center; justify-content:center; margin:0 auto 15px;">
                        <i class="fas fa-user" style="font-size:1.8rem; color:var(--primary);"></i>
                    </div>
                    <h5 style="font-weight:700; font-size:1.1rem;">ورود به ناظر یدک</h5>
                    <p class="font-12 text-muted">شماره موبایل خود را وارد کنید</p>
                </div>

                <form id="loginForm">
                    <div class="mb-3" id="mobileBox">
                        <label class="font-12 mb-1 text-muted">شماره موبایل</label>
                        <div class="position-relative">
                            <input type="tel" name="mobile" id="mobile"
                                class="form-control form-control-lg text-center"
                                placeholder="۰۹۱۲۰۰۰۰۰۰۰"
                                style="border-radius:var(--radius-sm); letter-spacing:2px; font-size:1.1rem; direction:ltr;">
                        </div>
                    </div>

                    <div class="mb-3 d-none" id="otpBox">
                        <label class="font-12 mb-1 text-muted">کد تأیید ارسال‌شده</label>
                        <input type="text" name="otp" id="otp"
                            class="form-control form-control-lg text-center"
                            placeholder="- - - - -"
                            maxlength="6"
                            style="border-radius:var(--radius-sm); letter-spacing:8px; font-size:1.3rem; direction:ltr;">
                        <p class="font-12 text-muted mt-2 text-center" id="otpHint">
                            <i class="fas fa-sms me-1"></i> کد تأیید به شماره <span id="sentTo" class="fw-bold"></span> ارسال شد
                        </p>
                    </div>

                    <button type="button" id="sendOtpBtn"
                        class="btn w-100 py-2 mt-2" style="background:linear-gradient(135deg, var(--primary), var(--primary-light)); color:#fff; border-radius:var(--radius-sm); font-size:.95rem; font-weight:600;">
                        <i class="fas fa-paper-plane me-1"></i> دریافت کد تأیید
                    </button>

                    <button type="button" id="verifyOtpBtn"
                        class="btn w-100 py-2 mt-2 d-none" style="background:linear-gradient(135deg, var(--success), #43a047); color:#fff; border-radius:var(--radius-sm); font-size:.95rem; font-weight:600;">
                        <i class="fas fa-sign-in-alt me-1"></i> ورود به حساب
                    </button>

                    <button type="button" id="editMobileBtn" class="btn btn-link w-100 d-none font-12 mt-1" style="color:var(--primary);">
                        <i class="fas fa-edit me-1"></i> تغییر شماره موبایل
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top">
                    <p class="font-12 text-muted mb-0" style="line-height:2;">
                        <i class="fas fa-shield-alt me-1" style="color:var(--success);"></i>
                        ورود شما با رمز یکبار مصرف (OTP) انجام می‌شود
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js')
<script>
let mobile = '';

$('#sendOtpBtn').click(function () {
    mobile = $('#mobile').val();
    let btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> در حال ارسال...');

    $.post("/auth/send-otp", {
        _token: '{{ csrf_token() }}',
        mobile: mobile
    }, function () {
        $('#otpBox').removeClass('d-none');
        $('#sendOtpBtn').addClass('d-none');
        $('#verifyOtpBtn').removeClass('d-none');
        $('#editMobileBtn').removeClass('d-none');
        $('#mobileBox').addClass('d-none');
        $('#sentTo').text(mobile);
        $('#otp').focus();
    }).fail(function (res) {
        btn.prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> دریافت کد تأیید');
        Swal.fire({ icon: 'error', text: res.responseJSON?.message ?? 'خطا در ارسال کد', confirmButtonColor: 'var(--primary)' });
    });
});

$('#verifyOtpBtn').click(function () {
    let btn = $(this);
    btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> بررسی...');

    $.post("/auth/verify-otp", {
        _token: '{{ csrf_token() }}',
        mobile: mobile,
        otp: $('#otp').val()
    }, function (res) {
        window.location.href = res.redirect;
    }).fail(function (res) {
        btn.prop('disabled', false).html('<i class="fas fa-sign-in-alt me-1"></i> ورود به حساب');
        Swal.fire({ icon: 'error', text: res.responseJSON?.message ?? 'کد وارد شده اشتباه است', confirmButtonColor: 'var(--primary)' });
    });
});

$('#editMobileBtn').click(function () {
    $('#mobileBox').removeClass('d-none');
    $('#otpBox').addClass('d-none');
    $('#sendOtpBtn').removeClass('d-none').prop('disabled', false).html('<i class="fas fa-paper-plane me-1"></i> دریافت کد تأیید');
    $('#verifyOtpBtn').addClass('d-none');
    $('#editMobileBtn').addClass('d-none');
    $('#mobile').focus();
});

$('#otp').on('keyup', function(e) {
    if (e.key === 'Enter') $('#verifyOtpBtn').click();
});
$('#mobile').on('keyup', function(e) {
    if (e.key === 'Enter') $('#sendOtpBtn').click();
});
</script>
@endsection
