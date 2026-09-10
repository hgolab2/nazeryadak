{{--
    جعبه‌ی تأیید شماره، بعد از ثبت سفارش.

    عمدا با لحن «سود» نوشته شده نه «وظیفه»: مشتری همین الان پول داده و
    دلیلی ندارد یک کار اداریِ دیگر انجام دهد. چیزی که او را وادار می‌کند
    کد را بزند، نه امنیت ماست، بلکه «دفعه‌ی بعد آدرس را دوباره وارد نکن».

    اگر کد را نزند هیچ‌چیز خراب نمی‌شود؛ سفارش ثبت است و کارشناس تماس می‌گیرد.
--}}
@if(\App\Http\Controllers\PhoneVerificationController::needed($order))
    @php
        $verifyPhone = optional($order->address)->receiver_phone ?: $order->customer->phone;
    @endphp
    <div class="nx-verify" id="nx-verify" data-order="{{ $order->id }}">
        <div class="nx-verify-head">
            <i class="fas fa-mobile-screen-button"></i>
            <div>
                <b>دفعه‌ی بعد، آدرس را دوباره وارد نکنید</b>
                <p>
                    کد ۶ رقمی که همراه پیامک سفارش به
                    <bdi class="nx-verify-phone">{{ toPersianNumbers($verifyPhone, false) }}</bdi>
                    فرستادیم را بزنید تا سابقه‌ی سفارش‌ها و آدرستان ذخیره شود.
                </p>
            </div>
        </div>

        <form class="nx-verify-form" id="nx-verify-form" autocomplete="off">
            @csrf
            <input type="hidden" name="order_id" value="{{ $order->id }}">
            <label class="visually-hidden" for="nx-verify-code">کد تأیید شماره</label>
            {{-- one-time-code روی iOS و WebOTP (پایین) روی اندروید؛ در حالت
                 خوب، مشتری اصلا چیزی تایپ نمی‌کند. --}}
            <input type="text" id="nx-verify-code" name="code" class="is-num is-code"
                   inputmode="numeric" autocomplete="one-time-code" maxlength="6"
                   placeholder="——————" enterkeyhint="go" aria-describedby="nx-verify-msg">
            <button type="submit" class="nx-verify-btn">تأیید</button>
        </form>

        <p class="nx-verify-msg" id="nx-verify-msg" role="status" aria-live="polite"></p>

        <p class="nx-verify-skip">
            وارد نکردن این کد مشکلی ایجاد نمی‌کند؛ سفارش شما ثبت است و
            کارشناسان ما برای هماهنگی با شما تماس می‌گیرند.
        </p>
    </div>

    <script>
    (function () {
        var box  = document.getElementById('nx-verify');
        var form = document.getElementById('nx-verify-form');
        var code = document.getElementById('nx-verify-code');
        var msg  = document.getElementById('nx-verify-msg');
        if (!box || !form || !code) return;

        function say(text, ok) {
            msg.textContent = text;
            msg.className = 'nx-verify-msg' + (ok ? ' is-ok' : (text ? ' is-error' : ''));
        }

        // فقط رقم، و رقم فارسی هم به انگلیسی تبدیل شود؛ کیبورد فارسی روی
        // موبایل خیلی رایج است و بدون این، کد همیشه «نادرست» می‌شد.
        code.addEventListener('input', function () {
            var fa = '۰۱۲۳۴۵۶۷۸۹', ar = '٠١٢٣٤٥٦٧٨٩';
            code.value = code.value.replace(/[^\d۰-۹٠-٩]/g, '').replace(/[۰-۹٠-٩]/g, function (d) {
                var i = fa.indexOf(d);
                return String(i > -1 ? i : ar.indexOf(d));
            });
            if (code.value.length === 6) form.requestSubmit();
        });

        var busy = false;
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (busy || code.value.length !== 6) return;
            busy = true;
            say('در حال بررسی…', false);

            fetch('/order/verify-phone', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': form.querySelector('input[name=_token]').value,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ order_id: box.dataset.order, code: code.value })
            })
            .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
            .then(function (res) {
                busy = false;
                if (res.ok && res.d.status === 'success') {
                    box.classList.add('is-done');
                    form.hidden = true;
                    say(res.d.message, true);
                } else {
                    code.value = '';
                    say(res.d.message || 'کد وارد شده نادرست یا منقضی شده است.', false);
                }
            })
            .catch(function () {
                busy = false;
                say('ارتباط برقرار نشد. دوباره تلاش کنید.', false);
            });
        });

        // WebOTP: روی کروم اندروید کد از پیامک خوانده و خودکار پر می‌شود.
        // پیامک برای همین خط آخرِ «@دامنه #کد» را دارد.
        if ('OTPCredential' in window && navigator.credentials) {
            var abort = new AbortController();
            navigator.credentials.get({ otp: { transport: ['sms'] }, signal: abort.signal })
                .then(function (otp) {
                    if (otp && otp.code) {
                        code.value = otp.code;
                        form.requestSubmit();
                    }
                })
                .catch(function () { /* کاربر اجازه نداد یا مرورگر پشتیبانی نکرد */ });
        }
    })();
    </script>
@endif
