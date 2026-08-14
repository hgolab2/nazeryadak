@php
    /* کادر کد تخفیف در خلاصه‌ی فاکتور.
       هم در بارگذاری اول رندر می‌شود و هم DiscountController بعد از ثبت یا
       برداشتن کد همین فایل را رندر می‌کند و جای قبلی را می‌گیرد. */
    $applied = $order->hasDiscount() && $order->discount_code;
@endphp
<div id="discountBox" data-order="{{ $order->id }}">
    @if($applied)
        <div class="d-flex align-items-center justify-content-between gap-2 p-2"
             style="background:#e8f5e9; border:1px solid #a5d6a7; border-radius:var(--radius-sm);">
            <div class="font-12" style="line-height:1.9; color:#1b5e20;">
                <i class="fas fa-tag me-1"></i>
                کد <bdi class="fw-bold">{{ $order->discount_code }}</bdi> اعمال شد
                <span class="d-block">{{ toPersianNumbers(number_format((int) $order->discount_amount)) }} تومان تخفیف</span>
            </div>
            <button type="button" id="removeDiscountBtn" class="btn btn-sm p-1 font-12"
                    style="color:#c62828; background:transparent; border:none;" title="برداشتن کد تخفیف">
                <i class="fas fa-times-circle"></i> حذف
            </button>
        </div>
    @else
        <label for="discountCodeInput" class="font-12 text-muted d-block mb-1">
            <i class="fas fa-tag me-1"></i> کد تخفیف دارید؟
        </label>
        <div class="d-flex gap-2">
            {{-- dir=ltr چون کدها لاتین‌اند و با راست‌چین بودن، مکان‌نما می‌پرد --}}
            <input type="text" id="discountCodeInput" class="form-control font-12" placeholder="مثلا NOROOZ"
                   autocomplete="off" maxlength="40" style="border-radius:var(--radius-sm); direction:ltr; text-align:left;">
            <button type="button" id="applyDiscountBtn" class="btn btn-sm font-12 px-3 text-nowrap"
                    style="border:1px solid var(--primary); color:var(--primary); border-radius:var(--radius-sm);">
                ثبت
            </button>
        </div>
    @endif
</div>
