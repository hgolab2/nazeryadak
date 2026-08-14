{{-- رفتار کادر کد تخفیف؛ بین صفحه‌ی «اطلاعات ارسال» و «پرداخت» مشترک است.
     کادر بعد از هر پاسخ کامل جایگزین می‌شود، پس هندلرها روی document بسته
     شده‌اند نه روی خود دکمه‌ها. --}}
<script>
(function () {
    var csrfToken = '{{ csrf_token() }}';

    // ردیف تخفیف و مبلغ نهایی در هر دو صفحه با همین شناسه‌ها ساخته شده‌اند
    function renderTotals(res) {
        var row = $('#discountRow');
        if (res.discount_amount > 0) {
            $('#discountAmount').text('−' + new Intl.NumberFormat().format(res.discount_amount) + ' تومان');
            row.removeClass('d-none');
        } else {
            row.addClass('d-none');
        }

        var price = new Intl.NumberFormat().format(res.total_price);
        $('#finalPrice').html(price + ' <small class="font-12 fw-normal">تومان</small>');
        $('#finalPriceMobile').html(price + ' <small>تومان</small>');
    }

    function send(url, data, button, busyLabel) {
        var original = button.html();
        button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> ' + busyLabel);

        data._token = csrfToken;
        data.order_id = $('#discountBox').data('order');

        $.post(url, data)
            .done(function (res) {
                $('#discountBox').replaceWith(res.html);
                renderTotals(res);
                toast.fire({ icon: 'success', title: res.message });
            })
            .fail(function (xhr) {
                button.prop('disabled', false).html(original);
                var message = (xhr.responseJSON && xhr.responseJSON.message) || 'خطا در بررسی کد تخفیف';
                toast.fire({ icon: 'error', title: message });
            });
    }

    $(document).on('click', '#applyDiscountBtn', function () {
        var input = $('#discountCodeInput');
        if ($.trim(input.val()) === '') {
            input.trigger('focus');
            return;
        }
        send("{{ route('order.discount.apply') }}", { code: input.val() }, $(this), 'بررسی...');
    });

    // زدن Enter داخل کادر همان کار دکمه را می‌کند؛ فرمی در کار نیست که
    // خودش submit شود، پس باید دستی وصل شود.
    $(document).on('keydown', '#discountCodeInput', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            $('#applyDiscountBtn').trigger('click');
        }
    });

    $(document).on('click', '#removeDiscountBtn', function () {
        send("{{ route('order.discount.remove') }}", {}, $(this), 'حذف...');
    });
})();
</script>
