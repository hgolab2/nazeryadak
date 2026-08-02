// Just Text
$('.js_valid_text').on('input', function() {
    this.value = this.value.replace(/[^a-zA-Z ا آ ئ ب پ ت ث ج چ ح خ د ذ ر ز ژ س ش ص ض ط ظ ع غ ف ق ک گ ل م ن و ه ی]/g, '');
});

// Just Number
$('.js_valid_number').on('input', function() {
    this.value = this.value.replace(/\D/g, '');
});

$('.js_valid_number_float').on('input', function() {
    this.value = this.value.replace(/[^\d.]/g, '');

    // حذف نقاط اضافی غیر از نقطه اول
    this.value = this.value.replace(/\.(?=.*\.)/g, '');
});

// Password
$('.js_valid_pass').on('input', function() {
    this.value = this.value.replace(/[^a-zA-Z0-9 @ $ ! % * ? & # ^]/g, '');
});

// choice user
$('.js_valid_user').on('input', function() {
    this.value = this.value.replace(/[^a-zA-Z0-9_  ]/g, '');
});
