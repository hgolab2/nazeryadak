-- ============================================================================
-- تغییرات دیتابیس — کد تخفیف درصدی با سقف مبلغ
--
-- مایگریشن این نسخه:
--   2026_08_14_010000_create_discount_codes_table
--
-- روی سرور می‌توانید به‌جای این فایل فقط «php artisan migrate» بزنید؛
-- این فایل برای وقتی است که دسترسی به artisan ندارید و مستقیم در
-- phpMyAdmin کار می‌کنید.
--
-- قبل از اجرا از دیتابیس بکاپ بگیرید.
-- ============================================================================


-- ---------------------------------------------------------------------------
-- ۱) جدول کدهای تخفیف
--
--   code                کد یکتا؛ همیشه با حروف بزرگ لاتین ذخیره می‌شود تا
--                       «off10» و «OFF10» یکی حساب شوند
--   title               یادداشت داخلی مدیر؛ به مشتری نشان داده نمی‌شود
--   percent             درصد تخفیف (۱ تا ۱۰۰)
--   max_discount        سقف تخفیف به تومان؛ NULL یعنی بی‌سقف.
--                       «۲۰٪ تا سقف ۵۰٬۰۰۰» روی فاکتور یک‌میلیونی هم فقط
--                       ۵۰٬۰۰۰ تومان کم می‌کند.
--   min_order_amount    حداقل مبلغ اقلام فاکتور برای پذیرفته‌شدن کد (تومان)
--   usage_limit         سقف کل دفعات استفاده؛ NULL یعنی نامحدود
--   per_customer_limit  سقف استفاده‌ی هر مشتری؛ NULL یعنی نامحدود
--   starts_at/expires_at بازه‌ی اعتبار؛ NULL یعنی بدون محدودیت زمانی
--
-- تعداد دفعات مصرف عمدا ستون شمارنده ندارد و هر بار از روی خود سفارش‌ها
-- شمرده می‌شود؛ شمارنده‌ی جدا با هر سبد رهاشده از واقعیت فاصله می‌گیرد.
-- ---------------------------------------------------------------------------

CREATE TABLE `discount_codes` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code`               VARCHAR(40)  COLLATE utf8mb4_unicode_ci NOT NULL,
    `title`              VARCHAR(150) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    `percent`            TINYINT UNSIGNED NOT NULL,
    `max_discount`       BIGINT UNSIGNED NULL DEFAULT NULL,
    `min_order_amount`   BIGINT UNSIGNED NOT NULL DEFAULT 0,
    `usage_limit`        INT UNSIGNED NULL DEFAULT NULL,
    `per_customer_limit` INT UNSIGNED NULL DEFAULT NULL,
    `starts_at`          TIMESTAMP NULL DEFAULT NULL,
    `expires_at`         TIMESTAMP NULL DEFAULT NULL,
    `is_active`          TINYINT(1) NOT NULL DEFAULT 1,
    `created_at`         TIMESTAMP NULL DEFAULT NULL,
    `updated_at`         TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `discount_codes_code_unique` (`code`),
    KEY `discount_codes_is_active_index`  (`is_active`),
    KEY `discount_codes_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------------
-- ۲) ستون‌های تخفیف روی جدول سفارش‌ها
--
-- کد به سفارش گره می‌خورد نه به سبد، چون سبد در session است و بعد از
-- پرداخت پاک می‌شود ولی فاکتور باید تخفیفش را برای همیشه نگه دارد.
--
--   discount_code_id  ارجاع به discount_codes.id
--   discount_code     اسنپ‌شات متن کد؛ اگر مدیر بعدا کد را حذف کند، فاکتور
--                     قدیمی همچنان می‌گوید مشتری با چه کدی خرید کرده است
--   discount_amount   مبلغ تخفیفِ همان لحظه (تومان)
--
-- معنی ستون‌های مبلغ بعد از این تغییر:
--   final_price   = جمع اقلام، پیش از تخفیف و بدون ارسال (بدون تغییر)
--   total_price   = final_price − discount_amount + shipping_price
-- ---------------------------------------------------------------------------

ALTER TABLE `orders`
    ADD COLUMN `discount_code_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `final_price`,
    ADD COLUMN `discount_code`    VARCHAR(40) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `discount_code_id`,
    ADD COLUMN `discount_amount`  BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `discount_code`,
    ADD INDEX `orders_discount_code_id_index` (`discount_code_id`);


-- ---------------------------------------------------------------------------
-- ۳) ثبت مایگریشن
--
-- بدون این سطر، اجرای بعدیِ «php artisan migrate» روی سرور دوباره تلاش
-- می‌کند همین جدول و ستون‌ها را بسازد و با خطا متوقف می‌شود.
-- اول این را بگیرید تا شماره‌ی batch بعدی را بدانید:
--     SELECT IFNULL(MAX(`batch`), 0) + 1 FROM `migrations`;
-- سپس عدد 99 پایین را با همان مقدار عوض کنید.
-- ---------------------------------------------------------------------------

INSERT INTO `migrations` (`migration`, `batch`) VALUES
    ('2026_08_14_010000_create_discount_codes_table', 99);


-- ---------------------------------------------------------------------------
-- ۴) یک کد نمونه (اختیاری)
--
-- «۲۰٪ تخفیف تا سقف ۵۰٬۰۰۰ تومان، برای خریدهای بالای ۳۰۰٬۰۰۰ تومان،
--   هر مشتری یک بار». ساختن کد از پنل («مدیریت فروشگاه ← کدهای تخفیف»)
-- راه بهتری است.
-- ---------------------------------------------------------------------------

-- INSERT INTO `discount_codes`
--     (`code`, `title`, `percent`, `max_discount`, `min_order_amount`,
--      `usage_limit`, `per_customer_limit`, `starts_at`, `expires_at`,
--      `is_active`, `created_at`, `updated_at`)
-- VALUES
--     ('NOROOZ', 'جشنواره نوروز', 20, 50000, 300000,
--      NULL, 1, NULL, '2026-04-01 23:59:59',
--      1, NOW(), NOW());


-- ---------------------------------------------------------------------------
-- ۵) دسترسی منوی پنل
--
-- صفحه‌ی «کدهای تخفیف» از همان کد دسترسی سفارشات (۳۸۸) استفاده می‌کند و
-- تابع access() در این پروژه فعلا برای هر کاربر واردشده true برمی‌گرداند؛
-- پس هیچ رکوردی در modules یا role_module لازم نیست.
-- ---------------------------------------------------------------------------


-- ---------------------------------------------------------------------------
-- ۶) بررسی بعد از اجرا
-- ---------------------------------------------------------------------------

-- SHOW COLUMNS FROM `discount_codes`;
-- SHOW COLUMNS FROM `orders` LIKE 'discount%';
-- SELECT `id`, `final_price`, `discount_code`, `discount_amount`,
--        `shipping_price`, `total_price`
--   FROM `orders` WHERE `discount_amount` > 0 ORDER BY `id` DESC LIMIT 10;


-- ============================================================================
-- برگشت (rollback)
-- ============================================================================

-- ALTER TABLE `orders`
--     DROP INDEX `orders_discount_code_id_index`,
--     DROP COLUMN `discount_code_id`,
--     DROP COLUMN `discount_code`,
--     DROP COLUMN `discount_amount`;
-- DROP TABLE IF EXISTS `discount_codes`;
-- DELETE FROM `migrations`
--  WHERE `migration` = '2026_08_14_010000_create_discount_codes_table';
