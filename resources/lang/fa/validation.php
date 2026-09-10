<?php

/*
|--------------------------------------------------------------------------
| پیام‌های اعتبارسنجی
|--------------------------------------------------------------------------
| تا پیش از این پوشه‌ی lang اصلا وجود نداشت؛ یعنی هر فرمی که پیام سفارشی
| نداشت، متن پیش‌فرض انگلیسی لاراول را نشان می‌داد
| («The address line field is required»).
|
| گذاشتن پیام سفارشی داخل تک‌تک فراخوانی‌های validate() جواب نمی‌دهد: سیزده
| کنترلر و ده‌ها قاعده هست و هر فرم تازه‌ای دوباره همان اشتباه را تکرار
| می‌کند. جای درستش همین‌جاست تا یک بار برای همیشه حل شود.
*/

return [

    'accepted'             => ':attribute باید پذیرفته شود.',
    'accepted_if'          => 'وقتی :other برابر :value است، :attribute باید پذیرفته شود.',
    'active_url'           => ':attribute یک نشانی معتبر نیست.',
    'after'                => ':attribute باید تاریخی بعد از :date باشد.',
    'after_or_equal'       => ':attribute باید تاریخی برابر یا بعد از :date باشد.',
    'alpha'                => ':attribute باید فقط شامل حروف باشد.',
    'alpha_dash'           => ':attribute باید فقط شامل حروف، عدد، خط تیره و زیرخط باشد.',
    'alpha_num'            => ':attribute باید فقط شامل حروف و عدد باشد.',
    'array'                => ':attribute باید یک آرایه باشد.',
    'before'               => ':attribute باید تاریخی پیش از :date باشد.',
    'before_or_equal'      => ':attribute باید تاریخی برابر یا پیش از :date باشد.',
    'boolean'              => ':attribute فقط می‌تواند بله یا خیر باشد.',
    'confirmed'            => 'تکرار :attribute با آن یکی نیست.',
    'current_password'     => 'رمز عبور فعلی درست نیست.',
    'date'                 => ':attribute یک تاریخ معتبر نیست.',
    'date_equals'          => ':attribute باید برابر با :date باشد.',
    'date_format'          => ':attribute با قالب :format هم‌خوان نیست.',
    'declined'             => ':attribute باید رد شود.',
    'different'            => ':attribute و :other باید متفاوت باشند.',
    'digits'               => ':attribute باید :digits رقم باشد.',
    'digits_between'       => ':attribute باید بین :min و :max رقم باشد.',
    'dimensions'           => 'ابعاد تصویر :attribute مجاز نیست.',
    'distinct'             => ':attribute تکراری است.',
    'doesnt_end_with'      => ':attribute نباید با یکی از این‌ها تمام شود: :values',
    'doesnt_start_with'    => ':attribute نباید با یکی از این‌ها شروع شود: :values',
    'email'                => ':attribute یک نشانی ایمیل معتبر نیست.',
    'ends_with'            => ':attribute باید با یکی از این‌ها تمام شود: :values',
    'enum'                 => ':attribute انتخاب‌شده معتبر نیست.',
    'exists'               => ':attribute انتخاب‌شده معتبر نیست.',
    'file'                 => ':attribute باید یک فایل باشد.',
    'filled'               => ':attribute نمی‌تواند خالی باشد.',
    'image'                => ':attribute باید یک تصویر باشد.',
    'in'                   => ':attribute انتخاب‌شده معتبر نیست.',
    'in_array'             => ':attribute در :other وجود ندارد.',
    'integer'              => ':attribute باید یک عدد صحیح باشد.',
    'ip'                   => ':attribute باید یک نشانی IP معتبر باشد.',
    'ipv4'                 => ':attribute باید یک نشانی IPv4 معتبر باشد.',
    'ipv6'                 => ':attribute باید یک نشانی IPv6 معتبر باشد.',
    'json'                 => ':attribute باید یک رشته‌ی JSON معتبر باشد.',
    'lowercase'            => ':attribute باید با حروف کوچک باشد.',
    'mac_address'          => ':attribute باید یک نشانی MAC معتبر باشد.',
    'max_digits'           => ':attribute نباید بیشتر از :max رقم باشد.',
    'mimes'                => 'فایل :attribute باید از نوع :values باشد.',
    'mimetypes'            => 'فایل :attribute باید از نوع :values باشد.',
    'min_digits'           => ':attribute نباید کمتر از :min رقم باشد.',
    'missing'              => ':attribute نباید فرستاده شود.',
    'multiple_of'          => ':attribute باید مضربی از :value باشد.',
    'not_in'               => ':attribute انتخاب‌شده معتبر نیست.',
    'not_regex'            => 'قالب :attribute معتبر نیست.',
    'numeric'              => ':attribute باید یک عدد باشد.',
    'present'              => ':attribute باید فرستاده شود.',
    'prohibited'           => ':attribute مجاز نیست.',
    'prohibited_if'        => 'وقتی :other برابر :value است، :attribute مجاز نیست.',
    'prohibited_unless'    => 'مگر اینکه :other یکی از :values باشد، :attribute مجاز نیست.',
    'regex'                => 'قالب :attribute درست نیست.',
    'required'             => 'وارد کردن :attribute الزامی است.',
    'required_array_keys'  => ':attribute باید شامل :values باشد.',
    'required_if'          => 'وقتی :other برابر :value است، وارد کردن :attribute الزامی است.',
    'required_if_accepted' => 'وقتی :other پذیرفته شده، وارد کردن :attribute الزامی است.',
    'required_unless'      => 'مگر اینکه :other یکی از :values باشد، وارد کردن :attribute الزامی است.',
    'required_with'        => 'وقتی :values هست، وارد کردن :attribute الزامی است.',
    'required_with_all'    => 'وقتی :values هستند، وارد کردن :attribute الزامی است.',
    'required_without'     => 'وقتی :values نیست، وارد کردن :attribute الزامی است.',
    'required_without_all' => 'وقتی هیچ‌کدام از :values نیستند، وارد کردن :attribute الزامی است.',
    'same'                 => ':attribute و :other باید یکی باشند.',
    'starts_with'          => ':attribute باید با یکی از این‌ها شروع شود: :values',
    'string'               => ':attribute باید متن باشد.',
    'timezone'             => ':attribute باید یک منطقه‌ی زمانی معتبر باشد.',
    'unique'               => ':attribute تکراری است و قبلا ثبت شده.',
    'uploaded'             => 'بارگذاری :attribute ناموفق بود.',
    'uppercase'            => ':attribute باید با حروف بزرگ باشد.',
    'url'                  => ':attribute یک نشانی معتبر نیست.',
    'uuid'                 => ':attribute باید یک UUID معتبر باشد.',

    // قاعده‌هایی که پیامشان به نوع فیلد بستگی دارد
    'between' => [
        'array'   => ':attribute باید بین :min و :max مورد باشد.',
        'file'    => 'حجم :attribute باید بین :min و :max کیلوبایت باشد.',
        'numeric' => ':attribute باید بین :min و :max باشد.',
        'string'  => ':attribute باید بین :min و :max نویسه باشد.',
    ],

    'gt' => [
        'array'   => ':attribute باید بیشتر از :value مورد باشد.',
        'file'    => 'حجم :attribute باید بیشتر از :value کیلوبایت باشد.',
        'numeric' => ':attribute باید بزرگ‌تر از :value باشد.',
        'string'  => ':attribute باید بیشتر از :value نویسه باشد.',
    ],

    'gte' => [
        'array'   => ':attribute باید :value مورد یا بیشتر باشد.',
        'file'    => 'حجم :attribute باید :value کیلوبایت یا بیشتر باشد.',
        'numeric' => ':attribute باید بزرگ‌تر یا مساوی :value باشد.',
        'string'  => ':attribute باید :value نویسه یا بیشتر باشد.',
    ],

    'lt' => [
        'array'   => ':attribute باید کمتر از :value مورد باشد.',
        'file'    => 'حجم :attribute باید کمتر از :value کیلوبایت باشد.',
        'numeric' => ':attribute باید کوچک‌تر از :value باشد.',
        'string'  => ':attribute باید کمتر از :value نویسه باشد.',
    ],

    'lte' => [
        'array'   => ':attribute نباید بیشتر از :value مورد باشد.',
        'file'    => 'حجم :attribute نباید بیشتر از :value کیلوبایت باشد.',
        'numeric' => ':attribute باید کوچک‌تر یا مساوی :value باشد.',
        'string'  => ':attribute نباید بیشتر از :value نویسه باشد.',
    ],

    'max' => [
        'array'   => ':attribute نباید بیشتر از :max مورد باشد.',
        'file'    => 'حجم :attribute نباید بیشتر از :max کیلوبایت باشد.',
        'numeric' => ':attribute نباید بزرگ‌تر از :max باشد.',
        'string'  => ':attribute نباید بیشتر از :max نویسه باشد.',
    ],

    'min' => [
        'array'   => ':attribute باید دست‌کم :min مورد باشد.',
        'file'    => 'حجم :attribute باید دست‌کم :min کیلوبایت باشد.',
        'numeric' => ':attribute نباید کوچک‌تر از :min باشد.',
        'string'  => ':attribute باید دست‌کم :min نویسه باشد.',
    ],

    'password' => [
        'letters'       => 'رمز عبور باید دست‌کم یک حرف داشته باشد.',
        'mixed'         => 'رمز عبور باید هم حرف بزرگ و هم حرف کوچک داشته باشد.',
        'numbers'       => 'رمز عبور باید دست‌کم یک رقم داشته باشد.',
        'symbols'       => 'رمز عبور باید دست‌کم یک نماد داشته باشد.',
        'uncompromised' => 'این رمز عبور در نشت اطلاعات دیده شده است. رمز دیگری بگذارید.',
    ],

    'size' => [
        'array'   => ':attribute باید :size مورد باشد.',
        'file'    => 'حجم :attribute باید :size کیلوبایت باشد.',
        'numeric' => ':attribute باید برابر :size باشد.',
        'string'  => ':attribute باید :size نویسه باشد.',
    ],

    'custom' => [],

    /*
    |--------------------------------------------------------------------------
    | نام فارسی فیلدها
    |--------------------------------------------------------------------------
    | بدون این بخش، پیام‌ها می‌شوند «وارد کردن address_line الزامی است» که
    | برای مشتری از متن انگلیسی هم بی‌معناتر است.
    */
    'attributes' => [

        // آدرس و تحویل
        'receiver_name'  => 'نام و نام خانوادگی گیرنده',
        'receiver_phone' => 'شماره تماس گیرنده',
        'address_line'   => 'آدرس کامل',
        'province_id'    => 'استان',
        'city'           => 'شهر',
        'postal_code'    => 'کد پستی',
        'shop_address'   => 'آدرس فروشگاه',
        'shop_postal_code' => 'کد پستی فروشگاه',

        // حساب کاربری
        'mobile'                => 'شماره موبایل',
        'phone'                 => 'شماره تماس',
        'otp'                   => 'کد تأیید',
        'code'                  => 'کد',
        'password'              => 'رمز عبور',
        'password_confirmation' => 'تکرار رمز عبور',
        'new_password'          => 'رمز عبور جدید',
        'current_password'      => 'رمز عبور فعلی',
        'first_name'            => 'نام',
        'last_name'             => 'نام خانوادگی',
        'family'                => 'نام خانوادگی',
        'name'                  => 'نام',
        'username'              => 'نام کاربری',
        'email'                 => 'ایمیل',
        'role_id'               => 'نقش',
        'status'                => 'وضعیت',

        // سفارش و پرداخت
        'order_id'           => 'شماره سفارش',
        'customer_id'        => 'مشتری',
        'address_id'         => 'آدرس',
        'shipping_method_id' => 'روش ارسال',
        'shipping_price'     => 'هزینه ارسال',
        'final_price'        => 'مبلغ نهایی',
        'amount'             => 'مبلغ',
        'method'             => 'روش پرداخت',
        'paid_at'            => 'تاریخ پرداخت',
        'payer_name'         => 'نام پرداخت‌کننده',
        'bank_name'          => 'نام بانک',
        'bank_card_number'   => 'شماره کارت',
        'bank_sheba'         => 'شماره شبا',
        'bank_account_name'  => 'نام صاحب حساب',
        'customer_note'      => 'یادداشت مشتری',
        'admin_note'         => 'یادداشت مدیر',
        'note'               => 'یادداشت',
        'file'               => 'فایل',

        // محصول
        'title'             => 'عنوان',
        'sku'               => 'کد فنی',
        'price'             => 'قیمت',
        'regular_price'     => 'قیمت پیش از تخفیف',
        'stock'             => 'موجودی',
        'car_model'         => 'مدل خودرو',
        'category_id'       => 'دسته‌بندی',
        'weight'            => 'وزن',
        'description'       => 'توضیحات',
        'short_description' => 'توضیح کوتاه',
        'is_active'         => 'وضعیت انتشار',
        'is_special_offer'  => 'پیشنهاد ویژه',
        'wholesale_enabled' => 'فروش عمده',
        'wholesale_min_qty' => 'حداقل تعداد عمده',
        'wholesale_price'   => 'قیمت عمده',
        'slug'              => 'نشانی صفحه',

        // نظر
        'comment'  => 'متن نظر',
        'rating'   => 'امتیاز',
        'criteria' => 'معیارها',

        // تخفیف
        'discount_percent' => 'درصد تخفیف',
        'max_discount'     => 'سقف تخفیف',
        'min_order_amount' => 'حداقل مبلغ سفارش',
        'usage_limit'      => 'سقف استفاده',
        'starts_at'        => 'تاریخ شروع',
        'expires_at'       => 'تاریخ پایان',

        // محتوا و سئو
        'body'            => 'متن',
        'text'            => 'متن',
        'titr'            => 'تیتر',
        'rutitr'          => 'روتیتر',
        'sutitr'          => 'سوتیتر',
        'intro'           => 'مقدمه',
        'heading'         => 'سرتیتر',
        'keywords'        => 'کلمات کلیدی',
        'seo_title'       => 'عنوان سئو',
        'seo_description' => 'توضیحات سئو',
        'canonical_url'   => 'نشانی کانونیکال',
        'focus_keyword'   => 'کلمه کلیدی اصلی',
        'faq'             => 'پرسش‌های متداول',
        'link'            => 'پیوند',
        'source'          => 'منبع',
        'source_path'     => 'مسیر مبدأ',
        'target_path'     => 'مسیر مقصد',
        'status_code'     => 'کد وضعیت',
        'showdate'        => 'تاریخ نمایش',
        'startdate'       => 'تاریخ شروع',
        'enddate'         => 'تاریخ پایان',

        // ارسال
        'local_province_id'       => 'استان مبدأ',
        'local_free_threshold'    => 'آستانه ارسال رایگان درون‌استانی',
        'local_shipping_cost'     => 'هزینه ارسال درون‌استانی',
        'national_free_threshold' => 'آستانه ارسال رایگان سایر شهرها',
        'working_hours'           => 'ساعات کاری',
        'contact_phone'           => 'تلفن تماس',
        'expert_name'             => 'نام کارشناس',
        'sms'                     => 'متن پیامک',
    ],
];
